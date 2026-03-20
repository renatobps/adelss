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
        Schema::create('discipleship_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discipleship_member_id');
            $table->foreign('discipleship_member_id')->references('id')->on('discipleship_members')->onDelete('cascade');
            $table->unsignedBigInteger('autor_id');
            $table->foreign('autor_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('visibilidade', ['discipulador', 'pastor', 'admin'])->default('discipulador');
            $table->text('conteudo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipleship_feedbacks');
    }
};
