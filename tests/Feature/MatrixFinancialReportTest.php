<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Services\Financial\MatrixFinancialReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MatrixFinancialReportTest extends TestCase
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
            $table->string('status')->default('recebido');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transactions');
        parent::tearDown();
    }

    public function test_divide_renda_mensal_em_37_e_63_por_cento_e_agrupa_saidas(): void
    {
        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2026-07-10',
            'description' => 'Dízimo',
            'amount' => 1000,
            'is_paid' => true,
            'status' => 'recebido',
        ]);
        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2026-07-15',
            'description' => 'Oferta',
            'amount' => 200,
            'is_paid' => false,
            'status' => 'a_receber',
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2026-07-20',
            'description' => 'Água',
            'amount' => 80,
            'is_paid' => true,
            'status' => 'pago',
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2026-07-22',
            'description' => 'Luz',
            'amount' => 120,
            'is_paid' => true,
            'status' => 'pago',
        ]);
        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2026-06-01',
            'description' => 'Saldo anterior',
            'amount' => 50,
            'is_paid' => true,
            'status' => 'recebido',
        ]);

        $julho = app(MatrixFinancialReportService::class)->buildMonth(2026, 7);

        $this->assertSame(1000.0, $julho['total_entradas']);
        $this->assertSame(370.0, $julho['dizimo_obreiros']);
        $this->assertSame(630.0, $julho['dizimo_membros']);
        $this->assertSame(200.0, $julho['total_saidas']);
        $this->assertSame('Saídas do mês de Julho', $julho['saidas_label']);
        $this->assertSame(50.0, $julho['saldo_anterior']);
        $this->assertSame(850.0, $julho['saldo_final']);
    }

    public function test_ano_tem_doze_meses_e_percentuais_somam_a_renda(): void
    {
        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2026-03-08',
            'description' => 'Oferta',
            'amount' => 10.01,
            'is_paid' => true,
            'status' => 'recebido',
        ]);

        $report = app(MatrixFinancialReportService::class)->buildYear(2026);

        $this->assertCount(12, $report['months']);
        $marco = $report['months'][2];
        $this->assertSame(10.01, $marco['total_entradas']);
        $this->assertEqualsWithDelta(10.01, $marco['dizimo_obreiros'] + $marco['dizimo_membros'], 0.001);
    }
}
