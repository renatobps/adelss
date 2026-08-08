@extends('layouts.porto')

@php
    use App\Models\CampaignReminderSetting;

    $isGlobal = $campaign === null;
    $title = $isGlobal ? 'Lembretes — configuração padrão' : 'Lembretes — ' . $campaign->name;
    $action = $isGlobal
        ? route('financial.campaigns.reminders.global.update')
        : route('financial.campaigns.reminders.update', $campaign);
    $inherits = ! $isGlobal && $settings->use_global;
@endphp

@section('title', $title)
@section('page-title', 'Lembretes automáticos')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.campaigns.index') }}">Campanhas</a></li>
    @if(! $isGlobal)
        <li><a href="{{ route('financial.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></li>
    @endif
    <li><span>Lembretes</span></li>
@endsection

@push('styles')
<style>
    .rm-var { cursor: pointer; font-size: .74rem; }
    .rm-preview {
        white-space: pre-wrap;
        background: #F7F9FB;
        border: 1px solid #EEF0F2;
        border-radius: 8px;
        padding: .75rem;
        min-height: 120px;
        font-size: .87rem;
        color: #2E353E;
    }
    .rm-template { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85rem; }
</style>
@endpush

@section('content')
@include('financial.campaigns.partials.alerts')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h5 class="mb-1">{{ $isGlobal ? 'Configuração padrão do sistema' : $campaign->name }}</h5>
        <p class="text-muted small mb-0">
            @if($isGlobal)
                Aplicada a toda campanha que não tiver configuração própria.
            @elseif($settings->paused)
                <span class="badge bg-warning text-dark">Pausada</span>
                Os lembretes desta campanha estão temporariamente desligados.
            @elseif($nextRun)
                <span class="badge bg-success">Ativo</span>
                Próximo envio em <strong>{{ $nextRun->format('d/m/Y \à\s H:i') }}</strong>.
            @else
                <span class="badge bg-secondary">Desativado</span>
                Nenhum lembrete será enviado nesta campanha.
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if(! $isGlobal)
            <a href="{{ route('financial.campaigns.reminders.batch', $campaign) }}" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-list-check me-1"></i>Prévia do próximo lote
            </a>
            <a href="{{ route('financial.campaigns.show', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-arrow-back me-1"></i>Voltar à campanha
            </a>
        @else
            <a href="{{ route('financial.campaigns.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-arrow-back me-1"></i>Voltar
            </a>
        @endif
    </div>
</div>

<form method="POST" action="{{ $action }}" id="remindersForm">
    @csrf
    @method('PUT')

    @if(! $isGlobal)
        <section class="card mb-3">
            <div class="card-body py-3">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="use_global" name="use_global" value="1"
                           @checked($inherits)>
                    <label class="form-check-label" for="use_global">
                        <strong>Usar a configuração padrão do sistema</strong>
                    </label>
                </div>
                <p class="text-muted small mb-2">
                    Desmarque para esta campanha ter regras próprias.
                    <a href="{{ route('financial.campaigns.reminders.global') }}">Ver a configuração padrão</a>.
                </p>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="paused" name="paused" value="1"
                           @checked($settings->paused)>
                    <label class="form-check-label" for="paused">
                        Pausar lembretes desta campanha (mantém as configurações)
                    </label>
                </div>
            </div>
        </section>
    @endif

    <div id="reminderFields" class="{{ $inherits ? 'opacity-50' : '' }}">
        <section class="card mb-3">
            <header class="card-header py-2">
                <h6 class="mb-0"><i class="bx bx-bell me-2"></i>Ativação</h6>
            </header>
            <div class="card-body py-3">
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="1"
                           @checked($settings->enabled)>
                    <label class="form-check-label" for="enabled"><strong>Enviar lembretes automáticos</strong></label>
                </div>
                <p class="text-muted small mb-0">
                    Só recebe lembrete quem tem parcela vencida em aberto. Quem está quitado, em dia
                    ou ainda não teve vencimento nunca é cobrado — essa regra não é configurável.
                </p>
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header py-2">
                <h6 class="mb-0"><i class="bx bx-message-detail me-2"></i>Textos da mensagem</h6>
            </header>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="mb-3">
                            <label class="form-label" for="template_1">1º lembrete (tom mais leve) *</label>
                            <textarea class="form-control rm-template js-template" id="template_1" name="template_1" rows="7"
                                      maxlength="2000" required>{{ old('template_1', $settings->template_1) }}</textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="tier_2_days">2º texto a partir de (dias de atraso)</label>
                                <input type="number" class="form-control" id="tier_2_days" name="tier_2_days" min="1" max="365"
                                       value="{{ old('tier_2_days', $settings->tier_2_days) }}" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="tier_3_days">3º texto a partir de (dias de atraso)</label>
                                <input type="number" class="form-control" id="tier_3_days" name="tier_3_days" min="1" max="365"
                                       value="{{ old('tier_3_days', $settings->tier_3_days) }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="template_2">2º lembrete</label>
                            <textarea class="form-control rm-template js-template" id="template_2" name="template_2" rows="6"
                                      maxlength="2000">{{ old('template_2', $settings->template_2) }}</textarea>
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="template_3">3º lembrete (tom mais direto)</label>
                            <textarea class="form-control rm-template js-template" id="template_3" name="template_3" rows="6"
                                      maxlength="2000">{{ old('template_3', $settings->template_3) }}</textarea>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <label class="form-label">Variáveis — clique para inserir</label>
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            @foreach(CampaignReminderSetting::VARIABLES as $var => $desc)
                                <button type="button" class="badge bg-light text-dark border rm-var js-insert-var"
                                        data-var="{{ $var }}" title="{{ $desc }}">{{ $var }}</button>
                            @endforeach
                        </div>

                        @if(! $isGlobal)
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Prévia com dados reais</span>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshPreview">
                                    <i class="bx bx-refresh"></i>
                                </button>
                            </label>
                            <div class="rm-preview" id="previewBox">{{ $preview['message'] ?? 'Nenhum patrocinador em atraso nesta campanha para gerar a prévia.' }}</div>
                            @if($preview)
                                <small class="text-muted d-block mt-1">Usando os dados de {{ $preview['sponsor']->name }}.</small>
                            @endif

                            <hr class="my-3">
                            <label class="form-label" for="testPhone">Enviar teste para</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="testPhone" placeholder="(00) 00000-0000">
                                <button type="button" class="btn btn-outline-primary" id="sendTest">
                                    <i class="bx bxl-whatsapp me-1"></i>Enviar
                                </button>
                            </div>
                            <small class="text-muted">O teste vai só para este número e não afeta ninguém da lista.</small>
                        @else
                            <div class="alert alert-info py-2 px-3 small mb-0">
                                <i class="bx bx-info-circle me-1"></i>
                                A prévia com dados reais e o envio de teste ficam na tela de lembretes de cada campanha.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header py-2">
                <h6 class="mb-0"><i class="bx bx-time-five me-2"></i>Frequência e repetição</h6>
            </header>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="days_between">Dias entre lembretes</label>
                        <input type="number" class="form-control" id="days_between" name="days_between" min="1" max="90"
                               value="{{ old('days_between', $settings->days_between) }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="max_reminders">Máximo por parcela</label>
                        <input type="number" class="form-control" id="max_reminders" name="max_reminders" min="1" max="10"
                               value="{{ old('max_reminders', $settings->max_reminders) }}" required>
                        <small class="text-muted">Ao atingir, o patrocinador é marcado na lista para tratamento manual.</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="send_time">Horário de envio</label>
                        <input type="time" class="form-control" id="send_time" name="send_time"
                               min="{{ sprintf('%02d:00', CampaignReminderSetting::QUIET_HOUR_START) }}"
                               max="{{ sprintf('%02d:59', CampaignReminderSetting::QUIET_HOUR_END - 1) }}"
                               value="{{ old('send_time', $settings->send_time) }}" required>
                        <small class="text-muted">
                            Permitido entre {{ CampaignReminderSetting::QUIET_HOUR_START }}h e
                            {{ CampaignReminderSetting::QUIET_HOUR_END }}h.
                        </small>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="daily_limit">Limite diário de mensagens</label>
                        <input type="number" class="form-control" id="daily_limit" name="daily_limit" min="1" max="500"
                               value="{{ old('daily_limit', $settings->daily_limit) }}" required>
                        <small class="text-muted">Contado sobre o número do WhatsApp, somando todas as campanhas.</small>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label d-block">Dias da semana permitidos</label>
                    @php $selectedDays = old('send_days', $settings->weekdays()); @endphp
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(CampaignReminderSetting::WEEKDAYS as $value => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="day_{{ $value }}" name="send_days[]"
                                       value="{{ $value }}" @checked(in_array($value, (array) $selectedDays))>
                                <label class="form-check-label" for="day_{{ $value }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">Domingo vem desmarcado por ser dia de culto.</small>
                </div>
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header py-2">
                <h6 class="mb-0"><i class="bx bx-paperclip me-2"></i>Carnê em PDF</h6>
            </header>
            <div class="card-body py-3">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="attach_pdf" name="attach_pdf" value="1"
                           @checked($settings->attach_pdf)>
                    <label class="form-check-label" for="attach_pdf">Anexar o carnê em PDF ao lembrete</label>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="attach_pdf_first_only"
                           name="attach_pdf_first_only" value="1" @checked($settings->attach_pdf_first_only)>
                    <label class="form-check-label" for="attach_pdf_first_only">
                        Apenas no primeiro lembrete de cada parcela
                    </label>
                </div>
                <small class="text-muted d-block mt-1">
                    Reenviar o mesmo PDF a cada lembrete consome dados do destinatário e pouco acrescenta.
                </small>
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header py-2">
                <h6 class="mb-0"><i class="bx bx-calendar-check me-2"></i>Aviso de vencimento próximo (não é cobrança)</h6>
            </header>
            <div class="card-body">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="courtesy_enabled"
                           name="courtesy_enabled" value="1" @checked($settings->courtesy_enabled)>
                    <label class="form-check-label" for="courtesy_enabled">
                        Avisar antes do vencimento
                    </label>
                </div>
                <p class="text-muted small">
                    Vai para quem está <strong>em dia</strong>, então contraria a regra de não incomodar
                    quem não deve nada. Por isso vem desligado e com texto próprio.
                </p>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="courtesy_days_before">Dias de antecedência</label>
                        <input type="number" class="form-control" id="courtesy_days_before" name="courtesy_days_before"
                               min="1" max="30" value="{{ old('courtesy_days_before', $settings->courtesy_days_before) }}" required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label" for="courtesy_template">Texto do aviso</label>
                        <textarea class="form-control rm-template" id="courtesy_template" name="courtesy_template" rows="4"
                                  maxlength="2000">{{ old('courtesy_template', $settings->courtesy_template) }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-3">
            <div class="card-body py-3">
                <h6 class="mb-2"><i class="bx bx-shield-quarter me-2"></i>Proteções fixas</h6>
                <ul class="text-muted small mb-0 ps-3">
                    <li>Intervalo aleatório de {{ CampaignReminderSetting::MIN_INTERVAL_SECONDS }} a
                        {{ CampaignReminderSetting::MAX_INTERVAL_SECONDS }} segundos entre mensagens — nunca em rajada.</li>
                    <li>Nada é enviado antes das {{ CampaignReminderSetting::QUIET_HOUR_START }}h nem depois das
                        {{ CampaignReminderSetting::QUIET_HOUR_END }}h.</li>
                    <li>A conexão do WhatsApp é verificada antes de iniciar a fila.</li>
                    <li>Cinco falhas seguidas interrompem o lote e avisam o administrador por e-mail.</li>
                    <li>Uma única mensagem por patrocinador, consolidando todas as parcelas vencidas.</li>
                </ul>
            </div>
        </section>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Salvar configuração</button>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let lastFocused = document.getElementById('template_1');

    document.querySelectorAll('.js-template, #courtesy_template').forEach(function (el) {
        el.addEventListener('focus', function () { lastFocused = el; });
    });

    document.querySelectorAll('.js-insert-var').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!lastFocused) return;
            const variable = btn.dataset.var;
            const start = lastFocused.selectionStart ?? lastFocused.value.length;
            const end = lastFocused.selectionEnd ?? lastFocused.value.length;
            lastFocused.value = lastFocused.value.slice(0, start) + variable + lastFocused.value.slice(end);
            lastFocused.focus();
            lastFocused.selectionStart = lastFocused.selectionEnd = start + variable.length;
            lastFocused.dispatchEvent(new Event('input'));
        });
    });

    // Herdando o padrão global, os campos abaixo não valem para esta campanha.
    const useGlobal = document.getElementById('use_global');
    const fields = document.getElementById('reminderFields');
    function applyInheritance() {
        if (!useGlobal || !fields) return;
        const inherit = useGlobal.checked;
        fields.classList.toggle('opacity-50', inherit);
        fields.querySelectorAll('input, textarea, select, button').forEach(function (el) {
            el.disabled = inherit;
        });
    }
    useGlobal?.addEventListener('change', applyInheritance);
    applyInheritance();

    @if(! $isGlobal)
    const previewBox = document.getElementById('previewBox');
    const previewUrl = '{{ route('financial.campaigns.reminders.preview', $campaign) }}';

    async function refreshPreview() {
        previewBox.textContent = 'Gerando prévia...';
        try {
            const resp = await fetch(previewUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ template: document.getElementById('template_1').value }),
            });
            const json = await resp.json();
            previewBox.textContent = json.message || 'Não foi possível gerar a prévia.';
        } catch (e) {
            previewBox.textContent = 'Não foi possível gerar a prévia.';
        }
    }

    document.getElementById('refreshPreview')?.addEventListener('click', refreshPreview);

    let previewTimer = null;
    document.getElementById('template_1')?.addEventListener('input', function () {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(refreshPreview, 600);
    });

    document.getElementById('sendTest')?.addEventListener('click', async function () {
        const phone = document.getElementById('testPhone').value.trim();
        if (!phone) { alert('Informe o número que vai receber o teste.'); return; }
        this.disabled = true;
        try {
            const resp = await fetch('{{ route('financial.campaigns.reminders.test', $campaign) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ phone: phone, template: document.getElementById('template_1').value }),
            });
            const json = await resp.json();
            alert(json.message || 'Teste processado.');
        } catch (e) {
            alert('Não foi possível enviar o teste.');
        } finally {
            this.disabled = false;
        }
    });
    @endif
});
</script>
@endpush
@endsection
