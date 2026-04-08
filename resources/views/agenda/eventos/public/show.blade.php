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
<section id="programacao" class="evx-section">
    <div class="container">
        <p class="evx-kicker text-center">Guia do Evento</p>
        <h2 class="text-center mb-4"></h2>
        <div class="evx-day-tabs">
            <button class="active" type="button">Dia 1</button>
        </div>
        <div class="evx-schedule-list">
            @foreach($event->scheduleItems as $item)
                <article class="evx-schedule-item">
                    <div class="evx-time">
                        {{ str_pad((string)($item->time_hh ?? 0), 2, '0', STR_PAD_LEFT) }}:{{ str_pad((string)($item->time_mm ?? 0), 2, '0', STR_PAD_LEFT) }}
                    </div>
                    <div class="evx-content">
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->detail ?: 'Detalhes desta atividade serão compartilhados no evento.' }}</p>
                    </div>
                    <div class="evx-speaker">
                        @if($item->responsible_photo_path)
                            <img src="{{ \App\Models\Event::publicStorageUrl($item->responsible_photo_path) }}" alt="Responsável">
                        @elseif($event->speakers->isNotEmpty() && $event->speakers->first()->photo_path)
                            <img src="{{ \App\Models\Event::publicStorageUrl($event->speakers->first()->photo_path) }}" alt="Palestrante">
                        @endif
                        <strong>{{ $item->responsible_name ?: ($event->speakers->first()->name ?? 'Equipe do Evento') }}</strong>
                    </div>
                </article>
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

<section id="inscricao" class="evx-section light">
    <div class="container">
        <div class="event-form-card">
            <h3>Inscrição</h3>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(!$event->registration_enabled)
                <p class="text-muted mb-0">Inscrições encerradas.</p>
            @elseif($spotsLeft !== null && $spotsLeft === 0)
                <p class="text-muted mb-0">Vagas esgotadas.</p>
            @else
                @if($spotsLeft !== null)
                    <p class="small text-muted">Vagas restantes: {{ $spotsLeft }}</p>
                @endif
                <form method="post" action="{{ route('events.public.register', $event->public_slug) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nome completo *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    @if(!$event->hide_phone || $event->phone_required)
                        <div class="mb-3">
                            <label class="form-label">Telefone @if($event->phone_required)*@endif</label>
                            <input type="text" name="phone" class="form-control js-phone-mask" value="{{ old('phone') }}" placeholder="(99) 99999-9999" maxlength="15" @if($event->phone_required) required @endif>
                        </div>
                    @endif
                    @if(!$event->hide_address || $event->address_required)
                        <div class="mb-3">
                            <label class="form-label">Endereço @if($event->address_required)*@endif</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}" @if($event->address_required) required @endif>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">E-mail @if($event->email_required)*@endif</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" @if($event->email_required) required @endif>
                    </div>

                    @foreach(($customFields ?? $event->registrationFields) as $field)
                        <div class="mb-3">
                            <label class="form-label">{{ $field->name }} @if($field->required)*@endif</label>
                            @if($field->field_type === 'textarea')
                                <textarea name="custom[{{ $field->id }}]" class="form-control" rows="3" @if($field->required) required @endif>{{ old('custom.'.$field->id) }}</textarea>
                            @elseif($field->field_type === 'checkbox')
                                <div class="form-check">
                                    <input type="checkbox" name="custom[{{ $field->id }}]" value="1" class="form-check-input" id="cf{{ $field->id }}"
                                           {{ old('custom.'.$field->id) ? 'checked' : '' }} @if($field->required) required @endif>
                                    <label class="form-check-label" for="cf{{ $field->id }}">Sim</label>
                                </div>
                            @elseif(in_array($field->field_type, ['radio', 'select'], true) && !empty($field->options))
                                @if($field->field_type === 'select')
                                    <select name="custom[{{ $field->id }}]" class="form-select" @if($field->required) required @endif>
                                        <option value="">Selecione...</option>
                                        @foreach($field->options as $opt)
                                            <option value="{{ $opt }}" @selected(old('custom.'.$field->id) == $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    @foreach($field->options as $opt)
                                        <div class="form-check">
                                            <input type="radio" name="custom[{{ $field->id }}]" value="{{ $opt }}" class="form-check-input" id="cf{{ $field->id }}_{{ $loop->index }}"
                                                   @checked(old('custom.'.$field->id) == $opt) @if($field->required) required @endif>
                                            <label class="form-check-label" for="cf{{ $field->id }}_{{ $loop->index }}">{{ $opt }}</label>
                                        </div>
                                    @endforeach
                                @endif
                            @else
                                @php
                                    $normalizedFieldName = \Illuminate\Support\Str::of($field->name)->lower()->ascii()->replace('-', ' ')->replace('_', ' ')->replaceMatches('/\s+/', ' ')->trim()->value();
                                    $isAgeField = in_array($normalizedFieldName, ['idade', 'age'], true);
                                @endphp
                                <input
                                    type="{{ $isAgeField ? 'number' : 'text' }}"
                                    name="custom[{{ $field->id }}]"
                                    class="form-control"
                                    value="{{ old('custom.'.$field->id) }}"
                                    @if($isAgeField) step="1" min="0" max="130" inputmode="numeric" @endif
                                    @if($field->required) required @endif
                                >
                            @endif
                        </div>
                    @endforeach

                    <button type="submit" class="event-cta w-100 text-center">Enviar inscrição</button>
                </form>
            @endif
        </div>
    </div>
</section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-phone-mask').forEach(function (input) {
        var formatPhone = function (value) {
            var digits = (value || '').replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 2) return digits.length ? '(' + digits : '';
            if (digits.length <= 7) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
            return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7);
        };
        input.addEventListener('input', function () {
            input.value = formatPhone(input.value);
        });
        if (input.value) {
            input.value = formatPhone(input.value);
        }
    });

    var countdown = document.querySelector('.evx-countdown');
    if (countdown) {
        var eventDate = new Date(countdown.getAttribute('data-event-date')).getTime();
        var tick = function () {
            var now = Date.now();
            var diff = Math.max(0, eventDate - now);
            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            var minutes = Math.floor((diff / (1000 * 60)) % 60);
            var seconds = Math.floor((diff / 1000) % 60);
            countdown.querySelector('[data-part="days"]').textContent = days;
            countdown.querySelector('[data-part="hours"]').textContent = hours;
            countdown.querySelector('[data-part="minutes"]').textContent = minutes;
            countdown.querySelector('[data-part="seconds"]').textContent = seconds;
        };
        tick();
        setInterval(tick, 1000);
    }
});
</script>
@endpush
