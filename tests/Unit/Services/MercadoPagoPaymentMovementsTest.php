<?php

namespace Tests\Unit\Services;

use App\Services\Payments\MercadoPagoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoPaymentMovementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config()->set('mercadopago.access_token', 'APP_USR-TOKEN');
    }

    public function test_agrupa_aprovados_como_entrada_e_estornos_como_saida(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/v1/payments/search')) {
                return Http::response([
                    'paging' => ['total' => 3, 'limit' => 50, 'offset' => 0],
                    'results' => [
                        [
                            'id' => 1,
                            'status' => 'approved',
                            'transaction_amount' => 100,
                            'transaction_amount_refunded' => 0,
                            'date_approved' => '2026-09-09T12:00:00.000-03:00',
                            'description' => 'Dízimo',
                            'payment_method_id' => 'pix',
                            'payer' => ['email' => 'a@example.com'],
                        ],
                        [
                            'id' => 2,
                            'status' => 'refunded',
                            'transaction_amount' => 30,
                            'date_last_updated' => '2026-09-08T12:00:00.000-03:00',
                            'description' => 'Estorno',
                            'payment_method_id' => 'visa',
                            'payer' => ['first_name' => 'Maria', 'last_name' => 'Silva'],
                        ],
                        [
                            'id' => 3,
                            'status' => 'cancelled',
                            'transaction_amount' => 50,
                            'date_created' => '2026-09-07T12:00:00.000-03:00',
                        ],
                    ],
                ], 200);
            }

            return Http::response(['paging' => ['total' => 0], 'results' => []], 200);
        });

        $movements = app(MercadoPagoService::class)->getPaymentMovements(true);

        $this->assertNull($movements['error']);
        $this->assertSame(100.0, $movements['in_total']);
        $this->assertSame(30.0, $movements['out_total']);
        $this->assertCount(2, $movements['items']);
        $this->assertSame('in', $movements['items'][0]['direction']);
        $this->assertSame('PIX', $movements['items'][0]['method']);
        $this->assertSame('out', $movements['items'][1]['direction']);
        $this->assertSame('Maria Silva', $movements['items'][1]['payer']);
        $this->assertFalse($movements['truncated']);
    }

    public function test_inclui_saque_do_relatorio_de_liberacoes(): void
    {
        $csv = implode("\n", [
            'DATE,SOURCE_ID,RECORD_TYPE,DESCRIPTION,NET_DEBIT_AMOUNT,NET_CREDIT_AMOUNT,GROSS_AMOUNT,MP_FEE_AMOUNT,PAYMENT_METHOD,OPERATION_TAGS',
            '2026-09-08T10:00:00.000-03:00,99,release,payout,150.00,0,150.00,0,pix,',
            '2026-09-08T11:00:00.000-03:00,1,release,payment,0,100.00,100.00,0,pix,',
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($csv) {
            $url = $request->url();
            if (str_contains($url, '/v1/payments/search')) {
                return Http::response([
                    'paging' => ['total' => 1, 'limit' => 50, 'offset' => 0],
                    'results' => [[
                        'id' => 1,
                        'status' => 'approved',
                        'transaction_amount' => 100,
                        'date_approved' => '2026-09-09T12:00:00.000-03:00',
                        'description' => 'Oferta',
                        'payment_method_id' => 'pix',
                    ]],
                ], 200);
            }
            if (str_contains($url, 'release_report/search')) {
                return Http::response([
                    'paging' => ['total' => 1],
                    'results' => [['file_name' => 'adelss-release.csv', 'status' => 'enabled']],
                ], 200);
            }
            if (str_contains($url, 'adelss-release.csv')) {
                return Http::response($csv, 200, ['Content-Type' => 'text/csv']);
            }

            return Http::response(['ok' => true], 200);
        });

        $movements = app(MercadoPagoService::class)->getPaymentMovements(true);

        $this->assertSame(100.0, $movements['in_total']);
        $this->assertSame(150.0, $movements['out_total']);
        $this->assertCount(2, $movements['items']);
        $this->assertSame('out', $movements['items'][1]['direction']);
        $this->assertSame('Saque / transferência', $movements['items'][1]['description']);
    }

    public function test_inclui_pix_enviado_do_relatorio_de_dinheiro_em_conta(): void
    {
        $csv = implode("\n", [
            'SOURCE_ID;TRANSACTION_TYPE;TRANSACTION_AMOUNT;SETTLEMENT_NET_AMOUNT;SETTLEMENT_DATE;PAYMENT_METHOD',
            '888;PAYOUTS;-75.50;-75.50;2026-09-09T15:36:00.000-03:00;pix',
            '1;SETTLEMENT;100.00;97.00;2026-09-09T15:25:00.000-03:00;pix',
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($csv) {
            $url = $request->url();
            if (str_contains($url, '/v1/payments/search')) {
                return Http::response([
                    'paging' => ['total' => 1, 'limit' => 50, 'offset' => 0],
                    'results' => [[
                        'id' => 1,
                        'status' => 'approved',
                        'transaction_amount' => 100,
                        'date_approved' => '2026-09-09T15:25:00.000-03:00',
                        'description' => 'Oferta',
                        'payment_method_id' => 'pix',
                    ]],
                ], 200);
            }
            if (str_contains($url, 'settlement_report/search')) {
                return Http::response([
                    'paging' => ['total' => 1],
                    'results' => [[
                        'file_name' => 'adelss-settlement.csv',
                        'file_status' => 'processed',
                        'date_created' => now()->toIso8601String(),
                    ]],
                ], 200);
            }
            if (str_contains($url, 'adelss-settlement.csv')) {
                return Http::response($csv, 200, ['Content-Type' => 'text/csv']);
            }
            if (str_contains($url, 'release_report/search')) {
                return Http::response(['paging' => ['total' => 0], 'results' => []], 200);
            }

            return Http::response(['ok' => true], 200);
        });

        $movements = app(MercadoPagoService::class)->getPaymentMovements(true);

        $this->assertSame(100.0, $movements['in_total']);
        $this->assertSame(75.5, $movements['out_total']);
        $this->assertCount(2, $movements['items']);
        $this->assertSame('out', $movements['items'][0]['direction']);
        $this->assertSame('Saque / PIX enviado', $movements['items'][0]['description']);
        $this->assertFalse($movements['outflows_pending']);
    }

    public function test_consulta_sem_renovar_relatorio_nao_gera_csv_novo(): void
    {
        $posts = 0;
        Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$posts) {
            if (in_array($request->method(), ['POST', 'PUT'], true)
                && (str_contains($request->url(), 'settlement_report') || str_contains($request->url(), 'release_report'))) {
                $posts++;
            }
            if (str_contains($request->url(), '/v1/payments/search')) {
                return Http::response(['paging' => ['total' => 0], 'results' => []], 200);
            }

            return Http::response(['results' => []], 200);
        });

        app(MercadoPagoService::class)->getPaymentMovements(true, 30, 50, false);

        $this->assertSame(0, $posts);
    }

    public function test_falha_da_api_nao_quebra_a_consulta(): void
    {
        Http::fake([
            'https://api.mercadopago.com/v1/payments/search*' => Http::response([
                'message' => 'forbidden',
                'error' => 'ForbiddenApiError',
            ], 403),
        ]);

        $movements = app(MercadoPagoService::class)->getPaymentMovements(true);

        $this->assertNotEmpty($movements['error']);
        $this->assertSame([], $movements['items']);
        $this->assertSame(0.0, $movements['in_total']);
    }

    public function test_le_movimentos_somente_do_cache(): void
    {
        Cache::put('mercadopago.payment_movements.30.50', [
            'days' => 30,
            'in_total' => 12.0,
            'out_total' => 0.0,
            'items' => [['id' => '9', 'direction' => 'in', 'amount' => 12]],
            'truncated' => false,
            'error' => null,
            'outflows_pending' => false,
        ], now()->addMinute());

        Http::fake();

        $cached = app(MercadoPagoService::class)->getCachedPaymentMovements();

        $this->assertSame(12.0, $cached['in_total']);
        $this->assertSame('9', $cached['items'][0]['id']);
        Http::assertNothingSent();
    }
}
