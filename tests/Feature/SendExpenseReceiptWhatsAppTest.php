<?php

namespace Tests\Feature;

use App\Models\FinancialNotificationLog;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\Member;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class SendExpenseReceiptWhatsAppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('financial.whatsapp.send_pdf_receipt', true);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(true);
            $table->string('status')->default('pago');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('received_from_other')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transaction_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_transaction_id')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('notification_type');
            $table->string('status');
            $table->text('message')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_envia_recibo_anexado_da_despesa_para_o_whatsapp_do_membro(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('financial/transactions/attachments/recibo.pdf', '%PDF-1.4 test');

        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('enviarDocumentoArquivo')
            ->once()
            ->withArgs(function (string $phone, string $path, string $name, string $caption) {
                return $phone === '61999999999'
                    && is_file($path)
                    && $name === 'recibo.pdf'
                    && str_contains($caption, 'Maria Souza');
            })
            ->andReturn(['success' => true]);
        $this->app->instance(WhatsAppService::class, $whatsapp);

        $member = Member::create(['name' => 'Maria Souza', 'phone' => '61999999999']);
        $transaction = FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => now()->toDateString(),
            'description' => 'Ajuda de custo',
            'amount' => 80,
            'is_paid' => true,
            'status' => 'pago',
            'member_id' => $member->id,
        ]);
        FinancialTransactionAttachment::create([
            'transaction_id' => $transaction->id,
            'file_path' => 'financial/transactions/attachments/recibo.pdf',
            'file_name' => 'recibo.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 12,
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('financial.transactions.send-receipt', $transaction))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, FinancialNotificationLog::where('status', 'sent')->count());
    }

    public function test_recusa_despesa_paga_a_pessoa_fora_da_lista(): void
    {
        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('enviarDocumentoArquivo')->never();
        $whatsapp->shouldReceive('enviarMensagem')->never();
        $this->app->instance(WhatsAppService::class, $whatsapp);

        $transaction = FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => now()->toDateString(),
            'description' => 'Serviço avulso',
            'amount' => 30,
            'is_paid' => true,
            'status' => 'pago',
            'received_from_other' => 'Fornecedor XYZ',
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('financial.transactions.send-receipt', $transaction))
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Esta despesa não foi paga a um membro cadastrado.']);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-recibo-despesa@test.local',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]);
    }
}
