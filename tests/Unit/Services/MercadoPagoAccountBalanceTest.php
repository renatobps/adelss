<?php

namespace Tests\Unit\Services;

use App\Services\Payments\MercadoPagoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoAccountBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config()->set('mercadopago.access_token', 'APP_USR-TOKEN');
    }

    public function test_consulta_saldo_pela_api_do_mercado_pago(): void
    {
        Http::fake([
            'https://api.mercadopago.com/users/me' => Http::response(['id' => 99], 200),
            'https://api.mercadopago.com/users/99/mercadopago_account/balance' => Http::response([
                'available_balance' => 1500.5,
                'unavailable_balance' => 20,
                'total_amount' => 1520.5,
                'currency_id' => 'BRL',
            ], 200),
        ]);

        $balance = app(MercadoPagoService::class)->getAccountBalance(true);

        $this->assertSame(1500.5, $balance['available']);
        $this->assertSame(20.0, $balance['unavailable']);
        $this->assertSame(1520.5, $balance['total']);
        $this->assertSame('BRL', $balance['currency']);
    }

    public function test_credencial_de_teste_explica_que_saldo_nao_existe(): void
    {
        Http::fake([
            'https://api.mercadopago.com/users/me' => Http::response(['id' => 99], 200),
        ]);

        config()->set('mercadopago.access_token', 'TEST-TOKEN');
        Cache::flush();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access Token atual é de teste');

        app(MercadoPagoService::class)->getAccountBalance(true);
    }

    public function test_credencial_de_teste_com_prefixo_app_usr_explica_usuario_ficticio(): void
    {
        Http::fake([
            'https://api.mercadopago.com/users/me' => Http::response([
                'id' => 99,
                'nickname' => 'TESTUSER845776823082101035',
                'identification' => ['type' => 'CPF'],
                'status' => ['mercadopago_account_type' => 'personal'],
                'tags' => ['test_user', 'normal'],
            ], 200),
        ]);

        config()->set('mercadopago.access_token', 'APP_USR-TOKEN');
        Cache::flush();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access Token atual é de teste');

        app(MercadoPagoService::class)->getAccountBalance(true);
    }

    public function test_conta_cnpj_consulta_saldo_mesmo_com_account_type_personal(): void
    {
        Http::fake([
            'https://api.mercadopago.com/users/me' => Http::response([
                'id' => 99,
                'nickname' => 'ONTECHLTDA',
                'identification' => ['type' => 'CNPJ'],
                'status' => ['mercadopago_account_type' => 'personal'],
                'tags' => ['normal'],
            ], 200),
            'https://api.mercadopago.com/users/99/mercadopago_account/balance' => Http::response([
                'available_balance' => 80.5,
                'unavailable_balance' => 0,
                'total_amount' => 80.5,
                'currency_id' => 'BRL',
            ], 200),
        ]);

        config()->set('mercadopago.access_token', 'APP_USR-TOKEN');
        Cache::flush();

        $balance = app(MercadoPagoService::class)->getAccountBalance(true);

        $this->assertSame(80.5, $balance['available']);
    }

    public function test_forbidden_no_saldo_explica_que_credencial_e_valida(): void
    {
        Http::fake([
            'https://api.mercadopago.com/users/me' => Http::response([
                'id' => 99,
                'nickname' => 'ONTECHLTDA',
                'identification' => ['type' => 'CNPJ'],
                'tags' => ['business', 'normal'],
            ], 200),
            'https://api.mercadopago.com/users/99/mercadopago_account/balance' => Http::response([
                'error' => 'ForbiddenApiError',
                'message' => 'forbidden',
                'cause' => 'forbidden',
            ], 403),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('credenciais estão válidas');

        app(MercadoPagoService::class)->getAccountBalance(true);
    }

    public function test_conta_mercado_pago_e_detectada_pelo_tipo_ou_nome(): void
    {
        $byType = new \App\Models\FinancialAccount(['type' => 'mercado_pago', 'name' => 'Carteira', 'bank_name' => null]);
        $byName = new \App\Models\FinancialAccount(['type' => 'caixa', 'name' => 'Mercado Pago Sede', 'bank_name' => null]);
        $caixa = new \App\Models\FinancialAccount(['type' => 'caixa', 'name' => 'Caixa', 'bank_name' => 'Bradesco']);

        $this->assertTrue($byType->isMercadoPago());
        $this->assertTrue($byName->isMercadoPago());
        $this->assertFalse($caixa->isMercadoPago());
    }
}
