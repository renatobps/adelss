<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_cta_primary_label')->default('Planeje sua visita');
            $table->string('hero_cta_primary_url')->nullable();
            $table->string('hero_cta_secondary_label')->default('Assistir ao vivo');
            $table->string('hero_cta_secondary_url')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('service_times_text')->nullable();
            $table->string('address_text')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->boolean('show_watch_section')->default(false);
            $table->string('watch_video_url')->nullable();
            $table->boolean('show_events_section')->default(true);
            $table->unsignedTinyInteger('events_count')->default(3);
            $table->boolean('pgi_card_show')->default(true);
            $table->string('pgi_card_title')->default('Pequenos Grupos');
            $table->text('pgi_card_description')->nullable();
            $table->string('pgi_card_url')->nullable();
            $table->string('social_facebook')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_youtube')->nullable();
            $table->text('footer_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_page_settings');
    }
};
