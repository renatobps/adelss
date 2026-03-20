<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexExists = collect(DB::select("SHOW INDEX FROM discipleship_meeting_goal WHERE Key_name = 'disc_meeting_goal_unique'"))->isNotEmpty();
        if (!$indexExists) {
            Schema::table('discipleship_meeting_goal', function (Blueprint $table) {
                $table->unique(['discipleship_meeting_id', 'discipleship_goal_id'], 'disc_meeting_goal_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('discipleship_meeting_goal', function (Blueprint $table) {
            $table->dropUnique('disc_meeting_goal_unique');
        });
    }
};
