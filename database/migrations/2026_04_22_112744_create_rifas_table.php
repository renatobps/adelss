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
        Schema::create('rifas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->unsignedInteger('quantidade_numeros');
            $table->decimal('valor_numero', 10, 2);
            $table->unsignedInteger('numeros_por_cartela');
            $table->date('data_sorteio')->nullable();
            $table->enum('status', ['ativa', 'finalizada', 'cancelada'])->default('ativa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rifas');
    }
};
