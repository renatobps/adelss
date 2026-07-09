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

        // Lembretes de despesas financeiras com vencimento próximo
        $schedule->command('financial:notify-due-expenses')->dailyAt('08:00');

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


