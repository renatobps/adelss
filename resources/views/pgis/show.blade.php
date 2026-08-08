@extends('layouts.porto')

@section('title', 'Detalhes do PGI')

@section('page-title', $pgi->name)

@section('breadcrumbs')
    <li><a href="{{ route('pgis.index') }}">PGIs</a></li>
    <li><span>Detalhes do PGI</span></li>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/css/pgis.css') }}?v={{ @filemtime(public_path('css/css/pgis.css')) ?: '1' }}">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $viewerMember = $user?->member;
    $isLeader = $viewerMember && $pgi->isLeader($viewerMember);

    $canCreatePgis = $isAdmin ||
                     ($user && ($user->hasPermission('pgis.index.create') ||
                                $user->hasPermission('pgis.index.manage')));
    $canEditPgis = $isAdmin ||
                   ($user && ($user->hasPermission('pgis.index.edit') ||
                              $user->hasPermission('pgis.index.manage')));
    $canDeletePgis = $isAdmin ||
                     ($user && ($user->hasPermission('pgis.index.delete') ||
                                $user->hasPermission('pgis.index.manage')));

    // Líderes e líderes em treinamento gerenciam reuniões e notificações
    $canManageMeetings = $isAdmin || $isLeader;
    $canSendPgiNotification = $isAdmin || $isLeader;

    $membersTotal = $pgi->members->count();
    $membersSorted = $pgi->members->sortBy('name')->values();
    $membersPreview = (int) config('pgis.members_preview', 8);
    $leaders = collect([
        ['member' => $pgi->leader1, 'role' => 'Líder'],
        ['member' => $pgi->leader2, 'role' => 'Líder'],
        ['member' => $pgi->leaderTraining1, 'role' => 'Em treinamento'],
        ['member' => $pgi->leaderTraining2, 'role' => 'Em treinamento'],
    ])->filter(fn ($item) => $item['member'] !== null)->values();

    $headerColors = [
        'Masculino' => ['start' => '#4169E1', 'end' => '#1E90FF'],
        'Feminino' => ['start' => '#FF69B4', 'end' => '#FF1493'],
        'Misto' => ['start' => '#9370DB', 'end' => '#BA55D3'],
        'default' => ['start' => '#87CEEB', 'end' => '#4682B4'],
    ];
    $colors = $headerColors[$pgi->profile] ?? $headerColors['default'];
    if ($pgi->time_schedule === 'Manhã') {
        $colors = ['start' => '#90EE90', 'end' => '#98FB98'];
    } elseif ($pgi->time_schedule === 'Tarde') {
        $colors = ['start' => '#FF8C00', 'end' => '#FF6347'];
    }

    $fullAddress = $pgi->fullAddress();
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

{{-- ============ Cabeçalho: identidade + informações essenciais ============ --}}
<div class="card pgi-card pgi-hero mb-4">
    <div class="pgi-hero__banner"
         style="background: linear-gradient(135deg, {{ $colors['start'] }} 0%, {{ $colors['end'] }} 100%);
                @if($pgi->banner_url) background-image: url('{{ asset('storage/' . $pgi->banner_url) }}'); @endif">
        @if($pgi->banner_url)<div class="pgi-hero__overlay"></div>@endif
        @if($canEditPgis)
            <button type="button" class="btn btn-sm btn-light position-absolute pgi-touch"
                    style="top: 10px; right: 10px; z-index: 2;"
                    data-bs-toggle="modal" data-bs-target="#updateBannerModal" title="Trocar banner">
                <i class="bx bx-image"></i>
            </button>
        @endif
    </div>

    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-end gap-3">
                    <div class="pgi-hero__logo position-relative d-flex align-items-center justify-content-center flex-shrink-0">
                        @if($pgi->logo_url)
                            <img src="{{ asset('storage/' . $pgi->logo_url) }}" alt="Logo do PGI">
                        @elseif($pgi->profile === 'Masculino')
                            <i class="bx bx-male text-primary" style="font-size: 2.5rem;"></i>
                        @elseif($pgi->profile === 'Feminino')
                            <i class="bx bx-female text-danger" style="font-size: 2.5rem;"></i>
                        @else
                            <i class="bx bx-group text-info" style="font-size: 2.5rem;"></i>
                        @endif
                        @if($canEditPgis)
                            <button type="button" class="btn btn-sm btn-light position-absolute rounded-circle"
                                    style="bottom: -4px; right: -4px; width: 28px; height: 28px; padding: 0;"
                                    data-bs-toggle="modal" data-bs-target="#updateLogoModal" title="Trocar logo">
                                <i class="bx bx-image"></i>
                            </button>
                        @endif
                    </div>
                    <div class="pb-1">
                        <h4 class="mb-1">{{ $pgi->name }}</h4>
                        <div class="text-muted small">
                            Pequeno Grupo Integrado
                            @if($pgi->parent)
                                · multiplicação de <a href="{{ route('pgis.show', $pgi->parent) }}">{{ $pgi->parent->name }}</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="pgi-fact__label mb-2">Liderança</span>
                    @if($leaders->isEmpty())
                        <p class="text-muted small mb-0">Nenhum líder definido.</p>
                    @else
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($leaders as $leader)
                                <div class="d-flex align-items-center gap-2">
                                    @include('members.partials.avatar', ['member' => $leader['member'], 'size' => 42])
                                    <div>
                                        <div class="pgi-member__name small">{{ $leader['member']->name }}</div>
                                        <div class="pgi-member__freq">{{ $leader['role'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="d-flex justify-content-end flex-wrap gap-2 mb-3">
                    <a href="{{ route('pgis.relatorio', $pgi) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-file me-1"></i>Relatório em PDF
                    </a>
                    @if($canEditPgis)
                        <a href="{{ route('pgis.edit', $pgi) }}" class="btn btn-primary btn-sm">
                            <i class="bx bx-edit me-1"></i>Editar PGI
                        </a>
                    @endif
                    @if($canDeletePgis)
                        <form action="{{ route('pgis.destroy', $pgi) }}" method="POST"
                              onsubmit="return confirm('Tem certeza que deseja remover este PGI?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bx bx-trash me-1"></i>Remover
                            </button>
                        </form>
                    @endif
                </div>

                <div class="pgi-facts">
                    <div>
                        <span class="pgi-fact__label">Encontro</span>
                        <span class="pgi-fact__value">
                            {{ $pgi->day_of_week ? ucfirst($pgi->day_of_week) : 'Dia não definido' }}
                            @if($pgi->time_schedule) · {{ $pgi->time_schedule }} @endif
                        </span>
                    </div>
                    <div>
                        <span class="pgi-fact__label">Perfil</span>
                        <span class="pgi-fact__value">{{ $pgi->profile ?: 'Não informado' }}</span>
                    </div>
                    <div>
                        <span class="pgi-fact__label">Data de abertura</span>
                        <span class="pgi-fact__value">{{ $pgi->opening_date?->format('d/m/Y') ?: 'Não informada' }}</span>
                    </div>
                    <div>
                        <span class="pgi-fact__label">Endereço</span>
                        <span class="pgi-fact__value">{{ $fullAddress ?: 'Não cadastrado' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ Indicadores rápidos ============ --}}
<div class="pgi-kpis mb-4">
    <div class="pgi-kpi">
        <div class="pgi-kpi__icon"><i class="bx bx-group"></i></div>
        <div>
            <div class="pgi-kpi__value">{{ $kpis['members'] }}</div>
            <div class="pgi-kpi__label">Membros no grupo</div>
        </div>
    </div>
    <div class="pgi-kpi">
        <div class="pgi-kpi__icon"><i class="bx bx-line-chart"></i></div>
        <div>
            <div class="pgi-kpi__value">
                {{ $kpis['average_attendance'] !== null ? number_format($kpis['average_attendance'], 1, ',', '.') : '—' }}
            </div>
            <div class="pgi-kpi__label">
                Média de presença
                @if($kpis['average_meetings'] > 0)
                    ({{ $kpis['average_meetings'] }} {{ $kpis['average_meetings'] === 1 ? 'reunião' : 'reuniões' }})
                @endif
            </div>
        </div>
    </div>
    <div class="pgi-kpi">
        <div class="pgi-kpi__icon"><i class="bx bx-calendar"></i></div>
        <div>
            <div class="pgi-kpi__value">{{ $kpis['meetings_this_month'] }}</div>
            <div class="pgi-kpi__label">Reuniões no mês</div>
        </div>
    </div>
    <div class="pgi-kpi">
        <div class="pgi-kpi__icon"><i class="bx bx-user-plus"></i></div>
        <div>
            <div class="pgi-kpi__value">{{ $kpis['visitors_this_month'] }}</div>
            <div class="pgi-kpi__label">Visitantes no mês</div>
        </div>
    </div>
</div>

{{-- ============ Atenção pastoral ============ --}}
@if($absentees->isNotEmpty())
    <div class="card pgi-card mb-4 border-warning">
        <header class="card-header">
            <h5 class="card-title mb-0 text-warning-emphasis">
                <i class="bx bx-heart me-2"></i>Precisam de atenção pastoral ({{ $absentees->count() }})
            </h5>
        </header>
        <div class="card-body">
            <p class="text-muted small">
                Ausentes nas últimas {{ $absenceThreshold }} reuniões com chamada registrada.
            </p>
            <div class="d-flex flex-wrap gap-3">
                @foreach($absentees as $member)
                    <div class="d-flex align-items-center gap-2 border rounded px-2 py-1">
                        @include('members.partials.avatar', ['member' => $member, 'size' => 34])
                        <div>
                            <div class="pgi-member__name small">{{ $member->name }}</div>
                            @if($member->phone)
                                <div class="pgi-member__freq">{{ $member->phone }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if($canSendPgiNotification)
                <a href="#notificarCollapse" data-bs-toggle="collapse" id="notifyAbsentees"
                   class="btn btn-sm btn-outline-success mt-3">
                    <i class="bx bxl-whatsapp me-1"></i>Enviar mensagem aos ausentes
                </a>
            @endif
        </div>
    </div>
@endif

<div class="row">
    {{-- ============ Reuniões ============ --}}
    <div class="col-lg-8 mb-4">
        <div class="card pgi-card mb-4">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar me-2"></i>Reuniões
                    @if($meetings->total() > 0)
                        <span class="text-muted fw-normal">({{ $meetings->total() }})</span>
                    @endif
                </h5>
                @if($canManageMeetings)
                    <a href="{{ route('pgis.meetings.create', $pgi) }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i>Nova reunião
                    </a>
                @endif
            </header>
            <div class="card-body">
                @if($kpis['pending_attendance'] > 0 && $canManageMeetings)
                    <div class="alert alert-warning py-2 small">
                        <i class="bx bx-time-five me-1"></i>
                        {{ $kpis['pending_attendance'] }} {{ $kpis['pending_attendance'] === 1 ? 'reunião está' : 'reuniões estão' }}
                        com a chamada pendente.
                    </div>
                @endif

                @forelse($meetings as $meeting)
                    @include('pgis.partials.meeting-row', [
                        'pgi' => $pgi,
                        'meeting' => $meeting,
                        'membersTotal' => $membersTotal,
                        'canManageMeetings' => $canManageMeetings,
                    ])
                @empty
                    @include('pgis.partials.empty-state', [
                        'icon' => 'bx-calendar-plus',
                        'title' => 'Nenhuma reunião registrada ainda',
                        'description' => 'Cadastre a primeira reunião para começar a registrar presenças.',
                        'actionUrl' => $canManageMeetings ? route('pgis.meetings.create', $pgi) : null,
                        'actionLabel' => 'Registrar primeira reunião',
                        'actionIcon' => 'bx-plus',
                    ])
                @endforelse

                @if($meetings->hasPages())
                    <div class="mt-3">{{ $meetings->links('pagination::bootstrap-5') }}</div>
                @endif
            </div>
        </div>

        <div class="card pgi-card">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-bar-chart-alt-2 me-2"></i>Presentes nas últimas reuniões
                </h5>
            </header>
            <div class="card-body">
                @if(count($chartData) > 0)
                    <canvas id="attendanceChart" height="160"></canvas>
                @else
                    @include('pgis.partials.empty-state', [
                        'icon' => 'bx-line-chart',
                        'title' => 'Ainda não há dados de presença',
                        'description' => 'O gráfico aparecerá após a primeira reunião com presença registrada.',
                    ])
                @endif
            </div>
        </div>
    </div>

    {{-- ============ Membros ============ --}}
    <div class="col-lg-4 mb-4">
        <div class="card pgi-card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bx bx-group me-2"></i>Membros ({{ $membersTotal }})</h5>
                @if($canEditPgis)
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="bx bx-plus me-1"></i>Adicionar
                    </button>
                @endif
            </header>
            <div class="card-body">
                @if($membersTotal > 0)
                    <div class="mb-3">
                        <input type="text" class="form-control form-control-sm" id="memberSearch"
                               placeholder="Buscar membro..." autocomplete="off">
                    </div>

                    <div id="memberList">
                        @foreach($membersSorted as $index => $member)
                            @php $freq = $frequency[$member->id] ?? null; @endphp
                            <div class="pgi-member member-item {{ $index >= $membersPreview ? 'member-item--extra d-none' : '' }}"
                                 data-member-name="{{ mb_strtolower($member->name) }}">
                                @include('members.partials.avatar', ['member' => $member, 'size' => 34])
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="pgi-member__name">{{ $member->name }}</div>
                                    @if($freq)
                                        <div class="pgi-member__freq">{{ $freq['present'] }}/{{ $freq['total'] }} últimas reuniões</div>
                                    @endif
                                </div>
                                @if($canEditPgis)
                                    <form action="{{ route('pgis.members.detach', [$pgi, $member]) }}" method="POST"
                                          onsubmit="return confirm('Remover este membro do PGI?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger pgi-touch" title="Remover">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <p class="text-muted text-center mb-0 mt-2 d-none" id="memberNoResults">
                        Nenhum membro encontrado.
                    </p>

                    @if($membersTotal > $membersPreview)
                        <button type="button" class="btn btn-link btn-sm w-100 mt-2" id="toggleMembers"
                                data-more="Ver todos os {{ $membersTotal }} membros" data-less="Ver menos">
                            Ver todos os {{ $membersTotal }} membros
                        </button>
                    @endif
                @else
                    @include('pgis.partials.empty-state', [
                        'icon' => 'bx-user-plus',
                        'title' => 'Nenhum membro vinculado',
                        'description' => 'Vincule membros da igreja a este pequeno grupo.',
                    ])
                    @if($canEditPgis)
                        <div class="text-center">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                <i class="bx bx-plus me-1"></i>Adicionar membros
                            </button>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============ Seções secundárias ============ --}}
<div class="accordion mb-4" id="pgiSecondary">
    @if($canSendPgiNotification)
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#notificarCollapse" aria-expanded="false">
                    <i class="bx bxl-whatsapp me-2"></i>Notificar participantes
                </button>
            </h2>
            <div id="notificarCollapse" class="accordion-collapse collapse" data-bs-parent="#pgiSecondary">
                <div class="accordion-body">
                    <form action="{{ route('pgis.notificacoes.enviar', $pgi) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="destinatarios">Destinatários <span class="text-danger">*</span></label>
                                <select class="form-select" name="destinatarios" id="destinatarios">
                                    <option value="todos" {{ old('destinatarios', 'todos') === 'todos' ? 'selected' : '' }}>
                                        Todos os participantes ({{ $pgi->members->filter(fn ($m) => filled($m->phone))->count() }})
                                    </option>
                                    <option value="ausentes" {{ old('destinatarios') === 'ausentes' ? 'selected' : '' }}>
                                        Ausentes na última reunião ({{ $lastMeetingAbsentees->filter(fn ($m) => filled($m->phone))->count() }})
                                    </option>
                                    <option value="selecionados" {{ old('destinatarios') === 'selecionados' ? 'selected' : '' }}>
                                        Seleção manual
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="tipo_envio">Tipo de envio <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo_envio" id="tipo_envio" required>
                                    <option value="texto" {{ old('tipo_envio', 'texto') === 'texto' ? 'selected' : '' }}>Texto</option>
                                    <option value="imagem" {{ old('tipo_envio') === 'imagem' ? 'selected' : '' }}>Imagem</option>
                                    <option value="video" {{ old('tipo_envio') === 'video' ? 'selected' : '' }}>Vídeo</option>
                                    <option value="enquete" {{ old('tipo_envio') === 'enquete' ? 'selected' : '' }}>Enquete cadastrada</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="mensagem">Mensagem / legenda</label>
                                <textarea class="form-control" name="mensagem" id="mensagem" rows="2" maxlength="4096"
                                          placeholder="Mensagem para os participantes...">{{ old('mensagem') }}</textarea>
                                <small class="text-muted">Obrigatória para texto; opcional para imagem e vídeo.</small>
                            </div>

                            <div class="col-12" id="membrosWrapper" style="display:none;">
                                <label class="form-label">Selecione os destinatários</label>
                                <div class="row g-2">
                                    @foreach($membersSorted->filter(fn ($m) => filled($m->phone)) as $member)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="membros[]"
                                                       value="{{ $member->id }}" id="notify_member_{{ $member->id }}"
                                                       {{ collect(old('membros', []))->contains($member->id) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="notify_member_{{ $member->id }}">
                                                    {{ $member->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-6" id="arquivoWrapper" style="display:none;">
                                <label class="form-label">Arquivo (imagem ou vídeo)</label>
                                <input type="file" class="form-control" name="arquivo" accept="image/*,video/mp4,video/mov,video/avi">
                            </div>
                            <div class="col-md-6" id="enqueteWrapper" style="display:none;">
                                <label class="form-label">Enquete cadastrada</label>
                                <select class="form-select" name="enquete_id">
                                    <option value="">Selecione...</option>
                                    @foreach($enquetes as $enquete)
                                        <option value="{{ $enquete->id }}" {{ (string) old('enquete_id') === (string) $enquete->id ? 'selected' : '' }}>
                                            {{ $enquete->titulo }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">As enquetes vêm do menu Notificações &gt; Enquetes.</small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="bx bx-send me-1"></i>Enviar via WhatsApp
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#anotacoesCollapse" aria-expanded="false">
                <i class="bx bx-note me-2"></i>Anotações
            </button>
        </h2>
        <div id="anotacoesCollapse" class="accordion-collapse collapse" data-bs-parent="#pgiSecondary">
            <div class="accordion-body">
                @if($canEditPgis)
                    <form action="{{ route('pgis.update', $pgi) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $pgi->name }}">
                        <input type="hidden" name="redirect_to" value="show">
                        <textarea class="form-control" name="notes" rows="6"
                                  placeholder="Digite suas anotações aqui...">{{ $pgi->notes }}</textarea>
                        <button type="submit" class="btn btn-primary btn-sm mt-3">
                            <i class="bx bx-save me-1"></i>Salvar anotações
                        </button>
                    </form>
                @else
                    <p class="mb-0" style="white-space: pre-line;">{{ $pgi->notes ?: 'Sem anotações.' }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#localizacaoCollapse" aria-expanded="false">
                <i class="bx bx-map me-2"></i>Localização
            </button>
        </h2>
        <div id="localizacaoCollapse" class="accordion-collapse collapse" data-bs-parent="#pgiSecondary">
            <div class="accordion-body">
                @if($fullAddress !== '')
                    <p class="mb-3"><i class="bx bx-map-pin me-1"></i>{{ $fullAddress }}</p>
                    <div id="pgiMap" class="pgi-map"></div>
                    <p class="text-muted small mt-2 mb-0 d-none" id="pgiMapFallback">
                        Não foi possível localizar este endereço no mapa.
                        <a href="{{ route('pgis.edit', $pgi) }}">Revise o endereço do PGI</a>.
                    </p>
                @else
                    @include('pgis.partials.empty-state', [
                        'icon' => 'bx-map-pin',
                        'title' => 'Endereço não cadastrado',
                        'description' => 'Informe onde o grupo se reúne para exibir o mapa.',
                        'actionUrl' => $canEditPgis ? route('pgis.edit', $pgi) : null,
                        'actionLabel' => 'Editar PGI',
                        'actionIcon' => 'bx-edit',
                    ])
                @endif
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#filhosCollapse" aria-expanded="false">
                <i class="bx bx-git-branch me-2"></i>PGIs filhos ({{ $pgi->children->count() }})
            </button>
        </h2>
        <div id="filhosCollapse" class="accordion-collapse collapse" data-bs-parent="#pgiSecondary">
            <div class="accordion-body">
                @forelse($pgi->children as $child)
                    <div class="pgi-member">
                        <i class="bx bx-group fs-4 text-primary"></i>
                        <div class="flex-grow-1">
                            <a href="{{ route('pgis.show', $child) }}" class="pgi-member__name text-decoration-none">
                                {{ $child->name }}
                            </a>
                            <div class="pgi-member__freq">
                                {{ $child->day_of_week ? ucfirst($child->day_of_week) : 'Dia não definido' }}
                                @if($child->time_schedule) · {{ $child->time_schedule }} @endif
                            </div>
                        </div>
                    </div>
                @empty
                    @include('pgis.partials.empty-state', [
                        'icon' => 'bx-git-branch',
                        'title' => 'Este PGI ainda não multiplicou',
                        'description' => 'Registre o grupo que nasceu deste PGI para acompanhar a multiplicação.',
                        'actionUrl' => $canCreatePgis ? route('pgis.create', ['parent' => $pgi->id]) : null,
                        'actionLabel' => 'Registrar PGI filho',
                        'actionIcon' => 'bx-plus',
                    ])
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ============ Modais ============ --}}
@if($canEditPgis)
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">
                    <i class="bx bx-user-plus me-2"></i>Adicionar membro ao PGI
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('pgis.members.attach', $pgi) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="member_search" class="form-label">Buscar membro</label>
                        <input type="text" class="form-control" id="member_search" placeholder="Digite o nome do membro...">
                    </div>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #eef0f2; border-radius: 8px; padding: 10px;">
                        @php
                            $availableMembers = \App\Models\Member::where(function ($query) use ($pgi) {
                                $query->whereNull('pgi_id')->orWhere('pgi_id', '!=', $pgi->id);
                            })->orderBy('name')->get();
                        @endphp
                        @forelse($availableMembers as $available)
                            <div class="form-check mb-2 member-option" data-member-name="{{ mb_strtolower($available->name) }}">
                                <input class="form-check-input" type="checkbox"
                                       id="add_member_{{ $available->id }}" name="members[]" value="{{ $available->id }}">
                                <label class="form-check-label d-flex align-items-center gap-2" for="add_member_{{ $available->id }}">
                                    @include('members.partials.avatar', ['member' => $available, 'size' => 30])
                                    <span>{{ $available->name }}</span>
                                </label>
                            </div>
                        @empty
                            <p class="text-muted text-center py-3 mb-0">Todos os membros já estão vinculados a este PGI</p>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Adicionar membros</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="updateLogoModal" tabindex="-1" aria-labelledby="updateLogoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateLogoModalLabel"><i class="bx bx-image me-2"></i>Trocar logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('pgis.logo.update', $pgi) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="logo_file" class="form-label">Selecione a nova imagem do logo</label>
                        <input type="file" class="form-control" id="logo_file" name="logo" accept="image/*" required>
                        <small class="form-text text-muted">JPEG, PNG, JPG, GIF ou SVG. Máximo 2MB.</small>
                    </div>
                    <div id="logoPreviewContainer" class="text-center" style="display: none;">
                        <p class="small text-muted mb-2">Preview:</p>
                        <img id="logoPreviewImg" src="" alt="Preview do logo" class="rounded-circle border border-2"
                             style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="updateBannerModal" tabindex="-1" aria-labelledby="updateBannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateBannerModalLabel"><i class="bx bx-image me-2"></i>Trocar banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('pgis.banner.update', $pgi) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="banner_file" class="form-label">Selecione a nova imagem do banner</label>
                        <input type="file" class="form-control" id="banner_file" name="banner" accept="image/*" required>
                        <small class="form-text text-muted">JPEG, PNG, JPG, GIF ou SVG. Máximo 2MB.</small>
                    </div>
                    <div id="bannerPreviewContainer" class="text-center" style="display: none;">
                        <p class="small text-muted mb-2">Preview:</p>
                        <img id="bannerPreviewImg" src="" alt="Preview do banner" class="border border-2 rounded"
                             style="max-width: 100%; max-height: 300px; object-fit: contain;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if(count($chartData) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const canvas = document.getElementById('attendanceChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const chartData = @json($chartData);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: chartData.map((item) => item.date),
            datasets: [
                {
                    label: 'Participantes',
                    data: chartData.map((item) => item.participants),
                    borderColor: '#0088CC',
                    backgroundColor: 'rgba(0, 136, 204, 0.15)',
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: 'Visitantes',
                    data: chartData.map((item) => item.visitors),
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.15)',
                    fill: true,
                    tension: 0.35,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: true, position: 'top' } },
        },
    });
})();
</script>
@endif

<script>
(function () {
    // Busca e "ver todos" na lista de membros
    const memberList = document.getElementById('memberList');
    const toggleMembers = document.getElementById('toggleMembers');
    const memberNoResults = document.getElementById('memberNoResults');
    let showingAll = false;

    function memberItems() {
        return memberList ? Array.from(memberList.querySelectorAll('.member-item')) : [];
    }

    toggleMembers?.addEventListener('click', function () {
        showingAll = !showingAll;
        memberList.querySelectorAll('.member-item--extra').forEach((item) => {
            item.classList.toggle('d-none', !showingAll);
        });
        this.textContent = showingAll ? this.dataset.less : this.dataset.more;
    });

    document.getElementById('memberSearch')?.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        let visible = 0;

        memberItems().forEach((item, index) => {
            const matches = (item.dataset.memberName || '').includes(term);
            const withinPreview = showingAll || term !== '' || !item.classList.contains('member-item--extra');
            const show = matches && withinPreview;
            item.classList.toggle('d-none', !show);
            if (matches) visible++;
        });

        memberNoResults?.classList.toggle('d-none', visible > 0);
        if (toggleMembers) {
            toggleMembers.classList.toggle('d-none', term !== '');
        }
    });

    // Campos condicionais do envio por WhatsApp
    const tipoEnvioEl = document.getElementById('tipo_envio');
    const destinatariosEl = document.getElementById('destinatarios');
    const arquivoWrapper = document.getElementById('arquivoWrapper');
    const enqueteWrapper = document.getElementById('enqueteWrapper');
    const membrosWrapper = document.getElementById('membrosWrapper');
    const arquivoInput = document.querySelector('input[name="arquivo"]');
    const enqueteSelect = document.querySelector('select[name="enquete_id"]');

    function atualizarCamposEnvioPgi() {
        if (!tipoEnvioEl) return;
        const tipo = tipoEnvioEl.value;
        const isMidia = tipo === 'imagem' || tipo === 'video';

        if (arquivoWrapper) arquivoWrapper.style.display = isMidia ? '' : 'none';
        if (enqueteWrapper) enqueteWrapper.style.display = tipo === 'enquete' ? '' : 'none';
        if (arquivoInput) arquivoInput.required = isMidia;
        if (enqueteSelect) enqueteSelect.required = tipo === 'enquete';
    }

    function atualizarDestinatarios() {
        if (!membrosWrapper || !destinatariosEl) return;
        membrosWrapper.style.display = destinatariosEl.value === 'selecionados' ? '' : 'none';
    }

    tipoEnvioEl?.addEventListener('change', atualizarCamposEnvioPgi);
    destinatariosEl?.addEventListener('change', atualizarDestinatarios);
    atualizarCamposEnvioPgi();
    atualizarDestinatarios();

    document.getElementById('notifyAbsentees')?.addEventListener('click', function () {
        if (!destinatariosEl) return;
        destinatariosEl.value = 'ausentes';
        atualizarDestinatarios();
    });

    // Busca no modal de adicionar membros
    document.getElementById('member_search')?.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        document.querySelectorAll('.member-option').forEach((option) => {
            option.style.display = (option.dataset.memberName || '').includes(term) ? '' : 'none';
        });
    });

    // Preview de logo e banner
    function bindPreview(inputId, containerId, imgId) {
        document.getElementById(inputId)?.addEventListener('change', function (event) {
            const file = event.target.files[0];
            const container = document.getElementById(containerId);
            const img = document.getElementById(imgId);
            if (!container || !img) return;

            if (!file) {
                container.style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                container.style.display = 'block';
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    bindPreview('logo_file', 'logoPreviewContainer', 'logoPreviewImg');
    bindPreview('banner_file', 'bannerPreviewContainer', 'bannerPreviewImg');
})();
</script>

@if($fullAddress !== '')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const mapEl = document.getElementById('pgiMap');
    const collapse = document.getElementById('localizacaoCollapse');
    if (!mapEl || !collapse || typeof L === 'undefined') return;

    let loaded = false;

    function loadMap() {
        if (loaded) return;
        loaded = true;

        fetch(@json(route('pgis.localizacao', $pgi)), { headers: { 'Accept': 'application/json' } })
            .then((response) => response.json())
            .then((data) => {
                if (!data.found) {
                    mapEl.classList.add('d-none');
                    document.getElementById('pgiMapFallback')?.classList.remove('d-none');
                    return;
                }

                const map = L.map(mapEl).setView([data.lat, data.lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 18,
                }).addTo(map);
                L.marker([data.lat, data.lng]).addTo(map).bindPopup(@json($pgi->name));
                setTimeout(() => map.invalidateSize(), 200);
            })
            .catch(() => {
                mapEl.classList.add('d-none');
                document.getElementById('pgiMapFallback')?.classList.remove('d-none');
            });
    }

    collapse.addEventListener('shown.bs.collapse', loadMap);
})();
</script>
@endif
@endpush
