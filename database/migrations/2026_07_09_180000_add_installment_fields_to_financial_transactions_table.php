<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('installments_count')->nullable()->after('payment_type');
            $table->unsignedTinyInteger('installment_number')->nullable()->after('installments_count');
            $table->foreignId('parent_transaction_id')
                ->nullable()
                ->after('installment_number')
                ->constrained('financial_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_transaction_id');
            $table->dropColumn(['installments_count', 'installment_number']);
        });
    }
};
