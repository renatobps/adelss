@extends('layouts.porto')

@section('title', 'Visualizar Escala')

@section('page-title', 'Visualizar Escala')

@section('breadcrumbs')
    <li><a href="{{ route('servico.escalas.index') }}">Escalas</a></li>
    <li><span>Visualizar</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
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
                            <i class="bx bx-calendar-check me-2 text-primary"></i>{{ $escala->title }}
                        </h3>
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-calendar me-2 text-muted"></i>
                                <span class="text-muted">{{ $escala->date->format('d/m/Y') }}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bx bx-time me-2 text-muted"></i>
                                <span class="text-muted">{{ \Carbon\Carbon::parse($escala->start_time)->format('H:i') }}</span>
                            </div>
                            @if($escala->location)
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-map me-2 text-muted"></i>
                                    <span class="text-muted">{{ $escala->location }}</span>
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
                            <a href="{{ route('servico.escalas.pdf', $escala) }}" class="btn btn-info" target="_blank" title="Salvar escala em PDF">
                                <i class="bx bx-download me-2"></i>Exportar PDF
                            </a>
                        @endif
                        @if($escala->status == 'rascunho')
                            <a href="{{ route('servico.escalas.edit', $escala) }}" class="btn btn-primary">
                                <i class="bx bx-edit me-2"></i>Editar
                            </a>
                            <form action="{{ route('servico.escalas.publish', $escala) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success" onclick="return confirm('Deseja publicar esta escala?')">
                                    <i class="bx bx-check me-2"></i>Publicar
                                </button>
                            </form>
                        @endif
                        @if($escala->status == 'cancelada')
                            <form action="{{ route('servico.escalas.publish', $escala) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success" onclick="return confirm('Deseja republicar esta escala?')">
                                    <i class="bx bx-check me-2"></i>Republicar
                                </button>
                            </form>
                        @endif
                        @if($escala->status != 'cancelada' && $escala->status != 'publicada')
                            <form action="{{ route('servico.escalas.cancel', $escala) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Deseja cancelar esta escala?')">
                                    <i class="bx bx-x me-2"></i>Cancelar
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('servico.escalas.index') }}" class="btn btn-default">
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
    @foreach($escala->areas as $area)
        @php
            $confirmedCount = $area->volunteers->where('status', 'confirmado')->count();
            $totalCount = $area->volunteers->count();
            $requiredCount = $area->required_quantity;
            $isComplete = $confirmedCount >= $requiredCount;
            $isIncomplete = $totalCount < $requiredCount;
            
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
                            ];
                            $icon = $areaIcons[$area->serviceArea->name] ?? 'bx-group';
                        @endphp
                        <i class="bx {{ $icon }} me-2 text-primary fs-5"></i>
                        <h6 class="mb-0 fw-bold">{{ $area->serviceArea->name }}</h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($isComplete)
                            <span class="badge badge-success px-2 py-1">
                                <i class="bx bx-check-circle me-1"></i>{{ $confirmedCount }}/{{ $requiredCount }}
                            </span>
                        @elseif($isIncomplete)
                            <span class="badge badge-warning px-2 py-1">
                                <i class="bx bx-error-circle me-1"></i>{{ $totalCount }}/{{ $requiredCount }}
                            </span>
                        @else
                            <span class="badge badge-info px-2 py-1">
                                {{ $confirmedCount }}/{{ $requiredCount }}
                            </span>
                        @endif
                    </div>
                </div>
                
                <!-- Body do Card -->
                <div class="card-body">
                    @if($area->responsible)
                        <div class="mb-3 pb-2 border-bottom">
                            <small class="text-muted d-block mb-1">Responsável</small>
                            <div class="d-flex align-items-center">
                                <i class="bx bx-user me-2 text-primary"></i>
                                <strong>{{ $area->responsible->name }}</strong>
                            </div>
                        </div>
                    @endif

                    @if($area->volunteers->count() > 0)
                        <div class="volunteers-list">
                            @foreach($area->volunteers as $volunteerSchedule)
                                <div class="volunteer-item mb-3 pb-3 border-bottom volunteer-row" 
                                     data-volunteer-id="{{ $volunteerSchedule->id }}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <strong class="me-2">{{ $volunteerSchedule->volunteer->member->name }}</strong>
                                                @if($volunteerSchedule->status == 'confirmado')
                                                    <span class="badge badge-success badge-sm">
                                                        <i class="bx bx-check-circle me-1"></i>Confirmado
                                                    </span>
                                                @elseif($volunteerSchedule->status == 'cancelado')
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
                                                @if($volunteerSchedule->status == 'pendente')
                                                    <button type="button" class="btn btn-sm btn-success confirm-volunteer" 
                                                            data-volunteer-id="{{ $volunteerSchedule->id }}"
                                                            title="Confirmar">
                                                        <i class="bx bx-check"></i>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-default substitute-volunteer" 
                                                        data-volunteer-id="{{ $volunteerSchedule->id }}"
                                                        title="Substituir">
                                                    <i class="bx bx-refresh"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger remove-volunteer" 
                                                        data-volunteer-id="{{ $volunteerSchedule->id }}"
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
                
                <!-- Footer do Card -->
                <div class="card-footer bg-light">
                    <button type="button" class="btn btn-sm btn-primary w-100 add-volunteer-btn" 
                            data-area-id="{{ $area->id }}">
                        <i class="bx bx-plus me-2"></i>Adicionar Voluntário
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($escala->notes)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <i class="bx bx-info-circle me-2"></i>Observações
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $escala->notes }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

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
    
    @media (max-width: 768px) {
        .volunteer-actions {
            margin-top: 8px;
            opacity: 1;
        }
        
        .volunteer-actions .btn-group {
            width: 100%;
        }
        
        .volunteer-actions .btn {
            flex: 1;
        }
        
        .card-header h6 {
            font-size: 0.9rem;
        }
        
        .volunteer-item {
            padding: 10px !important;
        }
    }
    
    @media (min-width: 769px) and (max-width: 992px) {
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
    }
    
    @media (min-width: 993px) {
        .col-lg-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
    }
</style>

<script>
    // Confirmar voluntário
    document.addEventListener('click', function(e) {
        if (e.target.closest('.confirm-volunteer')) {
            const btn = e.target.closest('.confirm-volunteer');
            const volunteerId = btn.getAttribute('data-volunteer-id');
            
            if (confirm('Deseja confirmar este voluntário?')) {
                fetch(`/servico/escalas/volunteers/${volunteerId}/confirm`, {
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
            const volunteerId = btn.getAttribute('data-volunteer-id');
            alert('Funcionalidade de substituição em desenvolvimento');
        }
        
        // Remover voluntário
        if (e.target.closest('.remove-volunteer')) {
            const btn = e.target.closest('.remove-volunteer');
            const volunteerId = btn.getAttribute('data-volunteer-id');
            
            if (confirm('Deseja remover este voluntário da escala?')) {
                fetch(`/servico/escalas/volunteers/${volunteerId}`, {
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
        
        // Adicionar voluntário
        if (e.target.closest('.add-volunteer-btn')) {
            const btn = e.target.closest('.add-volunteer-btn');
            const areaId = btn.getAttribute('data-area-id');
            alert('Funcionalidade de adicionar voluntário em desenvolvimento');
        }
    });
</script>
@endsection
