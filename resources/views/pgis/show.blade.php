@extends('layouts.porto')

@section('title', 'Detalhes do PGI')

@section('page-title', $pgi->name)

@section('breadcrumbs')
    <li><a href="{{ route('pgis.index') }}">PGIs</a></li>
    <li><span>Detalhes do PGI</span></li>
@endsection

@section('content')
<div class="row">
    <!-- Painel Esquerdo: Header do PGI e Liderança -->
    <div class="col-lg-4 mb-4">
        <!-- Header do PGI com Logo -->
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
            @php
                $headerColors = [
                    'Masculino' => ['start' => '#4169E1', 'end' => '#1E90FF'],
                    'Feminino' => ['start' => '#FF69B4', 'end' => '#FF1493'],
                    'Misto' => ['start' => '#9370DB', 'end' => '#BA55D3'],
                    'default' => ['start' => '#87CEEB', 'end' => '#4682B4']
                ];
                $colors = $headerColors[$pgi->profile] ?? $headerColors['default'];
                if ($pgi->time_schedule == 'Manhã') {
                    $colors = ['start' => '#90EE90', 'end' => '#98FB98'];
                } elseif ($pgi->time_schedule == 'Tarde') {
                    $colors = ['start' => '#FF8C00', 'end' => '#FF6347'];
                }
            @endphp
            <div class="card-header p-0" style="height: 200px; background: linear-gradient(135deg, {{ $colors['start'] }} 0%, {{ $colors['end'] }} 100%); position: relative; overflow: hidden;">
                @if($pgi->banner_url)
                    <img src="{{ asset('storage/' . $pgi->banner_url) }}" 
                         alt="Banner do PGI" 
                         style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3);"></div>
                @endif
                <!-- Botão para trocar banner -->
                <button type="button" 
                        class="btn btn-sm btn-light position-absolute" 
                        style="top: 10px; right: 10px; z-index: 2; padding: 4px 8px; border-radius: 4px; opacity: 0.9;"
                        data-bs-toggle="modal" 
                        data-bs-target="#updateBannerModal"
                        title="Trocar banner">
                    <i class="bx bx-image" style="font-size: 1.2rem;"></i>
                </button>
                <div class="position-absolute top-0 start-0 p-4 w-100 h-100 d-flex align-items-center justify-content-center" style="z-index: 1;">
                    <div class="text-center text-white">
                        <h2 class="mb-0 fw-bold" style="font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">{{ $pgi->name }}</h2>
                        <small style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">PEQUENO GRUPO INTEGRADO</small>
                    </div>
                </div>
            </div>
            
            <!-- Logo Circular -->
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3" style="margin-top: -40px;">
                    <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm overflow-hidden" 
                         style="width: 80px; height: 80px; border: 3px solid white;">
                        @if($pgi->logo_url)
                            <img src="{{ asset('storage/' . $pgi->logo_url) }}" 
                                 alt="Logo do PGI" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            @if($pgi->profile == 'Masculino')
                                <i class="bx bx-male text-primary" style="font-size: 3rem;"></i>
                            @elseif($pgi->profile == 'Feminino')
                                <i class="bx bx-female text-danger" style="font-size: 3rem;"></i>
                            @else
                                <i class="bx bx-group text-info" style="font-size: 3rem;"></i>
                            @endif
                        @endif
                    </div>
                    <!-- Botão para trocar logo -->
                    <button type="button" 
                            class="btn btn-sm btn-light position-absolute rounded-circle" 
                            style="bottom: -5px; right: -5px; width: 28px; height: 28px; padding: 0; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"
                            data-bs-toggle="modal" 
                            data-bs-target="#updateLogoModal"
                            title="Trocar logo">
                        <i class="bx bx-image" style="font-size: 1rem;"></i>
                    </button>
                </div>
                <h4 class="mb-3">{{ $pgi->name }}</h4>
                
                <!-- Liderança -->
                <div class="mb-3">
                    <strong class="d-block mb-2 small text-muted">Liderança:</strong>
                    <div class="d-flex gap-2 align-items-center justify-content-center flex-wrap">
                        @if($pgi->leader1)
                            @if($pgi->leader1->photo_url)
                                <img src="{{ $pgi->leader1->photo_url }}" 
                                     alt="{{ $pgi->leader1->name }}" 
                                     class="rounded-circle" 
                                     width="50" 
                                     height="50"
                                     style="object-fit: cover; border: 2px solid #007bff;"
                                     title="{{ $pgi->leader1->name }}">
                            @else
                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center border border-primary" 
                                     style="width: 50px; height: 50px;"
                                     title="{{ $pgi->leader1->name }}">
                                    <i class="bx bx-user text-white"></i>
                                </div>
                            @endif
                        @endif
                        
                        @if($pgi->leader2)
                            @if($pgi->leader2->photo_url)
                                <img src="{{ $pgi->leader2->photo_url }}" 
                                     alt="{{ $pgi->leader2->name }}" 
                                     class="rounded-circle" 
                                     width="50" 
                                     height="50"
                                     style="object-fit: cover; border: 2px solid #007bff;"
                                     title="{{ $pgi->leader2->name }}">
                            @else
                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center border border-primary" 
                                     style="width: 50px; height: 50px;"
                                     title="{{ $pgi->leader2->name }}">
                                    <i class="bx bx-user text-white"></i>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel Central: Lista de Membros -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); height: 100%;">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-group me-2"></i>Membros ({{ $pgi->members->count() }})
                </h5>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="bx bx-plus me-1"></i>Adicionar
                </button>
            </header>
            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                <!-- Campo de pesquisa -->
                <div class="mb-3">
                    <input type="text" class="form-control form-control-sm" id="memberSearch" 
                           placeholder="Pesquisar membro...">
                </div>

                @if($pgi->members->count() > 0)
                    <ul class="list-group list-group-flush" id="memberList">
                        @foreach($pgi->members as $member)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0" data-member-name="{{ strtolower($member->name) }}">
                                <div class="d-flex align-items-center">
                                    @if($member->photo_url)
                                        <img src="{{ $member->photo_url }}" 
                                             alt="{{ $member->name }}" 
                                             class="rounded-circle me-2" 
                                             width="35" 
                                             height="35"
                                             style="object-fit: cover;">
                                    @else
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                             style="width: 35px; height: 35px;">
                                            <i class="bx bx-user text-white" style="font-size: 0.8rem;"></i>
                                        </div>
                                    @endif
                                    <span>{{ $member->name }}</span>
                                </div>
                                <form action="{{ route('pgis.members.detach', [$pgi, $member]) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este membro do PGI?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm text-danger p-0" title="Remover">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted text-center py-4">Nenhum membro vinculado</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Painel Direito: Informações -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); height: 100%;">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-info-circle me-2"></i>Informações
                </h5>
                <div>
                    <a href="{{ route('pgis.edit', $pgi) }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-edit me-1"></i>Editar
                    </a>
                    <form action="{{ route('pgis.destroy', $pgi) }}" method="POST" class="d-inline" 
                          onsubmit="return confirm('Tem certeza que deseja remover este PGI?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bx bx-trash me-1"></i>Remover
                        </button>
                    </form>
                </div>
            </header>
            <div class="card-body">
                @if($pgi->opening_date)
                    <p class="mb-2"><strong>Data de abertura:</strong> {{ $pgi->opening_date->format('d/m/Y') }}</p>
                @endif
                @if($pgi->day_of_week)
                    <p class="mb-2">
                        <strong>Dia da semana:</strong> 
                        {{ ucfirst($pgi->day_of_week) }} 
                        @if($pgi->time_schedule)
                            ({{ $pgi->time_schedule }})
                        @endif
                    </p>
                @endif
                @if($pgi->profile)
                    <p class="mb-2"><strong>Perfil:</strong> {{ $pgi->profile }}</p>
                @endif
                <p class="mb-2"><strong>Categorias:</strong> Sem categorias</p>
                @if($pgi->leader1)
                    <p class="mb-2"><strong>Líder 1:</strong> {{ $pgi->leader1->name }}</p>
                @else
                    <p class="mb-2"><strong>Líder 1:</strong> Não definido</p>
                @endif
                @if($pgi->leader2)
                    <p class="mb-2"><strong>Líder 2:</strong> {{ $pgi->leader2->name }}</p>
                @else
                    <p class="mb-2"><strong>Líder 2:</strong> Não definido</p>
                @endif
                @if($pgi->leaderTraining1)
                    <p class="mb-2"><strong>Líder em treinamento 1:</strong> {{ $pgi->leaderTraining1->name }}</p>
                @else
                    <p class="mb-2"><strong>Líder em treinamento 1:</strong> Não definido</p>
                @endif
                @if($pgi->leaderTraining2)
                    <p class="mb-2"><strong>Líder em treinamento 2:</strong> {{ $pgi->leaderTraining2->name }}</p>
                @else
                    <p class="mb-2"><strong>Líder em treinamento 2:</strong> Não definido</p>
                @endif
                @if($pgi->address || $pgi->neighborhood)
                    <p class="mb-2">
                        <strong>Endereço:</strong> 
                        {{ trim(($pgi->address ?? '') . ($pgi->neighborhood ? ', ' . $pgi->neighborhood : '') . ($pgi->number ? ', ' . $pgi->number : '')) }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Dashboard de Reuniões -->
<div class="row">
    <!-- Painel: Reuniões -->
    <div class="col-lg-5 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar me-2"></i>Reuniões
                </h5>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#newMeetingModal">
                    <i class="bx bx-plus me-1"></i>Nova reunião
                </button>
            </header>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                @if($meetings->count() > 0)
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th class="text-center">Participantes</th>
                                <th class="text-center">Visitantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($meetings as $meeting)
                                <tr>
                                    <td>
                                        <i class="bx bx-calendar me-1"></i>
                                        {{ $meeting->meeting_date->format('d/m/Y') }}
                                    </td>
                                    <td class="text-center">{{ $meeting->participants_count }}</td>
                                    <td class="text-center">{{ $meeting->visitors_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted text-center py-4">Nenhuma reunião cadastrada</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Painel: Gráfico de Presença -->
    <div class="col-lg-7 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-bar-chart-alt-2 me-2"></i>Presentes nas últimas reuniões
                </h5>
            </header>
            <div class="card-body">
                <canvas id="attendanceChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Painéis Inferiores -->
<div class="row">
    <!-- Localização -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); height: 100%;">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-map me-2"></i>Localização
                </h5>
            </header>
            <div class="card-body text-center py-5">
                <i class="bx bx-data text-primary" style="font-size: 4rem; opacity: 0.3;"></i>
                <p class="text-muted mt-3">Não há dados disponíveis</p>
            </div>
        </div>
    </div>

    <!-- Anotações -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); height: 100%;">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-edit me-2"></i>Anotações
                </h5>
            </header>
            <div class="card-body">
                <form action="{{ route('pgis.update', $pgi) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $pgi->name }}">
                    <textarea class="form-control" name="notes" rows="8" 
                              placeholder="Digite suas anotações aqui...">{{ $pgi->notes }}</textarea>
                    <button type="submit" class="btn btn-primary btn-sm mt-3">
                        <i class="bx bx-save me-1"></i>Salvar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- PGIs filhos -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); height: 100%;">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-group me-2"></i>PGIs filhos
                </h5>
            </header>
            <div class="card-body text-center py-5">
                <i class="bx bx-data text-primary" style="font-size: 4rem; opacity: 0.3;"></i>
                <p class="text-muted mt-3">Não há dados disponíveis</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Membro -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">
                    <i class="bx bx-user-plus me-2"></i>Adicionar Membro ao PGI
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('pgis.members.attach', $pgi) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="member_search" class="form-label">Buscar membro</label>
                        <input type="text" class="form-control" id="member_search" 
                               placeholder="Digite o nome do membro...">
                    </div>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                        @php
                            // Buscar membros que não estão vinculados a este PGI
                            $allMembers = \App\Models\Member::where(function($query) use ($pgi) {
                                $query->whereNull('pgi_id')
                                      ->orWhere('pgi_id', '!=', $pgi->id);
                            })->orderBy('name')->get();
                        @endphp
                        @if($allMembers->count() > 0)
                            @foreach($allMembers as $member)
                                <div class="form-check mb-2 member-option" data-member-name="{{ strtolower($member->name) }}">
                                    <input class="form-check-input" type="checkbox" 
                                           id="add_member_{{ $member->id }}" 
                                           name="members[]" 
                                           value="{{ $member->id }}">
                                    <label class="form-check-label d-flex align-items-center" for="add_member_{{ $member->id }}">
                                        @if($member->photo_url)
                                            <img src="{{ $member->photo_url }}" 
                                                 alt="{{ $member->name }}" 
                                                 class="rounded-circle me-2" 
                                                 width="30" 
                                                 height="30"
                                                 style="object-fit: cover;">
                                        @else
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 30px; height: 30px;">
                                                <i class="bx bx-user text-white" style="font-size: 0.7rem;"></i>
                                            </div>
                                        @endif
                                        <span>{{ $member->name }}</span>
                                    </label>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted text-center py-3">Todos os membros já estão vinculados a este PGI</p>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Adicionar Membros
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Nova Reunião -->
<div class="modal fade" id="newMeetingModal" tabindex="-1" aria-labelledby="newMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMeetingModalLabel">
                    <i class="bx bx-calendar-plus me-2"></i>Adicionar reunião
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('pgis.meetings.store', $pgi) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="meeting_date" class="form-label">Data da reunião <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('meeting_date') is-invalid @enderror" 
                                   id="meeting_date" name="meeting_date" 
                                   value="{{ old('meeting_date', date('Y-m-d')) }}" required>
                            @error('meeting_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="subject" class="form-label">Assunto</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                   id="subject" name="subject" value="{{ old('subject') }}" 
                                   placeholder="Digite o assunto da reunião">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="total_value" class="form-label">Valor total</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('total_value') is-invalid @enderror" 
                                   id="total_value" name="total_value" value="{{ old('total_value', '0.00') }}" 
                                   placeholder="0.00">
                            @error('total_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Lista de presença</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            @foreach($pgi->members as $member)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           id="participant_{{ $member->id }}" 
                                           name="participants[]" 
                                           value="{{ $member->id }}"
                                           {{ old('participants') && in_array($member->id, old('participants')) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="participant_{{ $member->id }}">
                                        {{ $member->name }}
                                    </label>
                                </div>
                            @endforeach
                            @if($pgi->members->count() == 0)
                                <p class="text-muted text-center py-3">Nenhum membro no PGI</p>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Visitantes (<span id="visitorCount">0</span>)</label>
                        <button type="button" class="btn btn-primary btn-sm mb-2" id="addVisitorBtn">
                            <i class="bx bx-plus me-1"></i>Adicionar visitante
                        </button>
                        <div id="visitorsContainer" style="max-height: 200px; overflow-y: auto;">
                            <!-- Visitantes serão adicionados aqui via JavaScript -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Anotações da reunião</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" name="notes" rows="4" 
                                  placeholder="Digite as anotações da reunião...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Trocar Logo -->
<div class="modal fade" id="updateLogoModal" tabindex="-1" aria-labelledby="updateLogoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateLogoModalLabel">
                    <i class="bx bx-image me-2"></i>Trocar Logo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('pgis.logo.update', $pgi) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="logo_file" class="form-label">Selecione a nova imagem do logo</label>
                        <input type="file" class="form-control @error('logo') is-invalid @enderror" 
                               id="logo_file" name="logo" accept="image/*" required>
                        <small class="form-text text-muted">Formatos aceitos: JPEG, PNG, JPG, GIF, SVG. Tamanho máximo: 2MB.</small>
                        @error('logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div id="logoPreviewContainer" class="text-center" style="display: none;">
                        <p class="small text-muted mb-2">Preview:</p>
                        <img id="logoPreviewImg" src="" alt="Preview do logo" 
                             class="rounded-circle border border-2" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Trocar Banner -->
<div class="modal fade" id="updateBannerModal" tabindex="-1" aria-labelledby="updateBannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateBannerModalLabel">
                    <i class="bx bx-image me-2"></i>Trocar Banner
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('pgis.banner.update', $pgi) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="banner_file" class="form-label">Selecione a nova imagem do banner</label>
                        <input type="file" class="form-control @error('banner') is-invalid @enderror" 
                               id="banner_file" name="banner" accept="image/*" required>
                        <small class="form-text text-muted">Formatos aceitos: JPEG, PNG, JPG, GIF, SVG. Tamanho máximo: 2MB.</small>
                        @error('banner')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div id="bannerPreviewContainer" class="text-center" style="display: none;">
                        <p class="small text-muted mb-2">Preview:</p>
                        <img id="bannerPreviewImg" src="" alt="Preview do banner" 
                             class="border border-2 rounded" 
                             style="max-width: 100%; max-height: 300px; object-fit: contain;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de presença
    const ctx = document.getElementById('attendanceChart');
    if (ctx) {
        const chartData = @json($chartData);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(item => item.date),
                datasets: [
                    {
                        label: 'Participantes',
                        data: chartData.map(item => item.participants),
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Visitantes',
                        data: chartData.map(item => item.visitors),
                        borderColor: 'rgb(255, 159, 64)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Total',
                        data: chartData.map(item => item.total),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 20,
                        ticks: {
                            stepSize: 2
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }

    // Busca de membros na lista do PGI
    document.getElementById('memberSearch')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const memberItems = document.querySelectorAll('#memberList li');
        
        memberItems.forEach(item => {
            const memberName = item.getAttribute('data-member-name');
            if (memberName && memberName.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Busca de membros no modal de adicionar
    document.getElementById('member_search')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const memberOptions = document.querySelectorAll('.member-option');
        
        memberOptions.forEach(option => {
            const memberName = option.getAttribute('data-member-name');
            if (memberName && memberName.includes(searchTerm)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    });

    // Adicionar visitantes
    let visitorIndex = 0;
    document.getElementById('addVisitorBtn')?.addEventListener('click', function() {
        const container = document.getElementById('visitorsContainer');
        const visitorHtml = `
                        <div class="input-group mb-2 visitor-input" data-index="${visitorIndex}">
                            <input type="text" class="form-control form-control-sm" 
                                   name="visitors[${visitorIndex}][name]" 
                                   placeholder="Nome do visitante">
                            <button type="button" class="btn btn-danger btn-sm remove-visitor">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
        `;
        container.insertAdjacentHTML('beforeend', visitorHtml);
        visitorIndex++;
        updateVisitorCount();
    });

    // Remover visitante
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-visitor')) {
            e.target.closest('.visitor-input').remove();
            updateVisitorCount();
        }
    });

    function updateVisitorCount() {
        const count = document.querySelectorAll('.visitor-input').length;
        document.getElementById('visitorCount').textContent = count;
    }

    // Preview do logo ao selecionar arquivo no modal
    document.getElementById('logo_file')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const previewContainer = document.getElementById('logoPreviewContainer');
        const previewImg = document.getElementById('logoPreviewImg');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.style.display = 'block';
                previewImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    });

    // Preview do banner ao selecionar arquivo no modal
    document.getElementById('banner_file')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const previewContainer = document.getElementById('bannerPreviewContainer');
        const previewImg = document.getElementById('bannerPreviewImg');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.style.display = 'block';
                previewImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    });

    // Bootstrap 5 inicializa modais automaticamente com data-bs-toggle
    // Não é necessária inicialização manual, mas podemos adicionar tratamento de erros
</script>
@endpush
@endsection
