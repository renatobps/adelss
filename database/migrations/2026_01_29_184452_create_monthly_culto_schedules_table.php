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
        if (!Schema::hasTable('monthly_culto_schedules')) {
            Schema::create('monthly_culto_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id'); // Culto (evento)
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->integer('month'); // 1-12
            $table->integer('year'); // Ex: 2026
            $table->timestamps();
            
            // Garantir que não haja duplicatas para o mesmo evento no mesmo mês/ano
            $table->unique(['event_id', 'month', 'year'], 'mcs_event_month_year_unique');
            });
        }

        // Tabela pivot para preletores (membros)
        if (!Schema::hasTable('monthly_culto_preletores')) {
            Schema::create('monthly_culto_preletores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monthly_culto_schedule_id');
            $table->foreign('monthly_culto_schedule_id')->references('id')->on('monthly_culto_schedules')->onDelete('cascade');
            $table->unsignedBigInteger('member_id'); // Preletor
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['monthly_culto_schedule_id', 'member_id'], 'mcs_preletores_unique');
            });
        }

        // Tabela pivot para dirigentes (membros)
        if (!Schema::hasTable('monthly_culto_dirigentes')) {
            Schema::create('monthly_culto_dirigentes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monthly_culto_schedule_id');
            $table->foreign('monthly_culto_schedule_id')->references('id')->on('monthly_culto_schedules')->onDelete('cascade');
            $table->unsignedBigInteger('member_id'); // Dirigente
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['monthly_culto_schedule_id', 'member_id'], 'mcs_dirigentes_unique');
            });
        }

        // Tabela pivot para portaria (voluntários)
        if (!Schema::hasTable('monthly_culto_portaria')) {
            Schema::create('monthly_culto_portaria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monthly_culto_schedule_id');
            $table->foreign('monthly_culto_schedule_id')->references('id')->on('monthly_culto_schedules')->onDelete('cascade');
            $table->unsignedBigInteger('volunteer_id'); // Voluntário de portaria
            $table->foreign('volunteer_id')->references('id')->on('volunteers')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['monthly_culto_schedule_id', 'volunteer_id'], 'mcs_portaria_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_culto_portaria');
        Schema::dropIfExists('monthly_culto_dirigentes');
        Schema::dropIfExists('monthly_culto_preletores');
        Schema::dropIfExists('monthly_culto_schedules');
    }
};
