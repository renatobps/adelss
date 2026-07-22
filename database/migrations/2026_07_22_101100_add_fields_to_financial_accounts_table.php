<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_accounts', function (Blueprint $table) {
            $table->string('type', 32)->default('caixa')->after('name');
            $table->string('bank_name')->nullable()->after('type');
            $table->decimal('initial_balance', 15, 2)->default(0)->after('bank_name');
            $table->string('color', 16)->default('#ef4444')->after('initial_balance');
            $table->boolean('is_active')->default(true)->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('financial_accounts', function (Blueprint $table) {
            $table->dropColumn(['type', 'bank_name', 'initial_balance', 'color', 'is_active']);
        });
    }
};
