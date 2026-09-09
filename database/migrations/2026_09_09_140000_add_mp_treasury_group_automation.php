<?php

use App\Models\FinancialAutomation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financial_notification_logs')) {
            $driver = Schema::getConnection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE financial_notification_logs MODIFY phone VARCHAR(80) NULL');
            }
        }

        if (! Schema::hasTable('financial_automations')) {
            return;
        }

        $exists = DB::table('financial_automations')
            ->where('key', FinancialAutomation::KEY_MP_TREASURY_GROUP)
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();
        DB::table('financial_automations')->insert([
            'key' => FinancialAutomation::KEY_MP_TREASURY_GROUP,
            'name' => 'Grupo WhatsApp da tesouraria (Mercado Pago)',
            'enabled' => true,
            'settings' => json_encode(
                FinancialAutomation::defaultSettingsFor(FinancialAutomation::KEY_MP_TREASURY_GROUP),
                JSON_UNESCAPED_UNICODE
            ),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('financial_automations')) {
            DB::table('financial_automations')
                ->where('key', FinancialAutomation::KEY_MP_TREASURY_GROUP)
                ->delete();
        }
    }
};
