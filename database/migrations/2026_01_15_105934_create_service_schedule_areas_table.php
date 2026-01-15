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
        Schema::create('service_schedule_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('service_area_id');
            $table->integer('required_quantity')->default(1);
            $table->unsignedBigInteger('responsible_id')->nullable(); // Responsável da área
            $table->foreign('schedule_id')->references('id')->on('service_schedules')->onDelete('cascade');
            $table->foreign('service_area_id')->references('id')->on('service_areas')->onDelete('cascade');
            $table->foreign('responsible_id')->references('id')->on('members')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_schedule_areas');
    }
};
