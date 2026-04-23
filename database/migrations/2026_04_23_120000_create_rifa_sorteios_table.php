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
        Schema::create('rifa_sorteios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rifa_id')->constrained('rifas')->cascadeOnDelete();
            $table->foreignId('numero_rifa_id')->constrained('numeros_rifa')->cascadeOnDelete();
            $table->string('numero', 10);
            $table->string('comprador_nome');
            $table->foreignId('vendedor_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('vendedor_nome')->nullable();
            $table->foreignId('sorteado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rifa_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rifa_sorteios');
    }
};
