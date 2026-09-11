@extends('layouts.porto')

@section('title', 'Escalas Mensais de Cultos')

@section('page-title', 'Escalas Mensais de Cultos')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><span>Serviço</span></li>
    <li><span>Escalas Mensais</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-calendar me-2"></i>Escalas Mensais de Cultos
                </h2>
            </header>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                    <i class="bx bx-plus me-2"></i>Adicionar Escala
                                </button>
                                @if(auth()->user()?->can('update', new \App\Models\ServiceSchedule()))
                                    <a href="{{ route('voluntarios.escalas-mensais.settings.edit') }}" class="btn btn-default">
                                        <i class="bx bx-slider-alt me-2"></i>Configurar escalas
                                    </a>
                                @endif
                            </div>
                        </div>
                        <form method="GET" action="{{ route('voluntarios.escalas-mensais.index') }}" class="row g-3">
                            <div class="col-md-3">
                                <label for="month" class="form-label">Mês</label>
                                <select name="month" id="month" class="form-select" onchange="this.form.submit()">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->locale('pt_BR')->translatedFormat('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="year" class="form-label">Ano</label>
                                <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    <option value="rascunho" {{ request('status') == 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                                    <option value="publicada" {{ request('status') == 'publicada' ? 'selected' : '' }}>Publicada</option>
                                    <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                    <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                @if($schedules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Culto</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->event->start_date->format('d/m/Y') }}</td>
                                        <td><strong>{{ $schedule->event->title }}</strong></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm dropdown-toggle 
                                                    @if($schedule->status == 'publicada') btn-success
                                                    @elseif($schedule->status == 'cancelada') btn-danger
                                                    @elseif($schedule->status == 'concluido') btn-info
                                                    @else btn-warning
                                                    @endif" 
                                                    type="button" 
                                                    id="statusDropdown{{ $schedule->id }}" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false"
                                                    data-schedule-id="{{ $schedule->id }}">
                                                    @if($schedule->status == 'publicada')
                                                        Publicada
                                                    @elseif($schedule->status == 'cancelada')
                                                        Cancelada
                                                    @elseif($schedule->status == 'concluido')
                                                        Concluído
                                                    @else
                                                        Rascunho
                                                    @endif
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="statusDropdown{{ $schedule->id }}">
                                                    <li>
                                                        <a class="dropdown-item status-option" 
                                                           href="#" 
                                                           data-status="rascunho" 
                                                           data-schedule-id="{{ $schedule->id }}">
                                                            <span class="badge badge-warning">Rascunho</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item status-option" 
                                                           href="#" 
                                                           data-status="publicada" 
                                                           data-schedule-id="{{ $schedule->id }}">
                                                            <span class="badge badge-success">Publicada</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item status-option" 
                                                           href="#" 
                                                           data-status="cancelada" 
                                                           data-schedule-id="{{ $schedule->id }}">
                                                            <span class="badge badge-danger">Cancelada</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item status-option" 
                                                           href="#" 
                                                           data-status="concluido" 
                                                           data-schedule-id="{{ $schedule->id }}">
                                                            <span class="badge badge-info">Concluído</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('voluntarios.escalas-mensais.show', $schedule) }}" class="btn btn-sm btn-default" title="Ver">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-sm btn-success notify-all-btn"
                                                        data-schedule-id="{{ $schedule->id }}"
                                                        data-culto-title="{{ $schedule->event->title }}"
                                                        title="Notificar todo mundo">
                                                    <i class="bx bxl-whatsapp"></i>
                                                </button>
                                                @if($schedule->status == 'publicada')
                                                    <a href="{{ route('voluntarios.escalas-mensais.pdf', $schedule) }}" class="btn btn-sm btn-info" title="Imprimir PDF" target="_blank">
                                                        <i class="bx bx-printer"></i>
                                                    </a>
                                                @endif
                                                @if($schedule->status == 'rascunho')
                                                    <a href="{{ route('voluntarios.escalas-mensais.edit', $schedule) }}" class="btn btn-sm btn-primary" title="Editar">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                @endif
                                                @if($schedule->status != 'publicada')
                                                    <form action="{{ route('voluntarios.escalas-mensais.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover esta escala?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhuma escala cadastrada para o mês selecionado.
                        Use o botão <strong>Adicionar Escala</strong> para preencher as áreas de serviço.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('voluntarios.escalas-mensais.area.manual') }}" id="addScheduleForm">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">
                        Adicionar Escala de {{ \Carbon\Carbon::create($year, $month, 1)->locale('pt_BR')->translatedFormat('F Y') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @if($serviceAreas->count() === 0)
                        <div class="alert alert-danger mb-0">
                            Nenhuma área de serviço ativa foi encontrada.
                        </div>
                    @elseif($cultos->count() === 0)
                        <div class="alert alert-warning mb-0">
                            Nenhum culto de quarta ou domingo encontrado na agenda para o mês selecionado.
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="add_schedule_area_id" class="form-label">Área de serviço <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_schedule_area_id" name="service_area_id" required>
                                <option value="">Selecione a escala que deseja adicionar...</option>
                                @foreach($serviceAreas as $area)
                                    @php
                                        $areaMeta = $scheduleBuilder['areas'][(string) $area->id] ?? null;
                                        $quantity = $areaMeta['quantity'] ?? 1;
                                    @endphp
                                    <option value="{{ $area->id }}">
                                        {{ $area->name }} ({{ $quantity }} {{ $quantity === 1 ? 'pessoa' : 'pessoas' }}){{ !empty($areaMeta['sunday_only']) ? ' · somente domingo' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text" id="add_schedule_area_help">
                                Escolha a área. Os cultos do mês aparecerão para você preencher os voluntários.
                            </div>
                        </div>

                        <div id="add_schedule_empty_volunteers" class="alert alert-warning d-none">
                            Esta área não possui voluntários ativos cadastrados.
                        </div>

                        <div id="add_schedule_cultos" class="d-none">
                            <p class="text-muted" id="add_schedule_rules">
                                Cultos de quarta e domingo da agenda. A mesma pessoa não pode servir em áreas diferentes no mesmo culto.
                                Cultos sem preenchimento não são alterados.
                            </p>
                            @foreach($cultos as $culto)
                                @php
                                    $weekday = $culto->start_date->copy()->locale('pt_BR')->isoFormat('dddd');
                                    $isWednesday = $culto->start_date->dayOfWeek === \Carbon\Carbon::WEDNESDAY;
                                    $isSunday = $culto->start_date->dayOfWeek === \Carbon\Carbon::SUNDAY;
                                @endphp
                                <div class="border rounded p-3 mb-3" data-culto-row data-event-id="{{ $culto->id }}" data-is-sunday="{{ $isSunday ? '1' : '0' }}">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <div class="fw-semibold text-capitalize">{{ $weekday }}, {{ $culto->start_date->format('d/m/Y') }}</div>
                                            <div class="text-muted">{{ $culto->title }} · {{ $culto->start_date->format('H:i') }}</div>
                                        </div>
                                        <span class="badge {{ $isWednesday ? 'bg-warning text-dark' : 'bg-primary' }}">
                                            {{ $isWednesday ? 'Quarta' : 'Domingo' }}
                                        </span>
                                    </div>
                                    <div data-slots></div>
                                    <div class="d-none mt-2" data-guest-wrap>
                                        <label class="form-label">Nome do convidado</label>
                                        <input type="text" class="form-control" maxlength="150" placeholder="Ex.: Pr. José da Silva" data-guest-input>
                                        <small class="text-muted">Use este campo se o preletor não estiver na lista de voluntários.</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
                    @if($serviceAreas->count() > 0 && $cultos->count() > 0)
                        <button type="submit" class="btn btn-primary" id="add_schedule_submit" disabled>
                            <i class="bx bx-check me-2"></i>Salvar Escala
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="notifyAllFromListModal" tabindex="-1" aria-labelledby="notifyAllFromListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <form method="POST" id="notifyAllFromListForm" action="#" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="notifyAllFromListModalLabel">
                        <i class="bx bxl-whatsapp me-2"></i>Notificar Todos da Escala
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        Culto: <strong id="notify_all_list_culto">-</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Template de mensagem (opcional)</label>
                        <select class="form-select" id="notify_all_list_template_id" name="template_id">
                            <option value="">Sem template (digitar manualmente)</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" data-template="{{ e($template->template) }}">
                                    {{ $template->tipo_notificacao }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mensagem</label>
                        <textarea class="form-control" id="notify_all_list_message" name="mensagem" rows="5" placeholder="Digite a mensagem ou selecione um template acima"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivo de mídia (opcional)</label>
                        <input type="file" class="form-control" name="arquivo" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="notify_all_list_send_pdf" name="enviar_pdf">
                        <label class="form-check-label" for="notify_all_list_send_pdf">
                            Enviar também o PDF da escala para todos
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bxl-whatsapp me-1"></i>Enviar para Todos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.monthlyScheduleBuilder = @json($scheduleBuilder);
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const builder = window.monthlyScheduleBuilder || { areas: {}, assignments: {}, guests: {}, occupied: {} };
    const areaSelect = document.getElementById('add_schedule_area_id');
    const helpEl = document.getElementById('add_schedule_area_help');
    const rulesEl = document.getElementById('add_schedule_rules');
    const cultosWrap = document.getElementById('add_schedule_cultos');
    const emptyVolunteers = document.getElementById('add_schedule_empty_volunteers');
    const submitBtn = document.getElementById('add_schedule_submit');
    const addScheduleModalElement = document.getElementById('addScheduleModal');

    function selectedValuesFor(eventId, area) {
        const oldAreaId = String(builder.old_area_id || '');
        if (oldAreaId && oldAreaId === String(area.id) && builder.old_assignments && builder.old_assignments[eventId]) {
            return [].concat(builder.old_assignments[eventId]);
        }
        return ((builder.assignments[eventId] || {})[String(area.id)] || []).slice();
    }

    function guestValueFor(eventId, area) {
        if (!area.is_preletor) return '';
        const oldAreaId = String(builder.old_area_id || '');
        if (oldAreaId && oldAreaId === String(area.id) && builder.old_guests && builder.old_guests[eventId] !== undefined) {
            return builder.old_guests[eventId] || '';
        }
        return builder.guests[eventId] || '';
    }

    function refreshDisabledOptions(row, area) {
        const selects = Array.from(row.querySelectorAll('[data-slot-select]'));
        const chosen = selects.map(function(select) { return select.value; }).filter(Boolean);
        const occupied = builder.occupied[row.getAttribute('data-event-id')] || {};

        selects.forEach(function(select) {
            Array.from(select.options).forEach(function(option) {
                if (!option.value) {
                    option.disabled = false;
                    return;
                }

                const occupiedInfo = occupied[option.value] || occupied[String(option.value)];
                const takenInOtherArea = !area.allows_overlap && occupiedInfo && Number(occupiedInfo.area_id) !== Number(area.id);
                const takenInAnotherSlot = chosen.includes(option.value) && select.value !== option.value;
                option.disabled = takenInOtherArea || takenInAnotherSlot;

                const baseName = option.getAttribute('data-name') || option.textContent;
                option.textContent = takenInOtherArea
                    ? baseName + ' — já em ' + occupiedInfo.area_name
                    : baseName;
            });
        });
    }

    function renderAreaForm(areaId) {
        const area = builder.areas[String(areaId)];
        if (!area || !cultosWrap) {
            if (cultosWrap) cultosWrap.classList.add('d-none');
            if (emptyVolunteers) emptyVolunteers.classList.add('d-none');
            if (submitBtn) submitBtn.disabled = true;
            if (helpEl) helpEl.textContent = 'Escolha a área. Os cultos do mês aparecerão para você preencher os voluntários.';
            return;
        }

        if (helpEl) {
            helpEl.textContent = area.help;
        }
        if (rulesEl && area.rules_text) {
            rulesEl.textContent = area.rules_text;
        }
        const hasVolunteers = area.volunteers.length > 0;
        if (emptyVolunteers) {
            emptyVolunteers.classList.toggle('d-none', hasVolunteers || area.is_preletor);
            emptyVolunteers.textContent = area.uses_members
                ? 'Não há membros ativos para montar a escala de limpeza.'
                : 'Esta área não possui voluntários ativos cadastrados.';
        }
        if (cultosWrap) {
            cultosWrap.classList.remove('d-none');
        }
        if (submitBtn) submitBtn.disabled = !(hasVolunteers || area.is_preletor);

        document.querySelectorAll('[data-culto-row]').forEach(function(row) {
            const eventId = row.getAttribute('data-event-id');
            const slotsEl = row.querySelector('[data-slots]');
            const guestWrap = row.querySelector('[data-guest-wrap]');
            const guestInput = row.querySelector('[data-guest-input]');
            const isSunday = row.getAttribute('data-is-sunday') === '1';
            const hideRow = area.sunday_only && !isSunday;
            row.classList.toggle('d-none', hideRow);

            const selected = selectedValuesFor(eventId, area);
            if (!slotsEl || !guestWrap || !guestInput) {
                return;
            }
            slotsEl.innerHTML = '';
            if (hideRow) {
                return;
            }

            area.slots.forEach(function(label, index) {
                const wrapper = document.createElement('div');
                wrapper.className = 'mb-2';
                const labelEl = document.createElement('label');
                labelEl.className = 'form-label';
                labelEl.textContent = label;
                const select = document.createElement('select');
                select.className = 'form-select';
                select.name = 'assignments[' + eventId + '][]';
                select.setAttribute('data-slot-select', '1');

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = area.uses_members ? 'Selecione o membro...' : 'Selecione o voluntário...';
                select.appendChild(placeholder);

                area.volunteers.forEach(function(volunteer) {
                    const option = document.createElement('option');
                    option.value = String(volunteer.id);
                    option.setAttribute('data-name', volunteer.name);
                    option.textContent = volunteer.name;
                    select.appendChild(option);
                });

                if (selected[index]) {
                    select.value = String(selected[index]);
                }

                select.addEventListener('change', function() {
                    if (select.value && guestInput) {
                        guestInput.value = '';
                    }
                    refreshDisabledOptions(row, area);
                });

                wrapper.appendChild(labelEl);
                wrapper.appendChild(select);
                slotsEl.appendChild(wrapper);
            });

            if (area.is_preletor) {
                guestWrap.classList.remove('d-none');
                guestInput.name = 'guests[' + eventId + ']';
                guestInput.value = selected.some(Boolean) ? '' : guestValueFor(eventId, area);
                guestInput.oninput = function() {
                    if (!guestInput.value.trim()) return;
                    row.querySelectorAll('[data-slot-select]').forEach(function(select) {
                        select.value = '';
                    });
                    refreshDisabledOptions(row, area);
                };
            } else {
                guestWrap.classList.add('d-none');
                guestInput.removeAttribute('name');
                guestInput.value = '';
                guestInput.oninput = null;
            }

            refreshDisabledOptions(row, area);
        });
    }

    if (areaSelect) {
        areaSelect.addEventListener('change', function() {
            renderAreaForm(areaSelect.value);
        });
        if (builder.old_area_id) {
            areaSelect.value = String(builder.old_area_id);
            renderAreaForm(areaSelect.value);
        }
    }

    const addScheduleForm = document.getElementById('addScheduleForm');
    if (addScheduleForm) {
        addScheduleForm.addEventListener('submit', function(event) {
            const rows = Array.from(document.querySelectorAll('[data-culto-row]'));
            let hasValue = false;
            for (let index = 0; index < rows.length; index++) {
                const row = rows[index];
                const ids = Array.from(row.querySelectorAll('[data-slot-select]'))
                    .map(function(select) { return select.value; })
                    .filter(Boolean);
                const guestInput = row.querySelector('[data-guest-input]');
                const hasGuest = guestInput && guestInput.getAttribute('name') && guestInput.value.trim();
                if (ids.length || hasGuest) {
                    hasValue = true;
                }
                if (ids.length !== new Set(ids).size) {
                    event.preventDefault();
                    alert('No mesmo culto, o mesmo voluntário não pode servir em mais de uma vaga.');
                    return;
                }
            }
            if (!hasValue) {
                event.preventDefault();
                alert('Preencha ao menos um culto antes de salvar.');
            }
        });
    }

    @if(session('open_add_schedule_modal'))
        if (addScheduleModalElement && window.bootstrap) {
            new bootstrap.Modal(addScheduleModalElement).show();
        }
    @endif

    const notifyModalElement = document.getElementById('notifyAllFromListModal');
    const notifyModal = notifyModalElement ? new bootstrap.Modal(notifyModalElement) : null;
    const notifyForm = document.getElementById('notifyAllFromListForm');
    const notifyCulto = document.getElementById('notify_all_list_culto');
    const notifyTemplate = document.getElementById('notify_all_list_template_id');
    const notifyMessage = document.getElementById('notify_all_list_message');

    document.querySelectorAll('.notify-all-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const scheduleId = this.getAttribute('data-schedule-id');
            const cultoTitle = this.getAttribute('data-culto-title') || '-';
            if (!notifyForm || !notifyModal) return;

            notifyForm.action = `{{ url('/servico/voluntarios/escalas-mensais') }}/${scheduleId}/volunteers/notify-all`;
            notifyCulto.textContent = cultoTitle;
            notifyTemplate.value = '';
            notifyMessage.value = '';
            notifyModal.show();
        });
    });

    if (notifyTemplate && notifyMessage) {
        notifyTemplate.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const templateText = selected.getAttribute('data-template') || '';
            if (templateText) {
                notifyMessage.value = templateText;
            }
        });
    }

    // Adicionar evento de clique nas opções de status
    document.querySelectorAll('.status-option').forEach(function(option) {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const scheduleId = this.getAttribute('data-schedule-id');
            const newStatus = this.getAttribute('data-status');
            const button = document.querySelector(`#statusDropdown${scheduleId}`);
            
            // Confirmar alteração
            if (!confirm('Deseja alterar o status desta escala?')) {
                return;
            }
            
            // Fazer requisição AJAX
            fetch(`{{ url('servico/voluntarios/escalas-mensais') }}/${scheduleId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar o botão
                    const statusLabels = {
                        'rascunho': 'Rascunho',
                        'publicada': 'Publicada',
                        'cancelada': 'Cancelada',
                        'concluido': 'Concluído'
                    };
                    
                    const statusClasses = {
                        'rascunho': 'btn-warning',
                        'publicada': 'btn-success',
                        'cancelada': 'btn-danger',
                        'concluido': 'btn-info'
                    };
                    
                    // Remover todas as classes de status
                    button.classList.remove('btn-warning', 'btn-success', 'btn-danger', 'btn-info');
                    // Adicionar a classe correta
                    button.classList.add(statusClasses[newStatus]);
                    // Atualizar o texto
                    button.textContent = statusLabels[newStatus];
                    
                    // Mostrar mensagem de sucesso
                    alert(data.message);
                    
                    // Recarregar a página após 1 segundo para atualizar os filtros
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Erro ao atualizar status: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar status. Tente novamente.');
            });
        });
    });
});
</script>
@endpush
@endsection
