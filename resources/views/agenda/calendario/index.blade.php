@extends('layouts.porto')

@section('title', 'Calendário')

@section('page-title', 'Calendário')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><span>Calendário</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    // Visualização disponível para todos, apenas criar/editar/excluir precisa de permissão
    $canCreateEvents = $isAdmin || $user->hasPermission('agenda.events.create') || $user->hasPermission('agenda.events.manage');
    $canEditEvents = $isAdmin || $user->hasPermission('agenda.events.edit') || $user->hasPermission('agenda.events.manage');
    $canDeleteEvents = $isAdmin || $user->hasPermission('agenda.events.delete') || $user->hasPermission('agenda.events.manage');
    $canCreateCategories = $isAdmin || $user->hasPermission('agenda.categories.create') || $user->hasPermission('agenda.categories.manage');
    $canDeleteCategories = $isAdmin || $user->hasPermission('agenda.categories.delete') || $user->hasPermission('agenda.categories.manage');
@endphp

<div class="agenda-page" id="agendaWorkspace">
    <header class="agenda-hero">
        <div class="agenda-hero-icon" aria-hidden="true"><i class="bx bx-calendar"></i></div>
        <div class="agenda-hero-copy"><span class="agenda-eyebrow">Planejamento e encontros</span><h1>Agenda da igreja</h1><p>Uma visão dos cultos, encontros e eventos dos departamentos.</p></div>
        @if($canCreateEvents)<button type="button" class="btn btn-primary agenda-create" id="btnNewEvent"><i class="bx bx-plus" aria-hidden="true"></i>Novo evento</button>@endif
    </header>
    <div class="agenda-layout">
        <section class="agenda-calendar-panel" aria-label="Calendário da agenda">
            <div class="agenda-calendar-toolbar">
                <div class="agenda-period-navigation">
                    <div class="agenda-arrows"><button type="button" id="agendaPrev" aria-label="Período anterior"><i class="bx bx-chevron-left" aria-hidden="true"></i></button><button type="button" id="agendaNext" aria-label="Próximo período"><i class="bx bx-chevron-right" aria-hidden="true"></i></button></div>
                    <h2 id="agendaPeriodTitle" aria-live="polite">Calendário</h2>
                    <button type="button" class="btn btn-outline-secondary" id="agendaToday">Hoje</button>
                </div>
                <div class="agenda-view-switch" role="group" aria-label="Visualização do calendário">
                    <button type="button" id="btnMonth" aria-pressed="true"><i class="bx bx-calendar" aria-hidden="true"></i>Mês</button>
                    <button type="button" id="btnListMonthly" aria-pressed="false"><i class="bx bx-list-ul" aria-hidden="true"></i>Lista mensal</button>
                    <button type="button" id="btnListWeekly" aria-pressed="false"><i class="bx bx-calendar-week" aria-hidden="true"></i>Semana</button>
                </div>
            </div>
            <div class="agenda-filter-toolbar">
                <div class="agenda-search"><i class="bx bx-search" aria-hidden="true"></i><label for="agendaSearch" class="visually-hidden">Buscar eventos no período</label><input id="agendaSearch" type="search" class="form-control" placeholder="Buscar evento, local ou departamento..." autocomplete="off"></div>
                <div class="agenda-month-jump"><label for="agendaMonthJump">Ir para</label><input id="agendaMonthJump" type="month" class="form-control" aria-label="Ir para mês"></div>
                <button type="button" id="agendaRefresh" class="agenda-icon-button" aria-label="Atualizar eventos" title="Atualizar eventos"><i class="bx bx-refresh" aria-hidden="true"></i></button>
            </div>
            <div class="agenda-result-bar"><span id="agendaResultCount" role="status">Carregando eventos...</span><span class="agenda-tip">@if($canCreateEvents)Selecione uma data para criar um evento.@else Selecione um evento para ver os detalhes.@endif</span></div>
            <div class="agenda-notice" id="agendaLoadError" role="alert" hidden>Não foi possível carregar os eventos. <button type="button" id="agendaRetry">Tentar novamente</button></div>
            <div class="agenda-notice agenda-notice--empty" id="agendaEmpty" hidden>Nenhum evento encontrado neste período com os filtros selecionados. <button type="button" id="agendaClearFilters">Limpar filtros</button></div>
            <div class="agenda-calendar-body"><div id="calendar" aria-busy="true"></div></div>
        </section>
        <aside class="agenda-sidebar" aria-label="Filtros e próximos eventos">
            <section class="agenda-side-panel">
                <div class="agenda-side-heading"><h2>Filtrar agenda</h2><i class="bx bx-filter-alt" aria-hidden="true"></i></div>
                <label for="agendaSource" class="form-label">Origem dos eventos</label>
                <select id="agendaSource" class="form-select"><option value="all">Toda a agenda</option><option value="agenda">Eventos da Agenda</option><option value="department">Eventos dos departamentos</option></select>
                <div class="agenda-category-heading"><h3>Categorias da Agenda</h3><button type="button" id="agendaAllCategories">Marcar todas</button></div>
                <div id="categoriesList" class="agenda-categories">
                    @foreach($categories as $category)
                        <div class="category-item" data-category-id="{{ $category->id }}">
                            <label><input type="checkbox" data-category-filter value="{{ $category->id }}" checked><span class="agenda-color-dot" style="background:{{ preg_match('/^#[0-9a-f]{6}$/i', $category->color) ? $category->color : '#0088cc' }}" aria-hidden="true"></span><span>{{ $category->name }}</span></label>
                            @if($canDeleteCategories)<button type="button" class="agenda-icon-button btn-delete-category" data-category-id="{{ $category->id }}" title="Remover categoria {{ $category->name }}" aria-label="Remover categoria {{ $category->name }}"><i class="bx bx-trash" aria-hidden="true"></i></button>@endif
                        </div>
                    @endforeach
                </div>
                <label class="agenda-uncategorized"><input type="checkbox" id="agendaUncategorized" checked> Sem categoria</label>
                <p class="agenda-side-help">Os eventos dos departamentos mantêm a cor e a identificação de sua equipe.</p>
                @if($canCreateCategories)
                    <details class="agenda-category-create"><summary><i class="bx bx-plus" aria-hidden="true"></i>Nova categoria</summary>
                        <form id="categoryForm">@csrf<label for="categoryName" class="form-label">Nome da categoria</label><input type="text" class="form-control" id="categoryName" maxlength="255" required><div class="d-flex gap-2 mt-3"><label for="categoryColor" class="visually-hidden">Cor da categoria</label><input type="color" class="form-control form-control-color" id="categoryColor" value="#0088cc" title="Cor da categoria"><button type="submit" class="btn btn-outline-primary flex-grow-1"><i class="bx bx-plus" aria-hidden="true"></i>Adicionar</button></div></form>
                    </details>
                @endif
            </section>
            <section class="agenda-side-panel"><div class="agenda-side-heading"><h2>A seguir</h2><i class="bx bx-time-five" aria-hidden="true"></i></div><p class="agenda-side-help">Próximos eventos no período selecionado.</p><div id="agendaUpcoming" class="agenda-upcoming"><p class="agenda-side-help">Carregando...</p></div></section>
        </aside>
    </div>
</div>

<div class="modal fade agenda-detail-modal" id="agendaDetailModal" tabindex="-1" aria-labelledby="agendaDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down"><div class="modal-content">
        <div class="modal-header"><div><span class="agenda-eyebrow">Evento da Agenda</span><h2 class="modal-title h4" id="agendaDetailTitle"></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body"><dl class="agenda-detail-list"><dt>Quando</dt><dd id="agendaDetailDates"></dd><dt>Local</dt><dd id="agendaDetailLocation"></dd><dt>Descrição</dt><dd id="agendaDetailDescription" style="white-space:pre-wrap;overflow-wrap:anywhere"></dd></dl></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button></div>
    </div></div>
</div>

<div class="modal fade agenda-detail-modal" id="departmentEventModal" tabindex="-1" aria-labelledby="departmentEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="departmentEventModalLabel">Evento do departamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <h2 class="h5" id="departmentEventTitle"></h2>
                <dl class="agenda-detail-list">
                    <dt>Departamento</dt><dd id="departmentEventName"></dd>
                    <dt>Início</dt><dd id="departmentEventStart"></dd>
                    <div id="departmentEventEndRow"><dt>Término</dt><dd id="departmentEventEnd"></dd></div>
                    <dt>Local</dt><dd id="departmentEventLocation"></dd>
                    <dt>Descrição</dt><dd id="departmentEventDescription" style="white-space:pre-wrap; overflow-wrap:anywhere"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <a class="btn btn-default" id="departmentEventDetailLink" hidden>Abrir em Departamentos</a>
                <a class="btn btn-primary" id="departmentEventEditLink" hidden>Editar em Departamentos</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar/Editar Evento -->
@if($canCreateEvents || $canEditEvents)
<div class="modal fade agenda-event-modal" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addEventModalLabel">Novo evento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="eventForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="eventId" name="event_id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="eventTitle" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="eventTitle" name="title" required>
                        </div>
                        <div class="col-md-4">
                            <label for="eventVisibility" class="form-label">Visibilidade</label>
                            <div class="d-flex align-items-center">
                                <i class="bx bx-lock-alt text-danger me-2"></i>
                                <select class="form-select" id="eventVisibility" name="visibility">
                                    <option value="public">Público</option>
                                    <option value="private">Privado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="eventStartDate" class="form-label">Data Início</label>
                            <input type="date" class="form-control" id="eventStartDate" name="start_date" required>
                        </div>
                        <div class="col-md-4">
                            <label for="eventStartTime" class="form-label">Hora Início</label>
                            <div class="d-flex align-items-center">
                                <input type="number" class="form-control" id="eventStartTimeHour" aria-label="Hora de início" min="0" max="23" value="19" style="width: 60px;">
                                <span class="mx-2">:</span>
                                <input type="number" class="form-control" id="eventStartTimeMinute" aria-label="Minutos de início" min="0" max="59" value="30" style="width: 60px;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="eventEndTime" class="form-label">Hora Fim</label>
                            <div class="d-flex align-items-center">
                                <input type="number" class="form-control" id="eventEndTimeHour" aria-label="Hora de término" min="0" max="23" value="21" style="width: 60px;">
                                <span class="mx-2">:</span>
                                <input type="number" class="form-control" id="eventEndTimeMinute" aria-label="Minutos de término" min="0" max="59" value="0" style="width: 60px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="eventEndDate" class="form-label">Data Fim</label>
                            <input type="date" class="form-control" id="eventEndDate" name="end_date">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="eventAllDay" name="all_day">
                                <label class="form-check-label" for="eventAllDay">
                                    Dia inteiro
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="eventRecurrence" class="form-label">Repetição</label>
                            <select class="form-select" id="eventRecurrence" name="recurrence">
                                <option value="null">Não repetir</option>
                                <option value="daily">Todo dia</option>
                                <option value="weekly">Toda semana</option>
                                <option value="biweekly">Quinzenalmente</option>
                                <option value="monthly">Todo mês</option>
                                <option value="yearly">Todo ano</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="eventCategory" class="form-label">Categoria</label>
                            <select class="form-select" id="eventCategory" name="category_id">
                                <option value="">Nenhum</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(strtolower($category->name) === 'eventos')>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Use <strong>Eventos</strong> para aparecer na página principal do site.</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventLocation" class="form-label">Local</label>
                        <input type="text" class="form-control" id="eventLocation" name="location">
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" id="eventDescription" name="description" rows="5" placeholder="Descreva o encontro, a programação ou as orientações para os participantes..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="eventBannerImage" class="form-label">Imagem do evento</label>
                        <input type="file" class="form-control" id="eventBannerImage" name="banner_image" accept="image/*">
                        <small class="text-muted d-block mt-1">Opcional. Formatos: JPG, PNG, WEBP (max 5MB).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    @if($canDeleteEvents)
                    <button type="button" class="btn btn-danger" id="btnDeleteEvent" style="display: none;">
                        <i class="bx bx-trash me-1"></i>Remover
                    </button>
                    @endif
                    <div class="ms-auto">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        @if($canCreateEvents || $canEditEvents)
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-check me-1"></i>Salvar
                        </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal de Confirmação de Remoção -->
@if($canDeleteEvents)
<div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteEventModalLabel">Remover Evento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Este evento faz parte de uma série recorrente. O que deseja fazer?</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-danger" id="btnDeleteOne">
                        <i class="bx bx-calendar-x me-2"></i>Remover apenas esta ocorrência
                    </button>
                    <button type="button" class="btn btn-danger" id="btnDeleteAll">
                        <i class="bx bx-trash me-2"></i>Remover todas as ocorrências
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Modal de Confirmação de Edição -->
@if($canEditEvents)
<div class="modal fade" id="updateEventModal" tabindex="-1" aria-labelledby="updateEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateEventModalLabel">Editar Evento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Este evento faz parte de uma série recorrente. O que deseja fazer?</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" id="btnUpdateOne">
                        <i class="bx bx-edit me-2"></i>Editar apenas esta ocorrência
                    </button>
                    <button type="button" class="btn btn-primary" id="btnUpdateAll">
                        <i class="bx bx-calendar-edit me-2"></i>Editar todas as ocorrências
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/css/agenda-calendar.css') }}?v={{ filemtime(public_path('css/css/agenda-calendar.css')) }}">
@endpush

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script src="{{ asset('js/agenda-calendar-utils.js') }}?v={{ filemtime(public_path('js/agenda-calendar-utils.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    let selectedDate = null;
    let currentView = window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth';

    async function parseJsonResponse(response) {
        const text = await response.text();
        const clean = text.replace(/^\uFEFF/, '').trim();
        return clean ? JSON.parse(clean) : {};
    }

    function showDepartmentEvent(event) {
        const details = event.extendedProps;
        document.getElementById('departmentEventTitle').textContent = details.eventTitle;
        document.getElementById('departmentEventName').textContent = details.departmentName;
        document.getElementById('departmentEventStart').textContent = details.displayStart + (event.allDay ? ' · Dia inteiro' : '');
        document.getElementById('departmentEventEndRow').hidden = !details.displayEnd;
        document.getElementById('departmentEventEnd').textContent = details.displayEnd || '';
        document.getElementById('departmentEventLocation').textContent = details.location || 'Não informado';
        document.getElementById('departmentEventDescription').textContent = details.description || 'Não informada';
        for (const [id, url] of [
            ['departmentEventDetailLink', details.detailUrl],
            ['departmentEventEditLink', details.editUrl],
        ]) {
            const link = document.getElementById(id);
            link.hidden = !url;
            if (url) link.href = url;
            else link.removeAttribute('href');
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('departmentEventModal')).show();
    }
    
    const ui = window.AdelAgenda;
    const sourceFilter = document.getElementById('agendaSource');
    const searchInput = document.getElementById('agendaSearch');
    const errorNotice = document.getElementById('agendaLoadError');
    let cachedRange = null;
    let cachedEvents = [];
    let requestSequence = 0;
    let userSelectedView = false;
    const categoryFilters = () => Array.from(document.querySelectorAll('[data-category-filter]:checked')).map(input => input.value).concat(document.getElementById('agendaUncategorized').checked ? ['none'] : []);
    const filters = () => ({ source: sourceFilter.value, search: searchInput.value, categories: categoryFilters() });
    function reloadCalendarEvents() {
        cachedRange = null;
        calendar.refetchEvents();
    }
    function showNativeEvent(event) {
        document.getElementById('agendaDetailTitle').textContent = event.title;
        const start = [event.start_date?.split('-').reverse().join('/'), event.all_day ? 'Dia inteiro' : event.start_time].filter(Boolean).join(' · ');
        const end = event.end_date ? [event.end_date.split('-').reverse().join('/'), event.all_day ? '' : event.end_time].filter(Boolean).join(' · ') : '';
        document.getElementById('agendaDetailDates').textContent = start + (end ? ' — ' + end : '');
        document.getElementById('agendaDetailLocation').textContent = event.location || 'Não informado';
        document.getElementById('agendaDetailDescription').textContent = event.description || 'Não informada';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('agendaDetailModal')).show();
    }
    function updateOverview(events) {
        if (!errorNotice.hidden || (cachedRange === null && calendarEl.getAttribute('aria-busy') === 'true')) return;
        const view = calendar.view;
        const visible = events.filter(event => ui.inPeriod(event, view.currentStart, view.currentEnd));
        const departments = visible.filter(ui.isDepartment).length;
        document.getElementById('agendaResultCount').textContent = `${visible.length} ${visible.length === 1 ? 'evento' : 'eventos'} no período${departments ? ` · ${departments} de departamentos` : ''}`;
        document.getElementById('agendaEmpty').hidden = visible.length !== 0;
        const container = document.getElementById('agendaUpcoming');
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const upcoming = visible.filter(event => (event.end || event.start) >= today).sort((a, b) => a.start - b.start).slice(0, 4);
        const fragment = document.createDocumentFragment();
        upcoming.forEach(event => {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'agenda-upcoming-item';
            const badge = document.createElement('span'); badge.className = 'agenda-upcoming-date';
            const day = document.createElement('span'); day.textContent = String(event.start.getDate()).padStart(2, '0');
            const month = document.createElement('small'); month.textContent = event.start.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '');
            badge.append(day, month);
            const copy = document.createElement('span'); copy.className = 'agenda-upcoming-copy';
            const title = document.createElement('strong'); title.textContent = event.title;
            const time = document.createElement('small'); time.textContent = event.allDay ? 'Dia inteiro' : event.start.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            copy.append(title, time); button.append(badge, copy);
            button.addEventListener('click', () => openCalendarEvent(event));
            fragment.appendChild(button);
        });
        if (!upcoming.length) {
            const empty = document.createElement('p'); empty.className = 'agenda-side-help'; empty.textContent = 'Nenhum próximo evento neste período com os filtros atuais.'; fragment.appendChild(empty);
        }
        container.replaceChildren(fragment);
    }
    function updateViewButtons(type) {
        for (const [id, view] of [['btnMonth', 'dayGridMonth'], ['btnListMonthly', 'listMonth'], ['btnListWeekly', 'listWeek']]) {
            document.getElementById(id).setAttribute('aria-pressed', String(type === view));
        }
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: currentView,
        locale: 'pt-br',
        headerToolbar: false,
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia'
        },
        events: async function(info, successCallback, failureCallback) {
            const sequence = ++requestSequence;
            const range = `${info.startStr}|${info.endStr}`;
            calendarEl.setAttribute('aria-busy', 'true');
            errorNotice.hidden = true;
            try {
                if (cachedRange !== range) {
                    const url = new URL('{{ route("agenda.events.index") }}', window.location.origin);
                    url.searchParams.set('start', info.startStr);
                    url.searchParams.set('end', info.endStr);
                    const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                    const data = await parseJsonResponse(response);
                    if (!response.ok) throw new Error(data.message || 'Erro ao carregar eventos');
                    if (sequence !== requestSequence) return successCallback([]);
                    cachedEvents = Array.isArray(data) ? data : [];
                    cachedRange = range;
                }
                successCallback(ui.filterEvents(cachedEvents, filters()));
            } catch (error) {
                if (sequence !== requestSequence) return successCallback([]);
                errorNotice.hidden = false;
                document.getElementById('agendaResultCount').textContent = 'Eventos indisponíveis';
                document.getElementById('agendaEmpty').hidden = true;
                failureCallback(error);
            } finally {
                if (sequence === requestSequence) calendarEl.setAttribute('aria-busy', 'false');
            }
        },
        datesSet: function(info) {
            document.getElementById('agendaPeriodTitle').textContent = info.view.title;
            document.getElementById('agendaMonthJump').value = ui.localDate(info.view.currentStart).slice(0, 7);
            updateViewButtons(info.view.type);
        },
        eventsSet: updateOverview,
        height: 'auto',
        fixedWeekCount: false,
        dayMaxEvents: 3,
        moreLinkText: count => `+${count} eventos`,
        noEventsText: 'Nenhum evento neste período.',
        allDayText: 'Dia inteiro',
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        editable: false,
        selectable: {{ ($canCreateEvents) ? 'true' : 'false' }},
        selectMirror: {{ ($canCreateEvents) ? 'true' : 'false' }},
        eventInteractive: true,
        eventDisplay: 'block',
        eventTextColor: '#ffffff',
        eventDidMount: function(arg) {
            arg.el.style.cursor = 'pointer';
            arg.el.style.fontWeight = '500';
            if (!arg.el.classList.contains('fc-list-event')) {
                arg.el.style.color = ui.textColor(arg.event.backgroundColor);
                const main = arg.el.querySelector('.fc-event-main');
                if (main) main.style.color = ui.textColor(arg.event.backgroundColor);
            }
            if (arg.event.extendedProps.sourceType === 'department') {
                arg.el.title = arg.event.title;
            }
        },
        select: function(arg) {
            @if($canCreateEvents)
            selectedDate = arg.startStr;
            resetEventForm();
            const startDate = new Date(arg.start);
            document.getElementById('eventStartDate').value = ui.localDate(startDate);
            document.getElementById('eventEndDate').value = ui.localDate(startDate);
            document.getElementById('addEventModalLabel').textContent = 'Novo evento';
            if (document.getElementById('btnDeleteEvent')) document.getElementById('btnDeleteEvent').style.display = 'none';
            const modal = new bootstrap.Modal(document.getElementById('addEventModal'));
            modal.show();
            calendar.unselect();
            @else
            calendar.unselect();
            @endif
        },
        eventClick: function(arg) {
            arg.jsEvent.preventDefault();
            openCalendarEvent(arg.event);
        }
    });
    function openCalendarEvent(event) {
            if (event.extendedProps.sourceType === 'department') {
                showDepartmentEvent(event);
                return;
            }
            
            // Buscar dados do evento
            const eventId = event.id;
            
            if (!eventId) {
                console.error('Event ID não encontrado');
                alert('Erro: ID do evento não encontrado');
                return;
            }
            
            const url = '{{ route("agenda.events.show", ":id") }}'.replace(':id', eventId);
            
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.event) {
                        @if($canEditEvents)
                        loadEventToForm(data.event);
                        document.getElementById('addEventModalLabel').textContent = 'Editar evento';
                        if (document.getElementById('btnDeleteEvent')) document.getElementById('btnDeleteEvent').style.display = 'block';
                        const modal = new bootstrap.Modal(document.getElementById('addEventModal'));
                        modal.show();
                        @else
                        showNativeEvent(data.event);
                        @endif
                    } else {
                        console.error('Resposta sem sucesso:', data);
                        alert('Erro ao carregar evento: ' + (data.message || 'Dados inválidos'));
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar evento:', error);
                    alert('Erro ao carregar evento: ' + error.message);
                });
    }

    calendar.render();

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) reloadCalendarEvents();
    });
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') reloadCalendarEvents();
    });

    document.getElementById('agendaPrev').addEventListener('click', () => calendar.prev());
    document.getElementById('agendaNext').addEventListener('click', () => calendar.next());
    document.getElementById('agendaToday').addEventListener('click', () => calendar.today());
    document.getElementById('agendaMonthJump').addEventListener('change', event => {
        if (/^\d{4}-\d{2}$/.test(event.target.value)) calendar.gotoDate(event.target.value + '-01');
    });
    document.getElementById('agendaRefresh').addEventListener('click', reloadCalendarEvents);
    document.getElementById('agendaRetry').addEventListener('click', reloadCalendarEvents);
    for (const [id, view] of [['btnMonth', 'dayGridMonth'], ['btnListMonthly', 'listMonth'], ['btnListWeekly', 'listWeek']]) {
        document.getElementById(id).addEventListener('click', () => {
            userSelectedView = true; currentView = view; calendar.changeView(view);
        });
    }
    const mobileCalendarQuery = window.matchMedia('(max-width: 767.98px)');
    mobileCalendarQuery.addEventListener('change', () => {
        if (!userSelectedView) calendar.changeView(mobileCalendarQuery.matches ? 'listWeek' : 'dayGridMonth');
        calendar.updateSize();
    });
    sourceFilter.addEventListener('change', () => calendar.refetchEvents());
    let searchTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer); searchTimer = setTimeout(() => calendar.refetchEvents(), 180);
    });
    document.getElementById('categoriesList').addEventListener('change', () => calendar.refetchEvents());
    document.getElementById('agendaUncategorized').addEventListener('change', () => calendar.refetchEvents());
    document.getElementById('agendaAllCategories').addEventListener('click', () => {
        document.querySelectorAll('[data-category-filter]').forEach(input => { input.checked = true; });
        document.getElementById('agendaUncategorized').checked = true; calendar.refetchEvents();
    });
    document.getElementById('agendaClearFilters').addEventListener('click', () => {
        sourceFilter.value = 'all'; searchInput.value = '';
        document.querySelectorAll('[data-category-filter]').forEach(input => { input.checked = true; });
        document.getElementById('agendaUncategorized').checked = true; calendar.refetchEvents();
    });
    @if($canCreateEvents)
    document.getElementById('btnNewEvent').addEventListener('click', () => {
        resetEventForm();
        const date = ui.localDate(calendar.getDate());
        document.getElementById('eventStartDate').value = date;
        document.getElementById('eventEndDate').value = date;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addEventModal')).show();
    });
    @endif

    // Formulário de categoria (somente admin)
    @if($canCreateCategories)
    const categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('categoryName').value;
            const color = document.getElementById('categoryColor').value;
            
            fetch('{{ route("agenda.categories.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name, color })
            })
            .then(async response => {
                const data = await parseJsonResponse(response);
                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao adicionar categoria');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    // Adicionar categoria à lista
                    const categoriesList = document.getElementById('categoriesList');
                    const categoryDiv = document.createElement('div');
                    categoryDiv.className = 'category-item';
                    categoryDiv.dataset.categoryId = data.category.id;
                    const label = document.createElement('label');
                    const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.checked = true; checkbox.dataset.categoryFilter = ''; checkbox.value = data.category.id;
                    const dot = document.createElement('span'); dot.className = 'agenda-color-dot'; dot.style.backgroundColor = data.category.color;
                    const name = document.createElement('span'); name.textContent = data.category.name;
                    label.append(checkbox, dot, name); categoryDiv.appendChild(label);
                    @if($canDeleteCategories)
                    const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'agenda-icon-button btn-delete-category'; remove.dataset.categoryId = data.category.id;
                    remove.setAttribute('aria-label', 'Remover categoria ' + data.category.name);
                    const icon = document.createElement('i'); icon.className = 'bx bx-trash'; icon.setAttribute('aria-hidden', 'true'); remove.appendChild(icon); categoryDiv.appendChild(remove);
                    @endif
                    categoriesList.appendChild(categoryDiv);
                    
                    // Adicionar ao select de categorias
                    const categorySelect = document.getElementById('eventCategory');
                    if (categorySelect) {
                        const option = document.createElement('option');
                        option.value = data.category.id;
                        option.textContent = data.category.name;
                        categorySelect.appendChild(option);
                    }
                    
                    // Limpar formulário
                    document.getElementById('categoryName').value = '';
                    document.getElementById('categoryColor').value = '#0088cc';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Erro ao adicionar categoria');
            });
        });
    }
    @endif
    
    // Função para carregar evento no formulário
    function loadEventToForm(event) {
        document.getElementById('eventId').value = event.id;
        document.getElementById('eventTitle').value = event.title || '';
        document.getElementById('eventDescription').value = event.description || '';
        document.getElementById('eventStartDate').value = event.start_date || '';
        document.getElementById('eventEndDate').value = event.end_date || '';
        document.getElementById('eventVisibility').value = event.visibility || 'public';
        document.getElementById('eventLocation').value = event.location || '';
        document.getElementById('eventCategory').value = event.category_id || '';
        document.getElementById('eventRecurrence').value = event.recurrence || 'null';
        document.getElementById('eventAllDay').checked = event.all_day || false;
        
        // Preencher horas
        if (event.start_time) {
            const startTimeParts = event.start_time.split(':');
            document.getElementById('eventStartTimeHour').value = startTimeParts[0];
            document.getElementById('eventStartTimeMinute').value = startTimeParts[1];
        }
        
        if (event.end_time) {
            const endTimeParts = event.end_time.split(':');
            document.getElementById('eventEndTimeHour').value = endTimeParts[0];
            document.getElementById('eventEndTimeMinute').value = endTimeParts[1];
        }
        
        // Desabilitar campos de hora se for dia inteiro
        const timeInputs = document.querySelectorAll('#eventStartTimeHour, #eventStartTimeMinute, #eventEndTimeHour, #eventEndTimeMinute');
        timeInputs.forEach(input => {
            input.disabled = event.all_day;
        });
    }
    
    // Função para limpar formulário
    function resetEventForm() {
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = '';
        document.getElementById('addEventModalLabel').textContent = 'Novo evento';
        if (document.getElementById('btnDeleteEvent')) document.getElementById('btnDeleteEvent').style.display = 'none';
        
        // Resetar horas padrão
        document.getElementById('eventStartTimeHour').value = 19;
        document.getElementById('eventStartTimeMinute').value = 30;
        document.getElementById('eventEndTimeHour').value = 21;
        document.getElementById('eventEndTimeMinute').value = 0;
        
        // Reabilitar campos de hora
        const timeInputs = document.querySelectorAll('#eventStartTimeHour, #eventStartTimeMinute, #eventEndTimeHour, #eventEndTimeMinute');
        timeInputs.forEach(input => {
            input.disabled = false;
        });
    }
    
    // Variável para armazenar dados do formulário e se deve atualizar todos
    let pendingFormData = null;
    let shouldUpdateAll = false;
    
    // Função para salvar evento
    function saveEvent(formData, updateAll = false) {
        const eventId = document.getElementById('eventId').value;
        const url = eventId ? '{{ route("agenda.events.update", ":id") }}'.replace(':id', eventId) : '{{ route("agenda.events.store") }}';
        const method = 'POST';
        if (eventId) {
            formData.append('_method', 'PUT');
        }

        // Adicionar parâmetro update_all se for edição de evento recorrente.
        if (eventId && updateAll) {
            formData.append('update_all', '1');
        }
        
        fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const errors = data.errors ? Object.values(data.errors).flat().join('\n') : null;
                throw new Error(errors || data.message || 'Erro ao salvar evento');
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                reloadCalendarEvents();
                const modal = bootstrap.Modal.getInstance(document.getElementById('addEventModal'));
                if (modal) modal.hide();
                resetEventForm();
                
                // Mostrar mensagem se múltiplos eventos foram criados/atualizados
                if (data.message) {
                    alert(data.message);
                }
            } else {
                alert(data.message || 'Erro ao salvar evento');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Erro ao salvar evento');
        });
    }
    
    // Formulário de evento (somente admin)
    @if($canCreateEvents || $canEditEvents)
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const eventId = document.getElementById('eventId').value;
            const recurrenceValue = document.getElementById('eventRecurrence').value;
            const formData = new FormData();
            formData.append('title', document.getElementById('eventTitle').value || '');
            formData.append('description', document.getElementById('eventDescription').value || '');
            formData.append('start_date', document.getElementById('eventStartDate').value || '');
            formData.append('start_time', document.getElementById('eventStartTimeHour').value.padStart(2, '0') + ':' +
                document.getElementById('eventStartTimeMinute').value.padStart(2, '0'));
            formData.append('end_date', document.getElementById('eventEndDate').value || '');
            formData.append('end_time', document.getElementById('eventEndTimeHour').value.padStart(2, '0') + ':' +
                document.getElementById('eventEndTimeMinute').value.padStart(2, '0'));
            formData.append('all_day', document.getElementById('eventAllDay').checked ? '1' : '0');
            if (recurrenceValue !== 'null') {
                formData.append('recurrence', recurrenceValue);
            }
            formData.append('visibility', document.getElementById('eventVisibility').value || 'public');
            formData.append('location', document.getElementById('eventLocation').value || '');
            formData.append('category_id', document.getElementById('eventCategory').value || '');

            const bannerInput = document.getElementById('eventBannerImage');
            if (bannerInput && bannerInput.files && bannerInput.files[0]) {
                formData.append('banner_image', bannerInput.files[0]);
            }
            
            // Se for edição de evento, verificar se é recorrente
            if (eventId) {
                // Buscar dados do evento para verificar se é recorrente
                const showUrl = '{{ route("agenda.events.show", ":id") }}'.replace(':id', eventId);
                fetch(showUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && (data.event.recurrence === 'weekly' || data.event.recurrence === 'biweekly')) {
                        // Se for evento recorrente, mostrar modal de escolha
                        pendingFormData = formData;
                        const updateModal = new bootstrap.Modal(document.getElementById('updateEventModal'));
                        updateModal.show();
                    } else {
                        // Se não for recorrente, salvar diretamente
                        saveEvent(formData, false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Em caso de erro, salvar diretamente
                    saveEvent(formData, false);
                });
            } else {
                // Se for novo evento, salvar diretamente
                saveEvent(formData, false);
            }
        });
    }
    @endif
    
    // Botões do modal de edição (somente admin)
    @if($canEditEvents)
    const btnUpdateOne = document.getElementById('btnUpdateOne');
    if (btnUpdateOne) {
        btnUpdateOne.addEventListener('click', function() {
            if (pendingFormData) {
                saveEvent(pendingFormData, false);
                const updateModal = bootstrap.Modal.getInstance(document.getElementById('updateEventModal'));
                if (updateModal) updateModal.hide();
                pendingFormData = null;
            }
        });
    }
    
    const btnUpdateAll = document.getElementById('btnUpdateAll');
    if (btnUpdateAll) {
        btnUpdateAll.addEventListener('click', function() {
            if (pendingFormData) {
                if (confirm('Tem certeza que deseja atualizar TODAS as ocorrências deste evento?')) {
                    saveEvent(pendingFormData, true);
                    const updateModal = bootstrap.Modal.getInstance(document.getElementById('updateEventModal'));
                    if (updateModal) updateModal.hide();
                    pendingFormData = null;
                }
            }
        });
    }
    @endif
    
    // Variável para armazenar o ID do evento a ser removido
    let eventToDeleteId = null;
    
    // Botão remover evento (somente admin)
    @if($canDeleteEvents)
    const btnDeleteEvent = document.getElementById('btnDeleteEvent');
    if (btnDeleteEvent) {
        btnDeleteEvent.addEventListener('click', function() {
            eventToDeleteId = document.getElementById('eventId').value;
            if (!eventToDeleteId) return;
            
            // Verificar se o evento é recorrente (buscar dados do evento)
            const showUrl = '{{ route("agenda.events.show", ":id") }}'.replace(':id', eventToDeleteId);
            fetch(showUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && (data.event.recurrence === 'weekly' || data.event.recurrence === 'biweekly')) {
                    // Se for evento recorrente, mostrar modal de escolha
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteEventModal'));
                    deleteModal.show();
                } else {
                    // Se não for recorrente, remover diretamente
                    if (confirm('Tem certeza que deseja remover este evento?')) {
                        deleteEvent(eventToDeleteId, false);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Em caso de erro, remover diretamente
                if (confirm('Tem certeza que deseja remover este evento?')) {
                    deleteEvent(eventToDeleteId, false);
                }
            });
        });
    }
    @endif
    
    // Função para remover evento
    function deleteEvent(eventId, deleteAll) {
        const deleteUrl = '{{ route("agenda.events.destroy", ":id") }}'.replace(':id', eventId);
        const params = deleteAll ? '?delete_all=1' : '';
        
        fetch(deleteUrl + params, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                reloadCalendarEvents();
                const modal = bootstrap.Modal.getInstance(document.getElementById('addEventModal'));
                if (modal) modal.hide();
                const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteEventModal'));
                if (deleteModal) deleteModal.hide();
                resetEventForm();
                
                if (data.message) {
                    alert(data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao remover evento');
        });
    }
    
    // Botões do modal de confirmação (somente admin)
    @if($canDeleteEvents)
    const btnDeleteOne = document.getElementById('btnDeleteOne');
    if (btnDeleteOne) {
        btnDeleteOne.addEventListener('click', function() {
            if (eventToDeleteId) {
                deleteEvent(eventToDeleteId, false);
            }
        });
    }
    
    const btnDeleteAll = document.getElementById('btnDeleteAll');
    if (btnDeleteAll) {
        btnDeleteAll.addEventListener('click', function() {
            if (eventToDeleteId) {
                if (confirm('Tem certeza que deseja remover TODAS as ocorrências deste evento?')) {
                    deleteEvent(eventToDeleteId, true);
                }
            }
        });
    }
    @endif
    
    // Resetar formulário ao fechar modal (somente admin)
    @if($canCreateEvents || $canEditEvents)
    const addEventModal = document.getElementById('addEventModal');
    if (addEventModal) {
        addEventModal.addEventListener('hidden.bs.modal', function() {
            resetEventForm();
        });
    }
    
    // Toggle dia inteiro
    const eventAllDay = document.getElementById('eventAllDay');
    if (eventAllDay) {
        eventAllDay.addEventListener('change', function() {
            const timeInputs = document.querySelectorAll('#eventStartTimeHour, #eventStartTimeMinute, #eventEndTimeHour, #eventEndTimeMinute');
            timeInputs.forEach(input => {
                input.disabled = this.checked;
            });
        });
    }
    @endif
    
    // Remover categoria (somente admin)
    @if($canDeleteCategories)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-delete-category')) {
            const btn = e.target.closest('.btn-delete-category');
            const categoryId = btn.getAttribute('data-category-id');
            
            if (confirm('Tem certeza que deseja remover esta categoria?')) {
                const deleteUrl = '{{ route("agenda.categories.destroy", ":id") }}'.replace(':id', categoryId);
                
                fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    const data = await parseJsonResponse(response);
                    if (!response.ok) {
                        throw new Error(data.message || 'Erro ao remover categoria');
                    }
                    return data;
                })
                .then(data => {
                    if (data.success) {
                        // Remover o elemento da lista
                        const categoryItem = btn.closest('.category-item');
                        categoryItem.remove();

                        // Remover do select de categorias do modal
                        const categorySelect = document.getElementById('eventCategory');
                        if (categorySelect) {
                            const option = categorySelect.querySelector(`option[value="${categoryId}"]`);
                            if (option) option.remove();
                        }

                        // Recarregar eventos do calendário para atualizar categorias
                        reloadCalendarEvents();
                    } else {
                        alert(data.message || 'Erro ao remover categoria');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error.message || 'Erro ao remover categoria');
                });
            }
        }
    });
    @endif
});
</script>
@endpush
