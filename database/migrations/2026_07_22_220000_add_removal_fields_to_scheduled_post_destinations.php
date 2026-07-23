<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scheduled_post_destinations')) {
            return;
        }

        Schema::table('scheduled_post_destinations', function (Blueprint $table) {
            if (!Schema::hasColumn('scheduled_post_destinations', 'remove_after_days')) {
                $table->unsignedInteger('remove_after_days')->nullable()->after('published_at');
            }
            if (!Schema::hasColumn('scheduled_post_destinations', 'remove_at')) {
                $table->dateTime('remove_at')->nullable()->after('remove_after_days');
            }
            if (!Schema::hasColumn('scheduled_post_destinations', 'removed_at')) {
                $table->dateTime('removed_at')->nullable()->after('remove_at');
            }
            if (!Schema::hasColumn('scheduled_post_destinations', 'removal_status')) {
                $table->string('removal_status')->default('nao_agendado')->after('removed_at');
                $table->index(['removal_status', 'remove_at'], 'spd_removal_due_idx');
            }
            if (!Schema::hasColumn('scheduled_post_destinations', 'removal_error')) {
                $table->text('removal_error')->nullable()->after('removal_status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('scheduled_post_destinations')) {
            return;
        }

        Schema::table('scheduled_post_destinations', function (Blueprint $table) {
            if (Schema::hasColumn('scheduled_post_destinations', 'removal_status')) {
                $table->dropIndex('spd_removal_due_idx');
            }

            $cols = array_values(array_filter(
                ['removal_error', 'removal_status', 'removed_at', 'remove_at', 'remove_after_days'],
                fn ($col) => Schema::hasColumn('scheduled_post_destinations', $col)
            ));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
