<?php

namespace Tests\Feature;

use App\Services\FinancialNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SyncMercadoPagoMovementsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config()->set('mercadopago.access_token', 'APP_USR-TOKEN');
    }

    public function test_consulta_movimentos_e_avisa_a_tesouraria(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/v1/payments/search')) {
                return Http::response([
                    'paging' => ['total' => 1, 'limit' => 50, 'offset' => 0],
                    'results' => [[
                        'id' => 99,
                        'status' => 'approved',
                        'transaction_amount' => 5,
                        'date_approved' => now()->toIso8601String(),
                        'description' => 'PIX',
                        'payment_method_id' => 'pix',
                    ]],
                ], 200);
            }

            return Http::response(['results' => []], 200);
        });

        $notify = Mockery::mock(FinancialNotificationService::class);
        $notify->shouldReceive('notificarMovimentosMercadoPagoRecentes')
            ->once()
            ->andReturn(1);
        $this->app->instance(FinancialNotificationService::class, $notify);

        $this->artisan('financial:sync-mercadopago-movements')
            ->assertSuccessful()
            ->expectsOutputToContain('avisos enviados: 1');
    }
}
