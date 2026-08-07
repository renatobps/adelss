<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('registration_number', 30)->nullable()->unique()->after('status');
            $table->string('check_in_token', 40)->nullable()->unique()->after('registration_number');
            $table->dateTime('receipt_sent_at')->nullable()->after('check_in_token');
            $table->dateTime('checked_in_at')->nullable()->after('receipt_sent_at');
            $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_in_by');
            $table->dropColumn(['registration_number', 'check_in_token', 'receipt_sent_at', 'checked_in_at']);
        });
    }
};
