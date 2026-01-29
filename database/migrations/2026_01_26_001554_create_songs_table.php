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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('genre')->default('Louvor');
            $table->string('key')->nullable(); // Tom musical (ex: C, Dm, E, etc.)
            $table->foreignId('folder_id')->nullable()->constrained('folders')->onDelete('set null');
            $table->string('thumbnail_url')->nullable();
            $table->boolean('has_lyrics')->default(false); // Letra (A)
            $table->boolean('has_chords')->default(false); // Cifra (=)
            $table->boolean('has_audio')->default(false); // Áudio (nota musical)
            $table->boolean('has_video')->default(false); // Vídeo (câmera)
            $table->text('lyrics')->nullable();
            $table->text('chords')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('video_url')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
