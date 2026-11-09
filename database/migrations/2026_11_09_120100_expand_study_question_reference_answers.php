<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite não impõe o tamanho declarado em VARCHAR.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        Schema::table('study_form_questions', function (Blueprint $table) {
            $table->text('correct_answer')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Mantém o campo ampliado para não truncar respostas já importadas.
    }
};
