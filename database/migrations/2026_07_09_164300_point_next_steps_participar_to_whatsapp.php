<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = DB::table('home_page_settings')->orderBy('id')->first();

        if (!$settings || empty($settings->next_steps_cards)) {
            return;
        }

        $cards = json_decode($settings->next_steps_cards, true);

        if (!is_array($cards) || !isset($cards[0])) {
            return;
        }

        $linkUrl = trim((string) ($cards[0]['link_url'] ?? ''));

        if (in_array($linkUrl, ['#contato', ''], true)) {
            $cards[0]['link_url'] = '@whatsapp';
            DB::table('home_page_settings')
                ->where('id', $settings->id)
                ->update([
                    'next_steps_cards' => json_encode($cards),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        //
    }
};
