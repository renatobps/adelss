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
        Schema::create('moriah_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->nullable(); // Culto (evento) - pode ser null se for manual
            $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            $table->string('title')->nullable(); // Título da escala (pode ser do evento ou manual)
            $table->date('date'); // Data da escala
            $table->time('time')->nullable(); // Hora da escala
            $table->text('observations')->nullable(); // Observações
            $table->enum('status', ['rascunho', 'publicada'])->default('rascunho'); // Status
            $table->boolean('request_confirmation')->default(false); // Solicitar confirmação dos participantes
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moriah_schedules');
    }
};
