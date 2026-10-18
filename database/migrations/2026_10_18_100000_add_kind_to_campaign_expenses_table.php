<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue gasto e receita extra na mesma tabela de lançamentos do evento.
 * Lançamentos existentes continuam como gasto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_expenses', function (Blueprint $table) {
            $table->string('kind', 16)->default('gasto')->after('campaign_id');
            $table->index(['campaign_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('campaign_expenses', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'kind']);
            $table->dropColumn('kind');
        });
    }
};
