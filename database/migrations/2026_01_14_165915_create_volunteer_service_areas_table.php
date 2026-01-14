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
        Schema::create('volunteer_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_id')->constrained('volunteers')->onDelete('cascade');
            $table->foreignId('service_area_id')->constrained('service_areas')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['volunteer_id', 'service_area_id']); // Evita duplicatas
            $table->index('volunteer_id');
            $table->index('service_area_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_service_areas');
    }
};
