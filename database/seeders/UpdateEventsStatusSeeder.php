<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use Carbon\Carbon;

class UpdateEventsStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Atualizar eventos sem status para 'agendado'
        Event::whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => 'agendado']);
        
        // Marcar eventos passados como concluído
        Event::where('status', 'agendado')
            ->where(function($query) use ($now) {
                $query->where('end_date', '<', $now)
                      ->orWhere(function($q) use ($now) {
                          $q->whereNull('end_date')
                            ->where('start_date', '<', $now);
                      });
            })
            ->update(['status' => 'concluido']);
    }
}
