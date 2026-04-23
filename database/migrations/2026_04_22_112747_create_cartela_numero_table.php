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
        Schema::create('cartela_numero', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cartela_id')->constrained('cartelas')->cascadeOnDelete();
            $table->foreignId('numero_id')->constrained('numeros_rifa')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cartela_id', 'numero_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartela_numero');
    }
};
