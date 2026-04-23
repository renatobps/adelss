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
        Schema::create('numeros_rifa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rifa_id')->constrained('rifas')->cascadeOnDelete();
            $table->unsignedBigInteger('venda_id')->nullable();
            $table->string('numero', 10);
            $table->enum('status', ['disponivel', 'reservado', 'vendido'])->default('disponivel');
            $table->string('comprador_nome')->nullable();
            $table->string('comprador_telefone')->nullable();
            $table->foreignId('vendedor_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('data_venda')->nullable();
            $table->timestamps();

            $table->unique(['rifa_id', 'numero']);
            $table->index(['rifa_id', 'status']);
            $table->index('vendedor_id');
            $table->index('venda_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numeros_rifa');
    }
};
