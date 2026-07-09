<?php

namespace App\Console\Commands;

use App\Services\FinancialNotificationService;
use Illuminate\Console\Command;

class NotifyDueFinancialExpenses extends Command
{
    protected $signature = 'financial:notify-due-expenses';

    protected $description = 'Notifica tesoureiros sobre despesas com vencimento próximo';

    public function handle(FinancialNotificationService $notificationService): int
    {
        $resultado = $notificationService->notificarDespesasVencendo();

        if (!($resultado['success'] ?? false)) {
            $this->error($resultado['error'] ?? 'Falha ao enviar lembretes.');
            return Command::FAILURE;
        }

        $notificadas = $resultado['notificadas'] ?? 0;
        $this->info("Lembretes enviados para {$notificadas} despesa(s).");

        return Command::SUCCESS;
    }
}
