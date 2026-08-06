<?php

namespace App\Console\Commands;

use App\Models\FinancialNotificationLog;
use App\Models\NotificacaoEnviada;
use App\Models\WhatsAppConnectionLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PurgeOldNotificationLogs extends Command
{
    protected $signature = 'logs:purge-old
                            {--days= : Dias de retenção (padrão: config logs.retention_days)}
                            {--dry-run : Apenas exibe quantos registros seriam removidos}';

    protected $description = 'Remove logs antigos de notificações financeiras e WhatsApp';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('logs.retention_days', 180));
        $cutoff = Carbon::now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $financialCount = FinancialNotificationLog::query()
            ->where('created_at', '<', $cutoff)
            ->count();

        $whatsappCount = NotificacaoEnviada::query()
            ->where('created_at', '<', $cutoff)
            ->count();

        $connectionCount = WhatsAppConnectionLog::query()
            ->where('checked_at', '<', $cutoff)
            ->count();

        $this->info("Retenção: {$days} dias (anteriores a {$cutoff->format('d/m/Y H:i')})");
        $this->line("financial_notification_logs: {$financialCount}");
        $this->line("notificacoes_enviadas: {$whatsappCount}");
        $this->line("whatsapp_connection_logs: {$connectionCount}");

        if ($dryRun) {
            $this->warn('Dry-run: nenhum registro foi removido.');
            return self::SUCCESS;
        }

        $deletedFinancial = FinancialNotificationLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedWhatsapp = NotificacaoEnviada::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedConnection = WhatsAppConnectionLog::query()
            ->where('checked_at', '<', $cutoff)
            ->delete();

        $this->info("Removidos: {$deletedFinancial} logs financeiros, {$deletedWhatsapp} notificações WhatsApp, {$deletedConnection} verificações de conexão.");

        return self::SUCCESS;
    }
}
