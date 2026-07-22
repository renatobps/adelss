<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scheduled_posts')) {
            return;
        }

        Schema::table('scheduled_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('scheduled_posts', 'event_name')) {
                // Texto livre por enquanto. Evolução futura: event_id (FK opcional para events da Agenda).
                $table->string('event_name')->nullable()->after('caption');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('scheduled_posts')) {
            return;
        }

        Schema::table('scheduled_posts', function (Blueprint $table) {
            if (Schema::hasColumn('scheduled_posts', 'event_name')) {
                $table->dropColumn('event_name');
            }
        });
    }
};
