<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mercado_pago_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->string('public_key');
            $table->text('access_token');
            $table->text('webhook_secret');
            $table->string('statement_descriptor', 13)->default('ADELSS');
            $table->string('currency', 3)->default('BRL');
            $table->string('notification_url', 1000);
            $table->boolean('sandbox')->default(false);
            $table->string('merchant_id', 100);
            $table->string('merchant_name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('previous_id')->nullable()->constrained('mercado_pago_accounts')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('mercado_pago_account_id')->nullable()->constrained('mercado_pago_accounts')->restrictOnDelete();
        });
        Schema::table('event_registration_payments', function (Blueprint $table) {
            $table->foreignId('mercado_pago_account_id')->nullable()->constrained('mercado_pago_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registration_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('mercado_pago_account_id'));
        Schema::table('events', fn (Blueprint $table) => $table->dropConstrainedForeignId('mercado_pago_account_id'));
        Schema::dropIfExists('mercado_pago_accounts');
    }
};
