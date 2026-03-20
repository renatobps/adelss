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
        Schema::create('discipleship_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discipleship_member_id');
            $table->foreign('discipleship_member_id')->references('id')->on('discipleship_members')->onDelete('cascade');
            $table->enum('tipo', ['espiritual', 'material'])->default('espiritual');
            $table->string('descricao');
            $table->date('prazo')->nullable();
            $table->enum('status', ['em_andamento', 'concluido', 'pausado'])->default('em_andamento');
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipleship_goals');
    }
};
