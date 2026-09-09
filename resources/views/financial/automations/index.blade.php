@extends('layouts.porto')

@section('title', 'Automações Financeiras')

@section('page-title', 'Automações Financeiras')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Automações</span></li>
@endsection

@section('content')
@php
    $canManage = Auth::user()?->can('financial.manage-automations');
    $eligibleIds = $settings['eligible_category_ids'] ?? [];
    $previewTemplate = $settings['message_template'] ?? \App\Models\FinancialAutomation::defaultMessageTemplate();
    $previewMessage = str_replace(
        ['{igreja}', '{nome}', '{valor}', '{tipo}'],
        ['ADEL São Sebastião', 'Maria Silva', '150,00', 'Dízimo'],
        $previewTemplate
    );
    $delayOptions = [
        0 => 'Imediato',
        5 => '5 minutos',
        15 => '15 minutos',
        30 => '30 minutos',
        60 => '1 hora',
    ];
    $hours = [];
    for ($h = 0; $h < 24; $h++) {
        $label = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
        $hours[$label] = $label;
    }
@endphp

<div class="financial-automations">
    <div class="financial-automations__header mb-4">
        <div class="d-flex align-items-center gap-2">
            <span class="financial-automations__spark"><i class="bx bxs-magic-wand"></i></span>
            <div>
                <h2 class="financial-automations__title mb-0">Automações financeiras</h2>
                <p class="financial-automations__subtitle mb-0">
                    Mensagens automáticas para membros, sem mexer no fluxo de envio existente.
                </p>
            </div>
        </div>
    </div>

    <div class="financial-automation-card" id="automation-contribution-thanks">
        <div class="financial-automation-card__head">
            <button type="button"
                    class="financial-automation-card__toggle-area"
                    data-bs-toggle="collapse"
                    data-bs-target="#contributionThanksBody"
                    aria-expanded="false"
                    aria-controls="contributionThanksBody">
                <span class="financial-automation-card__icon">
                    <i class="bx bxs-heart"></i>
                </span>
                <span class="financial-automation-card__copy text-start">
                    <span class="financial-automation-card__name">Agradecimento por contribuição</span>
                    <span class="financial-automation-card__desc">
                        Envia uma mensagem de agradecimento via WhatsApp ao membro sempre que uma contribuição é registrada.
                    </span>
                </span>
            </button>

            <div class="financial-automation-card__controls">
                <div class="form-check form-switch financial-automation-switch-wrap mb-0" onclick="event.stopPropagation()">
                    <input class="form-check-input financial-automation-switch"
                           type="checkbox"
                           role="switch"
                           id="contributionThanksEnabled"
                           data-toggle-url="{{ route('financial.automations.toggle', $automation) }}"
                           @checked($automation->enabled)
                           @disabled(!$canManage)>
                    <label class="form-check-label visually-hidden" for="contributionThanksEnabled">Habilitar</label>
                </div>
                <button type="button"
                        class="financial-automation-card__chevron"
                        data-bs-toggle="collapse"
                        data-bs-target="#contributionThanksBody"
                        aria-expanded="false"
                        aria-controls="contributionThanksBody">
                    <i class="bx bx-chevron-down"></i>
                </button>
            </div>
        </div>

        <div id="contributionThanksBody" class="collapse">
            <div class="financial-automation-card__body">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="financial-automation-stat">
                            <div class="financial-automation-stat__label">
                                <i class="bx bx-check-circle text-success"></i> Enviadas hoje
                            </div>
                            <div class="financial-automation-stat__value">{{ $stats['sent_today'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="financial-automation-stat">
                            <div class="financial-automation-stat__label">
                                <i class="bx bx-calendar text-primary"></i> Últimos 7 dias
                            </div>
                            <div class="financial-automation-stat__value">{{ $stats['sent_7d'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="financial-automation-stat">
                            <div class="financial-automation-stat__label">
                                <i class="bx bx-time-five text-warning"></i> Em fila
                            </div>
                            <div class="financial-automation-stat__value">{{ $stats['queued'] }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="financial-automation-stat">
                            <div class="financial-automation-stat__label">
                                <i class="bx bx-error-circle text-danger"></i> Com erro
                            </div>
                            <div class="financial-automation-stat__value">{{ $stats['failed'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="financial-automation-section-title">Histórico recente</h6>
                    <div class="financial-automation-legend mb-2">
                        <span><i class="bx bxs-circle text-secondary"></i> Pendente</span>
                        <span><i class="bx bxs-circle text-warning"></i> Em fila</span>
                        <span><i class="bx bxs-circle text-danger"></i> Com erro</span>
                    </div>
                    @if($recentLogs->isEmpty())
                        <p class="text-muted mb-0 small">Nenhum envio registrado ainda.</p>
                    @else
                        <ul class="list-unstyled financial-automation-history mb-0">
                            @foreach($recentLogs as $log)
                                <li class="financial-automation-history__item">
                                    <span class="financial-automation-history__status status-{{ $log->status }}">
                                        @if($log->status === 'sent')
                                            <i class="bx bx-check-circle text-success"></i>
                                        @else
                                            <i class="bx bx-error-circle text-danger"></i>
                                        @endif
                                    </span>
                                    <span class="financial-automation-history__meta">
                                        <strong>{{ $log->member?->name ?? 'Membro' }}</strong>
                                        <small class="text-muted">{{ $log->created_at?->format('d/m/Y H:i') }}</small>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="alert alert-light border financial-automation-alert d-flex gap-2 align-items-start">
                    <i class="bx bx-info-circle text-primary mt-1"></i>
                    <div class="small mb-0">
                        Envia apenas para transações de entrada (receita) pagas, com membro vinculado e telefone válido.
                        Respeita valor mínimo, limite diário e janela horária configurados abaixo.
                    </div>
                </div>

                <form method="POST" action="{{ route('financial.automations.update', $automation) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Categorias elegíveis</label>
                            <select name="eligible_category_ids[]"
                                    class="form-select"
                                    multiple
                                    size="5"
                                    @disabled(!$canManage)>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(in_array($category->id, $eligibleIds, true))>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Vazio = usa as categorias marcadas para comprovante (dízimo/oferta).</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="min_amount">Valor mínimo (R$)</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   class="form-control"
                                   id="min_amount"
                                   name="min_amount"
                                   value="{{ old('min_amount', $settings['min_amount']) }}"
                                   @disabled(!$canManage)>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="daily_limit">Limite diário (anti-bloqueio)</label>
                            <input type="number"
                                   min="1"
                                   max="30"
                                   class="form-control"
                                   id="daily_limit"
                                   name="daily_limit"
                                   value="{{ old('daily_limit', $settings['daily_limit']) }}"
                                   @disabled(!$canManage)>
                            <div class="form-text">Máximo 30 mensagens/dia.</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="window_start">Janela início</label>
                            <select class="form-select" id="window_start" name="window_start" @disabled(!$canManage)>
                                @foreach($hours as $value => $label)
                                    <option value="{{ $value }}" @selected(old('window_start', $settings['window_start']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="window_end">Janela fim</label>
                            <select class="form-select" id="window_end" name="window_end" @disabled(!$canManage)>
                                @foreach($hours as $value => $label)
                                    <option value="{{ $value }}" @selected(old('window_end', $settings['window_end']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="delay_minutes">Atraso após registrar</label>
                            <select class="form-select" id="delay_minutes" name="delay_minutes" @disabled(!$canManage)>
                                @foreach($delayOptions as $value => $label)
                                    <option value="{{ $value }}" @selected((int) old('delay_minutes', $settings['delay_minutes']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="message_template">Mensagem personalizada (opcional)</label>
                            <textarea class="form-control"
                                      id="message_template"
                                      name="message_template"
                                      rows="7"
                                      @disabled(!$canManage)>{{ old('message_template', $settings['message_template']) }}</textarea>
                            <div class="form-text">Placeholders: <code>{igreja}</code>, <code>{nome}</code>, <code>{valor}</code>, <code>{tipo}</code></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Pré-visualização</label>
                            <div class="financial-automation-preview" id="messagePreview">{!! nl2br(e($previewMessage)) !!}</div>
                        </div>
                    </div>

                    @if($canManage)
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Salvar configuração
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="financial-automation-card mt-3" id="automation-treasurers">
        <div class="financial-automation-card__head">
            <button type="button"
                    class="financial-automation-card__toggle-area"
                    data-bs-toggle="collapse"
                    data-bs-target="#treasurersBody"
                    aria-expanded="false"
                    aria-controls="treasurersBody">
                <span class="financial-automation-card__icon">
                    <i class="bx bxs-group"></i>
                </span>
                <span class="financial-automation-card__copy text-start">
                    <span class="financial-automation-card__name">Tesoureiros (destinatários)</span>
                    <span class="financial-automation-card__desc">
                        Cadastre até {{ $maxTreasurers }} pessoas para receber os lembretes e resumos financeiros desta congregação pelo WhatsApp.
                    </span>
                </span>
            </button>

            <div class="financial-automation-card__controls">
                <div class="form-check form-switch financial-automation-switch-wrap mb-0" onclick="event.stopPropagation()">
                    <input class="form-check-input financial-automation-switch"
                           type="checkbox"
                           role="switch"
                           id="treasurersEnabled"
                           data-toggle-url="{{ route('financial.automations.toggle', $treasurersAutomation) }}"
                           @checked($treasurersAutomation->enabled)
                           @disabled(!$canManage)>
                    <label class="form-check-label visually-hidden" for="treasurersEnabled">Habilitar</label>
                </div>
                <button type="button"
                        class="financial-automation-card__chevron"
                        data-bs-toggle="collapse"
                        data-bs-target="#treasurersBody"
                        aria-expanded="false"
                        aria-controls="treasurersBody">
                    <i class="bx bx-chevron-down"></i>
                </button>
            </div>
        </div>

        <div id="treasurersBody" class="collapse">
            <div class="financial-automation-card__body">
                <form method="POST" action="{{ route('financial.automations.update', $treasurersAutomation) }}" id="treasurersForm">
                    @csrf
                    @method('PUT')

                    <div id="treasurersList"
                         data-max="{{ $maxTreasurers }}"
                         data-search-url="{{ route('financial.automations.members.search') }}"
                         data-test-url="{{ route('financial.automations.test-treasurer', $treasurersAutomation) }}">
                        @foreach($treasurerRecipients as $index => $recipient)
                            <div class="financial-treasurer-row" data-index="{{ $index }}">
                                <div class="financial-treasurer-row__header">
                                    <strong class="financial-treasurer-row__title">Tesoureiro {{ $index + 1 }}</strong>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($canManage)
                                            <button type="button" class="btn btn-sm btn-outline-primary js-pick-member">
                                                <i class="bx bx-user-plus"></i> Escolher da membresia
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-remove-treasurer {{ $index === 0 ? 'd-none' : '' }}"
                                                    title="Remover">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <input type="hidden"
                                       name="recipients[{{ $index }}][member_id]"
                                       class="js-member-id"
                                       value="{{ $recipient['member_id'] ?? '' }}">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nome (opcional)</label>
                                        <input type="text"
                                               class="form-control js-treasurer-name"
                                               name="recipients[{{ $index }}][name]"
                                               value="{{ $recipient['name'] ?? '' }}"
                                               placeholder="Ex: João Silva"
                                               @disabled(!$canManage)>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">WhatsApp <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control js-treasurer-phone"
                                               name="recipients[{{ $index }}][phone]"
                                               value="{{ $recipient['phone'] ?? '' }}"
                                               placeholder="(11) 99999-9999"
                                               @disabled(!$canManage)>
                                    </div>
                                </div>

                                @if($canManage)
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary js-send-test">
                                            <i class="bx bx-send"></i> Enviar teste
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($canManage)
                        <button type="button"
                                class="btn btn-outline-primary btn-sm mt-3"
                                id="addTreasurerBtn"
                                @disabled(count($treasurerRecipients) >= $maxTreasurers)>
                            <i class="bx bx-plus"></i> Adicionar {{ count($treasurerRecipients) >= 1 ? 'outro' : '' }} tesoureiro
                        </button>
                    @endif

                    <div class="alert alert-light border financial-automation-alert d-flex gap-2 align-items-start mt-3 mb-0">
                        <i class="bx bx-info-circle text-primary mt-1"></i>
                        <div class="small mb-0">
                            Cadastre ao menos um tesoureiro antes de ativar lembretes ou resumos.
                        </div>
                    </div>

                    @if($canManage)
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Salvar
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="financial-automation-card mt-3" id="automation-mp-treasury-group">
        <div class="financial-automation-card__head">
            <button type="button"
                    class="financial-automation-card__toggle-area"
                    data-bs-toggle="collapse"
                    data-bs-target="#mpTreasuryGroupBody"
                    aria-expanded="false"
                    aria-controls="mpTreasuryGroupBody">
                <span class="financial-automation-card__icon">
                    <i class="bx bxl-whatsapp"></i>
                </span>
                <span class="financial-automation-card__copy text-start">
                    <span class="financial-automation-card__name">Grupo WhatsApp da tesouraria</span>
                    <span class="financial-automation-card__desc">
                        Avisa o grupo sempre que entrar ou sair dinheiro na conta Mercado Pago.
                    </span>
                </span>
            </button>

            <div class="financial-automation-card__controls">
                <div class="form-check form-switch financial-automation-switch-wrap mb-0" onclick="event.stopPropagation()">
                    <input class="form-check-input financial-automation-switch"
                           type="checkbox"
                           role="switch"
                           id="mpTreasuryGroupEnabled"
                           data-toggle-url="{{ route('financial.automations.toggle', $mpTreasuryGroupAutomation) }}"
                           @checked($mpTreasuryGroupAutomation->enabled)
                           @disabled(!$canManage)>
                    <label class="form-check-label visually-hidden" for="mpTreasuryGroupEnabled">Habilitar</label>
                </div>
                <button type="button"
                        class="financial-automation-card__chevron"
                        data-bs-toggle="collapse"
                        data-bs-target="#mpTreasuryGroupBody"
                        aria-expanded="false"
                        aria-controls="mpTreasuryGroupBody">
                    <i class="bx bx-chevron-down"></i>
                </button>
            </div>
        </div>

        <div id="mpTreasuryGroupBody" class="collapse">
            <div class="financial-automation-card__body">
                <form method="POST" action="{{ route('financial.automations.update', $mpTreasuryGroupAutomation) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label" for="mp_whatsapp_group_jid">Grupo da tesouraria</label>
                        <div class="input-group">
                            <select name="whatsapp_group_jid"
                                    id="mp_whatsapp_group_jid"
                                    class="form-select"
                                    @disabled(!$canManage)>
                                <option value="">Carregando grupos...</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="mpWhatsappGroupsRefresh" title="Atualizar lista">
                                <i class="bx bx-refresh"></i>
                            </button>
                        </div>
                        <input type="hidden"
                               name="whatsapp_group_name"
                               id="mp_whatsapp_group_name"
                               value="{{ $mpTreasuryGroupSettings['whatsapp_group_name'] ?? '' }}">
                        @error('whatsapp_group_jid')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Grupos da instância WhatsApp em Notificações → Configuração WPP. A mensagem inclui valor, descrição e o saldo atual da carteira.
                        </div>
                    </div>

                    @if($canManage)
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Salvar
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Lembrete de despesas a vencer --}}
    <div class="financial-automation-card mt-3" id="automation-due-reminder">
        <div class="financial-automation-card__head">
            <button type="button"
                    class="financial-automation-card__toggle-area"
                    data-bs-toggle="collapse"
                    data-bs-target="#dueReminderBody"
                    aria-expanded="false"
                    aria-controls="dueReminderBody">
                <span class="financial-automation-card__icon">
                    <i class="bx bx-calendar-exclamation"></i>
                </span>
                <span class="financial-automation-card__copy text-start">
                    <span class="financial-automation-card__name">Lembrete de despesas a vencer</span>
                    <span class="financial-automation-card__desc">
                        Envia ao tesoureiro um aviso antes do vencimento de contas pendentes.
                    </span>
                </span>
            </button>

            <div class="financial-automation-card__controls">
                <div class="form-check form-switch financial-automation-switch-wrap mb-0" onclick="event.stopPropagation()">
                    <input class="form-check-input financial-automation-switch"
                           type="checkbox"
                           role="switch"
                           id="dueReminderEnabled"
                           data-toggle-url="{{ route('financial.automations.toggle', $dueReminderAutomation) }}"
                           @checked($dueReminderAutomation->enabled)
                           @disabled(!$canManage)>
                    <label class="form-check-label visually-hidden" for="dueReminderEnabled">Habilitar</label>
                </div>
                <button type="button"
                        class="financial-automation-card__chevron"
                        data-bs-toggle="collapse"
                        data-bs-target="#dueReminderBody"
                        aria-expanded="false"
                        aria-controls="dueReminderBody">
                    <i class="bx bx-chevron-down"></i>
                </button>
            </div>
        </div>

        <div id="dueReminderBody" class="collapse">
            <div class="financial-automation-card__body">
                <form method="POST" action="{{ route('financial.automations.update', $dueReminderAutomation) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="days_ahead">Avisar com antecedência de</label>
                            <select class="form-select" id="days_ahead" name="days_ahead" @disabled(!$canManage)>
                                @foreach([0 => 'No dia do vencimento', 1 => '1 dia antes', 2 => '2 dias antes', 3 => '3 dias antes', 5 => '5 dias antes', 7 => '7 dias antes'] as $value => $label)
                                    <option value="{{ $value }}" @selected((int) old('days_ahead', $dueReminderSettings['days_ahead']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="due_send_time">Horário de envio</label>
                            <select class="form-select" id="due_send_time" name="send_time" @disabled(!$canManage)>
                                @foreach($hourOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('send_time', $dueReminderSettings['send_time']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="due_min_amount">Valor mínimo (R$)</label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   class="form-control"
                                   id="due_min_amount"
                                   name="min_amount"
                                   value="{{ old('min_amount', $dueReminderSettings['min_amount']) }}"
                                   @disabled(!$canManage)>
                            <div class="form-text">Despesas abaixo desse valor são ignoradas.</div>
                        </div>
                        <div class="col-12">
                            <div class="financial-automation-inline-toggle">
                                <div>
                                    <div class="fw-semibold">Enviar um segundo lembrete</div>
                                    <div class="text-muted small">Envia também no dia do vencimento (quando a antecedência for maior que zero).</div>
                                </div>
                                <div class="form-check form-switch financial-automation-switch-wrap mb-0">
                                    <input class="form-check-input financial-automation-switch"
                                           type="checkbox"
                                           role="switch"
                                           id="second_reminder"
                                           name="second_reminder"
                                           value="1"
                                           @checked(old('second_reminder', $dueReminderSettings['second_reminder']))
                                           @disabled(!$canManage)>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="due_message_template">Mensagem personalizada (opcional)</label>
                            <textarea class="form-control"
                                      id="due_message_template"
                                      name="message_template"
                                      rows="8"
                                      @disabled(!$canManage)>{{ old('message_template', $dueReminderSettings['message_template']) }}</textarea>
                            <div class="form-text">
                                Placeholders: <code>{igreja}</code>, <code>{tesoureiro}</code>, <code>{dias}</code>,
                                <code>{quantidade}</code>, <code>{total}</code>, <code>{lista}</code>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pré-visualização</label>
                            <div class="financial-automation-preview" id="dueMessagePreview"></div>
                        </div>
                    </div>

                    @if($canManage)
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Salvar
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Resumo financeiro inteligente --}}
    <div class="financial-automation-card mt-3" id="automation-smart-summary">
        <div class="financial-automation-card__head">
            <button type="button"
                    class="financial-automation-card__toggle-area"
                    data-bs-toggle="collapse"
                    data-bs-target="#smartSummaryBody"
                    aria-expanded="false"
                    aria-controls="smartSummaryBody">
                <span class="financial-automation-card__icon">
                    <i class="bx bx-bar-chart-alt-2"></i>
                </span>
                <span class="financial-automation-card__copy text-start">
                    <span class="financial-automation-card__name">Resumo financeiro inteligente</span>
                    <span class="financial-automation-card__desc">
                        Envia um resumo com entradas, saídas, saldo e comparação com o período anterior.
                    </span>
                </span>
            </button>

            <div class="financial-automation-card__controls">
                <div class="form-check form-switch financial-automation-switch-wrap mb-0" onclick="event.stopPropagation()">
                    <input class="form-check-input financial-automation-switch"
                           type="checkbox"
                           role="switch"
                           id="smartSummaryEnabled"
                           data-toggle-url="{{ route('financial.automations.toggle', $smartSummaryAutomation) }}"
                           @checked($smartSummaryAutomation->enabled)
                           @disabled(!$canManage)>
                    <label class="form-check-label visually-hidden" for="smartSummaryEnabled">Habilitar</label>
                </div>
                <button type="button"
                        class="financial-automation-card__chevron"
                        data-bs-toggle="collapse"
                        data-bs-target="#smartSummaryBody"
                        aria-expanded="false"
                        aria-controls="smartSummaryBody">
                    <i class="bx bx-chevron-down"></i>
                </button>
            </div>
        </div>

        <div id="smartSummaryBody" class="collapse">
            <div class="financial-automation-card__body">
                <form method="POST" action="{{ route('financial.automations.update', $smartSummaryAutomation) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="summary_frequency">Frequência</label>
                            <select class="form-select" id="summary_frequency" name="frequency" @disabled(!$canManage)>
                                <option value="monthly" @selected(old('frequency', $smartSummarySettings['frequency']) === 'monthly')>Mensal</option>
                                <option value="weekly" @selected(old('frequency', $smartSummarySettings['frequency']) === 'weekly')>Semanal</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="day_of_month">Dia do mês</label>
                            <select class="form-select" id="day_of_month" name="day_of_month" @disabled(!$canManage)>
                                @for($d = 1; $d <= 28; $d++)
                                    <option value="{{ $d }}" @selected((int) old('day_of_month', $smartSummarySettings['day_of_month']) === $d)>{{ $d }}</option>
                                @endfor
                            </select>
                            <div class="form-text">Na frequência semanal, 1 = segunda … 7 = domingo.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="summary_send_time">Horário</label>
                            <select class="form-select" id="summary_send_time" name="send_time" @disabled(!$canManage)>
                                @foreach($hourOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('send_time', $smartSummarySettings['send_time']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="financial-automation-inline-toggle">
                                <div>
                                    <div class="fw-semibold">Comparar com o período anterior</div>
                                    <div class="text-muted small">Mostra variação percentual de entradas, saídas e saldo.</div>
                                </div>
                                <div class="form-check form-switch financial-automation-switch-wrap mb-0">
                                    <input class="form-check-input financial-automation-switch"
                                           type="checkbox"
                                           role="switch"
                                           id="compare_previous"
                                           name="compare_previous"
                                           value="1"
                                           @checked(old('compare_previous', $smartSummarySettings['compare_previous']))
                                           @disabled(!$canManage)>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="opening_message">Mensagem de abertura (opcional)</label>
                            <textarea class="form-control"
                                      id="opening_message"
                                      name="opening_message"
                                      rows="3"
                                      placeholder="Ex: Segue o resumo do mês para acompanhamento da tesouraria."
                                      @disabled(!$canManage)>{{ old('opening_message', $smartSummarySettings['opening_message']) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Exemplo do resumo</label>
                            <div class="financial-automation-preview">📊 *ADEL São Sebastião — Resumo financeiro*
*Período:* 01/06 a 30/06/2026

🟢 *Entradas:* R$ 18.500,00 (+12%)
🔴 *Saídas:* R$ 12.300,00 (-5%)
💰 *Saldo:* R$ 6.200,00 (+28%)

*Top 3 Entradas*
• Dízimos — R$ 10.000,00
• Ofertas — R$ 5.500,00
• Missões — R$ 3.000,00

*Top 3 Saídas*
• Aluguel — R$ 3.500,00
• Energia — R$ 850,00
• Pastor — R$ 4.000,00

⏳ *A vencer (7 dias):* R$ 2.100,00
⚠️ *Vencidas em aberto:* R$ 450,00</div>
                        </div>
                    </div>

                    @if($canManage)
                        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                            <button type="button"
                                    class="btn btn-outline-primary"
                                    id="sendSmartSummaryNowBtn"
                                    data-send-url="{{ route('financial.automations.send-smart-summary', $smartSummaryAutomation) }}">
                                <i class="bx bx-send me-1"></i> Enviar agora
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Salvar
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal: escolher membro --}}
<div class="modal fade" id="pickMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Escolher da membresia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="search"
                       class="form-control mb-3"
                       id="pickMemberSearch"
                       placeholder="Buscar por nome ou telefone..."
                       autocomplete="off">
                <div id="pickMemberResults" class="list-group list-group-flush">
                    <div class="text-muted small px-1">Carregando membros...</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.financial-automations__spark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: #eff6ff;
    color: #2563eb;
    flex-shrink: 0;
}
.financial-automations__title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}
.financial-automations__subtitle {
    font-size: 0.9rem;
    color: #6b7280;
}
.financial-automation-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.85rem;
    overflow: hidden;
}
.financial-automation-card__head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.1rem;
}
.financial-automation-card__toggle-area {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    padding: 0;
    text-align: left;
}
.financial-automation-card__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.65rem;
    background: #dbeafe;
    color: #2563eb;
    flex-shrink: 0;
    font-size: 1.25rem;
}
.financial-automation-card__name {
    display: block;
    font-weight: 700;
    color: #1f2937;
    font-size: 1rem;
}
.financial-automation-card__desc {
    display: block;
    color: #6b7280;
    font-size: 0.875rem;
    line-height: 1.35;
    margin-top: 0.15rem;
}
.financial-automation-card__controls {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-shrink: 0;
}
.financial-automation-card__chevron {
    border: 0;
    background: transparent;
    color: #9ca3af;
    font-size: 1.35rem;
    line-height: 1;
    padding: 0.15rem;
    transition: transform 0.2s ease;
}
.financial-automation-card__body {
    border-top: 1px solid #f3f4f6;
    padding: 1.15rem 1.1rem 1.25rem;
}
.financial-automation-stat {
    background: #f9fafb;
    border: 1px solid #f3f4f6;
    border-radius: 0.65rem;
    padding: 0.85rem 0.95rem;
}
.financial-automation-stat__label {
    font-size: 0.8rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.financial-automation-stat__value {
    margin-top: 0.35rem;
    font-size: 1.35rem;
    font-weight: 700;
    color: #111827;
}
.financial-automation-section-title {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}
.financial-automation-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    font-size: 0.8rem;
    color: #6b7280;
}
.financial-automation-legend span {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.financial-automation-legend i {
    font-size: 0.55rem;
}
.financial-automation-history__item {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.45rem 0;
    border-bottom: 1px solid #f3f4f6;
}
.financial-automation-history__meta {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}
.financial-automation-alert {
    border-radius: 0.65rem;
}
.financial-automation-preview {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.65rem;
    padding: 1rem 1.1rem;
    white-space: pre-wrap;
    font-size: 0.92rem;
    color: #374151;
    line-height: 1.45;
}
.financial-automation-switch-wrap {
    display: flex;
    align-items: center;
    min-height: 1.85rem;
    padding-left: 3.25rem;
    margin-bottom: 0;
}
.financial-automation-switch-wrap .form-check-input.financial-automation-switch {
    width: 3rem !important;
    height: 1.65rem !important;
    margin-top: 0;
    margin-left: -3.25rem !important;
    cursor: pointer;
    flex-shrink: 0;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%280,0,0,0.25%29'/%3e%3c/svg%3e") !important;
    background-position: left center !important;
    background-size: contain !important;
    border-radius: 2rem !important;
    border: 1px solid rgba(0, 0, 0, 0.12);
    transition: background-position 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}
.financial-automation-switch-wrap .form-check-input.financial-automation-switch:checked {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e") !important;
    background-position: right center !important;
}
.financial-automation-switch-wrap .form-check-input.financial-automation-switch:focus {
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.2);
}
.financial-automation-card + .financial-automation-card {
    margin-top: 0.85rem;
}
.financial-automation-card:has(.collapse:not(.show)) .financial-automation-card__chevron i {
    transform: rotate(-90deg);
}
.financial-treasurer-row {
    border: 1px solid #eef2f7;
    border-radius: 0.75rem;
    padding: 1rem;
    background: #fafbfc;
}
.financial-treasurer-row + .financial-treasurer-row {
    margin-top: 0.85rem;
}
.financial-treasurer-row__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
    flex-wrap: wrap;
}
.financial-treasurer-row__title {
    color: #1f2937;
    font-size: 0.95rem;
}
.financial-automation-inline-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.9rem 1rem;
    border: 1px solid #eef2f7;
    border-radius: 0.75rem;
    background: #fafbfc;
}
@media (max-width: 575.98px) {
    .financial-automation-inline-toggle {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const templateEl = document.getElementById('message_template');
    const previewEl = document.getElementById('messagePreview');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const canManage = {{ $canManage ? 'true' : 'false' }};

    function renderPreview() {
        if (!templateEl || !previewEl) return;
        const raw = templateEl.value || '';
        const filled = raw
            .replaceAll('{igreja}', 'ADEL São Sebastião')
            .replaceAll('{nome}', 'Maria Silva')
            .replaceAll('{valor}', '150,00')
            .replaceAll('{tipo}', 'Dízimo');
        previewEl.textContent = filled;
    }

    templateEl?.addEventListener('input', renderPreview);

    document.querySelectorAll('.financial-automation-switch[data-toggle-url]').forEach((switchEl) => {
        switchEl.addEventListener('change', async function () {
            if (switchEl.disabled) return;
            const enabled = switchEl.checked;
            const url = switchEl.dataset.toggleUrl;
            switchEl.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ enabled }),
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    switchEl.checked = !enabled;
                    alert(data.message || 'Não foi possível atualizar a automação.');
                }
            } catch (e) {
                switchEl.checked = !enabled;
                alert('Erro ao atualizar a automação.');
            } finally {
                switchEl.disabled = !canManage;
            }
        });
    });

    function bindCollapse(targetId) {
        const collapseEl = document.getElementById(targetId);
        const triggers = document.querySelectorAll('[data-bs-target="#' + targetId + '"]');
        collapseEl?.addEventListener('show.bs.collapse', () => {
            triggers.forEach((el) => el.setAttribute('aria-expanded', 'true'));
        });
        collapseEl?.addEventListener('hide.bs.collapse', () => {
            triggers.forEach((el) => el.setAttribute('aria-expanded', 'false'));
        });
    }

    bindCollapse('contributionThanksBody');
    bindCollapse('treasurersBody');
    bindCollapse('dueReminderBody');
    bindCollapse('smartSummaryBody');

    const dueTemplateEl = document.getElementById('due_message_template');
    const duePreviewEl = document.getElementById('dueMessagePreview');
    function renderDuePreview() {
        if (!dueTemplateEl || !duePreviewEl) return;
        const sampleList = [
            '• Aluguel — R$ 3.500,00 (21/07)',
            '• Energia — R$ 450,00 (22/07)',
            '• Internet — R$ 300,00 (23/07)',
        ].join('\n');
        const filled = (dueTemplateEl.value || '')
            .replaceAll('{igreja}', 'ADEL São Sebastião')
            .replaceAll('{tesoureiro}', 'João')
            .replaceAll('{dias}', '1')
            .replaceAll('{quantidade}', '3')
            .replaceAll('{total}', 'R$ 4.250,00')
            .replaceAll('{lista}', sampleList);
        duePreviewEl.textContent = filled;
    }
    dueTemplateEl?.addEventListener('input', renderDuePreview);
    renderDuePreview();

    const sendSummaryBtn = document.getElementById('sendSmartSummaryNowBtn');
    sendSummaryBtn?.addEventListener('click', async () => {
        if (!confirm('Enviar o resumo financeiro agora para os destinatários cadastrados?')) {
            return;
        }

        const url = sendSummaryBtn.dataset.sendUrl;
        sendSummaryBtn.disabled = true;
        const originalHtml = sendSummaryBtn.innerHTML;
        sendSummaryBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Enviando...';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            });
            const data = await response.json();
            alert(data.message || (response.ok ? 'Resumo enviado.' : 'Falha ao enviar o resumo.'));
        } catch (e) {
            alert('Erro ao enviar o resumo.');
        } finally {
            sendSummaryBtn.disabled = false;
            sendSummaryBtn.innerHTML = originalHtml;
        }
    });

    // --- Tesoureiros ---
    const listEl = document.getElementById('treasurersList');
    const addBtn = document.getElementById('addTreasurerBtn');
    if (!listEl) return;

    const maxTreasurers = parseInt(listEl.dataset.max || '5', 10);
    const searchUrl = listEl.dataset.searchUrl;
    const testUrl = listEl.dataset.testUrl;
    let activePickRow = null;
    let searchTimer = null;

    const pickModalEl = document.getElementById('pickMemberModal');
    const pickSearchEl = document.getElementById('pickMemberSearch');
    const pickResultsEl = document.getElementById('pickMemberResults');
    const pickModal = pickModalEl && window.bootstrap
        ? bootstrap.Modal.getOrCreateInstance(pickModalEl)
        : null;

    function maskPhone(value) {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 11);
        if (digits.length <= 2) return digits.length ? `(${digits}` : '';
        if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        if (digits.length <= 10) {
            return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
        }
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    }

    function reindexRows() {
        const rows = [...listEl.querySelectorAll('.financial-treasurer-row')];
        rows.forEach((row, index) => {
            row.dataset.index = String(index);
            const title = row.querySelector('.financial-treasurer-row__title');
            if (title) title.textContent = `Tesoureiro ${index + 1}`;

            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/recipients\[\d+]/, `recipients[${index}]`);
            });

            const removeBtn = row.querySelector('.js-remove-treasurer');
            if (removeBtn) {
                removeBtn.classList.toggle('d-none', rows.length === 1);
            }
        });

        if (addBtn) {
            addBtn.disabled = rows.length >= maxTreasurers;
            addBtn.innerHTML = rows.length >= 1
                ? '<i class="bx bx-plus"></i> Adicionar outro tesoureiro'
                : '<i class="bx bx-plus"></i> Adicionar tesoureiro';
        }
    }

    function createRow(data = {}) {
        const index = listEl.querySelectorAll('.financial-treasurer-row').length;
        const wrap = document.createElement('div');
        wrap.className = 'financial-treasurer-row';
        wrap.dataset.index = String(index);
        wrap.innerHTML = `
            <div class="financial-treasurer-row__header">
                <strong class="financial-treasurer-row__title">Tesoureiro ${index + 1}</strong>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary js-pick-member">
                        <i class="bx bx-user-plus"></i> Escolher da membresia
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger js-remove-treasurer" title="Remover">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
            <input type="hidden" name="recipients[${index}][member_id]" class="js-member-id" value="${data.member_id || ''}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome (opcional)</label>
                    <input type="text" class="form-control js-treasurer-name" name="recipients[${index}][name]"
                           value="${data.name || ''}" placeholder="Ex: João Silva">
                </div>
                <div class="col-md-6">
                    <label class="form-label">WhatsApp <span class="text-danger">*</span></label>
                    <input type="text" class="form-control js-treasurer-phone" name="recipients[${index}][phone]"
                           value="${data.phone || ''}" placeholder="(11) 99999-9999">
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary js-send-test">
                    <i class="bx bx-send"></i> Enviar teste
                </button>
            </div>
        `;
        return wrap;
    }

    addBtn?.addEventListener('click', () => {
        if (listEl.querySelectorAll('.financial-treasurer-row').length >= maxTreasurers) return;
        listEl.appendChild(createRow());
        reindexRows();
    });

    listEl.addEventListener('click', async (event) => {
        const row = event.target.closest('.financial-treasurer-row');
        if (!row) return;

        if (event.target.closest('.js-remove-treasurer')) {
            if (listEl.querySelectorAll('.financial-treasurer-row').length <= 1) return;
            row.remove();
            reindexRows();
            return;
        }

        if (event.target.closest('.js-pick-member')) {
            activePickRow = row;
            if (pickSearchEl) pickSearchEl.value = '';
            if (pickResultsEl) {
                pickResultsEl.innerHTML = '<div class="text-muted small px-1">Carregando membros...</div>';
            }
            pickModal?.show();
            runMemberSearch('');
            setTimeout(() => pickSearchEl?.focus(), 200);
            return;
        }

        if (event.target.closest('.js-send-test')) {
            const btn = event.target.closest('.js-send-test');
            const name = row.querySelector('.js-treasurer-name')?.value || '';
            const phone = row.querySelector('.js-treasurer-phone')?.value || '';
            if (!phone.trim()) {
                alert('Informe o WhatsApp antes de enviar o teste.');
                return;
            }

            btn.disabled = true;
            try {
                const response = await fetch(testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name, phone }),
                });
                const data = await response.json();
                alert(data.message || (response.ok ? 'Teste enviado.' : 'Falha no teste.'));
            } catch (e) {
                alert('Erro ao enviar teste.');
            } finally {
                btn.disabled = false;
            }
        }
    });

    listEl.addEventListener('input', (event) => {
        if (event.target.classList.contains('js-treasurer-phone')) {
            const cursor = event.target.selectionStart;
            const before = event.target.value;
            event.target.value = maskPhone(before);
            if (document.activeElement === event.target && typeof cursor === 'number') {
                const diff = event.target.value.length - before.length;
                event.target.setSelectionRange(cursor + diff, cursor + diff);
            }
        }
    });

    async function runMemberSearch(term) {
        if (!pickResultsEl) return;

        pickResultsEl.innerHTML = '<div class="text-muted small px-1">Carregando membros...</div>';
        try {
            const url = term
                ? `${searchUrl}?q=${encodeURIComponent(term)}`
                : searchUrl;
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json();
            const items = payload.data || [];
            if (!items.length) {
                pickResultsEl.innerHTML = '<div class="text-muted small px-1">Nenhum membro encontrado.</div>';
                return;
            }

            pickResultsEl.innerHTML = items.map((item) => `
                <button type="button" class="list-group-item list-group-item-action js-pick-result"
                        data-id="${item.id}"
                        data-name="${String(item.name || '').replaceAll('"', '&quot;')}"
                        data-phone="${String(item.phone || '').replaceAll('"', '&quot;')}">
                    <strong>${item.name || 'Sem nome'}</strong>
                    <div class="small text-muted">${item.phone ? maskPhone(item.phone) : 'Sem telefone'}</div>
                </button>
            `).join('');
        } catch (e) {
            pickResultsEl.innerHTML = '<div class="text-danger small px-1">Erro ao buscar membros.</div>';
        }
    }

    pickSearchEl?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => runMemberSearch(pickSearchEl.value.trim()), 250);
    });

    pickModalEl?.addEventListener('shown.bs.modal', () => {
        if (!pickSearchEl?.value.trim()) {
            runMemberSearch('');
        }
    });

    pickResultsEl?.addEventListener('click', (event) => {
        const btn = event.target.closest('.js-pick-result');
        if (!btn || !activePickRow) return;

        activePickRow.querySelector('.js-member-id').value = btn.dataset.id || '';
        activePickRow.querySelector('.js-treasurer-name').value = btn.dataset.name || '';
        activePickRow.querySelector('.js-treasurer-phone').value = maskPhone(btn.dataset.phone || '');
        pickModal?.hide();
    });

    // mascara inicial
    listEl.querySelectorAll('.js-treasurer-phone').forEach((input) => {
        if (input.value) input.value = maskPhone(input.value);
    });
    reindexRows();
})();

(function () {
    const select = document.getElementById('mp_whatsapp_group_jid');
    const nameInput = document.getElementById('mp_whatsapp_group_name');
    const refreshBtn = document.getElementById('mpWhatsappGroupsRefresh');
    if (!select) return;

    const groupsUrl = @json(route('financial.automations.whatsapp-groups'));
    const savedJid = @json(old('whatsapp_group_jid', $mpTreasuryGroupSettings['whatsapp_group_jid'] ?? ''));
    const savedName = @json(old('whatsapp_group_name', $mpTreasuryGroupSettings['whatsapp_group_name'] ?? ''));

    function syncGroupName() {
        if (!nameInput) return;
        const opt = select.selectedOptions[0];
        nameInput.value = opt && opt.value ? (opt.textContent || '').trim() : '';
    }

    async function loadGroups() {
        const previous = select.value || savedJid || '';
        select.innerHTML = '<option value="">Carregando grupos...</option>';
        select.disabled = true;
        try {
            const res = await fetch(groupsUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            select.innerHTML = '<option value="">Selecione o grupo...</option>';
            (data.groups || []).forEach((g) => {
                const opt = document.createElement('option');
                opt.value = g.jid;
                opt.textContent = g.name || g.jid;
                if (previous && previous === g.jid) opt.selected = true;
                select.appendChild(opt);
            });
            if (previous && !select.value) {
                const opt = document.createElement('option');
                opt.value = previous;
                opt.textContent = savedName || previous;
                opt.selected = true;
                select.appendChild(opt);
            }
            if (!(data.success) && (data.message || data.error) && !(data.groups || []).length) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = data.message || data.error;
                select.appendChild(opt);
            }
            syncGroupName();
        } catch (e) {
            select.innerHTML = '<option value="">Falha ao carregar grupos</option>';
        } finally {
            select.disabled = false;
        }
    }

    select.addEventListener('change', syncGroupName);
    refreshBtn?.addEventListener('click', loadGroups);
    loadGroups();
})();
</script>
@endpush
