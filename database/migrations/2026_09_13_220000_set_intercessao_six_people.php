<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_areas')
            ->whereRaw('LOWER(name) LIKE ?', ['%intercess%'])
            ->update([
                'min_quantity' => 6,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('service_areas')
            ->whereRaw('LOWER(name) LIKE ?', ['%intercess%'])
            ->update([
                'min_quantity' => 12,
                'updated_at' => now(),
            ]);
    }
};
