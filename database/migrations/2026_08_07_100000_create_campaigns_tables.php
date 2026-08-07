<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->decimal('goal_amount', 10, 2)->nullable();
            $table->decimal('installment_amount', 10, 2);
            $table->unsignedInteger('installments_count')->default(1);
            $table->date('first_due_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('receipt_message')->nullable();
            $table->string('status')->default('ativa'); // ativa | encerrada | cancelada
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('campaign_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('campaign_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_sponsor_id')->constrained('campaign_sponsors')->cascadeOnDelete();
            $table->unsignedInteger('installment_number');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->string('status')->default('pendente'); // pendente | pago | cancelado
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // dinheiro | pix | cartao | outro
            $table->string('receipt_number')->nullable();
            $table->dateTime('receipt_sent_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->dateTime('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users');
            $table->date('reminder_sent_on')->nullable(); // controle de lembrete diário
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_installments');
        Schema::dropIfExists('campaign_sponsors');
        Schema::dropIfExists('campaigns');
    }
};
