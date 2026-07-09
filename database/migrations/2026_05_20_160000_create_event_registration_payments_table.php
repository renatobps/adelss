<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registration_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->string('idempotency_key', 80)->unique();
            $table->string('external_payment_id', 64)->nullable()->index();
            $table->string('external_reference', 120)->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->string('status_detail', 128)->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 8)->default('BRL');
            $table->string('payer_email')->nullable();
            $table->string('payer_document', 32)->nullable();
            $table->longText('qr_code_base64')->nullable();
            $table->text('qr_code_text')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registration_payments');
    }
};
