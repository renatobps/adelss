<?php

namespace App\Services;

use App\Models\Event;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\ViewErrorBag;

class EventLandingHtmlRenderer
{
    public function render(Event $event, array $data = []): string
    {
        $nonce = $data['nonce'] ?? bin2hex(random_bytes(24));
        $data = array_merge([
            'event' => $event,
            'customFields' => collect(),
            'mercadoPagoPublicKey' => (string) config('mercadopago.public_key', ''),
            'errors' => new ViewErrorBag(),
            'spotsLeft' => $event->max_spots > 0 ? max(0, $event->max_spots - (int) ($event->registrations_em_vaga_count ?? 0)) : null,
            'pixPayment' => session('pix_payment'),
            'eventPriceJs' => number_format((float) ($event->price ?? 0), 2, '.', ''),
            'useConfiguredPayer' => !empty(config('mercadopago.test_payer_email')) && !empty(config('mercadopago.test_payer_document')),
        ], $data);
        $scalars = [
            'titulo' => $event->title,
            'tema' => $event->subtitle,
            'descricao' => $event->description,
            'data' => $event->start_date->format('d/m/Y'),
            'data-longa' => $event->start_date->translatedFormat('d \d\e F \d\e Y'),
            'horario' => $event->all_day ? 'Dia inteiro' : $event->start_date->format('H:i').($event->end_date ? ' às '.$event->end_date->format('H:i') : ''),
            'local' => $event->location ?: 'Local a confirmar',
            'preco' => $event->is_paid ? 'R$ '.number_format((float) $event->price, 2, ',', '.') : 'Gratuito',
        ];
        $replacements = [];
        foreach ($scalars as $key => $value) {
            $replacements['{{evento.'.$key.'}}'] = e($value);
        }
        foreach (['programacao', 'palestrantes', 'inscricao', 'sobre', 'fotos-local'] as $key) {
            $replacements['{{'.$key.'}}'] = '<div data-evento="'.$key.'"></div>';
        }
        // The file is parsed as HTML data. It is never compiled as Blade or PHP.
        $doc = $this->document(strtr((string) $event->landing_html, $replacements));
        $this->sanitize($doc);
        $xpath = new DOMXPath($doc);
        foreach ($scalars as $key => $value) {
            foreach ($xpath->query('//*[@data-evento="'.$key.'"]') as $node) {
                if ($value === null || $value === '') continue;
                $node->textContent = (string) $value;
            }
        }
        foreach (['programacao', 'palestrantes', 'sobre', 'fotos-local'] as $key) {
            foreach ($xpath->query('//*[@data-evento="'.$key.'"]') as $node) {
                if ($key === 'sobre' && !$event->about_html && !$event->description) continue;
                $this->fill($doc, $node, view('agenda.eventos.public.partials.imported-'.$key, $data)->render());
            }
        }
        foreach ($xpath->query('//*[@data-evento="banner"]') as $node) {
            if ($node->tagName === 'img' && $event->bannerImagePublicUrl()) {
                $node->setAttribute('src', $event->bannerImagePublicUrl());
                $node->setAttribute('alt', $event->title);
            } elseif ($event->bannerImagePublicUrl()) {
                $this->fill($doc, $node, '<img class="event-imported-banner" src="'.e($event->bannerImagePublicUrl()).'" alt="'.e($event->title).'">');
            }
        }
        $registration = view('agenda.eventos.public.partials.registration', $data)->render();
        $inserted = false;
        foreach ($xpath->query('//*[@data-evento="inscricao"]') as $node) {
            $this->fill($doc, $node, $inserted ? '<a href="#inscricao">Ir para inscrição</a>' : $registration);
            $inserted = true;
        }
        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $inserted) {
            $slot = $doc->createElement('div');
            $slot->setAttribute('data-evento', 'inscricao');
            $body->appendChild($slot);
            $this->fill($doc, $slot, $registration);
        }
        $head = $doc->getElementsByTagName('head')->item(0);
        $bootstrap = $doc->createElement('link');
        $bootstrap->setAttribute('rel', 'stylesheet');
        $bootstrap->setAttribute('href', asset('vendor/vendor/bootstrap/css/bootstrap.css'));
        $head->insertBefore($bootstrap, $head->firstChild);
        $style = $doc->createElement('link');
        $style->setAttribute('rel', 'stylesheet');
        $style->setAttribute('href', asset('events/imported-components.css'));
        $head->appendChild($style);
        $runtime = '<script src="'.e(asset('events/imported-layout.js')).'" nonce="'.e($nonce).'" defer></script>';
        $scripts = view('agenda.eventos.public.partials.registration-scripts', $data)->render();
        // Only application-owned scripts receive the CSP nonce, after the
        // imported document has been cleaned and all components inserted.
        $scripts = preg_replace('/<script\b/', '<script nonce="'.e($nonce).'"', $scripts);
        $scriptData = json_encode(['title' => $event->title], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $runtime .= '<script nonce="'.e($nonce).'">window.eventLandingData='.$scriptData.';</script>';
        return str_replace('</body>', $runtime.$scripts.'</body>', $doc->saveHTML());
    }

    private function document(string $html): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $doc->encoding = 'UTF-8';
        foreach (iterator_to_array($doc->childNodes) as $node) {
            if ($node->nodeType === XML_PI_NODE) $doc->removeChild($node);
        }
        if (! $doc->getElementsByTagName('html')->length) $doc->appendChild($doc->createElement('html'));
        $root = $doc->getElementsByTagName('html')->item(0);
        if (! $doc->getElementsByTagName('head')->length) $root->insertBefore($doc->createElement('head'), $root->firstChild);
        if (! $doc->getElementsByTagName('body')->length) $root->appendChild($doc->createElement('body'));
        return $doc;
    }

    private function fill(DOMDocument $doc, DOMElement $node, string $html): void
    {
        while ($node->firstChild) $node->removeChild($node->firstChild);
        $fragment = $this->document($html);
        foreach (iterator_to_array($fragment->getElementsByTagName('body')->item(0)->childNodes) as $child) {
            $node->appendChild($doc->importNode($child, true));
        }
    }

    private function sanitize(DOMDocument $doc): void
    {
        foreach ((new DOMXPath($doc))->query('//processing-instruction() | //comment()') as $node) {
            $node->parentNode?->removeChild($node);
        }
        $allowed = explode(' ', 'html head body title meta link style main header footer nav section article aside div span p a h1 h2 h3 h4 h5 h6 strong em b i u s small br hr ul ol li dl dt dd blockquote cite time address figure figcaption img picture source video audio table thead tbody tfoot tr th td caption colgroup col details summary button svg g defs pattern path rect circle ellipse line polyline polygon lineargradient radialgradient stop clippath text tspan');
        foreach (iterator_to_array($doc->getElementsByTagName('*')) as $node) {
            $tag = strtolower($node->tagName);
            if (! in_array($tag, $allowed, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            if ($tag === 'meta' && $node->hasAttribute('http-equiv')) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            if ($tag === 'link' && strtolower($node->getAttribute('rel')) !== 'stylesheet') {
                $node->parentNode?->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if (str_starts_with($name, 'on') || in_array($name, ['nonce', 'srcdoc', 'is', 'formaction', 'form', 'autofocus', 'srcset', 'ping', 'http-equiv'], true)) {
                    $node->removeAttribute($attribute->name);
                } elseif (in_array($name, ['href', 'src', 'xlink:href', 'poster', 'background'], true)) {
                    $url = preg_replace('/[\x00-\x20\x7f]+/', '', $attribute->value);
                    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) && ! preg_match('/^(https?:|mailto:|tel:)/i', $url)) {
                        if (! ($tag === 'img' && $name === 'src' && preg_match('#^data:image/(png|jpeg|gif|webp|avif);base64,#i', $url))) {
                            $node->removeAttribute($attribute->name);
                        }
                    }
                }
            }
            if ($tag === 'button') $node->setAttribute('type', 'button');
            if (in_array($node->getAttribute('id'), ['inscricao', 'event-registration-form'], true)) $node->removeAttribute('id');
        }
    }
}
