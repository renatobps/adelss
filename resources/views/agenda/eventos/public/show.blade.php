@extends('layouts.event-landing')

@section('title', $event->title)

@section('content')
@php
    $bannerUrl = $event->bannerImagePublicUrl();
    $spotsLeft = null;
    if ($event->max_spots !== null && $event->max_spots > 0) {
        $spotsLeft = max(0, $event->max_spots - (int) ($event->registrations_em_vaga_count ?? 0));
    }
    $locationPhotoUrls = $event->locationPhotoPublicUrls();
    $pixPayment = session('pix_payment');
    $eventPriceJs = number_format((float) ($event->price ?? 0), 2, '.', '');
    $useConfiguredPayer = !empty(config('mercadopago.test_payer_email')) && !empty(config('mercadopago.test_payer_document'));
@endphp

<div class="evx-theme evx-palette-{{ $event->page_palette ?: 'oceano' }}">
<header class="evx-topbar">
    <div class="container evx-topbar-inner">
        <a href="#" class="evx-brand">
            <span class="evx-brand-icon"><i class="fas fa-calendar-alt"></i></span>
            <span>
                <strong>{{ strtoupper($event->title) }}</strong>
                <small>Event Planner</small>
            </span>
        </a>
        <nav class="d-none d-lg-flex evx-menu">
            <a href="#sobre">Sobre</a>
            <a href="#programacao">Programação</a>
            <a href="#local">Local</a>
            <a href="#palestrantes">Palestrantes</a>
        </nav>
        <a href="#inscricao" class="evx-ticket-btn">Inscreva-se</a>
    </div>
</header>

<section class="evx-hero" @if($bannerUrl) style="--hero-bg: url('{{ $bannerUrl }}');" @endif>
    <div class="evx-overlay"></div>
    <div class="container position-relative">
        <p class="evx-date">
            {{ $event->start_date->translatedFormat('d \\d\\e F, Y') }}
            @if($event->location)
                | {{ $event->location }}
            @endif
        </p>
        <h1>{{ $event->title }}</h1>
        @if($event->subtitle)
            @php
                $themeStyle = '';
                if ($event->subtitle_color) {
                    $themeStyle .= 'color:'.e($event->subtitle_color).';';
                }
                if ($event->subtitle_font_family) {
                    $themeStyle .= 'font-family:\''.e($event->subtitle_font_family).'\',sans-serif;';
                }
            @endphp
            <p class="evx-subtitle evx-subtitle-highlight" @if($themeStyle !== '') style="{{ $themeStyle }}" @endif>
                <span class="evx-subtitle-label">Tema</span>
                {{ $event->subtitle }}
            </p>
        @endif
        <div class="evx-countdown" data-event-date="{{ $event->start_date->format('c') }}">
            <div><strong data-part="days">0</strong><span>Dias</span></div>
            <div><strong data-part="hours">0</strong><span>Horas</span></div>
            <div><strong data-part="minutes">0</strong><span>Min</span></div>
            <div><strong data-part="seconds">0</strong><span>Seg</span></div>
        </div>
        <a href="#programacao" class="evx-primary-btn evx-hero-program-btn">Ver programação</a>
    </div>
</section>

<section id="sobre" class="evx-section light">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-10 col-xl-9 text-center">
                <p class="evx-kicker">Sobre o Evento</p>
                <h2>{{ $event->subtitle ?: 'Conexões que transformam vidas' }}</h2>
                <div class="evx-copy">
                    @if($event->about_html)
                        {!! $event->about_html !!}
                    @else
                        <p>{{ $event->description ?: 'Informações em breve.' }}</p>
                    @endif
                </div>
                <ul class="evx-list">
                    <li>Programação objetiva e prática</li>
                    <li>Conteúdo voltado para crescimento</li>
                    <li>Ambiente de comunhão e networking</li>
                </ul>
            </div>
        </div>
    </div>
</section>

@if($event->scheduleItems->isNotEmpty())
@php
    $scheduleStartDay = $event->start_date->copy()->startOfDay();
    $scheduleEndDay = ($event->end_date ?: $event->start_date)->copy()->startOfDay();
    $scheduleTotalDays = max(1, (int) $scheduleStartDay->diffInDays($scheduleEndDay) + 1);

    // Itens sem horário vão para o fim do dia, mantendo a ordem manual como desempate.
    $sortedScheduleItems = $event->scheduleItems->sortBy(function ($item) {
        $minutes = $item->time_hh === null ? (24 * 60 + 1) : ((int) $item->time_hh * 60 + (int) ($item->time_mm ?? 0));

        return sprintf('%05d-%05d', $minutes, (int) $item->sort_order);
    })->values();

    $itemsByDay = $sortedScheduleItems
        ->groupBy(fn ($item) => min(max((int) ($item->day ?? 1), 1), $scheduleTotalDays))
        ->sortKeys();

    $periodOf = function ($item) {
        if ($item->time_hh === null) {
            return 'sem';
        }
        $hh = (int) $item->time_hh;
        if ($hh <= 11) {
            return 'manha';
        }

        return $hh <= 17 ? 'tarde' : 'noite';
    };

    $periodLabels = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite', 'sem' => 'Horário a definir'];
    $periodIcons = ['manha' => 'fa-sun', 'tarde' => 'fa-cloud-sun', 'noite' => 'fa-moon', 'sem' => 'fa-clock'];
    $showDayTabs = $scheduleTotalDays > 1;
@endphp
<section id="programacao" class="evx-section">
    <div class="container">
        <p class="evx-kicker text-center">Guia do Evento</p>
        <h2 class="text-center mb-3">Programação</h2>

        <div class="evx-sched-controls">
            @if($showDayTabs)
                <div class="evx-day-tabs" role="tablist">
                    @foreach($itemsByDay as $dayNumber => $dayItems)
                        <button
                            type="button"
                            class="evx-day-tab {{ $loop->first ? 'active' : '' }}"
                            data-day-target="evx-day-{{ $dayNumber }}"
                            role="tab"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        >
                            Dia {{ $dayNumber }}
                            <small>{{ $scheduleStartDay->copy()->addDays($dayNumber - 1)->format('d/m') }}</small>
                        </button>
                    @endforeach
                </div>
            @endif
            <div class="evx-view-toggle" role="group" aria-label="Modo de visualização da programação">
                <button type="button" class="active" data-view="compact">Compacta</button>
                <button type="button" data-view="detailed">Detalhada</button>
            </div>
        </div>

        <div class="evx-schedule" id="evx-schedule">
            @foreach($itemsByDay as $dayNumber => $dayItems)
                <div
                    class="evx-day-pane {{ $loop->first ? 'active' : '' }}"
                    id="evx-day-{{ $dayNumber }}"
                    data-date="{{ $scheduleStartDay->copy()->addDays($dayNumber - 1)->format('Y-m-d') }}"
                >
                    @foreach(['manha', 'tarde', 'noite', 'sem'] as $period)
                        @php
                            $periodItems = $dayItems->filter(fn ($it) => $periodOf($it) === $period)->values();
                        @endphp
                        @if($periodItems->isNotEmpty())
                            <div class="evx-period">
                                <div class="evx-period-head">
                                    <i class="fas {{ $periodIcons[$period] }}" aria-hidden="true"></i>
                                    <span>{{ $periodLabels[$period] }}</span>
                                </div>
                                <div class="evx-tl">
                                    @foreach($periodItems as $item)
                                        @php
                                            $hasTime = $item->time_hh !== null;
                                            $minutes = $hasTime ? ((int) $item->time_hh * 60 + (int) ($item->time_mm ?? 0)) : null;
                                            $timeLabel = $hasTime
                                                ? sprintf('%02d:%02d', (int) $item->time_hh, (int) ($item->time_mm ?? 0))
                                                : '--:--';
                                            $photoUrl = $item->responsible_photo_path
                                                ? \App\Models\Event::publicStorageUrl($item->responsible_photo_path)
                                                : null;
                                            $detailText = trim((string) $item->detail);
                                            $hasDetails = $detailText !== '' || $photoUrl;
                                        @endphp
                                        <div class="evx-tl-item {{ $hasDetails ? 'has-details' : '' }}" @if($minutes !== null) data-minutes="{{ $minutes }}" @endif>
                                            <button type="button" class="evx-tl-head" @if($hasDetails) aria-expanded="false" @else disabled @endif>
                                                <span class="evx-tl-time">{{ $timeLabel }}</span>
                                                <span class="evx-tl-track"><span class="evx-tl-dot"></span></span>
                                                <span class="evx-tl-main">
                                                    <span class="evx-tl-title">{{ $item->title }}</span>
                                                    @if($item->responsible_name)
                                                        <span class="evx-tl-resp">{{ $item->responsible_name }}</span>
                                                    @endif
                                                </span>
                                                <span class="evx-tl-now-badge" aria-hidden="true">Agora</span>
                                                @if($hasDetails)
                                                    <i class="fas fa-chevron-down evx-tl-caret" aria-hidden="true"></i>
                                                @endif
                                            </button>
                                            @if($hasDetails)
                                                <div class="evx-tl-body">
                                                    <div class="evx-tl-body-inner {{ $photoUrl ? 'with-photo' : '' }}">
                                                        @if($photoUrl)
                                                            <img src="{{ $photoUrl }}" alt="{{ $item->responsible_name ?: 'Responsável' }}">
                                                        @endif
                                                        @if($detailText !== '')
                                                            <p>{{ $detailText }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($event->speakers->isNotEmpty())
<section id="palestrantes" class="evx-section light">
    <div class="container">
        <p class="evx-kicker text-center">Convidados</p>
        <h2 class="text-center mb-4">Palestrantes</h2>
        <div class="row g-3 justify-content-center">
            @foreach($event->speakers as $speaker)
                <div class="col-md-6 col-lg-4">
                    <div class="evx-speaker-card">
                        @if($speaker->photo_path)
                            <img src="{{ \App\Models\Event::publicStorageUrl($speaker->photo_path) }}" alt="{{ $speaker->name }}">
                        @else
                            <div class="evx-speaker-placeholder"><i class="fas fa-user"></i></div>
                        @endif
                        <h3>{{ $speaker->name }}</h3>
                        <p>{{ $speaker->description ?: 'Participação especial.' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section id="local" class="evx-section">
    <div class="container">
        <p class="evx-kicker">Onde será</p>
        <h2>Local</h2>
        <p class="evx-copy">{{ $event->location ?: 'Local a ser confirmado.' }}</p>
        @if(count($locationPhotoUrls))
            <div class="evx-location-grid mt-3">
                @foreach($locationPhotoUrls as $photoUrl)
                    <img src="{{ $photoUrl }}" alt="Local do evento">
                @endforeach
            </div>
        @endif
    </div>
</section>

@include('agenda.eventos.public.partials.registration')
</div>
@endsection

@push('scripts')
@include('agenda.eventos.public.partials.registration-scripts')
@endpush
