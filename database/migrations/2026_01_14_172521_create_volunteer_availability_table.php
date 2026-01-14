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
        Schema::create('volunteer_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_id')->unique()->constrained('volunteers')->onDelete('cascade');
            $table->json('days_of_week')->nullable(); // ['segunda', 'terça', etc]
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->date('unavailable_start')->nullable();
            $table->date('unavailable_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('volunteer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_availability');
    }
};
