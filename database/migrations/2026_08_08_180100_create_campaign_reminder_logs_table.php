<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_sponsor_id')->constrained()->cascadeOnDelete();
            // Parcela mais antiga em atraso no momento do envio: é ela que
            // ancora a contagem de "máximo de lembretes por parcela".
            $table->foreignId('campaign_installment_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 20);           // atraso | cortesia | teste
            $table->string('template_used', 20)->nullable();
            $table->string('status', 20);         // enviado | falhou | pulado
            $table->string('trigger', 20)->default('automatico'); // automatico | manual
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->text('reason')->nullable();
            $table->text('message')->nullable();
            $table->unsignedSmallInteger('overdue_count')->default(0);
            $table->decimal('overdue_amount', 10, 2)->default(0);
            $table->unsignedSmallInteger('days_overdue')->default(0);
            $table->boolean('pdf_attached')->default(false);
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['campaign_sponsor_id', 'status', 'sent_at']);
            $table->index(['campaign_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_reminder_logs');
    }
};
