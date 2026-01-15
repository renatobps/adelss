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
        Schema::create('service_schedule_volunteers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_area_id');
            $table->unsignedBigInteger('volunteer_id');
            $table->enum('status', ['confirmado', 'pendente', 'cancelado'])->default('pendente');
            $table->text('notes')->nullable();
            $table->foreign('schedule_area_id')->references('id')->on('service_schedule_areas')->onDelete('cascade');
            $table->foreign('volunteer_id')->references('id')->on('volunteers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_schedule_volunteers');
    }
};
