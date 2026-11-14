<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventScheduleItem;
use App\Models\EventSpeaker;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class EventLandingTemplateTest extends TestCase
{
    private function renderLanding(string $view, array $attributes = []): string
    {
        $event = new Event(array_merge([
            'title' => 'FILHOS Teste', 'public_slug' => 'filhos-teste',
            'start_date' => '2026-11-28 08:00:00', 'end_date' => '2026-11-28 18:00:00',
            'status' => 'agendado', 'location' => 'Local de teste',
            'registration_enabled' => true, 'max_spots' => 50,
        ], $attributes));
        $event->setRelation('scheduleItems', collect([new EventScheduleItem([
            'day' => 1, 'title' => 'Mensagem de teste', 'time_hh' => 9, 'time_mm' => 30,
        ])]));
        $event->setRelation('speakers', collect([new EventSpeaker(['name' => 'Palestrante de teste'])]));
        return view($view, [
            'event' => $event, 'customFields' => collect(), 'mercadoPagoPublicKey' => '',
            'errors' => new ViewErrorBag(),
            'nonce' => 'test-nonce',
        ])->render();
    }

    public function test_filhos_uses_event_data_and_the_official_registration_form(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.filhos');
        $this->assertStringContainsString('Local de teste', $html);
        $this->assertStringContainsString('Mensagem de teste', $html);
        $this->assertStringContainsString('09:30', $html);
        $this->assertStringContainsString('Palestrante de teste', $html);
        $this->assertStringContainsString(route('events.public.register', 'filhos-teste'), $html);
        $this->assertSame(1, substr_count($html, 'id="event-registration-form"'));
        $this->assertStringNotContainsString('O canal de inscrição ainda será divulgado', $html);
    }

    public function test_standard_layout_keeps_the_shared_registration_form(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.show');
        $this->assertSame(1, substr_count($html, 'id="event-registration-form"'));
        $this->assertStringContainsString('Palestrante de teste', $html);
    }

    public function test_paid_filhos_keeps_payment_fields(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.filhos', ['is_paid' => true, 'price' => 75]);
        $this->assertStringContainsString('75,00', $html);
        $this->assertStringContainsString('name="payment_method"', $html);
        $this->assertStringContainsString('name="payer_document"', $html);
    }

    public function test_completed_events_do_not_offer_registration(): void
    {
        foreach (['show', 'filhos'] as $template) {
            $html = $this->renderLanding('agenda.eventos.public.'.$template, ['status' => 'concluido']);
            $this->assertStringNotContainsString('id="event-registration-form"', $html);
            $this->assertStringContainsString('Inscrições encerradas', $html);
        }
    }
    public function test_event_validation_preserves_the_selected_model(): void
    {
        $controller = new \App\Http\Controllers\Agenda\EventosController();
        $validate = new \ReflectionMethod($controller, 'validateEvent');
        $request = \Illuminate\Http\Request::create('/eventos', 'POST', [
            'title' => 'FILHOS', 'start_date' => '2026-11-28', 'landing_template' => 'filhos',
        ]);
        $data = $validate->invoke($controller, $request);
        $this->assertSame('filhos', $data['base']['landing_template']);
    }

    public function test_event_validation_rejects_unknown_models(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller = new \App\Http\Controllers\Agenda\EventosController();
        $validate = new \ReflectionMethod($controller, 'validateEvent');
        $request = \Illuminate\Http\Request::create('/eventos', 'POST', [
            'title' => 'FILHOS', 'start_date' => '2026-11-28', 'landing_template' => '../custom',
        ]);
        $validate->invoke($controller, $request);
    }

    public function test_custom_html_contains_the_official_form_inside_the_marked_area(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.custom', [
            'landing_html' => '<!doctype html><html><head><style>.brand{color:red}</style></head><body><h1>{{evento.titulo}}</h1><section id="participar"><div data-evento="inscricao"></div></section><div data-evento="palestrantes"></div><div data-evento="programacao"></div><script>window.demo=true</script></body></html>',
        ]);
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);
        $this->assertSame(1, $xpath->query('//*[@id="participar"]//form[@id="event-registration-form"]')->length);
        $this->assertStringNotContainsString('srcdoc=', $html);
        $this->assertStringNotContainsString('window.demo=true', $html);
        $this->assertStringContainsString('<h1>FILHOS Teste</h1>', $html);
        $this->assertStringContainsString('Palestrante de teste', $html);
        $this->assertStringContainsString('Mensagem de teste', $html);
        $this->assertStringContainsString('.brand{color:red}', $html);
        $this->assertSame(1, substr_count($html, 'id="event-registration-form"'));
        $this->assertStringContainsString('nonce="test-nonce"', $html);
    }

    public function test_html_upload_takes_precedence_over_pasted_code(): void
    {
        $controller = new \App\Http\Controllers\Agenda\EventosController();
        $request = \Illuminate\Http\Request::create('/eventos', 'POST', [
            'title' => 'Teste', 'start_date' => '2026-11-28', 'landing_template' => 'custom',
            'landing_html' => '<h1>Colado</h1>',
        ], [], ['landing_html_file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('pagina.html', '<h1>Upload</h1>')]);
        $data = (new \ReflectionMethod($controller, 'validateEvent'))->invoke($controller, $request);
        $this->assertSame('<h1>Upload</h1>', $data['base']['landing_html']);
        $this->assertSame('custom', $data['base']['landing_template']);
    }

    public function test_custom_model_requires_html(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller = new \App\Http\Controllers\Agenda\EventosController();
        $request = \Illuminate\Http\Request::create('/eventos', 'POST', [
            'title' => 'Teste', 'start_date' => '2026-11-28', 'landing_template' => 'custom',
        ]);
        (new \ReflectionMethod($controller, 'validateEvent'))->invoke($controller, $request);
    }

    public function test_existing_html_is_kept_when_no_replacement_is_sent(): void
    {
        $controller = new \App\Http\Controllers\Agenda\EventosController();
        $request = \Illuminate\Http\Request::create('/eventos', 'POST', [
            'title' => 'Teste', 'start_date' => '2026-11-28', 'landing_template' => 'custom',
        ]);
        $event = new Event(['landing_html' => '<h1>Existente</h1>']);
        $data = (new \ReflectionMethod($controller, 'validateEvent'))->invoke($controller, $request, $event);
        $this->assertSame('<h1>Existente</h1>', $data['base']['landing_html']);
    }

    public function test_non_html_upload_is_rejected(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller = new \App\Http\Controllers\Agenda\EventosController();
        $request = \Illuminate\Http\Request::create('/eventos', 'POST', [
            'title' => 'Teste', 'start_date' => '2026-11-28', 'landing_template' => 'custom',
        ], [], ['landing_html_file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('pagina.php', '<?php echo 1;')]);
        (new \ReflectionMethod($controller, 'validateEvent'))->invoke($controller, $request);
    }

    public function test_imported_code_is_never_evaluated_as_blade_or_php(): void
    {
        $event = new Event(['title' => '<script>alert(1)</script>', 'public_slug' => 'teste',
            'start_date' => '2026-11-28', 'landing_html' => '{{evento.titulo}} @php echo "executed"; @endphp <?php echo "executed"; ?>']);
        $event->setRelation('speakers', collect());
        $event->setRelation('scheduleItems', collect());
        $html = app(\App\Services\EventLandingHtmlRenderer::class)->render($event);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('@php echo "executed"; @endphp', $html);
        $this->assertStringNotContainsString('<?php', $html);
    }

    public function test_scalar_slots_and_legacy_markers_are_filled_from_event_data(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.custom', [
            'landing_html' => '<p data-evento="local">Antigo</p><span data-evento="horario"></span>{{inscricao}}',
        ]);
        $this->assertStringContainsString('<p data-evento="local">Local de teste</p>', $html);
        $this->assertStringContainsString('08:00 às 18:00', html_entity_decode($html));
        $this->assertSame(1, substr_count($html, 'id="event-registration-form"'));
    }

    public function test_multiple_registration_markers_do_not_duplicate_the_form(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.custom', [
            'landing_html' => '<div data-evento="inscricao"></div>{{inscricao}}',
        ]);
        $this->assertSame(1, substr_count($html, 'id="event-registration-form"'));
        $this->assertStringContainsString('Ir para inscrição', html_entity_decode($html));
    }

    public function test_active_imported_content_is_removed_before_trusted_components_are_inserted(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.custom', [
            'landing_html' => '<meta http-equiv="refresh" content="0;url=https://example.com"><base href="https://example.com"><div onclick="alert(1)">Hello</div><a href="jav&#x61;script:alert(1)">Click</a><svg><foreignObject><iframe srcdoc="bad"></iframe></foreignObject><path onload="alert(1)" d="M0 0"></path></svg><form action="https://example.com"><input name="secret"></form><script nonce="forged">alert(1)</script>',
        ]);
        foreach (['onclick=', 'onload=', 'javascript:', 'srcdoc=', '<base', '<iframe', 'nonce="forged"', 'name="secret"', 'http-equiv='] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html);
        }
        $this->assertSame(1, substr_count($html, 'id="event-registration-form"'));
    }

    public function test_prepared_filhos_layout_has_static_period_tabs_and_live_registration(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.custom', [
            'landing_html' => file_get_contents(public_path('events/templates/filhos/filhos-integrado.html')),
            'start_date' => '2027-12-01 10:00:00',
        ]);
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);
        $this->assertSame(1, $xpath->query('//*[@id="participar"]//form[@id="event-registration-form"]')->length);
        $this->assertStringContainsString('Palestrante de teste', $html);
        $this->assertStringContainsString('Boas-vindas à mesa', html_entity_decode($html));
        $this->assertStringContainsString('Retorno e dinâmica de identidade', html_entity_decode($html));
        $this->assertSame(6, $xpath->query('//*[@id="painel-manha"]//div[contains(concat(" ", normalize-space(@class), " "), " schedule-row ")]')->length);
        $this->assertSame(6, $xpath->query('//*[@id="painel-tarde"]//div[contains(concat(" ", normalize-space(@class), " "), " schedule-row ")]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="painel-tarde"][@hidden]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="tab-manha"][@aria-selected="true"]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-evento="programacao"]')->length);
        $this->assertStringContainsString('Local de teste', $html);
        $this->assertStringNotContainsString('28 de novembro de 2026', $html);
        $this->assertStringNotContainsString('O canal de inscrição ainda será divulgado', $html);
    }

    public function test_custom_paid_event_keeps_sdk_payment_fields_and_custom_questions(): void
    {
        $event = new Event(['title' => 'Pago', 'public_slug' => 'pago', 'start_date' => '2026-11-28',
            'status' => 'agendado', 'registration_enabled' => true, 'is_paid' => true, 'price' => 50,
            'landing_html' => '<section id="participar"><div data-evento="inscricao"></div></section>']);
        $event->setRelation('speakers', collect());
        $event->setRelation('scheduleItems', collect());
        $field = new \App\Models\EventRegistrationField(['name' => 'Sua igreja', 'field_type' => 'text', 'required' => true]);
        $field->id = 17;
        $html = app(\App\Services\EventLandingHtmlRenderer::class)->render($event, [
            'mercadoPagoPublicKey' => 'test-key', 'customFields' => collect([$field]), 'nonce' => 'trusted-nonce',
        ]);
        $this->assertStringContainsString('Sua igreja', $html);
        $this->assertStringContainsString('name="custom[17]"', $html);
        $this->assertStringContainsString('name="payer_document"', $html);
        $this->assertStringContainsString('src="https://sdk.mercadopago.com/js/v2"', $html);
        $this->assertStringContainsString('nonce="trusted-nonce"', $html);
    }

    public function test_presentation_banner_and_location_photos_use_the_event(): void
    {
        $html = $this->renderLanding('agenda.eventos.public.custom', [
            'landing_html' => '<div data-evento="sobre"></div><div data-evento="banner"></div><div data-evento="fotos-local"></div>',
            'about_html' => '<p>Apresentação do cadastro</p><script>alert(1)</script>',
            'banner_image' => 'https://example.com/banner.png',
            'location_photos' => ['https://example.com/local.png'],
        ]);
        $this->assertStringContainsString('Apresentação do cadastro', html_entity_decode($html));
        $this->assertStringContainsString('src="https://example.com/banner.png"', $html);
        $this->assertStringContainsString('src="https://example.com/local.png"', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_import_editor_offers_upload_markers_and_the_prepared_example(): void
    {
        $html = view('agenda.eventos.partials.form', [
            'event' => null, 'categories' => collect(), 'errors' => new ViewErrorBag(),
        ])->render();
        $this->assertStringContainsString('Carregar FILHOS integrado', $html);
        $this->assertSame(1, substr_count($html, 'id="landing-html-file"'));
        $this->assertStringContainsString('&lt;div data-evento="inscricao"&gt;', $html);
        $this->assertStringContainsString('{{inscricao}}', $html);
    }

}
