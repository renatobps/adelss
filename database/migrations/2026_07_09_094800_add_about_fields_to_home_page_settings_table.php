<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            $table->string('hero_eyebrow')->nullable()->after('hero_title');
            $table->string('about_eyebrow')->nullable()->default('Nossa Missão')->after('hero_cta_secondary_url');
            $table->string('about_title')->nullable()->after('about_eyebrow');
            $table->text('about_text')->nullable()->after('about_title');
            $table->string('about_highlight_word')->nullable()->after('about_text');
            $table->string('about_link_label')->nullable()->default('Conheça nossa história')->after('about_highlight_word');
            $table->string('about_link_url')->nullable()->after('about_link_label');
            $table->string('about_bible_reference')->nullable()->after('about_link_url');
        });
    }

    public function down(): void
    {
        Schema::table('home_page_settings', function (Blueprint $table) {
            $table->dropColumn([
                'hero_eyebrow',
                'about_eyebrow',
                'about_title',
                'about_text',
                'about_highlight_word',
                'about_link_label',
                'about_link_url',
                'about_bible_reference',
            ]);
        });
    }
};
