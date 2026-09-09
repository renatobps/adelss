<?php

namespace App\Console\Commands;

use App\Services\FinancialNotificationService;
use App\Services\Payments\MercadoPagoService;
use Illuminate\Console\Command;

class SyncMercadoPagoMovements extends Command
{
    protected $signature = 'financial:sync-mercadopago-movements';

    protected $description = 'Consulta entradas e saídas do Mercado Pago e avisa o grupo da tesouraria';

    public function handle(
        MercadoPagoService $mercadoPago,
        FinancialNotificationService $notificationService
    ): int {
        if (trim((string) config('mercadopago.access_token', '')) === '') {
            $this->warn('MP_ACCESS_TOKEN não configurado.');

            return self::SUCCESS;
        }

        $movements = $mercadoPago->getPaymentMovements(true);

        if (! empty($movements['error'])) {
            $this->error((string) $movements['error']);

            return self::FAILURE;
        }

        $sent = $notificationService->notificarMovimentosMercadoPagoRecentes($movements);

        $this->info(sprintf(
            'Entradas R$ %s · Saídas R$ %s · avisos enviados: %d',
            number_format((float) ($movements['in_total'] ?? 0), 2, ',', '.'),
            number_format((float) ($movements['out_total'] ?? 0), 2, ',', '.'),
            $sent
        ));

        return self::SUCCESS;
    }
}
