<?php

namespace App\Console\Commands;

use App\Services\DepartmentEventNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendDepartmentEventNotifications extends Command
{
    protected $signature = 'departments:notify-events {--limit=10 : Máximo de mensagens por ciclo} {--watch : Mantém um processador local ativo}';

    protected $description = 'Processa avisos manuais e agendados dos eventos dos departamentos';

    public function handle(DepartmentEventNotificationService $service): int
    {
        do {
            try {
                Cache::lock('departments.event-notifications.worker', 600)->get(function () use ($service) {
                    Cache::put('departments.event-notifications.last-run', now()->toIso8601String(), now()->addMinutes(10));
                    $batches = $service->enqueueDue();
                    $sent = $service->processPending((int) $this->option('limit'));
                    $this->info("{$batches} agendamento(s) iniciado(s); {$sent} mensagem(ns) processada(s).");
                });
            } catch (\Throwable $exception) {
                Log::error('Falha no processador de notificações de departamentos.', ['exception' => $exception]);
                $this->error('Falha ao processar as notificações; consulte o log da aplicação.');
                if (!$this->option('watch')) return self::FAILURE;
            }
            if ($this->option('watch')) sleep(30);
        } while ($this->option('watch'));

        return self::SUCCESS;
    }
}
