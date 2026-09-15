<?php

namespace Tests\Feature;

use App\Models\CashClosing;
use App\Models\FinancialAutomation;
use App\Models\FinancialNotificationLog;
use App\Services\Financial\WeeklyCashClosingService;
use App\Services\FinancialNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class WeeklyClosingTreasuryGroupNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('financial_automations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_transaction_id')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('notification_type', 50);
            $table->string('status', 20)->default('sent');
            $table->text('message')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_receitas', 12, 2);
            $table->decimal('total_despesas', 12, 2);
            $table->decimal('saldo', 12, 2);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->string('status')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        FinancialAutomation::query()->create([
            'key' => FinancialAutomation::KEY_MP_TREASURY_GROUP,
            'name' => 'Grupo tesouraria',
            'enabled' => true,
            'settings' => [
                'whatsapp_group_jid' => '120363@g.us',
                'whatsapp_group_name' => 'Tesouraria ADEL',
            ],
        ]);
    }

    public function test_envia_fechamento_semanal_ao_grupo_dos_tesoureiros(): void
    {
        $pdfPath = storage_path('app/temp/receipts/fechamento-teste.pdf');
        @mkdir(dirname($pdfPath), 0755, true);
        file_put_contents($pdfPath, '%PDF-1.4 test');

        $closings = Mockery::mock(WeeklyCashClosingService::class)->makePartial();
        $closings->shouldReceive('writePdfTemp')->once()->andReturn($pdfPath);
        $this->app->instance(WeeklyCashClosingService::class, $closings);

        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('enviarDocumentoArquivo')
            ->once()
            ->withArgs(function (string $jid, string $path, string $name, string $caption) {
                return $jid === '120363@g.us'
                    && str_contains($caption, 'Fechamento semanal')
                    && str_contains($caption, '1.250,00')
                    && str_contains($name, 'fechamento-semanal');
            })
            ->andReturn(['success' => true]);
        $this->app->instance(WhatsAppService::class, $whatsapp);

        $closing = CashClosing::create([
            'period_start' => '2026-09-14',
            'period_end' => '2026-09-20',
            'total_receitas' => 1250,
            'total_despesas' => 400,
            'saldo' => 850,
            'generated_at' => now(),
        ]);

        $result = app(FinancialNotificationService::class)->notificarFechamentoSemanal($closing);

        $this->assertTrue($result['success'] ?? false);
        $this->assertSame(1, FinancialNotificationLog::where('notification_type', FinancialNotificationLog::TYPE_WEEKLY_CLOSING)->where('status', 'sent')->count());
        $this->assertSame('120363@g.us', FinancialNotificationLog::first()->phone);
        $this->assertFalse(is_file($pdfPath));
    }

    public function test_avisa_quando_o_grupo_nao_esta_configurado(): void
    {
        FinancialAutomation::query()
            ->where('key', FinancialAutomation::KEY_MP_TREASURY_GROUP)
            ->update(['settings' => ['whatsapp_group_jid' => '', 'whatsapp_group_name' => '']]);

        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('enviarDocumentoArquivo')->never();
        $whatsapp->shouldReceive('enviarMensagem')->never();
        $this->app->instance(WhatsAppService::class, $whatsapp);

        $closing = CashClosing::create([
            'period_start' => '2026-09-14',
            'period_end' => '2026-09-20',
            'total_receitas' => 10,
            'total_despesas' => 0,
            'saldo' => 10,
            'generated_at' => now(),
        ]);

        $result = app(FinancialNotificationService::class)->notificarFechamentoSemanal($closing);

        $this->assertFalse($result['success'] ?? true);
        $this->assertSame('Selecione o grupo WhatsApp da tesouraria em Financeiro → Automações.', $result['error'] ?? null);
    }
}
