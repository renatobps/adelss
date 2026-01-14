<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $serviceAreas = [
            ['name' => 'Portaria', 'description' => null, 'status' => 'ativo'],
            ['name' => 'Recepção', 'description' => null, 'status' => 'ativo'],
            ['name' => 'Água', 'description' => null, 'status' => 'ativo'],
            ['name' => 'Direção de Culto', 'description' => null, 'status' => 'ativo'],
            ['name' => 'Sala das Crianças', 'description' => null, 'status' => 'ativo'],
            ['name' => 'Apoio Geral', 'description' => null, 'status' => 'ativo'],
        ];

        foreach ($serviceAreas as $area) {
            DB::table('service_areas')->insert([
                'name' => $area['name'],
                'description' => $area['description'],
                'status' => $area['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('service_areas')->whereIn('name', [
            'Portaria',
            'Recepção',
            'Água',
            'Direção de Culto',
            'Sala das Crianças',
            'Apoio Geral',
        ])->delete();
    }
};
