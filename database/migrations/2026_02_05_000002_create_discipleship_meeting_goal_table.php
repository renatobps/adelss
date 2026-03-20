<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('discipleship_meeting_goal')) {
            return;
        }
        Schema::create('discipleship_meeting_goal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discipleship_meeting_id');
            $table->unsignedBigInteger('discipleship_goal_id');
            $table->timestamps();

            $table->foreign('discipleship_meeting_id')->references('id')->on('discipleship_meetings')->onDelete('cascade');
            $table->foreign('discipleship_goal_id')->references('id')->on('discipleship_goals')->onDelete('cascade');
            $table->unique(['discipleship_meeting_id', 'discipleship_goal_id'], 'disc_meeting_goal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discipleship_meeting_goal');
    }
};
