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
        Schema::table('monthly_culto_service_areas', function (Blueprint $table) {
            $table->enum('status', ['pendente', 'confirmado', 'cancelado'])->default('pendente')->after('volunteer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_culto_service_areas', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
