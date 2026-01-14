<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da turma
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade'); // Escola
            $table->enum('schedule', ['manhã', 'tarde', 'noite'])->nullable(); // Horário
            $table->enum('status', ['preparando turma', 'em andamento', 'pausada', 'finalizada'])->default('preparando turma'); // Status
            $table->text('description')->nullable(); // Descrição
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
