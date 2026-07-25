<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scheduled_post_destinations')) {
            return;
        }

        Schema::table('scheduled_post_destinations', function (Blueprint $table) {
            if (!Schema::hasColumn('scheduled_post_destinations', 'channel')) {
                $table->string('channel')->default('instagram')->after('scheduled_post_id');
            }
            if (!Schema::hasColumn('scheduled_post_destinations', 'target_id')) {
                $table->string('target_id')->nullable()->after('destination');
            }
            if (!Schema::hasColumn('scheduled_post_destinations', 'target_name')) {
                $table->string('target_name')->nullable()->after('target_id');
            }
        });

        DB::table('scheduled_post_destinations')
            ->where(function ($q) {
                $q->whereNull('channel')->orWhere('channel', '');
            })
            ->update(['channel' => 'instagram']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('scheduled_post_destinations')) {
            return;
        }

        Schema::table('scheduled_post_destinations', function (Blueprint $table) {
            foreach (['target_name', 'target_id', 'channel'] as $column) {
                if (Schema::hasColumn('scheduled_post_destinations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
