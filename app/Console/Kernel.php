<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Verificar eventos passados a cada hora
        $schedule->command('events:check-past')->hourly();

        // Lembretes / resumos financeiros (horário vem da configuração da automação)
        $schedule->command('financial:notify-due-expenses')->hourly();
        $schedule->command('financial:send-smart-summary')->hourly();
        $schedule->command('cultos:check-pastoral-alerts')->dailyAt('09:00');
        // withoutOverlapping evita duas execuções simultâneas (causa de envio
        // duplicado ao WhatsApp quando a publicação no Instagram demora +5 min)
        $schedule->command('midia:publish-instagram-posts')
            ->everyFiveMinutes()
            ->withoutOverlapping(30);
        $schedule->command('midia:remove-expired-instagram-posts')
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        // Geocodifica endereços de membros (mapa) — Nominatim ~1 req/s
        $schedule->command('members:geocode-addresses --limit=20')
            ->hourly()
            ->withoutOverlapping();

        // Monitor de conexão do WhatsApp (alerta por e-mail — nunca por WhatsApp)
        $schedule->command('whatsapp:check-connection')
            ->everyTenMinutes()
            ->withoutOverlapping();

        // Purge de logs antigos de notificação (domingo 03:00)
        $schedule->command('logs:purge-old')->weeklyOn(0, '03:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}


