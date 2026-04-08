<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('responsible_name')->nullable()->after('notify_emails');
            $table->string('responsible_phone', 20)->nullable()->after('responsible_name');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['responsible_name', 'responsible_phone']);
        });
    }
};
