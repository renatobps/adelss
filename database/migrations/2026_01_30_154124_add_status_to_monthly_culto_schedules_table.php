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
        Schema::table('monthly_culto_schedules', function (Blueprint $table) {
            $table->enum('status', ['rascunho', 'publicada', 'cancelada', 'concluido'])->default('rascunho')->after('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_culto_schedules', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
