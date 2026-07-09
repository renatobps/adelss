<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HomePageSetting extends Model
{
    protected $fillable = [
        'hero_title',
        'hero_eyebrow',
        'hero_subtitle',
        'hero_cta_primary_label',
        'hero_cta_primary_url',
        'hero_cta_secondary_label',
        'hero_cta_secondary_url',
        'about_eyebrow',
        'about_title',
        'about_text',
        'about_highlight_word',
        'about_link_label',
        'about_link_url',
        'about_bible_reference',
        'banner_image',
        'service_times_text',
        'address_text',
        'address_line2',
        'phone',
        'contact_email',
        'whatsapp_number',
        'show_watch_section',
        'watch_video_url',
        'show_events_section',
        'events_count',
        'pgi_card_show',
        'pgi_card_title',
        'pgi_card_description',
        'pgi_card_image',
        'pgi_card_url',
        'show_next_steps_section',
        'next_steps_eyebrow',
        'next_steps_title',
        'next_steps_intro',
        'next_steps_cards',
        'social_facebook',
        'social_instagram',
        'social_youtube',
        'footer_text',
    ];

    protected $casts = [
        'show_watch_section' => 'boolean',
        'show_events_section' => 'boolean',
        'pgi_card_show' => 'boolean',
        'show_next_steps_section' => 'boolean',
        'next_steps_cards' => 'array',
        'events_count' => 'integer',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], static::defaultAttributes());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultAttributes(): array
    {
        return [
            'hero_eyebrow' => 'Igreja Cristã · ADEL São Sebastião',
            'hero_title' => 'Jesus Cristo é o nosso único *fundamento*.',
            'hero_subtitle' => 'Existimos para levar o evangelho a todos e acolher quem precisa, apontando o único caminho de esperança e salvação em Cristo Jesus.',
            'hero_cta_primary_label' => 'Planeje sua visita',
            'hero_cta_secondary_label' => 'Assista uma mensagem',
            'about_eyebrow' => 'Nossa Missão',
            'about_title' => 'Existimos para anunciar a Cristo e acolher quem precisa.',
            'about_text' => 'Recebemos gente de toda história — famílias, solteiros, quem está conhecendo Jesus agora e quem caminha com Ele há anos. A todos anunciamos o mesmo evangelho: Jesus Cristo morreu e ressuscitou para nos salvar. É essa esperança que oferecemos a quem chega até nós, através dos Pequenos Grupos, dos cultos de domingo e de cada encontro pelo caminho.',
            'about_highlight_word' => 'evangelho',
            'about_link_label' => 'Conheça nossa história',
            'about_bible_reference' => 'Mateus 28:19',
            'service_times_text' => 'Domingo, 9h e 19h',
            'show_watch_section' => false,
            'show_events_section' => true,
            'events_count' => 3,
            'pgi_card_show' => true,
            'pgi_card_title' => 'Pequenos Grupos',
            'pgi_card_description' => 'Cresça em comunidade através dos Pequenos Grupos de Interesse (PGIs).',
            'show_next_steps_section' => true,
            'next_steps_eyebrow' => 'Próximos passos',
            'next_steps_title' => 'A mudança começa com um passo.',
            'next_steps_intro' => 'Não importa em que ponto da caminhada você está, sempre tem um próximo passo. Escolha por onde começar.',
            'next_steps_cards' => self::defaultNextStepsCards(),
        ];
    }

    /**
     * @return list<array{enabled: bool, title: string, text: string, link_url: string, link_label: string, show_link: bool}>
     */
    public static function defaultNextStepsCards(): array
    {
        return [
            [
                'enabled' => true,
                'title' => 'Conhecer a igreja',
                'text' => 'Um encontro rápido pra tirar dúvidas e entender no que a gente acredita.',
                'link_url' => '@whatsapp',
                'link_label' => 'Participar',
                'show_link' => true,
            ],
            [
                'enabled' => true,
                'title' => 'Entrar num grupo',
                'text' => 'Trocar a fileira de domingo pela roda de conversa durante a semana.',
                'link_url' => '@pgi',
                'link_label' => 'Encontrar grupo',
                'show_link' => true,
            ],
            [
                'enabled' => true,
                'title' => 'Servir',
                'text' => 'Usar seus dons pra edificar a igreja local em uma das nossas equipes.',
                'link_url' => '@portal',
                'link_label' => 'Quero servir',
                'show_link' => true,
            ],
            [
                'enabled' => true,
                'title' => 'Gerar impacto',
                'text' => 'Levar cuidado prático pra comunidade ao redor e além dela.',
                'link_url' => '#contato',
                'link_label' => 'Ver oportunidades',
                'show_link' => true,
            ],
        ];
    }

    /**
     * @return list<array{enabled: bool, title: string, text: string, link_url: string, link_label: string, show_link: bool}>
     */
    public function nextStepsCardsForForm(): array
    {
        $cards = $this->next_steps_cards ?? self::defaultNextStepsCards();

        while (count($cards) < 4) {
            $cards[] = [
                'enabled' => false,
                'title' => '',
                'text' => '',
                'link_url' => '',
                'link_label' => '',
                'show_link' => true,
            ];
        }

        return array_slice($cards, 0, 4);
    }

    /**
     * @return list<array{title: string, text: string, link_url: string, link_label: string, show_link: bool}>
     */
    public function visibleNextStepsCards(?string $portalUrl = null): array
    {
        $cards = $this->next_steps_cards ?? self::defaultNextStepsCards();
        $visible = [];

        foreach ($cards as $card) {
            if (empty($card['enabled'])) {
                continue;
            }

            $title = trim((string) ($card['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $linkUrl = trim((string) ($card['link_url'] ?? ''));
            if ($linkUrl === '@pgi') {
                $linkUrl = $this->pgiCardLink();
            } elseif ($linkUrl === '@portal' && $portalUrl) {
                $linkUrl = $portalUrl;
            } elseif ($linkUrl === '@whatsapp') {
                $linkUrl = $this->whatsappLink() ?: '#contato';
            } elseif ($linkUrl === '') {
                $linkUrl = '#contato';
            }

            $visible[] = [
                'title' => $title,
                'text' => trim((string) ($card['text'] ?? '')),
                'link_url' => $linkUrl,
                'link_label' => trim((string) ($card['link_label'] ?? 'Saiba mais')),
                'show_link' => !empty($card['show_link']),
            ];
        }

        return $visible;
    }

    public function bannerUrl(): ?string
    {
        if (!$this->banner_image) {
            return null;
        }

        return asset('storage/' . $this->banner_image);
    }

    public function pgiCardLink(): string
    {
        return $this->pgi_card_url ?: route('login');
    }

    public function whatsappLink(): ?string
    {
        $raw = config('whatsapp.number')
            ?: $this->whatsapp_number
            ?: env('WHATSAPP_NUMBER');

        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $raw);

        return $digits ? 'https://wa.me/' . $digits : null;
    }

    public function pgiCardImageUrl(): ?string
    {
        if (!$this->pgi_card_image) {
            return null;
        }

        return asset('storage/' . $this->pgi_card_image);
    }
}
