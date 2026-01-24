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
        Schema::create('service_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id'); // Membro que serviu
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->unsignedBigInteger('volunteer_id'); // Voluntário relacionado
            $table->foreign('volunteer_id')->references('id')->on('volunteers')->onDelete('cascade');
            $table->unsignedBigInteger('service_area_id'); // Área de serviço
            $table->foreign('service_area_id')->references('id')->on('service_areas')->onDelete('cascade');
            $table->unsignedBigInteger('schedule_id'); // Escala relacionada
            $table->foreign('schedule_id')->references('id')->on('service_schedules')->onDelete('cascade');
            $table->date('date'); // Data do serviço
            $table->string('service_type')->default('culto'); // 'culto' ou 'evento'
            $table->enum('status', ['serviu', 'confirmado_nao_compareceu', 'indisponivel', 'substituido'])->default('serviu');
            $table->text('notes')->nullable(); // Observações
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index('member_id');
            $table->index('volunteer_id');
            $table->index('date');
            $table->index('schedule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_histories');
    }
};
