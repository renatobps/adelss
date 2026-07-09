<?php

use App\Models\HomePageSetting;
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

        if ($settings->next_steps_title === null) {
            $updates['next_steps_title'] = 'A mudança começa com um passo.';
        }

        if ($settings->next_steps_intro === null) {
            $updates['next_steps_intro'] = 'Não importa em que ponto da caminhada você está, sempre tem um próximo passo. Escolha por onde começar.';
        }

        if (empty($settings->next_steps_eyebrow)) {
            $updates['next_steps_eyebrow'] = 'Próximos passos';
        }

        if (empty($settings->next_steps_cards)) {
            $updates['next_steps_cards'] = json_encode(HomePageSetting::defaultNextStepsCards());
        }

        if (!empty($updates)) {
            $updates['updated_at'] = now();
            DB::table('home_page_settings')->where('id', $settings->id)->update($updates);
        }
    }

    public function down(): void
    {
        //
    }
};
