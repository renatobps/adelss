<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financial_transactions') && ! Schema::hasColumn('financial_transactions', 'culto_id')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->foreignId('culto_id')->nullable()->after('category_id')->constrained('events')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('cash_closings')) {
            Schema::create('cash_closings', function (Blueprint $table) {
                $table->id();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('total_receitas', 12, 2)->default(0);
                $table->decimal('total_despesas', 12, 2)->default(0);
                $table->decimal('saldo', 12, 2)->default(0);
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();

                $table->index(['period_start', 'period_end']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('financial_transactions') && Schema::hasColumn('financial_transactions', 'culto_id')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('culto_id');
            });
        }

        Schema::dropIfExists('cash_closings');
    }
};
