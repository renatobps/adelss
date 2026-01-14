<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['receita', 'despesa']); // Receita ou Despesa
            $table->date('transaction_date'); // Data da transação
            $table->string('description'); // Descrição
            $table->decimal('amount', 10, 2); // Valor
            $table->boolean('is_paid')->default(false); // Pago/Recebido (para receitas: recebido, para despesas: pago)
            $table->date('due_date')->nullable(); // Data de vencimento (para a receber/a pagar)
            $table->enum('status', ['pago', 'a_receber', 'a_pagar', 'recebido'])->default('pago'); // Status
            
            // Relacionamentos
            $table->foreignId('member_id')->nullable()->constrained('members')->onDelete('set null'); // Para receitas (quem doou)
            $table->string('received_from_other')->nullable(); // Para receitas quando "outros" é selecionado
            $table->foreignId('contact_id')->nullable()->constrained('financial_contacts')->onDelete('set null'); // Para despesas (pago à)
            $table->foreignId('category_id')->nullable()->constrained('financial_categories')->onDelete('set null');
            $table->foreignId('account_id')->nullable()->constrained('financial_accounts')->onDelete('set null');
            $table->foreignId('cost_center_id')->nullable()->constrained('financial_cost_centers')->onDelete('set null');
            
            // Informações adicionais
            $table->enum('payment_type', ['unico', 'parcelado'])->default('unico'); // Tipo de pagamento
            $table->string('document_number')->nullable(); // Doc nº
            $table->text('notes')->nullable(); // Anotações
            $table->date('competence_date')->nullable(); // Competência (período fiscal)
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('transaction_date');
            $table->index('status');
            $table->index('category_id');
            $table->index('account_id');
        });

        // Tabela para anexos de transações
        Schema::create('financial_transaction_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('financial_transactions')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable(); // image, pdf, etc
            $table->integer('file_size')->nullable(); // em bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transaction_attachments');
        Schema::dropIfExists('financial_transactions');
    }
};
