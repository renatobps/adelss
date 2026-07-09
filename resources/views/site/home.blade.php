@extends('layouts.site')

@section('title', ($settings->hero_title ?? 'ADEL São Sebastião') . ' — ADELSS')

@section('content')
@php
    $portalUrl = auth()->check() ? route('dashboard') : route('login');
    $portalLabel = 'Portal do Membro';
    $bannerUrl = $settings->bannerUrl();

    $youtubeEmbed = null;
    if ($settings->watch_video_url) {
        $url = $settings->watch_video_url;
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            $youtubeEmbed = 'https://www.youtube.com/embed/' . $m[1];
        }
    }

    $whatsappLink = null;
    if ($settings->whatsapp_number) {
        $digits = preg_replace('/\D/', '', $settings->whatsapp_number);
        if ($digits) {
            $whatsappLink = 'https://wa.me/' . $digits;
        }
    }

    $footerWhatsappRaw = config('whatsapp.number')
        ?: $settings->whatsapp_number
        ?: env('WHATSAPP_NUMBER')
        ?: '5561993640457';
    $footerWhatsappLink = null;
    if ($footerWhatsappRaw) {
        $footerDigits = preg_replace('/\D/', '', $footerWhatsappRaw);
        if ($footerDigits) {
            $footerWhatsappLink = 'https://wa.me/' . $footerDigits;
        }
    }

    $heroTitleFormatted = $settings->hero_title
        ? preg_replace('/\*(.+?)\*/', '<em>$1</em>', e($settings->hero_title))
        : '';

    $aboutTextEscaped = e($settings->about_text);
    $aboutTextFormatted = $aboutTextEscaped;
    if ($settings->about_highlight_word) {
        $highlightWordEscaped = e($settings->about_highlight_word);
        $aboutTextFormatted = str_ireplace(
            $highlightWordEscaped,
            '<strong class="text-accent">' . $highlightWordEscaped . '</strong>',
            $aboutTextEscaped
        );
    }

    $nextStepsCards = $settings->visibleNextStepsCards($portalUrl);
@endphp

@if($settings->service_times_text)
<div class="site-utility">
    <div class="container">
        <i class="fas fa-clock me-1"></i> Cultos: {{ $settings->service_times_text }}
    </div>
</div>
@endif

<header class="site-header">
    <div class="container site-header__inner">
        <a href="{{ route('home') }}" class="site-logo">
            <img src="{{ asset('img/img/LOG SS branca.png') }}" alt="ADEL São Sebastião">
        </a>

        <button type="button" class="site-menu-toggle" id="siteMenuToggle" aria-label="Abrir menu">
            <i class="fas fa-bars"></i>
        </button>

        <nav class="site-nav" id="siteNav">
            <a href="#sobre">Sobre</a>
            @if($settings->show_watch_section)
                <a href="#assista">Assista</a>
            @endif
            @if($settings->show_events_section)
                <a href="#eventos">Eventos</a>
            @endif
            @if($settings->show_next_steps_section ?? true)
                <a href="#proximos-passos">Próximos passos</a>
            @endif
            <a href="#contato">Contato</a>
            <a href="{{ $portalUrl }}" class="site-btn site-btn--primary">{{ $portalLabel }}</a>
        </nav>
    </div>
</header>

<section class="site-hero">
    <div class="site-hero__aurora"></div>
    @if($bannerUrl)
        <div class="site-hero__bg" style="background-image: url('{{ $bannerUrl }}');"></div>
    @endif
    <div class="container">
        <div class="site-hero__content">
            @if($settings->hero_eyebrow)
                <span class="eyebrow" style="color:#8fd4ff;">{{ $settings->hero_eyebrow }}</span>
            @endif
            <h1 class="site-hero__title">{!! $heroTitleFormatted !!}</h1>
            @if($settings->hero_subtitle)
                <p class="site-hero__subtitle">{{ $settings->hero_subtitle }}</p>
            @endif
            <div class="site-hero__ctas">
                @if($settings->hero_cta_primary_label)
                    <a href="{{ $settings->hero_cta_primary_url ?: '#contato' }}" class="site-btn site-btn--primary">
                        {{ $settings->hero_cta_primary_label }}
                    </a>
                @endif
                @if($settings->show_watch_section && $settings->hero_cta_secondary_label)
                    <a href="{{ $settings->hero_cta_secondary_url ?: '#assista' }}" class="site-btn site-btn--outline">
                        {{ $settings->hero_cta_secondary_label }}
                    </a>
                @endif
            </div>
        </div>

        @if($nextCulto || $homeAddress)
        <div class="site-info-card">
            <div class="site-info-card__grid">
                @if($nextCulto)
                <div class="site-info-card__item">
                    <div class="site-info-card__label"><i class="fas fa-clock"></i> Próximo culto</div>
                    <p class="site-info-card__value">
                        {{ $nextCulto->start_date->translatedFormat('l, d/m \à\s H:i') }}
                    </p>
                </div>
                @endif
                @if($homeAddress)
                <div class="site-info-card__item">
                    <div class="site-info-card__label"><i class="fas fa-map-marker-alt"></i> Endereço</div>
                    <p class="site-info-card__value">{{ $homeAddress }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</section>

<section class="site-section site-section--muted" id="sobre">
    <div class="container">
        <div class="site-about">
            <div class="site-about__intro">
                @if($settings->about_eyebrow)
                    <p class="site-about__eyebrow"><span class="site-about__line"></span> {{ $settings->about_eyebrow }}</p>
                @endif
                @if($settings->about_title)
                    <h2 class="site-about__title">{{ $settings->about_title }}</h2>
                @endif
                @if($settings->about_text)
                    <p class="site-about__text">{!! $aboutTextFormatted !!}</p>
                @endif
                @if($settings->about_bible_reference)
                    <p style="font-family:'Fraunces',serif; font-style:italic; color:var(--text-secondary, var(--site-muted)); font-size:.85rem; margin:4px 0 18px;">
                        — {{ $settings->about_bible_reference }}
                    </p>
                @endif
                @if($settings->about_link_label)
                    <a href="{{ $settings->about_link_url ?: '#contato' }}" class="site-about__link">
                        {{ $settings->about_link_label }} <i class="fas fa-arrow-right"></i>
                    </a>
                @endif
            </div>

            <div class="site-about__stats">
                <article class="site-about-stat">
                    <p class="site-about-stat__number">1</p>
                    <div>
                        <h3>Pequenos Grupos (PGI)</h3>
                        <p>Reunindo vidas semanalmente para comunhão, oração e estudo da Palavra.</p>
                    </div>
                </article>
                <article class="site-about-stat">
                    <p class="site-about-stat__number">2</p>
                    <div>
                        <h3>Cultos por semana</h3>
                        <p>Momentos de adoração, ensino da Palavra e comunhão.</p>
                    </div>
                </article>
                <article class="site-about-stat">
                    <p class="site-about-stat__number">8</p>
                    <div>
                        <h3>Ministérios ativos</h3>
                        <p>Jovens, Kids, Homens, Mulheres, Intercessão, Louvor, Voluntariado e Ação Social.</p>
                    </div>
                </article>
            </div>
        </div>

        <h2 class="site-section__title">Conheça nossos ministérios</h2>
        <p class="site-section__subtitle">Cada departamento é um espaço para servir, crescer e fazer parte da família ADEL.</p>

        @php
            $homeCardsCount = $departments->count() + ($settings->pgi_card_show ? 1 : 0);
        @endphp

        @if($homeCardsCount > 0)
        <div class="site-departments-carousel" id="departmentsCarousel" data-total-slides="{{ $homeCardsCount }}">
            <div class="swiper site-departments-swiper">
                <div class="swiper-wrapper">
                    @foreach($departments as $department)
                        <div class="swiper-slide">
                            @include('site.partials.department-card', ['department' => $department])
                        </div>
                    @endforeach

                    @if($settings->pgi_card_show)
                        <div class="swiper-slide">
                            <article class="site-card site-card--pgi">
                                @if($settings->pgi_card_image)
                                    <div class="site-card__icon site-card__icon--logo" style="border-color: #6BCB77;">
                                        <img src="{{ asset('storage/' . $settings->pgi_card_image) }}" alt="{{ $settings->pgi_card_title }}">
                                    </div>
                                @else
                                    <div class="site-card__icon" style="background: #6BCB77;">
                                        <i class="bx bx-group"></i>
                                    </div>
                                @endif
                                <h3 class="site-card__title">{{ $settings->pgi_card_title }}</h3>
                                @if($settings->pgi_card_description)
                                    <p class="site-card__text">{{ $settings->pgi_card_description }}</p>
                                @endif
                                <a href="{{ $settings->pgiCardLink() }}" class="site-card__link">
                                    Saiba mais <i class="fas fa-arrow-right"></i>
                                </a>
                            </article>
                        </div>
                    @endif
                </div>
            </div>

            <button type="button" class="site-departments-nav site-departments-nav--prev" aria-label="Ministérios anteriores">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" class="site-departments-nav site-departments-nav--next" aria-label="Próximos ministérios">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="site-departments-pagination"></div>
        </div>
        @else
        <p class="site-section__subtitle text-center mb-0">Em breve novos ministérios serão exibidos aqui.</p>
        @endif
    </div>
</section>

@if($settings->show_next_steps_section ?? true)
<section class="site-section site-next-steps" id="proximos-passos">
    <div class="container">
        <div class="site-next-steps__header">
            @if($settings->next_steps_eyebrow)
                <p class="site-next-steps__label"><span class="site-next-steps__label-line"></span> {{ $settings->next_steps_eyebrow }}</p>
            @endif
            @if($settings->next_steps_title)
                <h2 class="site-next-steps__title">{{ $settings->next_steps_title }}</h2>
            @endif
            @if($settings->next_steps_intro)
                <p class="site-next-steps__intro">{{ $settings->next_steps_intro }}</p>
            @endif
        </div>

        @if(!empty($nextStepsCards))
        <div class="site-next-steps__grid">
            @foreach($nextStepsCards as $index => $card)
                <article class="site-step-card">
                    <span class="site-step-card__num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="site-step-card__title">{{ $card['title'] }}</h3>
                    @if($card['text'])
                        <p class="site-step-card__text">{{ $card['text'] }}</p>
                    @endif
                    @if($card['show_link'] && $card['link_label'])
                        <a href="{{ $card['link_url'] }}" class="site-step-card__link"
                           @if(str_starts_with($card['link_url'], 'https://wa.me')) target="_blank" rel="noopener" @endif>
                            {{ $card['link_label'] }} <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

@if($settings->show_watch_section)
<section class="site-watch" id="assista">
    <div class="container site-watch__inner">
        <div>
            <h2 class="site-watch__title">Assista</h2>
            <p class="site-watch__text">Acompanhe nossos cultos e mensagens. Participe conosco, onde quer que você esteja.</p>
            @if($settings->watch_video_url && !$youtubeEmbed)
                <a href="{{ $settings->watch_video_url }}" class="site-btn site-btn--primary mt-3" target="_blank" rel="noopener">
                    <i class="fas fa-play"></i> Assistir agora
                </a>
            @endif
        </div>
        @if($youtubeEmbed)
        <div class="site-video">
            <iframe src="{{ $youtubeEmbed }}" title="Transmissão ao vivo" allowfullscreen loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
        </div>
        @elseif($settings->watch_video_url)
        <div class="site-video" style="padding-bottom:0;height:200px;display:flex;align-items:center;justify-content:center;">
            <a href="{{ $settings->watch_video_url }}" class="site-btn site-btn--light" target="_blank" rel="noopener">
                <i class="fas fa-external-link-alt"></i> Abrir transmissão
            </a>
        </div>
        @endif
    </div>
</section>
@endif

@if($settings->show_events_section)
<section class="site-section" id="eventos">
    <div class="container">
        <div class="site-events-header">
            <div>
                <p class="site-events-header__label"><span class="site-events-header__line"></span> Agenda</p>
                <h2 class="site-events-header__title">O que vem por aí.</h2>
            </div>
            <a href="{{ route('login') }}" class="site-events-header__cta">Ver agenda completa</a>
        </div>

        @if($weeklyAgenda->isNotEmpty())
        <div class="site-events-block">
            <div class="site-events-block__header">
                <h3 class="site-events-block__title">Agenda da semana</h3>
                <span class="site-events-block__range">{{ $weekLabel }}</span>
            </div>
            <div class="site-events">
                @foreach($weeklyAgenda as $event)
                    @include('site.partials.event-card', ['event' => $event])
                @endforeach
            </div>
        </div>
        @endif

        @if($monthlyEvents->isNotEmpty())
        <div class="site-events-block">
            <div class="site-events-block__header">
                <h3 class="site-events-block__title">Eventos do mês</h3>
                <span class="site-events-block__range">{{ $monthLabel }}</span>
            </div>
            <div class="site-events">
                @foreach($monthlyEvents as $event)
                    @include('site.partials.event-card', ['event' => $event])
                @endforeach
            </div>
        </div>
        @endif

        @if($weeklyAgenda->isEmpty() && $monthlyEvents->isEmpty())
            <div class="site-empty">
                <i class="far fa-calendar fa-2x mb-2 d-block"></i>
                Nenhum compromisso programado no momento. Acompanhe nossas redes sociais.
            </div>
        @endif
    </div>
</section>
@endif

<footer class="site-footer" id="contato">
    <div class="container">
        <div class="site-footer__grid">
            <div class="site-footer__logo">
                <div class="site-brand">
                    <div class="site-brand__mark">
                        <img src="{{ asset('img/img/LOG SS branca.png') }}" alt="ADEL São Sebastião">
                    </div>
                    <h4 class="site-brand__name">ADEL São Sebastião</h4>
                </div>
                <p class="site-brand__text">
                    {{ $settings->footer_text ?: 'Uma comunidade que acredita que fé se vive junto, todos os dias da semana.' }}
                </p>
                @if($settings->social_facebook || $settings->social_instagram || $settings->social_youtube)
                <div class="site-social">
                    @if($settings->social_facebook)
                        <a href="{{ $settings->social_facebook }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    @endif
                    @if($settings->social_instagram)
                        <a href="{{ $settings->social_instagram }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    @endif
                    @if($settings->social_youtube)
                        <a href="{{ $settings->social_youtube }}" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    @endif
                </div>
                @endif
            </div>

            <div>
                <h4>Contato</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> df473 chacara via sacra lotes 16/17</li>
                    <li>
                        <i class="fab fa-whatsapp"></i>
                        @if($footerWhatsappLink)
                            <a href="{{ $footerWhatsappLink }}" target="_blank" rel="noopener">
                                Fale conosco no WhatsApp
                            </a>
                        @else
                            Fale conosco no WhatsApp
                        @endif
                    </li>
                </ul>
            </div>

            <div>
                <h4>Horários</h4>
                <ul>
                    <li>Culto de Adoração - Domingo, 19h</li>
                    <li>Culto da Graça - Quarta, 20h</li>
                    <li>PGI - Sexta - 19:30h</li>
                </ul>
            </div>

            <div>
                <h4>Links</h4>
                <ul>
                    <li><a href="{{ $portalUrl }}">{{ $portalLabel }}</a></li>
                    <li><a href="#sobre">Ministérios</a></li>
                    @if($settings->show_events_section)
                        <li><a href="#eventos">Eventos</a></li>
                    @endif
                    @if($settings->show_next_steps_section ?? true)
                        <li><a href="#proximos-passos">Próximos passos</a></li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            &copy; {{ date('Y') }} ADEL São Sebastião. Todos os direitos reservados.
        </div>
    </div>
</footer>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.getElementById('siteMenuToggle')?.addEventListener('click', function () {
    document.getElementById('siteNav')?.classList.toggle('is-open');
});

(function () {
    const container = document.getElementById('departmentsCarousel');
    if (!container || typeof Swiper === 'undefined') return;

    const swiperEl = container.querySelector('.site-departments-swiper');
    const wrapper = swiperEl.querySelector('.swiper-wrapper');
    const totalSlides = parseInt(container.dataset.totalSlides, 10) || swiperEl.querySelectorAll('.swiper-slide').length;
    if (!totalSlides) return;

    let swiperInstance = null;
    let touchResumeTimer = null;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function slidesPerView() {
        if (window.innerWidth >= 1200) return 4;
        if (window.innerWidth >= 768) return 2;
        return 1;
    }

    function shouldCarousel() {
        return totalSlides > slidesPerView();
    }

    function clearStaticLayout() {
        swiperEl.classList.remove('site-departments-swiper--static');
        container.classList.remove('site-departments-carousel--static');
        wrapper.style.display = '';
        wrapper.style.gridTemplateColumns = '';
        wrapper.style.gap = '';
        wrapper.style.transform = '';
    }

    function applyStaticLayout() {
        if (swiperInstance) {
            swiperInstance.destroy(true, true);
            swiperInstance = null;
        }

        clearStaticLayout();
        swiperEl.classList.add('site-departments-swiper--static');
        container.classList.add('site-departments-carousel--static');

        const spv = slidesPerView();
        const cols = Math.min(totalSlides, spv);
        wrapper.style.display = 'grid';
        wrapper.style.gridTemplateColumns = 'repeat(' + cols + ', minmax(0, 1fr))';
        wrapper.style.gap = '1.5rem';
    }

    function initCarousel() {
        if (swiperInstance) {
            swiperInstance.destroy(true, true);
            swiperInstance = null;
        }

        clearStaticLayout();

        const spv = slidesPerView();
        const enableLoop = totalSlides > spv;

        swiperInstance = new Swiper(swiperEl, {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: enableLoop,
            rewind: !enableLoop,
            speed: 600,
            grabCursor: true,
            watchOverflow: true,
            autoplay: reducedMotion ? false : {
                delay: 5500,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            navigation: {
                nextEl: container.querySelector('.site-departments-nav--next'),
                prevEl: container.querySelector('.site-departments-nav--prev'),
            },
            pagination: {
                el: container.querySelector('.site-departments-pagination'),
                clickable: true,
            },
            breakpoints: {
                768: { slidesPerView: 2 },
                1200: { slidesPerView: 4 },
            },
            on: {
                touchStart: function () {
                    this.autoplay?.stop();
                },
                touchEnd: function () {
                    if (reducedMotion || !this.autoplay) return;
                    clearTimeout(touchResumeTimer);
                    const swiper = this;
                    touchResumeTimer = setTimeout(function () {
                        swiper.autoplay?.start();
                    }, 4000);
                },
            },
        });
    }

    function refresh() {
        if (shouldCarousel()) {
            initCarousel();
        } else {
            applyStaticLayout();
        }
    }

    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(refresh, 200);
    });

    reducedMotion && window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', refresh);

    refresh();
})();
</script>
@endpush
