<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            $table->boolean('show_next_steps_section')->default(true)->after('pgi_card_url');
            $table->string('next_steps_eyebrow')->nullable()->default('Próximos passos')->after('show_next_steps_section');
            $table->string('next_steps_title')->nullable()->after('next_steps_eyebrow');
            $table->text('next_steps_intro')->nullable()->after('next_steps_title');
            $table->json('next_steps_cards')->nullable()->after('next_steps_intro');
        });
    }

    public function down(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_next_steps_section',
                'next_steps_eyebrow',
                'next_steps_title',
                'next_steps_intro',
                'next_steps_cards',
            ]);
        });
    }
};
