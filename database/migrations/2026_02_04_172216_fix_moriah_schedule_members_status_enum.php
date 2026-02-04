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
        // Garantir que o enum tenha todos os valores corretos
        DB::statement("ALTER TABLE moriah_schedule_members MODIFY COLUMN status ENUM('pendente', 'confirmado', 'recusado', 'cancelado') DEFAULT 'pendente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para o estado anterior se necessário
        DB::statement("ALTER TABLE moriah_schedule_members MODIFY COLUMN status ENUM('pendente', 'confirmado', 'recusado', 'cancelado') DEFAULT 'pendente'");
    }
};
