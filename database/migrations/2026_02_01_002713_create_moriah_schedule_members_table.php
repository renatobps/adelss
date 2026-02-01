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
        Schema::create('moriah_schedule_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('moriah_schedule_id');
            $table->foreign('moriah_schedule_id')->references('id')->on('moriah_schedules')->onDelete('cascade');
            $table->unsignedBigInteger('member_id');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->enum('status', ['pendente', 'confirmado', 'recusado', 'cancelado'])->default('pendente');
            $table->timestamps();
            
            $table->unique(['moriah_schedule_id', 'member_id'], 'moriah_schedule_members_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moriah_schedule_members');
    }
};
