@extends('layouts.porto')

@section('title', 'Visualizar Escala Mensal de Culto')

@section('page-title', 'Visualizar Escala Mensal de Culto')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas-mensais.index') }}">Escalas Mensais</a></li>
    <li><span>Visualizar</span></li>
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
@php
    $shortName = function ($fullName) {
        $parts = preg_split('/\s+/', trim((string) $fullName), -1, PREG_SPLIT_NO_EMPTY);
        return empty($parts) ? 'Sem nome' : implode(' ', array_slice($parts, 0, 2));
    };
    $firstName = function ($fullName) {
        $parts = preg_split('/\s+/', trim((string) $fullName), -1, PREG_SPLIT_NO_EMPTY);
        return empty($parts) ? 'Sem nome' : ($parts[0] ?? 'Sem nome');
    };
@endphp

<!-- Header da Escala -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div class="flex-grow-1">
                        <h3 class="mb-2">
                            <i class="bx bx-calendar-check me-2 text-primary"></i>{{ $escala->event->title }}
                        </h3>
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-calendar me-2 text-muted"></i>
                                <span class="text-muted">{{ $escala->event->start_date->format('d/m/Y') }}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bx bx-time me-2 text-muted"></i>
                                <span class="text-muted">{{ $escala->event->start_date->format('H:i') }}</span>
                            </div>
                            @if($escala->event->location)
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-map me-2 text-muted"></i>
                                    <span class="text-muted">{{ $escala->event->location }}</span>
                                </div>
                            @endif
                            <div>
                                @if($escala->status == 'publicada')
                                    <span class="badge badge-success px-3 py-2">
                                        <i class="bx bx-check-circle me-1"></i>Publicada
                                    </span>
                                @elseif($escala->status == 'cancelada')
                                    <span class="badge badge-danger px-3 py-2">
                                        <i class="bx bx-x-circle me-1"></i>Cancelada
                                    </span>
                                @elseif($escala->status == 'concluido')
                                    <span class="badge badge-info px-3 py-2">
                                        <i class="bx bx-check me-1"></i>Concluído
                                    </span>
                                @else
                                    <span class="badge badge-warning px-3 py-2">
                                        <i class="bx bx-edit me-1"></i>Rascunho
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#notifyAllVolunteersModal">
                            <i class="bx bxl-whatsapp me-2"></i>Notificar Todo Mundo
                        </button>
                        @if($escala->status == 'publicada')
                            <a href="{{ route('voluntarios.escalas-mensais.pdf', $escala) }}" class="btn btn-info" target="_blank" title="Salvar escala em PDF">
                                <i class="bx bx-download me-2"></i>Exportar PDF
                            </a>
                        @endif
                        @if($escala->status == 'rascunho')
                            <a href="{{ route('voluntarios.escalas-mensais.edit', $escala) }}" class="btn btn-primary">
                                <i class="bx bx-edit me-2"></i>Editar
                            </a>
                            <form action="{{ route('voluntarios.escalas-mensais.publish', $escala) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success" onclick="return confirm('Deseja publicar esta escala?')">
                                    <i class="bx bx-check me-2"></i>Publicar
                                </button>
                            </form>
                        @endif
                        @if($escala->status == 'cancelada')
                            <form action="{{ route('voluntarios.escalas-mensais.publish', $escala) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success" onclick="return confirm('Deseja republicar esta escala?')">
                                    <i class="bx bx-check me-2"></i>Republicar
                                </button>
                            </form>
                        @endif
                        @if($escala->status != 'cancelada' && $escala->status != 'publicada')
                            <form action="{{ route('voluntarios.escalas-mensais.cancel', $escala) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Deseja cancelar esta escala?')">
                                    <i class="bx bx-x me-2"></i>Cancelar
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('voluntarios.escalas-mensais.index', ['month' => $escala->month, 'year' => $escala->year]) }}" class="btn btn-default">
                            <i class="bx bx-arrow-back me-2"></i>Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grid de Áreas de Serviço -->
<div class="row">
    @foreach($serviceAreas as $area)
        @php
            $volunteers = $volunteersByArea[$area->id] ?? collect();
            $confirmedCount = $volunteers->filter(function($v) {
                return ($v->pivot->status ?? 'pendente') == 'confirmado';
            })->count();
            $totalCount = $volunteers->count();
            $minQuantity = $area->min_quantity ?? 1;
            $isComplete = $confirmedCount >= $minQuantity && $totalCount >= $minQuantity;
            $isIncomplete = $totalCount < $minQuantity;
            
            // Determinar cor da borda do card
            $borderClass = 'border-primary';
            if ($isComplete) {
                $borderClass = 'border-success';
            } elseif ($isIncomplete) {
                $borderClass = 'border-warning';
            }
        @endphp
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <div class="card h-100 {{ $borderClass }} border-2 shadow-sm">
                <!-- Header do Card da Área -->
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        @php
                            $areaIcons = [
                                'Portaria' => 'bx-door-open',
                                'Recepção' => 'bx-user-voice',
                                'Água' => 'bx-water',
                                'Direção de Culto' => 'bx-microphone',
                                'Sala das Crianças' => 'bx-child',
                                'Apoio Geral' => 'bx-support',
                                'Intercessão' => 'bx-pray',
                                'Preletor(a)' => 'bx-book',
                                'Preletor' => 'bx-book',
                            ];
                            $icon = $areaIcons[$area->name] ?? 'bx-group';
                        @endphp
                        <i class="bx {{ $icon }} me-2 text-primary fs-5"></i>
                        <h6 class="mb-0 fw-bold">{{ $area->name }}</h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($isComplete)
                            <span class="badge badge-success px-2 py-1">
                                <i class="bx bx-check-circle me-1"></i>{{ $confirmedCount }}/{{ $minQuantity }}
                            </span>
                        @elseif($isIncomplete)
                            <span class="badge badge-warning px-2 py-1">
                                <i class="bx bx-error-circle me-1"></i>{{ $totalCount }}/{{ $minQuantity }}
                            </span>
                        @else
                            <span class="badge badge-info px-2 py-1">
                                {{ $confirmedCount }}/{{ $minQuantity }}
                            </span>
                        @endif
                    </div>
                </div>
                
                <!-- Body do Card -->
                <div class="card-body">
                    @if($area->leader)
                        <div class="mb-3 pb-2 border-bottom">
                            <small class="text-muted d-block mb-1">Responsável</small>
                            <div class="d-flex align-items-center">
                                <i class="bx bx-user me-2 text-primary"></i>
                                <strong>{{ $shortName($area->leader->name) }}</strong>
                            </div>
                        </div>
                    @endif

                    @if($volunteers->count() > 0)
                        <div class="volunteers-list">
                            @foreach($volunteers as $volunteer)
                                @php
                                    $pivotId = $volunteer->pivot->id ?? null;
                                    $status = $volunteer->pivot->status ?? 'pendente';
                                @endphp
                                <div class="volunteer-item mb-3 pb-3 border-bottom volunteer-row" 
                                     data-pivot-id="{{ $pivotId }}"
                                     data-service-area-id="{{ $area->id }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <strong class="me-2">{{ $firstName($volunteer->member->name ?? null) }}</strong>
                                                @if($status == 'confirmado')
                                                    <span class="badge badge-success badge-sm">
                                                        <i class="bx bx-check-circle me-1"></i>Confirmado
                                                    </span>
                                                @elseif($status == 'cancelado')
                                                    <span class="badge badge-danger badge-sm">
                                                        <i class="bx bx-x-circle me-1"></i>Cancelado
                                                    </span>
                                                @else
                                                    <span class="badge badge-warning badge-sm">
                                                        <i class="bx bx-time me-1"></i>Pendente
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="volunteer-actions">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @if($status == 'pendente')
                                                    <button type="button" class="btn btn-sm btn-success confirm-volunteer" 
                                                            data-pivot-id="{{ $pivotId }}"
                                                            title="Confirmar">
                                                        <i class="bx bx-check"></i>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-default substitute-volunteer" 
                                                        data-pivot-id="{{ $pivotId }}"
                                                        data-service-area-id="{{ $area->id }}"
                                                        title="Substituir">
                                                    <i class="bx bx-refresh"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info notify-volunteer"
                                                        data-pivot-id="{{ $pivotId }}"
                                                        data-volunteer-name="{{ $volunteer->member->name ?? 'Sem nome' }}"
                                                        data-service-area-name="{{ $area->name }}"
                                                        title="Notificar WhatsApp">
                                                    <i class="bx bxl-whatsapp"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger remove-volunteer" 
                                                        data-pivot-id="{{ $pivotId }}"
                                                        title="Remover">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bx bx-user-x fs-1 text-muted mb-2 d-block"></i>
                            <p class="text-muted mb-0">Nenhum voluntário atribuído</p>
                            <button type="button"
                                    class="btn btn-sm btn-primary mt-2 add-volunteer-manual"
                                    data-service-area-id="{{ $area->id }}">
                                <i class="bx bx-plus me-1"></i>Adicionar Manualmente
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Modal: Notificar Todos -->
<div class="modal fade" id="notifyAllVolunteersModal" tabindex="-1" aria-labelledby="notifyAllVolunteersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('voluntarios.escalas-mensais.volunteers.notify-all', $escala) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="notifyAllVolunteersModalLabel">
                        <i class="bx bxl-whatsapp me-2"></i>Notificar Todos os Escalados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Template de mensagem (opcional)</label>
                        <select class="form-select" id="notify_all_template_id" name="template_id">
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
                        <textarea class="form-control" id="notify_all_message" name="mensagem" rows="5" placeholder="Digite a mensagem ou selecione um template acima"></textarea>
                        <small class="text-muted">
                            Variáveis disponíveis: <code>{nome}</code>, <code>{culto}</code>, <code>{dia_culto}</code>, <code>{hora_culto}</code>, <code>{area_servico}</code>, <code>{local_servico}</code>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivo de mídia (opcional)</label>
                        <input type="file" class="form-control" name="arquivo" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="notify_all_send_pdf" name="enviar_pdf">
                        <label class="form-check-label" for="notify_all_send_pdf">
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

<!-- Modal: Notificar Voluntário -->
<div class="modal fade" id="notifyVolunteerModal" tabindex="-1" aria-labelledby="notifyVolunteerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('voluntarios.escalas-mensais.volunteers.notify') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="notify_pivot_id" name="pivot_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="notifyVolunteerModalLabel">
                        <i class="bx bxl-whatsapp me-2"></i>Notificar Voluntário no WhatsApp
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <strong id="notify_volunteer_name">Voluntário</strong>
                        <span class="text-muted"> - Área: <span id="notify_area_name"></span></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Template de mensagem (opcional)</label>
                        <select class="form-select" id="notify_template_id" name="template_id">
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
                        <textarea class="form-control" id="notify_message" name="mensagem" rows="5" placeholder="Digite a mensagem ou selecione um template acima"></textarea>
                        <small class="text-muted">
                            Você pode usar variáveis como: <code>{nome}</code>, <code>{culto}</code>, <code>{dia_culto}</code>, <code>{hora_culto}</code>, <code>{area_servico}</code>, <code>{local_servico}</code>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivo de mídia (opcional)</label>
                        <input type="file" class="form-control" name="arquivo" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx">
                        <small class="text-muted">Se informar arquivo, ele será enviado com a mensagem como legenda.</small>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="notify_send_pdf" name="enviar_pdf">
                        <label class="form-check-label" for="notify_send_pdf">
                            Enviar também o PDF da escala do dia
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bxl-whatsapp me-1"></i>Enviar Notificação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Voluntário Manualmente -->
<div class="modal fade" id="addVolunteerModal" tabindex="-1" aria-labelledby="addVolunteerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVolunteerModalLabel">
                    <i class="bx bx-user-plus me-2"></i>Adicionar Voluntário Manualmente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="addVolunteerForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="add_service_area_id" name="service_area_id">

                    <div class="mb-3">
                        <label for="add_volunteer_id" class="form-label">Selecione o voluntário <span class="text-danger">*</span></label>
                        <select class="form-select" id="add_volunteer_id" name="volunteer_id" required>
                            <option value="">Carregando voluntários...</option>
                        </select>
                        <small class="form-text text-muted">
                            Só aparecem voluntários da área selecionada e ainda não escalados neste culto.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-check me-1"></i>Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Substituir Voluntário -->
<div class="modal fade" id="substituteVolunteerModal" tabindex="-1" aria-labelledby="substituteVolunteerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="substituteVolunteerModalLabel">
                    <i class="bx bx-refresh me-2"></i>Substituir Voluntário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="substituteVolunteerForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="substitute_pivot_id" name="pivot_id">
                    <input type="hidden" id="substitute_service_area_id" name="service_area_id">
                    
                    <div class="mb-3">
                        <label for="new_volunteer_id" class="form-label">Selecione o novo voluntário <span class="text-danger">*</span></label>
                        <select class="form-select" id="new_volunteer_id" name="new_volunteer_id" required>
                            <option value="">Carregando voluntários...</option>
                        </select>
                        <small class="form-text text-muted">Os voluntários são filtrados pela área de serviço</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-check me-1"></i>Substituir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .volunteer-row {
        transition: all 0.2s ease;
        border-radius: 6px;
        padding: 12px;
    }
    
    .volunteer-row:hover {
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }
    
    .badge-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        font-weight: 500;
    }
    
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    }
    
    .volunteer-item {
        transition: all 0.2s;
    }
    
    .volunteer-actions {
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    
    .volunteer-row:hover .volunteer-actions {
        opacity: 1;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirmar voluntário
    document.addEventListener('click', function(e) {
        if (e.target.closest('.confirm-volunteer')) {
            const btn = e.target.closest('.confirm-volunteer');
            const pivotId = btn.getAttribute('data-pivot-id');
            
            if (confirm('Deseja confirmar este voluntário?')) {
                fetch(`{{ url('/servico/voluntarios/escalas-mensais/volunteers') }}/${pivotId}/confirm`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao confirmar voluntário');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao confirmar voluntário');
                });
            }
        }
        
        // Substituir voluntário
        if (e.target.closest('.substitute-volunteer')) {
            const btn = e.target.closest('.substitute-volunteer');
            const pivotId = btn.getAttribute('data-pivot-id');
            const serviceAreaId = btn.getAttribute('data-service-area-id');
            
            // Preencher modal
            document.getElementById('substitute_pivot_id').value = pivotId;
            document.getElementById('substitute_service_area_id').value = serviceAreaId;
            
            // Carregar voluntários disponíveis
            const newVolunteerSelect = document.getElementById('new_volunteer_id');
            newVolunteerSelect.innerHTML = '<option value="">Carregando...</option>';
            
            // Buscar voluntários disponíveis para a área
            fetch(`{{ url('/servico/voluntarios/escalas-mensais') }}/volunteers/available?service_area_id=${serviceAreaId}&schedule_id={{ $escala->id }}`)
                .then(response => response.json())
                .then(data => {
                    newVolunteerSelect.innerHTML = '<option value="">Selecione um voluntário...</option>';
                    if (data.volunteers && data.volunteers.length > 0) {
                        data.volunteers.forEach(volunteer => {
                            const option = document.createElement('option');
                            option.value = volunteer.id;
                            option.textContent = volunteer.name;
                            newVolunteerSelect.appendChild(option);
                        });
                    } else {
                        newVolunteerSelect.innerHTML = '<option value="">Nenhum voluntário disponível</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar voluntários:', error);
                    newVolunteerSelect.innerHTML = '<option value="">Erro ao carregar voluntários</option>';
                });
            
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('substituteVolunteerModal'));
            modal.show();
        }

        // Notificar voluntário no WhatsApp
        if (e.target.closest('.notify-volunteer')) {
            const btn = e.target.closest('.notify-volunteer');
            const pivotId = btn.getAttribute('data-pivot-id');
            const volunteerName = btn.getAttribute('data-volunteer-name') || 'Voluntário';
            const areaName = btn.getAttribute('data-service-area-name') || '-';

            document.getElementById('notify_pivot_id').value = pivotId;
            document.getElementById('notify_volunteer_name').textContent = volunteerName;
            document.getElementById('notify_area_name').textContent = areaName;
            document.getElementById('notify_template_id').value = '';
            document.getElementById('notify_message').value = '';
            document.getElementById('notify_send_pdf').checked = false;

            const notifyModal = new bootstrap.Modal(document.getElementById('notifyVolunteerModal'));
            notifyModal.show();
        }

        // Adicionar voluntário manualmente
        if (e.target.closest('.add-volunteer-manual')) {
            const btn = e.target.closest('.add-volunteer-manual');
            const serviceAreaId = btn.getAttribute('data-service-area-id');

            document.getElementById('add_service_area_id').value = serviceAreaId;
            const addVolunteerSelect = document.getElementById('add_volunteer_id');
            addVolunteerSelect.innerHTML = '<option value="">Carregando...</option>';

            fetch(`{{ url('/servico/voluntarios/escalas-mensais') }}/volunteers/available?service_area_id=${serviceAreaId}&schedule_id={{ $escala->id }}`)
                .then(response => response.json())
                .then(data => {
                    addVolunteerSelect.innerHTML = '<option value="">Selecione um voluntário...</option>';
                    if (data.volunteers && data.volunteers.length > 0) {
                        data.volunteers.forEach(volunteer => {
                            const option = document.createElement('option');
                            option.value = volunteer.id;
                            option.textContent = volunteer.name;
                            addVolunteerSelect.appendChild(option);
                        });
                    } else {
                        addVolunteerSelect.innerHTML = '<option value="">Nenhum voluntário disponível</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar voluntários:', error);
                    addVolunteerSelect.innerHTML = '<option value="">Erro ao carregar voluntários</option>';
                });

            const addModal = new bootstrap.Modal(document.getElementById('addVolunteerModal'));
            addModal.show();
        }
        
        // Remover voluntário
        if (e.target.closest('.remove-volunteer')) {
            const btn = e.target.closest('.remove-volunteer');
            const pivotId = btn.getAttribute('data-pivot-id');
            
            if (confirm('Deseja remover este voluntário da escala?')) {
                fetch(`{{ url('/servico/voluntarios/escalas-mensais/volunteers') }}/${pivotId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao remover voluntário');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao remover voluntário');
                });
            }
        }
    });
    
    // Formulário de substituição
    document.getElementById('substituteVolunteerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const pivotId = document.getElementById('substitute_pivot_id').value;
        const newVolunteerId = document.getElementById('new_volunteer_id').value;
        
        if (!newVolunteerId) {
            alert('Selecione um voluntário');
            return;
        }
        
        fetch(`{{ url('/servico/voluntarios/escalas-mensais/volunteers') }}/${pivotId}/substitute`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                new_volunteer_id: newVolunteerId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('substituteVolunteerModal')).hide();
                location.reload();
            } else {
                alert(data.message || 'Erro ao substituir voluntário');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao substituir voluntário');
        });
    });

    // Formulário de adição manual
    document.getElementById('addVolunteerForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const serviceAreaId = document.getElementById('add_service_area_id').value;
        const volunteerId = document.getElementById('add_volunteer_id').value;

        if (!volunteerId) {
            alert('Selecione um voluntário');
            return;
        }

        fetch(`{{ route('voluntarios.escalas-mensais.volunteers.add', $escala) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                service_area_id: serviceAreaId,
                volunteer_id: volunteerId
            })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Erro ao adicionar voluntário');
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addVolunteerModal')).hide();
                location.reload();
            } else {
                alert(data.message || 'Erro ao adicionar voluntário');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert(error.message || 'Erro ao adicionar voluntário');
        });
    });

    // Preencher mensagem a partir do template selecionado
    const templateSelect = document.getElementById('notify_template_id');
    const messageTextarea = document.getElementById('notify_message');
    if (templateSelect && messageTextarea) {
        templateSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const templateText = selectedOption.getAttribute('data-template') || '';
            if (templateText) {
                messageTextarea.value = templateText;
            }
        });
    }

    // Preencher mensagem de lote a partir do template selecionado
    const allTemplateSelect = document.getElementById('notify_all_template_id');
    const allMessageTextarea = document.getElementById('notify_all_message');
    if (allTemplateSelect && allMessageTextarea) {
        allTemplateSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const templateText = selectedOption.getAttribute('data-template') || '';
            if (templateText) {
                allMessageTextarea.value = templateText;
            }
        });
    }
});
</script>
@endpush
@endsection
