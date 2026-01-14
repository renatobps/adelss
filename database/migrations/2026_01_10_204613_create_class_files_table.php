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
        Schema::create('class_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('discipline_id')->nullable()->constrained('disciplines')->onDelete('set null');
            $table->string('title');
            $table->enum('type', ['file', 'text', 'external_link'])->default('file');
            $table->string('file_path')->nullable();
            $table->text('content')->nullable();
            $table->string('external_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_files');
    }
};
