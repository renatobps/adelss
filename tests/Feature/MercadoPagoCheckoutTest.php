<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\MercadoPagoService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MercadoPagoCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('mercadopago.webhook_secret', '');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['receita', 'despesa']);
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->enum('status', ['pago', 'a_receber', 'a_pagar', 'recebido'])->default('a_receber');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_transaction_id')->constrained('financial_transactions')->cascadeOnDelete();
            $table->string('gateway', 32)->default('mercado_pago');
            $table->string('idempotency_key', 80)->unique();
            $table->string('external_payment_id', 64)->nullable();
            $table->string('external_reference', 120)->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('status_detail', 128)->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 8)->default('BRL');
            $table->string('payer_email')->nullable();
            $table->string('payer_document', 32)->nullable();
            $table->longText('qr_code_base64')->nullable();
            $table->text('qr_code_text')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    private function actingAsFinancialAdmin(): User
    {
        $user = User::create([
            'name' => 'Admin Teste',
            'email' => 'admin-financial@test.local',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_nao_permite_checkout_pix_para_transacao_ja_recebida(): void
    {
        $this->actingAsFinancialAdmin();

        $transaction = FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => now()->toDateString(),
            'description' => 'Oferta de teste',
            'amount' => 45.90,
            'is_paid' => true,
            'status' => 'recebido',
        ]);

        $response = $this->postJson(route('financial.checkout.pix', $transaction), [
            'payer_email' => 'teste@example.com',
            'payer_document' => '12345678901',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_webhook_aprovado_da_baixa_na_receita(): void
    {
        $transaction = FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => now()->toDateString(),
            'description' => 'Doacao',
            'amount' => 80,
            'is_paid' => false,
            'status' => 'a_receber',
        ]);

        $payment = PaymentTransaction::create([
            'financial_transaction_id' => $transaction->id,
            'gateway' => 'mercado_pago',
            'idempotency_key' => 'idem-test-1',
            'external_payment_id' => '123456',
            'external_reference' => 'ftx-test',
            'status' => 'pending',
            'payment_method' => 'pix',
            'amount' => 80,
            'currency' => 'BRL',
        ]);

        $serviceMock = $this->mock(MercadoPagoService::class);
        $serviceMock->shouldReceive('getPayment')
            ->once()
            ->andReturn([
                'id' => '123456',
                'status' => 'approved',
                'status_detail' => 'accredited',
                'payment_method_id' => 'pix',
            ]);

        $response = $this->postJson(route('webhooks.mercadopago', ['type' => 'payment', 'data.id' => '123456']), [
            'type' => 'payment',
            'data' => ['id' => '123456'],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $transaction->refresh();
        $payment->refresh();

        $this->assertTrue($transaction->is_paid);
        $this->assertSame('recebido', $transaction->status);
        $this->assertSame('approved', $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_webhook_duplicado_nao_duplica_registro_de_pagamento(): void
    {
        $transaction = FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => now()->toDateString(),
            'description' => 'Doacao duplicada',
            'amount' => 150,
            'is_paid' => false,
            'status' => 'a_receber',
        ]);

        PaymentTransaction::create([
            'financial_transaction_id' => $transaction->id,
            'gateway' => 'mercado_pago',
            'idempotency_key' => 'idem-test-2',
            'external_payment_id' => '654321',
            'external_reference' => 'ftx-test-2',
            'status' => 'pending',
            'payment_method' => 'pix',
            'amount' => 150,
            'currency' => 'BRL',
        ]);

        $serviceMock = $this->mock(MercadoPagoService::class);
        $serviceMock->shouldReceive('getPayment')
            ->twice()
            ->andReturn([
                'id' => '654321',
                'status' => 'approved',
                'status_detail' => 'accredited',
                'payment_method_id' => 'pix',
            ]);

        $payload = [
            'type' => 'payment',
            'data' => ['id' => '654321'],
        ];

        $this->postJson(route('webhooks.mercadopago', ['type' => 'payment', 'data.id' => '654321']), $payload)
            ->assertOk();
        $this->postJson(route('webhooks.mercadopago', ['type' => 'payment', 'data.id' => '654321']), $payload)
            ->assertOk();

        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseHas('payment_transactions', [
            'external_payment_id' => '654321',
            'status' => 'approved',
        ]);
    }
}
