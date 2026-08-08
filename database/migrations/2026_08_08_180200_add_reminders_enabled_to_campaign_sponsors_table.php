<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_sponsors', function (Blueprint $table) {
            // Opt-out individual: a pessoa pediu para não ser cobrada por mensagem
            // ou a liderança prefere tratar o caso pessoalmente.
            $table->boolean('reminders_enabled')->default(true)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_sponsors', function (Blueprint $table) {
            $table->dropColumn('reminders_enabled');
        });
    }
};
