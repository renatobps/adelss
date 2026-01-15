<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use Carbon\Carbon;

class CheckPastEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:check-past';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica eventos passados e marca como concluído';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        
        // Buscar eventos agendados que já passaram
        $pastEvents = Event::where('status', 'agendado')
            ->where(function($query) use ($now) {
                $query->where('end_date', '<', $now)
                      ->orWhere(function($q) use ($now) {
                          // Se não tiver end_date, usar start_date
                          $q->whereNull('end_date')
                            ->where('start_date', '<', $now);
                      });
            })
            ->get();

        $count = 0;
        foreach ($pastEvents as $event) {
            $event->update(['status' => 'concluido']);
            $count++;
        }

        $this->info("{$count} evento(s) marcado(s) como concluído(s).");
        
        return Command::SUCCESS;
    }
}
