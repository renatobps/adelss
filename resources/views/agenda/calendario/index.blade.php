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

<div class="row">
    <div class="col-lg-9">
        <section class="card">
            <header class="card-header">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h2 class="card-title mb-0">Calendário</h2>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary" id="btnMonth">Mês</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnListMonthly">Lista mensal</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnListWeekly">Lista semanal</button>
                    </div>
                </div>
            </header>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </section>
    </div>
    <div class="col-lg-3">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Categorias</h2>
            </header>
            <div class="card-body">
                @if($canCreateCategories)
                <form id="categoryForm">
                    @csrf
                    <div class="mb-3">
                        <input type="text" class="form-control" id="categoryName" placeholder="Nova categoria" required>
                    </div>
                    <div class="mb-3">
                        <input type="color" class="form-control form-control-color" id="categoryColor" value="#0088cc" title="Escolha a cor">
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bx bx-plus me-1"></i>Adicionar
                    </button>
                </form>
                <hr>
                @endif
                <div id="categoriesList">
                    @foreach($categories as $category)
                        <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded category-item" data-category-id="{{ $category->id }}">
                            <div class="d-flex align-items-center">
                                <span class="badge me-2" style="background-color: {{ $category->color }}; width: 20px; height: 20px; display: inline-block; border-radius: 4px;"></span>
                                <span>{{ $category->name }}</span>
                            </div>
                            @if($canDeleteCategories)
                            <button type="button" class="btn btn-sm btn-danger btn-delete-category" data-category-id="{{ $category->id }}" title="Remover categoria">
                                <i class="bx bx-trash"></i>
                            </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal Adicionar/Editar Evento -->
@if($canCreateEvents || $canEditEvents)
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addEventModalLabel">Adicionar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="eventForm">
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
                                <input type="number" class="form-control" id="eventStartTimeHour" min="0" max="23" value="19" style="width: 60px;">
                                <span class="mx-2">:</span>
                                <input type="number" class="form-control" id="eventStartTimeMinute" min="0" max="59" value="30" style="width: 60px;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="eventEndTime" class="form-label">Hora Fim</label>
                            <div class="d-flex align-items-center">
                                <input type="number" class="form-control" id="eventEndTimeHour" min="0" max="23" value="21" style="width: 60px;">
                                <span class="mx-2">:</span>
                                <input type="number" class="form-control" id="eventEndTimeMinute" min="0" max="59" value="0" style="width: 60px;">
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
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventLocation" class="form-label">Local</label>
                        <input type="text" class="form-control" id="eventLocation" name="location">
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" id="eventDescription" name="description" rows="5" placeholder="Insert text here..."></textarea>
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
<style>
    .fc-event {
        cursor: pointer !important;
        transition: opacity 0.2s;
    }
    .fc-event:hover {
        opacity: 0.8;
    }
    .fc-event-title {
        font-weight: 500;
    }
</style>
@endpush

@push('scripts')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    let selectedDate = null;
    let currentView = 'dayGridMonth';
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia'
        },
        events: '{{ route("agenda.events.index") }}',
        editable: false,
        selectable: {{ ($canCreateEvents) ? 'true' : 'false' }},
        selectMirror: {{ ($canCreateEvents) ? 'true' : 'false' }},
        dayMaxEvents: true,
        eventInteractive: true,
        eventDisplay: 'block',
        eventTextColor: '#ffffff',
        eventDidMount: function(arg) {
            arg.el.style.cursor = 'pointer';
            arg.el.style.fontWeight = '500';
        },
        select: function(arg) {
            @if($canCreateEvents)
            selectedDate = arg.startStr;
            resetEventForm();
            const startDate = new Date(arg.start);
            document.getElementById('eventStartDate').value = startDate.toISOString().split('T')[0];
            document.getElementById('eventEndDate').value = startDate.toISOString().split('T')[0];
            document.getElementById('addEventModalLabel').textContent = 'Adicionar';
            document.getElementById('btnDeleteEvent').style.display = 'none';
            const modal = new bootstrap.Modal(document.getElementById('addEventModal'));
            modal.show();
            calendar.unselect();
            @else
            calendar.unselect();
            @endif
        },
        eventClick: function(arg) {
            // Prevenir comportamento padrão
            arg.jsEvent.preventDefault();
            
            // Buscar dados do evento
            const eventId = arg.event.id;
            
            if (!eventId) {
                console.error('Event ID não encontrado');
                alert('Erro: ID do evento não encontrado');
                return;
            }
            
            const url = '{{ route("agenda.events.show", ":id") }}'.replace(':id', eventId);
            
            console.log('Carregando evento ID:', eventId);
            console.log('URL:', url);
            
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
                    console.log('Dados recebidos:', data);
                    if (data.success && data.event) {
                        @if($canEditEvents)
                        loadEventToForm(data.event);
                        document.getElementById('addEventModalLabel').textContent = 'Editar - ID ' + data.event.id;
                        document.getElementById('btnDeleteEvent').style.display = 'block';
                        const modal = new bootstrap.Modal(document.getElementById('addEventModal'));
                        modal.show();
                        @else
                        // Para usuários comuns, apenas mostrar informações do evento
                        const event = data.event;
                        let info = 'Evento: ' + event.title + '\n';
                        if (event.description) info += 'Descrição: ' + event.description + '\n';
                        if (event.location) info += 'Local: ' + event.location + '\n';
                        if (event.start_date) info += 'Data: ' + event.start_date + '\n';
                        if (event.start_time) info += 'Hora: ' + event.start_time;
                        alert(info);
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
    });
    
    calendar.render();
    
    // Botões de visualização
    document.getElementById('btnMonth').addEventListener('click', function() {
        calendar.changeView('dayGridMonth');
        currentView = 'dayGridMonth';
        updateButtons('month');
    });
    
    document.getElementById('btnListMonthly').addEventListener('click', function() {
        calendar.changeView('listMonth');
        currentView = 'listMonth';
        updateButtons('listMonthly');
    });
    
    document.getElementById('btnListWeekly').addEventListener('click', function() {
        calendar.changeView('listWeek');
        currentView = 'listWeek';
        updateButtons('listWeekly');
    });
    
    function updateButtons(active) {
        document.getElementById('btnMonth').classList.remove('btn-primary');
        document.getElementById('btnMonth').classList.add('btn-outline-primary');
        document.getElementById('btnListMonthly').classList.remove('btn-primary');
        document.getElementById('btnListMonthly').classList.add('btn-outline-primary');
        document.getElementById('btnListWeekly').classList.remove('btn-primary');
        document.getElementById('btnListWeekly').classList.add('btn-outline-primary');
        
        if (active === 'month') {
            document.getElementById('btnMonth').classList.remove('btn-outline-primary');
            document.getElementById('btnMonth').classList.add('btn-primary');
        } else if (active === 'listMonthly') {
            document.getElementById('btnListMonthly').classList.remove('btn-outline-primary');
            document.getElementById('btnListMonthly').classList.add('btn-primary');
        } else if (active === 'listWeekly') {
            document.getElementById('btnListWeekly').classList.remove('btn-outline-primary');
            document.getElementById('btnListWeekly').classList.add('btn-primary');
        }
    }
    
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                },
                body: JSON.stringify({ name, color })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Adicionar categoria à lista
                    const categoriesList = document.getElementById('categoriesList');
                    const categoryDiv = document.createElement('div');
                    categoryDiv.className = 'd-flex align-items-center justify-content-between mb-2 p-2 border rounded category-item';
                    categoryDiv.setAttribute('data-category-id', data.category.id);
                    categoryDiv.innerHTML = `
                        <div class="d-flex align-items-center">
                            <span class="badge me-2" style="background-color: ${data.category.color}; width: 20px; height: 20px; display: inline-block; border-radius: 4px;"></span>
                            <span>${data.category.name}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-category" data-category-id="${data.category.id}" title="Remover categoria">
                            <i class="bx bx-trash"></i>
                        </button>
                    `;
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
                alert('Erro ao adicionar categoria');
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
        document.getElementById('addEventModalLabel').textContent = 'Adicionar';
        document.getElementById('btnDeleteEvent').style.display = 'none';
        
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
        const method = eventId ? 'PUT' : 'POST';
        
        // Adicionar parâmetro update_all se for edição de evento recorrente
        if (eventId && updateAll) {
            formData.update_all = true;
        }
        
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                calendar.refetchEvents();
                const modal = bootstrap.Modal.getInstance(document.getElementById('addEventModal'));
                if (modal) modal.hide();
                resetEventForm();
                
                // Mostrar mensagem se múltiplos eventos foram criados/atualizados
                if (data.message) {
                    alert(data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao salvar evento');
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
            const formData = {
                title: document.getElementById('eventTitle').value,
                description: document.getElementById('eventDescription').value,
                start_date: document.getElementById('eventStartDate').value,
                start_time: document.getElementById('eventStartTimeHour').value.padStart(2, '0') + ':' + 
                           document.getElementById('eventStartTimeMinute').value.padStart(2, '0'),
                end_date: document.getElementById('eventEndDate').value,
                end_time: document.getElementById('eventEndTimeHour').value.padStart(2, '0') + ':' + 
                         document.getElementById('eventEndTimeMinute').value.padStart(2, '0'),
                all_day: document.getElementById('eventAllDay').checked,
                recurrence: recurrenceValue === 'null' ? null : recurrenceValue,
                visibility: document.getElementById('eventVisibility').value,
                location: document.getElementById('eventLocation').value,
                category_id: document.getElementById('eventCategory').value || null
            };
            
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
                calendar.refetchEvents();
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
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remover o elemento da lista
                        const categoryItem = btn.closest('.category-item');
                        categoryItem.remove();
                        
                        // Recarregar eventos do calendário para atualizar categorias
                        calendar.refetchEvents();
                    } else {
                        alert(data.message || 'Erro ao remover categoria');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao remover categoria');
                });
            }
        }
    });
    @endif
});
</script>
@endpush
