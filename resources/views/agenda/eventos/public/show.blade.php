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

<section id="inscricao" class="evx-section light">
    <div class="container">
        <div class="event-form-card">
            <h3>Inscrição</h3>
            @if($event->is_paid)
                <div class="alert alert-warning">
                    <strong>Ingresso pago:</strong> R$ {{ number_format((float) ($event->price ?? 0), 2, ',', '.') }}<br>
                    <small>A vaga é confirmada automaticamente após aprovação do pagamento.</small>
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('duplicate_registration'))
                @php $jaInscrito = session('duplicate_registration'); @endphp
                <div class="alert alert-warning">
                    <strong>Você já está inscrito neste evento.</strong><br>
                    Inscrição nº {{ $jaInscrito['numero'] }}, feita em {{ $jaInscrito['criada_em'] }}.
                    @if($jaInscrito['pagamento_pendente'] ?? false)
                        <div class="mt-1">O pagamento continua em aberto — conclua abaixo para confirmar sua vaga.</div>
                    @endif
                    @if($jaInscrito['tem_telefone'])
                        <form method="post" action="{{ route('events.public.resend-receipt', $event->public_slug) }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="contato" value="{{ $jaInscrito['contato'] }}">
                            <button type="submit" class="btn btn-sm btn-outline-dark">
                                Reenviar meu comprovante por WhatsApp
                            </button>
                        </form>
                    @else
                        <div class="mt-1"><small>Fale com a organização do evento para receber o comprovante.</small></div>
                    @endif
                </div>
            @endif
            @if(!$event->registration_enabled)
                <p class="text-muted mb-0">Inscrições encerradas.</p>
            @elseif($spotsLeft !== null && $spotsLeft === 0)
                <p class="text-muted mb-0">Vagas esgotadas.</p>
            @else
                @if($spotsLeft !== null)
                    <p class="small text-muted">Vagas restantes: {{ $spotsLeft }}</p>
                @endif
                @if(is_array($pixPayment) && (($pixPayment['payment_method'] ?? 'pix') === 'pix') && !empty($pixPayment['qr_code_text']))
                    <div class="alert alert-info">
                        <strong>Pagamento pendente</strong><br>
                        Conclua o PIX abaixo para confirmar seu ingresso.
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            @if(!empty($pixPayment['qr_code_base64']))
                                <div class="text-center mb-2">
                                    <img src="data:image/png;base64,{{ $pixPayment['qr_code_base64'] }}" alt="QR Code PIX" style="max-width:260px; width:100%;">
                                </div>
                            @endif
                            <label class="form-label">Código copia e cola</label>
                            <textarea class="form-control" rows="4" readonly>{{ $pixPayment['qr_code_text'] }}</textarea>
                        </div>
                    </div>
                @elseif(is_array($pixPayment) && (($pixPayment['payment_method'] ?? '') !== 'pix'))
                    <div class="alert alert-info">
                        <strong>Pagamento em processamento</strong><br>
                        Método: cartão. Status atual: {{ $pixPayment['status'] ?? 'pending' }}.
                    </div>
                @endif
                <form method="post" action="{{ route('events.public.register', $event->public_slug) }}" id="event-registration-form">
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
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $useConfiguredPayer ? config('mercadopago.test_payer_email') : '') }}" @if($event->email_required) required @endif @if($useConfiguredPayer) readonly @endif>
                    </div>
                    @if($event->is_paid)
                        <div class="mb-3">
                            <label class="form-label">CPF para pagamento *</label>
                            <input type="text" id="payer_document" name="payer_document" class="form-control js-cpf-mask" value="{{ old('payer_document', $useConfiguredPayer ? config('mercadopago.test_payer_document') : '') }}" placeholder="Somente números" maxlength="14" required @if($useConfiguredPayer) readonly @endif>
                            @if($useConfiguredPayer)
                                <small class="text-muted d-block mt-1">Pagador de teste configurado via `.env` (MP_TEST_PAYER_EMAIL / MP_TEST_PAYER_DOCUMENT).</small>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Forma de pagamento *</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input js-payment-method" type="radio" name="payment_method" id="pay_pix" value="pix" {{ old('payment_method', 'pix') === 'pix' ? 'checked' : '' }}>
                                <label class="form-check-label" for="pay_pix">PIX</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input js-payment-method" type="radio" name="payment_method" id="pay_card" value="card" {{ old('payment_method') === 'card' ? 'checked' : '' }}>
                                <label class="form-check-label" for="pay_card">Cartão</label>
                            </div>
                        </div>
                        <div id="card-payment-box" class="border rounded p-3 mb-3 d-none">
                            <p class="small text-muted mb-2">Preencha os dados do cartão para pagamento imediato.</p>
                            <div class="mb-2">
                                <label class="form-label">Nome no cartão</label>
                                <input type="text" class="form-control js-card-field" id="form-checkout__cardholderName" autocomplete="cc-name">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Número do cartão</label>
                                <input type="text" class="form-control js-card-field" id="form-checkout__cardNumber" autocomplete="cc-number">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label">Validade</label>
                                    <input type="text" class="form-control js-card-field" id="form-checkout__expirationDate" placeholder="MM/AA" autocomplete="cc-exp">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control js-card-field" id="form-checkout__securityCode" autocomplete="cc-csc">
                                </div>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <label class="form-label">Bandeira</label>
                                    <input type="text" class="form-control" id="card_brand_display" placeholder="Preenchido automaticamente" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Banco emissor</label>
                                    <select class="form-select js-card-field" id="form-checkout__issuer"></select>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Parcelas</label>
                                <select class="form-select js-card-field" id="form-checkout__installments"></select>
                            </div>
                            <select id="form-checkout__identificationType" class="d-none"></select>
                            <input type="hidden" id="form-checkout__identificationNumber">
                            <input type="hidden" id="form-checkout__cardholderEmail">
                            <input type="hidden" name="card_token" id="card_token">
                            <input type="hidden" name="card_payment_method_id" id="card_payment_method_id">
                            <input type="hidden" name="card_issuer_id" id="card_issuer_id">
                            <input type="hidden" name="card_installments" id="card_installments">
                        </div>
                    @endif

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

                    <button type="submit" id="event-registration-submit" class="event-cta w-100 text-center">
                        <span class="js-submit-label">Enviar inscrição</span>
                        <span class="js-submit-spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</section>
</div>
@endsection

@push('scripts')
@if($event->is_paid && !empty($mercadoPagoPublicKey))
<script src="https://sdk.mercadopago.com/js/v2"></script>
@endif
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

    document.querySelectorAll('.js-cpf-mask').forEach(function (input) {
        var formatCpf = function (value) {
            var digits = (value || '').replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 3) return digits;
            if (digits.length <= 6) return digits.slice(0, 3) + '.' + digits.slice(3);
            if (digits.length <= 9) return digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6);
            return digits.slice(0, 3) + '.' + digits.slice(3, 6) + '.' + digits.slice(6, 9) + '-' + digits.slice(9);
        };
        input.addEventListener('input', function () {
            input.value = formatCpf(input.value);
        });
        if (input.value) {
            input.value = formatCpf(input.value);
        }
    });

    var formEl = document.getElementById('event-registration-form');
    var paymentRadios = document.querySelectorAll('.js-payment-method');
    var cardBox = document.getElementById('card-payment-box');
    var emailInput = document.getElementById('email');
    var payerDocInput = document.getElementById('payer_document');
    var hiddenCardEmail = document.getElementById('form-checkout__cardholderEmail');
    var hiddenCardDoc = document.getElementById('form-checkout__identificationNumber');
    var cardBrandDisplay = document.getElementById('card_brand_display');
    var allowNativeSubmit = false;
    var mpCardForm = null;
    var mpCardFormInitialized = false;

    var submitBtn = document.getElementById('event-registration-submit');
    var isSubmitting = false;

    var setSubmitting = function (state) {
        isSubmitting = state;
        if (!submitBtn) return;
        submitBtn.disabled = state;
        var label = submitBtn.querySelector('.js-submit-label');
        var spinner = submitBtn.querySelector('.js-submit-spinner');
        if (label) label.textContent = state ? 'Enviando...' : 'Enviar inscrição';
        if (spinner) spinner.classList.toggle('d-none', !state);
    };

    if (formEl) {
        // Primeira barreira contra inscrição duplicada: o segundo clique não vira
        // uma segunda requisição. Registrado antes do Mercado Pago para vir primeiro.
        formEl.addEventListener('submit', function (event) {
            if (isSubmitting) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }
            setSubmitting(true);
        });
    }

    // Voltar pelo histórico pode restaurar a página com o botão travado.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            setSubmitting(false);
        }
    });

    var syncCardPayerData = function () {
        if (hiddenCardEmail && emailInput) {
            hiddenCardEmail.value = emailInput.value || '';
        }
        if (hiddenCardDoc && payerDocInput) {
            hiddenCardDoc.value = (payerDocInput.value || '').replace(/\D/g, '');
        }
    };

    var getSelectedPaymentMethod = function () {
        var checked = document.querySelector('.js-payment-method:checked');
        return checked ? checked.value : 'pix';
    };

    var toggleCardBox = function () {
        if (!cardBox) return;
        var isCard = getSelectedPaymentMethod() === 'card';
        cardBox.classList.toggle('d-none', !isCard);
        cardBox.querySelectorAll('.js-card-field').forEach(function (field) {
            if (isCard) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        });

        // Em PIX, desmonta o cardForm para não interceptar submit com validações de cartão.
        if (!isCard && mpCardForm && typeof mpCardForm.unmount === 'function') {
            try {
                mpCardForm.unmount();
            } catch (e) {
                console.warn('Não foi possível desmontar cardForm', e);
            }
            mpCardForm = null;
            mpCardFormInitialized = false;
        }
    };

    var initCardFormIfNeeded = function () {
        if (mpCardFormInitialized || !formEl || !window.MercadoPago) {
            return;
        }

        try {
            var mp = new window.MercadoPago(@json($mercadoPagoPublicKey), { locale: 'pt-BR' });
            mpCardForm = mp.cardForm({
                amount: @json($eventPriceJs),
                iframe: false,
                form: {
                    id: 'event-registration-form',
                    cardNumber: { id: 'form-checkout__cardNumber' },
                    expirationDate: { id: 'form-checkout__expirationDate' },
                    securityCode: { id: 'form-checkout__securityCode' },
                    cardholderName: { id: 'form-checkout__cardholderName' },
                    issuer: { id: 'form-checkout__issuer' },
                    installments: { id: 'form-checkout__installments' },
                    identificationType: { id: 'form-checkout__identificationType' },
                    identificationNumber: { id: 'form-checkout__identificationNumber' },
                    cardholderEmail: { id: 'form-checkout__cardholderEmail' },
                },
                callbacks: {
                    onFormMounted: function (error) {
                        if (error) {
                            console.error('Falha ao montar formulário Mercado Pago', error);
                        }
                    },
                    onSubmit: function (event) {
                        if (allowNativeSubmit) {
                            return;
                        }
                        event.preventDefault();
                        if (getSelectedPaymentMethod() !== 'card') {
                            allowNativeSubmit = true;
                            formEl.submit();
                            return;
                        }

                        syncCardPayerData();
                        var cardData = mpCardForm.getCardFormData();
                        if (!cardData || !cardData.token) {
                            setSubmitting(false);
                            alert('Não foi possível tokenizar o cartão. Verifique os dados.');
                            return;
                        }

                        if (cardBrandDisplay) {
                            cardBrandDisplay.value = cardData.paymentMethodId || '';
                        }

                        document.getElementById('card_token').value = cardData.token || '';
                        document.getElementById('card_payment_method_id').value = cardData.paymentMethodId || '';
                        document.getElementById('card_issuer_id').value =
                            cardData.issuerId || document.getElementById('form-checkout__issuer')?.value || '';
                        document.getElementById('card_installments').value =
                            cardData.installments || document.getElementById('form-checkout__installments')?.value || '1';

                        allowNativeSubmit = true;
                        formEl.submit();
                    },
                },
            });
            mpCardFormInitialized = true;
        } catch (e) {
            console.error('Erro ao iniciar Mercado Pago cardForm', e);
        }
    };

    paymentRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            toggleCardBox();
            if (getSelectedPaymentMethod() === 'card') {
                initCardFormIfNeeded();
            }
        });
    });
    toggleCardBox();
    syncCardPayerData();
    emailInput && emailInput.addEventListener('input', syncCardPayerData);
    payerDocInput && payerDocInput.addEventListener('input', syncCardPayerData);

    @if($event->is_paid && !empty($mercadoPagoPublicKey))
    if (getSelectedPaymentMethod() === 'card') {
        initCardFormIfNeeded();
    }
    @endif

    // ------- Programação: abas de dia, accordion, toggle de visualização e "acontecendo agora" -------
    var schedule = document.getElementById('evx-schedule');
    if (schedule) {
        var dayTabs = document.querySelectorAll('.evx-day-tab');
        var dayPanes = schedule.querySelectorAll('.evx-day-pane');

        var activateDay = function (paneId) {
            dayPanes.forEach(function (pane) {
                pane.classList.toggle('active', pane.id === paneId);
            });
            dayTabs.forEach(function (tab) {
                var isActive = tab.getAttribute('data-day-target') === paneId;
                tab.classList.toggle('active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        };

        dayTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activateDay(tab.getAttribute('data-day-target'));
            });
        });

        // Accordion (visão compacta)
        schedule.querySelectorAll('.evx-tl-item.has-details .evx-tl-head').forEach(function (head) {
            head.addEventListener('click', function () {
                if (schedule.classList.contains('evx-detailed')) return;
                var item = head.closest('.evx-tl-item');
                var open = item.classList.toggle('open');
                head.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        // Toggle Compacta / Detalhada
        document.querySelectorAll('.evx-view-toggle button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.evx-view-toggle button').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                schedule.classList.toggle('evx-detailed', btn.getAttribute('data-view') === 'detailed');
            });
        });

        // Destaque do item "acontecendo agora" + scroll automático
        (function () {
            var now = new Date();
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var todayStr = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
            var todayPane = null;
            dayPanes.forEach(function (pane) {
                if (pane.getAttribute('data-date') === todayStr) todayPane = pane;
            });
            if (!todayPane) return;

            activateDay(todayPane.id);

            var nowMinutes = now.getHours() * 60 + now.getMinutes();
            var timed = Array.prototype.filter.call(
                todayPane.querySelectorAll('.evx-tl-item[data-minutes]'),
                function () { return true; }
            );
            if (!timed.length) return;

            var current = null;
            for (var i = 0; i < timed.length; i++) {
                var start = parseInt(timed[i].getAttribute('data-minutes'), 10);
                var next = (i + 1 < timed.length) ? parseInt(timed[i + 1].getAttribute('data-minutes'), 10) : (24 * 60);
                if (nowMinutes >= start && nowMinutes < next) {
                    current = timed[i];
                    break;
                }
            }
            if (!current) return;

            current.classList.add('evx-tl-now');
            if (!window.location.hash) {
                setTimeout(function () {
                    current.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 600);
            }
        })();
    }

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
