<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticatedRoutesTest extends TestCase
{
    private function createHomePageTables(): void
    {
        if (!Schema::hasTable('home_page_settings')) {
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
                $table->boolean('show_watch_section')->default(true);
                $table->string('watch_video_url')->nullable();
                $table->boolean('show_events_section')->default(true);
                $table->unsignedTinyInteger('events_count')->default(3);
                $table->boolean('pgi_card_show')->default(true);
                $table->string('pgi_card_title')->default('Pequenos Grupos');
                $table->text('pgi_card_description')->nullable();
                $table->string('pgi_card_image')->nullable();
                $table->string('pgi_card_url')->nullable();
                $table->string('social_facebook')->nullable();
                $table->string('social_instagram')->nullable();
                $table->string('social_youtube')->nullable();
                $table->text('footer_text')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('template')->nullable();
                $table->string('icon')->nullable();
                $table->string('color')->nullable();
                $table->string('status')->default('ativo');
                $table->text('description')->nullable();
                $table->boolean('show_on_homepage')->default(false);
                $table->unsignedSmallInteger('homepage_order')->nullable();
                $table->string('homepage_url')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_categories')) {
            Schema::create('event_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('color')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->dateTime('start_date');
                $table->dateTime('end_date')->nullable();
                $table->boolean('all_day')->default(false);
                $table->string('visibility')->default('private');
                $table->string('location')->nullable();
                $table->string('public_slug')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->timestamps();
            });
        }
    }
    /**
     * @return array<string, array{0: string}>
     */
    public static function protectedRoutesProvider(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'members' => ['/members'],
            'financial summary' => ['/financial/summary'],
            'financial transactions' => ['/financial/transactions'],
            'financial correction' => ['/financial/correction'],
            'pgis' => ['/pgis'],
            'agenda calendario' => ['/agenda/calendario'],
            'moriah ministerio' => ['/moriah/ministerio'],
            'notificacoes painel' => ['/notificacoes/painel'],
            'discipleship cycles' => ['/discipleship/cycles'],
            'ensino estudos' => ['/ensino/estudos'],
            'servico voluntarios' => ['/servico/voluntarios/cadastro'],
            'rifas' => ['/rifas'],
            'permissoes' => ['/permissoes'],
        ];
    }

    /**
     * @dataProvider protectedRoutesProvider
     */
    public function test_usuario_deslogado_e_redirecionado_para_login(string $url): void
    {
        $response = $this->get($url);

        $response->assertRedirect(route('login'));
    }

    public function test_login_permite_acesso_anonimo(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_home_permite_acesso_anonimo(): void
    {
        $this->createHomePageTables();

        $response = $this->get(route('home'));

        $response->assertOk();
    }
}
