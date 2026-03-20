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
        Schema::create('discipleship_indicator_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('indicator_id');
            $table->foreign('indicator_id')->references('id')->on('discipleship_indicators')->onDelete('cascade');
            $table->unsignedBigInteger('discipleship_member_id');
            $table->foreign('discipleship_member_id')->references('id')->on('discipleship_members')->onDelete('cascade');
            $table->enum('valor', ['0', '1', '2', '3', '4', '5'])->default('0'); // Escala de 0 a 5
            $table->text('observacao')->nullable();
            $table->date('data_registro');
            $table->timestamps();
            
            $table->index(['discipleship_member_id', 'data_registro'], 'idx_disc_member_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipleship_indicator_values');
    }
};
