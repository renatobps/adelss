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
        // Alterar o enum para incluir 'concluido'
        DB::statement("ALTER TABLE service_schedules MODIFY COLUMN status ENUM('rascunho', 'publicada', 'cancelada', 'concluido') DEFAULT 'rascunho'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para o enum original
        DB::statement("ALTER TABLE service_schedules MODIFY COLUMN status ENUM('rascunho', 'publicada', 'cancelada') DEFAULT 'rascunho'");
    }
};
