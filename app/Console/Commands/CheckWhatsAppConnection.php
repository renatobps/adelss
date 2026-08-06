<?php

namespace App\Console\Commands;

use App\Mail\WhatsAppConnectionAlert;
use App\Models\WhatsAppConnectionLog;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckWhatsAppConnection extends Command
{
    protected $signature = 'whatsapp:check-connection';

    protected $description = 'Verifica a conexão da instância WhatsApp, grava histórico e alerta por e-mail em caso de queda/recuperação';

    public function handle(WhatsAppService $whatsapp): int
    {
        $result = $whatsapp->checkConnectionStatus();

        $previous = WhatsAppConnectionLog::query()
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->first();

        $current = WhatsAppConnectionLog::create([
            'instance_name' => $result['instance_name'] ?: 'padrão',
            'instance_id' => $result['instance_id'] ?: null,
            'status' => $result['status'],
            'raw_state' => $result['raw_state'] ?: null,
            'checked_at' => now(),
            'notified' => false,
        ]);

        // Atualiza o banner do layout sem esperar o TTL do cache.
        Cache::forget('whatsapp.last_connection_log');

        $this->info("Status: {$result['status']} (instância: {$current->instance_name})");

        if ($current->isOnline()) {
            $this->handleRecovery($current, $previous);
        } else {
            $this->handleDown($current, $previous);
        }

        return self::SUCCESS;
    }

    /**
     * Alerta de queda: só após 2 verificações consecutivas offline
     * e apenas uma vez por período de queda.
     */
    private function handleDown(WhatsAppConnectionLog $current, ?WhatsAppConnectionLog $previous): void
    {
        if (!$previous || $previous->isOnline()) {
            // Primeira falha isolada: aguarda a próxima verificação (evita falso positivo).
            return;
        }

        if ($this->alertAlreadySentSinceLastOnline($current)) {
            return;
        }

        $downSince = $this->outageStartedAt($current) ?? $current->checked_at;

        $sent = $this->sendAlert(new WhatsAppConnectionAlert(
            WhatsAppConnectionAlert::TYPE_DOWN,
            $current->instance_name,
            $downSince,
        ));

        if ($sent) {
            $current->update(['notified' => true]);
            $this->warn('Alerta de queda enviado por e-mail.');
        }
    }

    /**
     * Alerta de recuperação: apenas na transição offline -> online,
     * e só se houve alerta de queda para o período.
     */
    private function handleRecovery(WhatsAppConnectionLog $current, ?WhatsAppConnectionLog $previous): void
    {
        if (!$previous || $previous->isOnline()) {
            return;
        }

        if (!$this->alertAlreadySentSinceLastOnline($current)) {
            // Queda curta sem alerta enviado: nada a comunicar.
            return;
        }

        $downSince = $this->outageStartedAt($current);
        $downtime = $downSince
            ? $downSince->locale('pt_BR')->diffForHumans($current->checked_at, ['parts' => 2, 'short' => false, 'syntax' => Carbon::DIFF_ABSOLUTE])
            : null;

        $sent = $this->sendAlert(new WhatsAppConnectionAlert(
            WhatsAppConnectionAlert::TYPE_RECOVERED,
            $current->instance_name,
            $current->checked_at,
            $downtime,
        ));

        if ($sent) {
            $current->update(['notified' => true]);
            $this->info('Alerta de recuperação enviado por e-mail.');
        }
    }

    /**
     * Já existe alerta disparado desde a última vez que a instância esteve online?
     */
    private function alertAlreadySentSinceLastOnline(WhatsAppConnectionLog $current): bool
    {
        $lastOnline = WhatsAppConnectionLog::query()
            ->where('status', WhatsAppConnectionLog::STATUS_CONECTADO)
            ->where('id', '<', $current->id)
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->first();

        return WhatsAppConnectionLog::query()
            ->where('notified', true)
            ->where('id', '<', $current->id)
            ->when($lastOnline, fn ($q) => $q->where('id', '>', $lastOnline->id))
            ->exists();
    }

    /**
     * Início do período de queda atual: primeira verificação offline
     * após o último registro online.
     */
    private function outageStartedAt(WhatsAppConnectionLog $current): ?Carbon
    {
        $lastOnline = WhatsAppConnectionLog::query()
            ->where('status', WhatsAppConnectionLog::STATUS_CONECTADO)
            ->where('id', '<', $current->id)
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->first();

        $firstOffline = WhatsAppConnectionLog::query()
            ->where('status', '!=', WhatsAppConnectionLog::STATUS_CONECTADO)
            ->where('id', '<', $current->id)
            ->when($lastOnline, fn ($q) => $q->where('id', '>', $lastOnline->id))
            ->orderBy('checked_at')
            ->orderBy('id')
            ->first();

        return $firstOffline?->checked_at;
    }

    private function sendAlert(WhatsAppConnectionAlert $mailable): bool
    {
        $recipients = (array) config('whatsapp.alert_emails', []);
        if ($recipients === []) {
            Log::warning('WhatsApp monitor: WHATSAPP_ALERT_EMAILS não configurado; alerta não enviado.');

            return false;
        }

        try {
            Mail::to($recipients)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            Log::critical('WhatsApp monitor: falha ao enviar alerta por e-mail.', [
                'type' => $mailable->type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
