<?php

use App\Models\FinancialAutomation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('financial_automations')) {
            return;
        }

        $exists = DB::table('financial_automations')
            ->where('key', FinancialAutomation::KEY_TREASURERS)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('financial_automations')->insert([
            'key' => FinancialAutomation::KEY_TREASURERS,
            'name' => 'Tesoureiros (destinatários)',
            'enabled' => true,
            'settings' => json_encode(['recipients' => []], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('financial_automations')) {
            return;
        }

        DB::table('financial_automations')
            ->where('key', FinancialAutomation::KEY_TREASURERS)
            ->delete();
    }
};
