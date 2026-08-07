<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('reminder_message')->nullable()->after('pix_recipient');
            $table->date('reminder_send_date')->nullable()->after('reminder_message');
            $table->dateTime('reminder_sent_at')->nullable()->after('reminder_send_date');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['reminder_message', 'reminder_send_date', 'reminder_sent_at']);
        });
    }
};
