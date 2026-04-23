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
        Schema::create('rifa_vendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rifa_id')->constrained('rifas')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->constrained('members')->restrictOnDelete();
            $table->string('comprador_nome');
            $table->string('comprador_telefone')->nullable();
            $table->enum('status', ['reservado', 'vendido', 'cancelada'])->default('vendido');
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->timestamp('data_venda')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rifa_vendas');
    }
};
