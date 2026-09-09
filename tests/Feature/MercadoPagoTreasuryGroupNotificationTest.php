<?php

namespace Tests\Feature;

use App\Models\FinancialAutomation;
use App\Models\FinancialNotificationLog;
use App\Services\FinancialNotificationService;
use App\Services\Payments\MercadoPagoService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MercadoPagoTreasuryGroupNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('financial.whatsapp.mp_treasury_group_enabled', true);

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

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_notification_logs');
        Schema::dropIfExists('financial_automations');
        parent::tearDown();
    }

    public function test_envia_mensagem_ao_grupo_na_entrada_e_nao_repete(): void
    {
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('enviarMensagem')
            ->once()
            ->with('120363@g.us', Mockery::on(fn ($msg) => str_contains($msg, 'Entrada') && str_contains($msg, '[mp:555:in]')))
            ->andReturn(['success' => true]);
        $this->app->instance(WhatsAppService::class, $whatsapp);

        $mp = Mockery::mock(MercadoPagoService::class);
        $mp->shouldReceive('getAccountBalance')->andReturn([
            'available' => 200.0,
            'unavailable' => 0.0,
            'total' => 200.0,
            'currency' => 'BRL',
        ]);
        $this->app->instance(MercadoPagoService::class, $mp);

        $service = app(FinancialNotificationService::class);

        $first = $service->notificarMovimentacaoMercadoPago('in', [
            'id' => '555',
            'status' => 'approved',
            'transaction_amount' => 80,
            'description' => 'Oferta',
            'payment_method_id' => 'pix',
        ]);
        $this->assertTrue($first['success'] ?? false);

        $second = $service->notificarMovimentacaoMercadoPago('in', [
            'id' => '555',
            'status' => 'approved',
            'transaction_amount' => 80,
        ]);
        $this->assertFalse($second['success'] ?? true);
        $this->assertSame('Movimentação já notificada.', $second['error'] ?? null);

        $this->assertSame(1, FinancialNotificationLog::query()->where('status', 'sent')->count());
        $this->assertSame('120363@g.us', FinancialNotificationLog::first()->phone);
    }

    public function test_notifica_apenas_movimentos_recentes_ainda_nao_enviados(): void
    {
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('enviarMensagem')
            ->once()
            ->with('120363@g.us', Mockery::on(fn ($msg) => str_contains($msg, '5,00') && str_contains($msg, '[mp:pix5:in]')))
            ->andReturn(['success' => true]);
        $this->app->instance(WhatsAppService::class, $whatsapp);

        $service = app(FinancialNotificationService::class);

        $sent = $service->notificarMovimentosMercadoPagoRecentes([
            'in_total' => 5,
            'out_total' => 0,
            'items' => [
                [
                    'id' => 'old1',
                    'direction' => 'in',
                    'amount' => 100,
                    'description' => 'Antigo',
                    'method' => 'PIX',
                    'occurred_at' => now()->subDays(3)->toIso8601String(),
                ],
                [
                    'id' => 'pix5',
                    'direction' => 'in',
                    'amount' => 5,
                    'description' => 'PIX',
                    'method' => 'PIX',
                    'payer' => 'Renato',
                    'occurred_at' => now()->subMinutes(10)->toIso8601String(),
                ],
            ],
        ]);

        $this->assertSame(1, $sent);
    }
}
