<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financial_automations')) {
            return;
        }

        Schema::create('financial_automations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name');
            $table->boolean('enabled')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        $defaultMessage = implode("\n", [
            '*{igreja}*',
            '',
            'Olá, {nome}! 🙏',
            '',
            'Recebemos sua contribuição de *R$ {valor}* ({tipo}).',
            '',
            'Muito obrigado pela sua fidelidade e generosidade!',
            'Deus continue abençoando você e sua família. ✨',
        ]);

        DB::table('financial_automations')->insert([
            'key' => 'contribution_thanks',
            'name' => 'Agradecimento por contribuição',
            'enabled' => (bool) config('financial.whatsapp.dizimo_receipt_enabled', true),
            'settings' => json_encode([
                'min_amount' => 0,
                'daily_limit' => 30,
                'window_start' => '08:00',
                'window_end' => '20:00',
                'delay_minutes' => 0,
                'message_template' => $defaultMessage,
                'eligible_category_ids' => [],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_automations');
    }
};
