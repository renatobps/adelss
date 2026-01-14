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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da escola
            $table->text('description')->nullable(); // Descrição
            $table->unsignedBigInteger('manager_id')->nullable(); // Gestor (membro)
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('manager_id')->references('id')->on('members')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
