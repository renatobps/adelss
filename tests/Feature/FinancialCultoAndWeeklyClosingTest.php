<?php

namespace Tests\Feature;

use App\Models\CashClosing;
use App\Models\Event;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Services\Financial\CultoOfferingReportService;
use App\Services\Financial\WeeklyCashClosingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialCultoAndWeeklyClosingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('start_date')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('type');
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
            $table->string('status')->default('recebido');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('culto_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
    }

    public function test_lista_de_cultos_do_relatorio_vai_de_hoje_ate_2016(): void
    {
        \Carbon\Carbon::setTestNow('2026-08-21 15:00:00');

        Event::create(['title' => 'Culto 2027', 'start_date' => '2027-01-10 19:00:00']);
        Event::create(['title' => 'Culto amanhã', 'start_date' => '2026-08-22 19:00:00']);
        Event::create(['title' => 'Culto de hoje', 'start_date' => '2026-08-21 19:00:00']);
        Event::create(['title' => 'Culto em janeiro', 'start_date' => '2026-01-01 09:00:00']);
        Event::create(['title' => 'Culto 2016', 'start_date' => '2016-01-01 09:00:00']);
        Event::create(['title' => 'Culto 2015', 'start_date' => '2015-12-31 19:00:00']);

        $titulos = Event::query()->paraRelatorioFinanceiro()->pluck('title')->all();

        $this->assertSame(['Culto de hoje', 'Culto em janeiro', 'Culto 2016'], $titulos);

        \Carbon\Carbon::setTestNow();
    }

    public function test_relatorio_do_culto_lista_entradas_e_saidas_do_mesmo_dia(): void
    {
        $dizimo = FinancialCategory::create(['name' => 'Dízimo', 'slug' => 'dizimo', 'type' => 'receita']);
        $luz = FinancialCategory::create(['name' => 'Luz', 'slug' => 'luz', 'type' => 'despesa']);

        $culto = Event::create(['title' => 'Culto da noite', 'start_date' => '2026-08-21 19:00:00']);

        $this->tx($dizimo->id, null, 100, '2026-08-21');
        $this->tx($luz->id, null, 30, '2026-08-21', 'despesa', 'pago');
        $this->tx($dizimo->id, null, 50, '2026-08-20');

        $report = app(CultoOfferingReportService::class)->build($culto);

        $this->assertCount(1, $report['entradas']);
        $this->assertCount(1, $report['saidas']);
        $this->assertEquals(100.0, $report['totalEntradas']);
        $this->assertEquals(30.0, $report['totalSaidas']);
        $this->assertEquals(70.0, $report['saldoDia']);
    }

    public function test_cultos_no_mesmo_dia_compartilham_o_movimento(): void
    {
        $dizimo = FinancialCategory::create(['name' => 'Dízimo', 'slug' => 'dizimo', 'type' => 'receita']);
        $oferta = FinancialCategory::create(['name' => 'Oferta', 'slug' => 'oferta', 'type' => 'receita']);

        $manha = Event::create(['title' => 'Culto da manhã', 'start_date' => '2026-08-23 09:00:00']);
        $noite = Event::create(['title' => 'Culto da noite', 'start_date' => '2026-08-23 19:00:00']);

        $this->tx($dizimo->id, $manha->id, 100, '2026-08-23');
        $this->tx($oferta->id, $manha->id, 40, '2026-08-23');
        $this->tx($dizimo->id, $noite->id, 70, '2026-08-23');

        $reportManha = app(CultoOfferingReportService::class)->build($manha);
        $reportNoite = app(CultoOfferingReportService::class)->build($noite);

        $this->assertEquals(210.0, $reportManha['totalEntradas']);
        $this->assertEquals(210.0, $reportNoite['totalEntradas']);
        $this->assertEquals(170.0, $reportManha['totalDizimos']);
        $this->assertEquals(40.0, $reportManha['totalOfertas']);
    }

    public function test_vinculo_de_dizimo_ao_culto_so_no_fechamento(): void
    {
        $dizimo = FinancialCategory::create(['name' => 'Dízimo', 'slug' => 'dizimo', 'type' => 'receita']);
        $culto = Event::create(['title' => 'Culto da noite', 'start_date' => '2026-08-23 19:00:00']);
        $tx = $this->tx($dizimo->id, null, 80, '2026-08-20');

        $service = app(CultoOfferingReportService::class);
        $this->assertCount(1, $service->pendentes());
        $this->assertEquals(0.0, $service->build($culto)['totalEntradas']);

        $this->assertSame(1, $service->attachToCulto($culto, [$tx->id]));
        $this->assertSame($culto->id, $tx->fresh()->culto_id);
        $this->assertCount(0, $service->pendentes());
    }

    public function test_fechamento_semanal_detecta_divergencia_apos_alteracao(): void
    {
        $dizimo = FinancialCategory::create(['name' => 'Dízimo', 'slug' => 'dizimo', 'type' => 'receita']);
        $this->tx($dizimo->id, null, 200, '2026-08-24', 'receita', 'recebido');

        $service = app(WeeklyCashClosingService::class);
        $week = $service->weekFor('2026-08-26');
        $this->assertSame('2026-08-24', $week['start']->toDateString());
        $this->assertSame('2026-08-30', $week['end']->toDateString());

        $live = $service->liveTotals($week['start'], $week['end']);
        $closing = CashClosing::create([
            'period_start' => $week['start']->toDateString(),
            'period_end' => $week['end']->toDateString(),
            'total_receitas' => $live['total_receitas'],
            'total_despesas' => $live['total_despesas'],
            'saldo' => $live['saldo'],
            'generated_at' => now(),
        ]);

        $this->assertFalse($service->snapshotHasDiverged($closing, $live));

        FinancialTransaction::query()->first()->update(['amount' => 250]);
        $liveAfter = $service->liveTotals($week['start'], $week['end']);
        $this->assertTrue($service->snapshotHasDiverged($closing, $liveAfter));
    }

    private function tx(int $categoryId, ?int $cultoId, float $amount, string $date, string $type = 'receita', string $status = 'recebido'): FinancialTransaction
    {
        return FinancialTransaction::create([
            'type' => $type,
            'transaction_date' => $date,
            'description' => 'Teste',
            'amount' => $amount,
            'is_paid' => true,
            'status' => $status,
            'category_id' => $categoryId,
            'culto_id' => $cultoId,
        ]);
    }
}
