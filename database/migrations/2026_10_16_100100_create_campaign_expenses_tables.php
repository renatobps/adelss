<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gastos dos eventos (módulo Gestor de Eventos): cada despesa pertence a uma
 * campanha e exige ao menos um comprovante anexado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('description');
            $table->string('category')->default('outros'); // alimentacao | aluguel | transporte | decoracao | material | divulgacao | servicos | outros
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('supplier')->nullable();
            $table->string('payment_method')->nullable(); // dinheiro | pix | cartao | outro
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['campaign_id', 'expense_date']);
        });

        Schema::create('campaign_expense_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_expense_id')->constrained('campaign_expenses')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_expense_attachments');
        Schema::dropIfExists('campaign_expenses');
    }
};
