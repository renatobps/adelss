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
                                <strong>{{ $area->leader->name }}</strong>
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
                                                <strong class="me-2">{{ $volunteer->member->name ?? 'Sem nome' }}</strong>
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
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
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
            fetch(`{{ url('/servico/voluntarios/escalas-mensais') }}/volunteers/available?service_area_id=${serviceAreaId}`)
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
});
</script>
@endpush
@endsection
