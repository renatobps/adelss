<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_transactions')) {
            return;
        }

        if (! Schema::hasColumn('financial_transactions', 'culto_id')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->foreignId('culto_id')->nullable()->constrained('events')->nullOnDelete();
            });

            return;
        }

        Schema::table('financial_transactions', function (Blueprint $table) {
            try {
                $table->dropForeign(['culto_id']);
            } catch (Throwable $e) {
                // SQLite / FK já apontando para events
            }
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreign('culto_id')->references('id')->on('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        //
    }
};
