<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_schedule_items', function (Blueprint $table) {
            $table->string('responsible_name')->nullable()->after('detail');
            $table->string('responsible_photo_path')->nullable()->after('responsible_name');
        });
    }

    public function down(): void
    {
        Schema::table('event_schedule_items', function (Blueprint $table) {
            $table->dropColumn([
                'responsible_name',
                'responsible_photo_path',
            ]);
        });
    }
};
