<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('financial_transactions', 'external_ref')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->string('external_ref', 64)->nullable()->after('document_number');
                $table->unique('external_ref', 'fin_tx_external_ref_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('financial_transactions', 'external_ref')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropUnique('fin_tx_external_ref_unique');
                $table->dropColumn('external_ref');
            });
        }
    }
};
