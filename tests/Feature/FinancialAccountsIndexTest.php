<?php

namespace Tests\Feature;

use App\Http\Controllers\Financial\AccountController;
use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialAccountsIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('mercadopago.access_token', 'APP_USR-TOKEN');
        Cache::flush();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 32)->default('caixa');
            $table->string('bank_name')->nullable();
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->string('color', 16)->default('#ef4444');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('type');
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('financial_accounts');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_pagina_de_contas_nao_espera_a_api_do_mercado_pago(): void
    {
        Http::fake([
            'https://api.mercadopago.com/*' => Http::response(['results' => []], 200),
        ]);

        FinancialAccount::create([
            'name' => 'Mercado Pago',
            'type' => FinancialAccount::TYPE_MERCADO_PAGO,
            'initial_balance' => 0,
            'color' => '#3b82f6',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin());
        $view = app(AccountController::class)->index(Request::create('/financial/accounts', 'GET'));
        $data = $view->getData();

        $this->assertSame('financial.accounts.index', $view->name());
        $this->assertTrue($data['mpMovements']['loading'] ?? false);
        $this->assertSame([], $data['mpMovements']['items']);
        Http::assertNothingSent();

        $blade = file_get_contents(resource_path('views/financial/accounts/index.blade.php'));
        $this->assertStringContainsString('js-mp-search', $blade);
        $this->assertStringContainsString('js-mp-pagination', $blade);
        $this->assertStringContainsString('js-mp-cards', $blade);
        $this->assertStringContainsString('js-mp-page-size', $blade);
        $this->assertStringContainsString('value="10"', $blade);
        $this->assertStringContainsString('value="25"', $blade);
        $this->assertStringContainsString('value="50"', $blade);
        $this->assertStringContainsString('js-mp-export-pdf', $blade);
        $this->assertStringContainsString('js-mp-export-excel', $blade);
        $this->assertStringContainsString('d-md-none', $blade);
    }

    public function test_extrato_em_cache_entra_na_pagina_sem_nova_consulta(): void
    {
        Cache::put('mercadopago.payment_movements.30.50', [
            'days' => 30,
            'in_total' => 80.0,
            'out_total' => 10.0,
            'items' => [[
                'id' => '1',
                'direction' => 'in',
                'description' => 'Dízimo PIX',
                'payer' => 'Maria',
                'method' => 'PIX',
                'amount' => 80,
                'occurred_at_label' => '09/09/2026 12:00',
            ]],
            'truncated' => false,
            'error' => null,
            'outflows_pending' => false,
        ], now()->addMinute());

        FinancialAccount::create([
            'name' => 'Mercado Pago',
            'type' => FinancialAccount::TYPE_MERCADO_PAGO,
            'initial_balance' => 0,
            'color' => '#3b82f6',
            'is_active' => true,
        ]);

        Http::fake();
        $this->actingAs($this->admin());

        $data = app(AccountController::class)
            ->index(Request::create('/financial/accounts', 'GET'))
            ->getData();

        $this->assertSame(80.0, $data['mpMovements']['in_total']);
        $this->assertSame('Dízimo PIX', $data['mpMovements']['items'][0]['description']);
        $this->assertEquals(70.0, $data['saldoAtivas']);
        Http::assertNothingSent();
    }

    public function test_exporta_extrato_filtrado_em_excel_e_pdf(): void
    {
        Cache::put('mercadopago.payment_movements.30.50', [
            'days' => 30,
            'in_total' => 80.0,
            'out_total' => 10.0,
            'items' => [
                [
                    'id' => '1',
                    'direction' => 'in',
                    'description' => 'Dízimo PIX',
                    'payer' => 'Maria',
                    'method' => 'PIX',
                    'amount' => 80,
                    'occurred_at_label' => '09/09/2026 12:00',
                ],
                [
                    'id' => '2',
                    'direction' => 'out',
                    'description' => 'Saque',
                    'payer' => '',
                    'method' => 'PIX',
                    'amount' => 10,
                    'occurred_at_label' => '08/09/2026 10:00',
                ],
            ],
            'truncated' => false,
            'error' => null,
            'outflows_pending' => false,
        ], now()->addMinute());

        $this->actingAs($this->admin());
        Http::fake();

        $excel = app(AccountController::class)->exportMercadoPagoExcel(Request::create('/export', 'GET', ['q' => 'Dízimo']));
        ob_start();
        $excel->sendContent();
        $csv = (string) ob_get_clean();

        $this->assertStringContainsString('Dízimo PIX', $csv);
        $this->assertStringNotContainsString('Saque', $csv);
        $this->assertStringContainsString('Entradas', $csv);

        $pdf = app(AccountController::class)->exportMercadoPagoPdf(Request::create('/export', 'GET', ['q' => 'Dízimo']));
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        Http::assertNothingSent();
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-contas@test.local',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]);
    }
}
