<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = DB::table('home_page_settings')->orderBy('id')->first();

        if (!$settings) {
            return;
        }

        $updates = [];

        if (empty($settings->hero_eyebrow)) {
            $updates['hero_eyebrow'] = 'Igreja Cristã · ADEL São Sebastião';
        }

        if (empty($settings->hero_title) || $settings->hero_title === 'Bem-vindo à ADEL São Sebastião') {
            $updates['hero_title'] = 'Jesus Cristo é o nosso único *fundamento*.';
        }

        $oldSubtitle = 'Uma igreja viva, acolhedora e comprometida com o Reino de Deus. Venha fazer parte da nossa família.';
        if (empty($settings->hero_subtitle) || $settings->hero_subtitle === $oldSubtitle) {
            $updates['hero_subtitle'] = 'Existimos para levar o evangelho a todos e acolher quem precisa, apontando o único caminho de esperança e salvação em Cristo Jesus.';
        }

        if (empty($settings->hero_cta_secondary_label) || $settings->hero_cta_secondary_label === 'Assistir ao vivo') {
            $updates['hero_cta_secondary_label'] = 'Assista uma mensagem';
        }

        if (empty($settings->about_eyebrow)) {
            $updates['about_eyebrow'] = 'Nossa Missão';
        }

        if (empty($settings->about_title)) {
            $updates['about_title'] = 'Existimos para anunciar a Cristo e acolher quem precisa.';
        }

        if (empty($settings->about_text)) {
            $updates['about_text'] = 'Recebemos gente de toda história — famílias, solteiros, quem está conhecendo Jesus agora e quem caminha com Ele há anos. A todos anunciamos o mesmo evangelho: Jesus Cristo morreu e ressuscitou para nos salvar. É essa esperança que oferecemos a quem chega até nós, através dos Pequenos Grupos, dos cultos de domingo e de cada encontro pelo caminho.';
        }

        if (empty($settings->about_highlight_word)) {
            $updates['about_highlight_word'] = 'evangelho';
        }

        if (empty($settings->about_link_label)) {
            $updates['about_link_label'] = 'Conheça nossa história';
        }

        if (empty($settings->about_bible_reference)) {
            $updates['about_bible_reference'] = 'Mateus 28:19';
        }

        if (!empty($updates)) {
            $updates['updated_at'] = now();
            DB::table('home_page_settings')->where('id', $settings->id)->update($updates);
        }
    }

    public function down(): void
    {
        // Backfill reverso não é aplicado para evitar perda de conteúdo customizado.
    }
};
