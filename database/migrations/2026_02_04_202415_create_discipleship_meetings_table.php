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
        Schema::create('discipleship_meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discipleship_member_id');
            $table->foreign('discipleship_member_id')->references('id')->on('discipleship_members')->onDelete('cascade');
            $table->date('data');
            $table->enum('tipo', ['presencial', 'online'])->default('presencial');
            $table->text('assuntos_tratados')->nullable();
            $table->text('versiculos')->nullable();
            $table->text('oracao')->nullable();
            $table->text('observacoes_privadas')->nullable();
            $table->text('proximo_passo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipleship_meetings');
    }
};
