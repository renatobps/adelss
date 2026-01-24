@extends('layouts.porto')

@section('title', 'PGIs')

@section('page-title', 'PGIs')

@section('breadcrumbs')
    <li><span>PGIs</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewPgis = $isAdmin || 
                   ($user && ($user->hasPermission('pgis.index.view') || 
                              $user->hasPermission('pgis.index.manage')));
    $canCreatePgis = $isAdmin || 
                     ($user && ($user->hasPermission('pgis.index.create') || 
                                $user->hasPermission('pgis.index.manage')));
    $canEditPgis = $isAdmin || 
                   ($user && ($user->hasPermission('pgis.index.edit') || 
                              $user->hasPermission('pgis.index.manage')));
    $canDeletePgis = $isAdmin || 
                     ($user && ($user->hasPermission('pgis.index.delete') || 
                                $user->hasPermission('pgis.index.manage')));
@endphp

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
    <!-- Conteúdo Principal -->
    <div class="col-lg-9">
        <div class="mb-4">
            <h2 class="mb-1">PGIs</h2>
            <div class="alert alert-info mb-3" style="background-color: #e3f2fd; border: none; border-left: 4px solid #2196f3;">
                <i class="bx bx-info-circle me-2"></i>
                Cadastre todos os grupos da sua instituição, adicionando líderes e membros
            </div>
        </div>

        <!-- Contador de Resultados -->
        <div class="mb-3">
            <strong>Resultados: {{ $pgis->total() }}</strong>
        </div>

        @if($pgis->count() > 0)
            <div class="row">
                @foreach($pgis as $pgi)
                    @php
                        // Define cores baseadas no perfil e horário
                        $headerColors = [
                            'Masculino' => ['start' => '#4169E1', 'end' => '#1E90FF'],
                            'Feminino' => ['start' => '#FF69B4', 'end' => '#FF1493'],
                            'Misto' => ['start' => '#9370DB', 'end' => '#BA55D3'],
                            'default' => ['start' => '#87CEEB', 'end' => '#4682B4']
                        ];
                        $colors = $headerColors[$pgi->profile] ?? $headerColors['default'];
                        if ($pgi->time_schedule == 'Manhã') {
                            $colors = ['start' => '#FFD700', 'end' => '#FFA500'];
                        } elseif ($pgi->time_schedule == 'Tarde') {
                            $colors = ['start' => '#FF8C00', 'end' => '#FF6347'];
                        }
                    @endphp
                    <div class="col-md-4 mb-4">
                        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; height: 100%;">
                            <!-- Header com imagem/cor -->
                            <div class="card-header p-0" style="height: 180px; background: linear-gradient(135deg, {{ $colors['start'] }} 0%, {{ $colors['end'] }} 100%); position: relative; overflow: hidden;">
                                @if($pgi->banner_url)
                                    <!-- Banner como background -->
                                    <img src="{{ asset('storage/' . $pgi->banner_url) }}" 
                                         alt="Banner do PGI" 
                                         style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;">
                                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3);"></div>
                                @endif

                                <!-- Logo no canto superior esquerdo -->
                                <div class="position-absolute top-0 start-0 p-3" style="z-index: 2;">
                                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm overflow-hidden" 
                                         style="width: 70px; height: 70px; border: 3px solid rgba(255,255,255,0.8);">
                                        @if($pgi->logo_url)
                                            <img src="{{ asset('storage/' . $pgi->logo_url) }}" 
                                                 alt="Logo do PGI" 
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            @if($pgi->profile == 'Masculino')
                                                <i class="bx bx-male text-primary" style="font-size: 2.5rem;"></i>
                                            @elseif($pgi->profile == 'Feminino')
                                                <i class="bx bx-female text-danger" style="font-size: 2.5rem;"></i>
                                            @else
                                                <i class="bx bx-group text-info" style="font-size: 2.5rem;"></i>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                <!-- Ícone de fundo decorativo (apenas se não houver banner) -->
                                @if(!$pgi->banner_url)
                                <div class="position-absolute top-50 start-50 translate-middle" style="opacity: 0.15; z-index: 1;">
                                    @if($pgi->profile == 'Masculino')
                                        <i class="bx bx-male-sign text-white" style="font-size: 8rem;"></i>
                                    @elseif($pgi->profile == 'Feminino')
                                        <i class="bx bx-female-sign text-white" style="font-size: 8rem;"></i>
                                    @else
                                        <i class="bx bx-group text-white" style="font-size: 8rem;"></i>
                                    @endif
                                </div>
                                @endif

                                <!-- Nome do PGI no header -->
                                <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(to top, rgba(0,0,0,0.6), transparent); z-index: 2;">
                                    <h4 class="mb-0 text-white fw-bold" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.7); font-size: 1.5rem;">
                                        {{ $pgi->name }}
                                    </h4>
                                </div>
                            </div>

                            <!-- Body do Card -->
                            <div class="card-body" style="padding: 1.5rem;">
                                <!-- Informações básicas -->
                                <div class="mb-3">
                                    <span class="badge badge-primary me-2" style="background-color: #007bff; padding: 6px 12px;">
                                        {{ $pgi->members->count() }} membros
                                    </span>
                                    @if($pgi->day_of_week)
                                        <span class="badge badge-info" style="background-color: #17a2b8; padding: 6px 12px;">
                                            {{ ucfirst($pgi->day_of_week) }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Liderança com fotos -->
                                <div class="mb-3">
                                    <strong class="d-block mb-2 small text-muted">Liderança:</strong>
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        @if($pgi->leader1)
                                            <div class="position-relative">
                                                @if($pgi->leader1->photo_url)
                                                    <img src="{{ $pgi->leader1->photo_url }}" 
                                                         alt="{{ $pgi->leader1->name }}" 
                                                         class="rounded-circle" 
                                                         width="45" 
                                                         height="45"
                                                         style="object-fit: cover; border: 2px solid #007bff;"
                                                         title="{{ $pgi->leader1->name }}">
                                                @else
                                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center border border-primary" 
                                                         style="width: 45px; height: 45px;"
                                                         title="{{ $pgi->leader1->name }}">
                                                        <i class="bx bx-user text-white"></i>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        @if($pgi->leader2)
                                            <div class="position-relative">
                                                @if($pgi->leader2->photo_url)
                                                    <img src="{{ $pgi->leader2->photo_url }}" 
                                                         alt="{{ $pgi->leader2->name }}" 
                                                         class="rounded-circle" 
                                                         width="45" 
                                                         height="45"
                                                         style="object-fit: cover; border: 2px solid #007bff;"
                                                         title="{{ $pgi->leader2->name }}">
                                                @else
                                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center border border-primary" 
                                                         style="width: 45px; height: 45px;"
                                                         title="{{ $pgi->leader2->name }}">
                                                        <i class="bx bx-user text-white"></i>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        @if($pgi->leaderTraining1)
                                            <div class="position-relative" style="display: inline-block;">
                                                @if($pgi->leaderTraining1->photo_url)
                                                    <img src="{{ $pgi->leaderTraining1->photo_url }}" 
                                                         alt="{{ $pgi->leaderTraining1->name }}" 
                                                         class="rounded-circle" 
                                                         width="45" 
                                                         height="45"
                                                         style="object-fit: cover; border: 2px solid #ffc107; opacity: 0.85;"
                                                         title="{{ $pgi->leaderTraining1->name }} (Em treinamento)">
                                                @else
                                                    <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center border border-warning" 
                                                         style="width: 45px; height: 45px; opacity: 0.85;"
                                                         title="{{ $pgi->leaderTraining1->name }} (Em treinamento)">
                                                        <i class="bx bx-user text-dark"></i>
                                                    </div>
                                                @endif
                                                <span class="position-absolute top-0 start-100 translate-middle badge bg-warning text-dark rounded-pill" 
                                                      style="font-size: 0.6rem; padding: 1px 4px; font-weight: bold; transform: translate(-40%, -30%); white-space: nowrap;">
                                                    T
                                                </span>
                                            </div>
                                        @endif

                                        @if($pgi->leaderTraining2)
                                            <div class="position-relative" style="display: inline-block;">
                                                @if($pgi->leaderTraining2->photo_url)
                                                    <img src="{{ $pgi->leaderTraining2->photo_url }}" 
                                                         alt="{{ $pgi->leaderTraining2->name }}" 
                                                         class="rounded-circle" 
                                                         width="45" 
                                                         height="45"
                                                         style="object-fit: cover; border: 2px solid #ffc107; opacity: 0.85;"
                                                         title="{{ $pgi->leaderTraining2->name }} (Em treinamento)">
                                                @else
                                                    <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center border border-warning" 
                                                         style="width: 45px; height: 45px; opacity: 0.85;"
                                                         title="{{ $pgi->leaderTraining2->name }} (Em treinamento)">
                                                        <i class="bx bx-user text-dark"></i>
                                                    </div>
                                                @endif
                                                <span class="position-absolute top-0 start-100 translate-middle badge bg-warning text-dark rounded-pill" 
                                                      style="font-size: 0.6rem; padding: 1px 4px; font-weight: bold; transform: translate(-40%, -30%); white-space: nowrap;">
                                                    T
                                                </span>
                                            </div>
                                        @endif

                                        @if(!$pgi->leader1 && !$pgi->leader2 && !$pgi->leaderTraining1 && !$pgi->leaderTraining2)
                                            <span class="text-muted small">Nenhum líder definido</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Informações adicionais (horário e endereço) -->
                                @if($pgi->time_schedule || ($pgi->address || $pgi->neighborhood))
                                <div class="mb-3 pt-2 border-top">
                                    @if($pgi->time_schedule)
                                        <small class="text-muted d-block mb-1">
                                            <i class="bx bx-time me-1"></i><strong>Horário:</strong> {{ $pgi->time_schedule }}
                                        </small>
                                    @endif
                                    @if($pgi->address || $pgi->neighborhood)
                                        <small class="text-muted d-block">
                                            <i class="bx bx-map me-1"></i>
                                            {{ trim(($pgi->address ?? '') . ($pgi->neighborhood ? ', ' . $pgi->neighborhood : '') . ($pgi->number ? ', ' . $pgi->number : '')) }}
                                        </small>
                                    @endif
                                </div>
                                @endif

                                <!-- Botão Visualizar -->
                                <div class="d-grid mt-auto">
                                    <a href="{{ route('pgis.show', $pgi) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="bx bx-show me-2"></i>Visualizar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Paginação -->
            <div class="mt-4">
                {{ $pgis->links() }}
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="bx bx-info-circle me-2"></i>
                Nenhum PGI encontrado.
                @if($canCreatePgis)
                <br>
                <a href="{{ route('pgis.create') }}" class="mt-2 d-inline-block">
                    Cadastrar primeiro PGI
                </a>
                @endif
            </div>
        @endif
    </div>

    <!-- Sidebar Direita -->
    <div class="col-lg-3">
        <!-- Botão Adicionar -->
        @if($canCreatePgis)
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body text-center">
                <a href="{{ route('pgis.create') }}" class="btn btn-success w-100" style="font-size: 1.1rem; padding: 12px;">
                    <i class="bx bx-plus me-2"></i>Adicionar PGI
                </a>
            </div>
        </div>
        @endif

        <!-- Filtros -->
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="bx bx-filter me-2"></i>Filtros
                </h5>
            </header>
            <div class="card-body">
                <form method="GET" action="{{ route('pgis.index') }}">
                    <!-- Pesquisar -->
                    <div class="mb-3">
                        <label for="search" class="form-label small">Pesquisar</label>
                        <input type="text" class="form-control form-control-sm" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Nome, endereço...">
                    </div>

                    <!-- Sexo -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Sexo</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sexo_masculino" name="gender[]" value="Masculino"
                                   {{ in_array('Masculino', request('gender', [])) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="sexo_masculino">Masculino</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sexo_feminino" name="gender[]" value="Feminino"
                                   {{ in_array('Feminino', request('gender', [])) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="sexo_feminino">Feminino</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sexo_ambos" name="gender[]" value="Misto"
                                   {{ in_array('Misto', request('gender', [])) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="sexo_ambos">Ambos</label>
                        </div>
                    </div>

                    <!-- Dia da semana -->
                    <div class="mb-3">
                        <label for="day_of_week" class="form-label small fw-bold">Dia da semana</label>
                        <select class="form-select form-select-sm" id="day_of_week" name="day_of_week">
                            <option value="">Todos</option>
                            <option value="segunda" {{ request('day_of_week') == 'segunda' ? 'selected' : '' }}>Segunda</option>
                            <option value="terça" {{ request('day_of_week') == 'terça' ? 'selected' : '' }}>Terça</option>
                            <option value="quarta" {{ request('day_of_week') == 'quarta' ? 'selected' : '' }}>Quarta</option>
                            <option value="quinta" {{ request('day_of_week') == 'quinta' ? 'selected' : '' }}>Quinta</option>
                            <option value="sexta" {{ request('day_of_week') == 'sexta' ? 'selected' : '' }}>Sexta</option>
                            <option value="sábado" {{ request('day_of_week') == 'sábado' ? 'selected' : '' }}>Sábado</option>
                            <option value="domingo" {{ request('day_of_week') == 'domingo' ? 'selected' : '' }}>Domingo</option>
                        </select>
                    </div>

                    <!-- Horário -->
                    <div class="mb-3">
                        <label for="time_schedule" class="form-label small fw-bold">Horário</label>
                        <select class="form-select form-select-sm" id="time_schedule" name="time_schedule">
                            <option value="">Todos</option>
                            <option value="Manhã" {{ request('time_schedule') == 'Manhã' ? 'selected' : '' }}>Manhã</option>
                            <option value="Tarde" {{ request('time_schedule') == 'Tarde' ? 'selected' : '' }}>Tarde</option>
                            <option value="Noite" {{ request('time_schedule') == 'Noite' ? 'selected' : '' }}>Noite</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bx bx-filter me-2"></i>Aplicar Filtros
                    </button>
                    @if(request()->hasAny(['search', 'gender', 'day_of_week', 'time_schedule']))
                        <a href="{{ route('pgis.index') }}" class="btn btn-default btn-sm w-100 mt-2">
                            <i class="bx bx-x me-2"></i>Limpar
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    }
    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }
</style>
@endpush
@endsection
