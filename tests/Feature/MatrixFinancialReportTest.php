<?php

namespace Tests\Feature;

use App\Models\FinancialCategory;
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

        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('type')->default('despesa');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->string('status')->default('recebido');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('financial_categories');
        parent::tearDown();
    }

    public function test_lista_cada_saida_paga_do_mes(): void
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
        $this->assertCount(2, $julho['saidas']);
        $this->assertSame('Água', $julho['saidas'][0]['description']);
        $this->assertSame(80.0, $julho['saidas'][0]['amount']);
        $this->assertSame('Luz', $julho['saidas'][1]['description']);
        $this->assertSame(120.0, $julho['saidas'][1]['amount']);
        $this->assertSame(50.0, $julho['saldo_anterior']);
        $this->assertSame(850.0, $julho['saldo_final']);

        $html = view('financial.reports.pdf.matrix-demonstrativo', [
            'year' => 2026,
            'months' => [$julho],
            'headerSrc' => null,
            'congregacao' => 'ADEL São Sebastião',
            'cidade' => 'Luziânia',
            'pastorNome' => null,
            'pastorAssinaturaSrc' => null,
            'tesoureiroNome' => 'Maria Tesoureira',
            'tesoureiroAssinaturaSrc' => null,
        ])->render();

        $this->assertStringNotContainsString('Saídas do mês de', $html);
        $this->assertStringContainsString('Água', $html);
        $this->assertStringContainsString('Luz', $html);
        $this->assertStringContainsString('ASSEMBLEIA DE DEUS DE LUZIÂNIA em São Sebastião', $html);
        $this->assertStringContainsString('Maria Tesoureira', $html);
        $this->assertSame(2, substr_count($html, 'class="recibo-slot"'));
        $this->assertSame(1, substr_count($html, 'recibo-corte">corte'));
        $this->assertStringContainsString('julho', $html);
        $this->assertStringContainsString('2026', $html);
    }

    public function test_agrupa_saidas_pelo_tipo_da_categoria(): void
    {
        $combustivel = FinancialCategory::create([
            'name' => 'Combustível',
            'slug' => 'combustivel',
            'type' => 'despesa',
        ]);
        $energia = FinancialCategory::create([
            'name' => 'Energia',
            'slug' => 'energia',
            'type' => 'despesa',
        ]);

        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-05',
            'description' => 'Gasolina',
            'amount' => 200,
            'is_paid' => true,
            'status' => 'pago',
            'category_id' => $combustivel->id,
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-20',
            'description' => 'Gasolina',
            'amount' => 150,
            'is_paid' => true,
            'status' => 'pago',
            'category_id' => $combustivel->id,
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-12',
            'description' => 'Conta de luz',
            'amount' => 80,
            'is_paid' => true,
            'status' => 'pago',
            'category_id' => $energia->id,
        ]);

        $janeiro = app(MatrixFinancialReportService::class)->buildMonth(2024, 1);

        $this->assertCount(2, $janeiro['saidas']);
        $this->assertSame('Combustível', $janeiro['saidas'][0]['description']);
        $this->assertSame(350.0, $janeiro['saidas'][0]['amount']);
        $this->assertSame('240101', $janeiro['saidas'][0]['recibo_numero']);
        $this->assertSame('trezentos e cinquenta reais', $janeiro['saidas'][0]['amount_extenso']);
        $this->assertSame('Energia', $janeiro['saidas'][1]['description']);
        $this->assertSame(80.0, $janeiro['saidas'][1]['amount']);
        $this->assertSame('240102', $janeiro['saidas'][1]['recibo_numero']);
        $this->assertSame(430.0, $janeiro['total_saidas']);
    }

    public function test_ano_lista_cada_saida_no_mes_correto(): void
    {
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-10',
            'description' => 'Aluguel',
            'amount' => 3000,
            'is_paid' => true,
            'status' => 'pago',
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-18',
            'description' => 'Energia',
            'amount' => 450,
            'is_paid' => true,
            'status' => 'pago',
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-02-02',
            'description' => 'Água fevereiro',
            'amount' => 90,
            'is_paid' => true,
            'status' => 'pago',
        ]);

        $report = app(MatrixFinancialReportService::class)->buildYear(2024);
        $janeiro = $report['months'][0];

        $this->assertCount(2, $janeiro['saidas']);
        $this->assertSame('Aluguel', $janeiro['saidas'][0]['description']);
        $this->assertSame('Energia', $janeiro['saidas'][1]['description']);
        $this->assertSame(3450.0, $janeiro['total_saidas']);
        $this->assertSame('Água fevereiro', $report['months'][1]['saidas'][0]['description']);
    }

    public function test_anexa_recibos_dois_por_folha_depois_do_mes(): void
    {
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-04',
            'description' => 'Aluguel',
            'amount' => 100,
            'is_paid' => true,
            'status' => 'pago',
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-08',
            'description' => 'Energia',
            'amount' => 40,
            'is_paid' => true,
            'status' => 'pago',
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-19',
            'description' => 'Água',
            'amount' => 20,
            'is_paid' => true,
            'status' => 'pago',
        ]);

        $html = view('financial.reports.pdf.matrix-demonstrativo', app(MatrixFinancialReportService::class)->buildYear(2024))->render();

        $this->assertSame(3, substr_count($html, 'class="recibo-slot"'));
        $this->assertSame(1, substr_count($html, 'recibo-corte">corte'));
        $this->assertStringContainsString('ASSEMBLEIA DE DEUS DE LUZIÂNIA em São Sebastião', $html);
        $this->assertStringContainsString('janeiro', $html);
    }

    public function test_prebenda_assina_pastor_e_demais_saidas_a_tesoureira(): void
    {
        $prebenda = FinancialCategory::create([
            'name' => 'Prebenda Pastoral',
            'slug' => 'prebenda-pastoral',
            'type' => 'despesa',
        ]);
        $energia = FinancialCategory::create([
            'name' => 'Energia',
            'slug' => 'energia',
            'type' => 'despesa',
        ]);

        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-03-10',
            'description' => 'Prebenda',
            'amount' => 400,
            'is_paid' => true,
            'status' => 'pago',
            'category_id' => $prebenda->id,
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-03-12',
            'description' => 'Luz',
            'amount' => 90,
            'is_paid' => true,
            'status' => 'pago',
            'category_id' => $energia->id,
        ]);

        $marco = app(MatrixFinancialReportService::class)->buildMonth(2024, 3);

        $this->assertTrue($marco['saidas'][1]['is_prebenda']);
        $this->assertFalse($marco['saidas'][0]['is_prebenda']);
        $this->assertSame('Prebenda Pastoral', $marco['saidas'][1]['description']);
        $this->assertSame('Energia', $marco['saidas'][0]['description']);

        $html = view('financial.reports.pdf.matrix-demonstrativo', [
            'year' => 2024,
            'months' => [$marco],
            'headerSrc' => null,
            'reciboFundoSrc' => null,
            'congregacao' => 'ADEL São Sebastião',
            'cidade' => 'Luziânia',
            'pastorNome' => 'Rodrigo Barbosa da Silva',
            'pastorAssinaturaSrc' => null,
            'tesoureiroNome' => 'Maria Tesoureira',
            'tesoureiroAssinaturaSrc' => null,
        ])->render();

        $this->assertStringContainsString('Rodrigo Barbosa da Silva', $html);
        $this->assertStringContainsString('Maria Tesoureira', $html);
        $this->assertStringContainsString('PREBENDA PASTORAL', $html);
        $this->assertStringContainsString('ENERGIA', $html);
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
