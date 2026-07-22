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

        $now = now();

        $rows = [
            [
                'key' => FinancialAutomation::KEY_DUE_REMINDER,
                'name' => 'Lembrete de despesas a vencer',
                'enabled' => (bool) config('financial.whatsapp.due_reminder_enabled', false),
                'settings' => json_encode(
                    FinancialAutomation::defaultSettingsFor(FinancialAutomation::KEY_DUE_REMINDER),
                    JSON_UNESCAPED_UNICODE
                ),
            ],
            [
                'key' => FinancialAutomation::KEY_SMART_SUMMARY,
                'name' => 'Resumo financeiro inteligente',
                'enabled' => false,
                'settings' => json_encode(
                    FinancialAutomation::defaultSettingsFor(FinancialAutomation::KEY_SMART_SUMMARY),
                    JSON_UNESCAPED_UNICODE
                ),
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('financial_automations')->where('key', $row['key'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('financial_automations')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('financial_automations')) {
            return;
        }

        DB::table('financial_automations')
            ->whereIn('key', [
                FinancialAutomation::KEY_DUE_REMINDER,
                FinancialAutomation::KEY_SMART_SUMMARY,
            ])
            ->delete();
    }
};
