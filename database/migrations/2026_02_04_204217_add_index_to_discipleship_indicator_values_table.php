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
        Schema::table('discipleship_indicator_values', function (Blueprint $table) {
            $table->index(['discipleship_member_id', 'data_registro'], 'idx_disc_member_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipleship_indicator_values', function (Blueprint $table) {
            $table->dropIndex('idx_disc_member_date');
        });
    }
};
