<?php

namespace App\Console\Commands;

use App\Services\FinancialNotificationService;
use Illuminate\Console\Command;

class SendSmartFinancialSummary extends Command
{
    protected $signature = 'financial:send-smart-summary {--force : Ignora horário/dia configurados}';

    protected $description = 'Envia o resumo financeiro inteligente aos tesoureiros';

    public function handle(FinancialNotificationService $notificationService): int
    {
        $resultado = $notificationService->enviarResumoFinanceiroInteligente(
            (bool) $this->option('force')
        );

        if (!($resultado['success'] ?? false)) {
            $this->error($resultado['error'] ?? 'Falha ao enviar resumo.');
            return Command::FAILURE;
        }

        $enviados = $resultado['enviados'] ?? 0;
        $message = $resultado['message'] ?? "Resumo enviado para {$enviados} destinatário(s).";
        $this->info($message);

        return Command::SUCCESS;
    }
}
