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
        if (!Schema::hasTable('monthly_culto_service_areas')) {
            Schema::create('monthly_culto_service_areas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('monthly_culto_schedule_id');
                $table->foreign('monthly_culto_schedule_id')->references('id')->on('monthly_culto_schedules')->onDelete('cascade');
                $table->unsignedBigInteger('service_area_id');
                $table->foreign('service_area_id')->references('id')->on('service_areas')->onDelete('cascade');
                $table->unsignedBigInteger('volunteer_id');
                $table->foreign('volunteer_id')->references('id')->on('volunteers')->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['monthly_culto_schedule_id', 'service_area_id', 'volunteer_id'], 'mcs_service_area_volunteer_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_culto_service_areas');
    }
};
