<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('monthly_culto_schedules')) {
            return;
        }

        if (! Schema::hasColumn('monthly_culto_schedules', 'guest_preletor_name')) {
            Schema::table('monthly_culto_schedules', function (Blueprint $table) {
                $table->string('guest_preletor_name', 150)->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('monthly_culto_schedules') && Schema::hasColumn('monthly_culto_schedules', 'guest_preletor_name')) {
            Schema::table('monthly_culto_schedules', function (Blueprint $table) {
                $table->dropColumn('guest_preletor_name');
            });
        }
    }
};
