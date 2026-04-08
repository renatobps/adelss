<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $indexExists = !empty(DB::select(
            "SHOW INDEX FROM discipleship_indicator_values WHERE Key_name = 'idx_disc_member_date'"
        ));

        if (!$indexExists) {
            Schema::table('discipleship_indicator_values', function (Blueprint $table) {
                $table->index(['discipleship_member_id', 'data_registro'], 'idx_disc_member_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexExists = !empty(DB::select(
            "SHOW INDEX FROM discipleship_indicator_values WHERE Key_name = 'idx_disc_member_date'"
        ));

        if ($indexExists) {
            Schema::table('discipleship_indicator_values', function (Blueprint $table) {
                $table->dropIndex('idx_disc_member_date');
            });
        }
    }
};
