<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\Member;
use App\Models\MemberRole;
use App\Services\Financial\CashFlowClosingPdfService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CashFlowClosingPdfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->string('status')->default('pago');
            $table->string('received_from_other')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
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

        Schema::create('member_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('ativo');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transaction_attachments');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('member_roles');
        parent::tearDown();
    }

    public function test_pdf_de_fechamento_soma_apenas_lancamentos_pagos_e_inclui_comprovante(): void
    {
        Storage::fake('public');

        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2026-07-20',
            'description' => 'Saldo anterior',
            'amount' => 100,
            'is_paid' => true,
            'status' => 'recebido',
            'received_from_other' => 'Membro',
        ]);

        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2026-08-05',
            'description' => 'Dízimo',
            'amount' => 50,
            'is_paid' => true,
            'status' => 'recebido',
            'received_from_other' => 'Membro',
        ]);

        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2026-08-10',
            'description' => 'Ainda a receber',
            'amount' => 999,
            'is_paid' => false,
            'status' => 'a_receber',
            'received_from_other' => 'Membro',
        ]);

        $despesa = FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2026-08-12',
            'description' => 'Conta de luz',
            'amount' => 20,
            'is_paid' => true,
            'status' => 'pago',
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk('public')->put('financial/transactions/attachments/luz.png', $png);

        FinancialTransactionAttachment::create([
            'transaction_id' => $despesa->id,
            'file_path' => 'financial/transactions/attachments/luz.png',
            'file_name' => 'luz.png',
            'file_type' => 'image/png',
            'file_size' => strlen($png),
        ]);

        $pastorRole = MemberRole::create(['name' => 'Pastor', 'is_active' => true]);
        $tesoureiro1Role = MemberRole::create(['name' => '1º Tesoureiro(a)', 'is_active' => true]);
        $tesoureiro2Role = MemberRole::create(['name' => '2º Tesoureiro(a)', 'is_active' => true]);
        Member::create(['name' => 'João Pastor', 'status' => 'ativo', 'role_id' => $pastorRole->id]);
        Member::create(['name' => 'Maria Tesoureira', 'status' => 'ativo', 'role_id' => $tesoureiro1Role->id]);
        Member::create(['name' => 'Pedro Segundo', 'status' => 'ativo', 'role_id' => $tesoureiro2Role->id]);

        $request = Request::create('/financial/reports/cash-flow/extract/pdf', 'GET', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);

        $service = app(CashFlowClosingPdfService::class);
        $data = $service->buildViewData($request);

        $this->assertCount(1, $data['entradas']);
        $this->assertCount(1, $data['saidas']);
        $this->assertEquals(50.0, $data['totalEntradas']);
        $this->assertEquals(20.0, $data['totalSaidas']);
        $this->assertEquals(100.0, $data['saldoAnterior']);
        $this->assertEquals(130.0, $data['saldoFinal']);
        $this->assertCount(1, $data['comprovantes']);
        $this->assertSame('image', $data['comprovantes'][0]['kind']);
        $this->assertNotEmpty($data['comprovantes'][0]['imageSrc']);
        $this->assertSame('Maria Tesoureira', $data['tesoureiroNome']);
        $this->assertSame('João Pastor', $data['pastorNome']);

        $response = $service->download($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('fechamento-caixa-2026-08.pdf', $response->headers->get('Content-Disposition'));
    }
}
