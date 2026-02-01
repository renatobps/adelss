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
        Schema::create('moriah_schedule_songs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('moriah_schedule_id');
            $table->foreign('moriah_schedule_id')->references('id')->on('moriah_schedules')->onDelete('cascade');
            $table->unsignedBigInteger('song_id');
            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
            $table->integer('order')->default(0); // Ordem das músicas
            $table->timestamps();
            
            $table->unique(['moriah_schedule_id', 'song_id'], 'moriah_schedule_songs_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moriah_schedule_songs');
    }
};
