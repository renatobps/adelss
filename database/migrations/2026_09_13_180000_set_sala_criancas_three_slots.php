<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_areas')
            ->whereRaw('LOWER(name) LIKE ?', ['%sala das crian%'])
            ->update([
                'min_quantity' => 3,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('service_areas')
            ->whereRaw('LOWER(name) LIKE ?', ['%sala das crian%'])
            ->update([
                'min_quantity' => 2,
                'updated_at' => now(),
            ]);
    }
};
