<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('financial_fixed_expenses')) {
            Schema::create('financial_fixed_expenses', function (Blueprint $table) {
                $table->id();
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->unsignedTinyInteger('due_day');
                $table->foreignId('category_id')->nullable()->constrained('financial_categories')->nullOnDelete();
                $table->foreignId('account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
                $table->foreignId('cost_center_id')->nullable()->constrained('financial_cost_centers')->nullOnDelete();
                $table->foreignId('contact_id')->nullable()->constrained('financial_contacts')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('amount_variable')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'due_day']);
            });
        }

        if (Schema::hasTable('financial_transactions') && !Schema::hasColumn('financial_transactions', 'fixed_expense_id')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->foreignId('fixed_expense_id')
                    ->nullable()
                    ->after('parent_transaction_id')
                    ->constrained('financial_fixed_expenses')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('financial_transactions') && Schema::hasColumn('financial_transactions', 'fixed_expense_id')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('fixed_expense_id');
            });
        }

        Schema::dropIfExists('financial_fixed_expenses');
    }
};
