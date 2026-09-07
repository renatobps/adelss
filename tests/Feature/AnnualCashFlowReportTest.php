<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnnualCashFlowReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

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
            $table->string('type');
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(true);
            $table->string('status')->default('pago');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_resumo_anual_agrupa_entrada_saida_e_saldo_por_mes(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin-anual@test.local',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]);
        $this->actingAs($user);

        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2024-01-10',
            'description' => 'Dízimo',
            'amount' => 1000,
            'is_paid' => true,
            'status' => 'recebido',
        ]);
        FinancialTransaction::create([
            'type' => 'despesa',
            'transaction_date' => '2024-01-20',
            'description' => 'Conta',
            'amount' => 980,
            'is_paid' => true,
            'status' => 'pago',
        ]);
        FinancialTransaction::create([
            'type' => 'receita',
            'transaction_date' => '2024-02-05',
            'description' => 'Oferta',
            'amount' => 500,
            'is_paid' => true,
            'status' => 'recebido',
        ]);

        $view = app(\App\Http\Controllers\Financial\ReportController::class)
            ->annualCashFlow(\Illuminate\Http\Request::create('/', 'GET', ['year' => 2024]));

        $byMonth = $view->getData()['byMonth'];
        $this->assertSame(1000.0, $byMonth[0]['entrada']);
        $this->assertSame(980.0, $byMonth[0]['saida']);
        $this->assertSame(20.0, $byMonth[0]['saldo']);
        $this->assertSame(20.0, $byMonth[0]['saldo_acumulado']);
        $this->assertSame(500.0, $byMonth[1]['entrada']);
        $this->assertSame(0.0, $byMonth[1]['saida']);
        $this->assertSame(520.0, $byMonth[1]['saldo_acumulado']);
        $this->assertSame(1500.0, $view->getData()['totalEntrada']);
        $this->assertSame(980.0, $view->getData()['totalSaida']);
    }
}
