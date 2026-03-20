@extends('layouts.porto')

@section('title', 'Ajuda — Discipulado')
@section('page-title', 'Ajuda — Fluxo do Discipulado')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Ajuda</span></li>
@endsection

@section('content')

<style>
    .help-card {
        transition: all .25s ease;
    }
    .help-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,.08) !important;
    }
    .help-icon {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

{{-- Cabeçalho --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm"
             style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); color: white;">
            <div class="card-body py-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="bx bx-book-open" style="font-size: 2.5rem;"></i>
                    </div>
                    <div>
                        <h2 class="mb-1 fw-bold">Ajuda — Fluxo do Discipulado</h2>
                        <p class="mb-0 opacity-90">
                            Guia rápido para entender e usar corretamente o módulo de Discipulado do ADELSS
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Visão Geral do Fluxo --}}
<div class="row mb-4">
    <div class="col-12">
        <h4 class="text-primary mb-3">
            <i class="bx bx-git-branch me-2"></i>Fluxo Geral do Módulo
        </h4>

        <div class="card border-0 shadow-sm">
            <div class="card-body py-4">
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-3">

                    @php
                        $flow = [
                            ['icon' => 'bx-calendar-alt', 'label' => 'Ciclos', 'color' => 'primary'],
                            ['icon' => 'bx-user', 'label' => 'Membros', 'color' => 'success'],
                            ['icon' => 'bx-calendar', 'label' => 'Encontros', 'color' => 'info'],
                            ['icon' => 'bx-target-lock', 'label' => 'Propósitos', 'color' => 'purple'],
                            ['icon' => 'bx-message', 'label' => 'Feedbacks', 'color' => 'warning'],
                            ['icon' => 'bx-bar-chart', 'label' => 'Indicadores', 'color' => 'secondary'],
                            ['icon' => 'bx-dashboard', 'label' => 'Dashboard', 'color' => 'dark'],
                        ];
                    @endphp

                    @foreach($flow as $item)
                        <div class="text-center">
                            <div class="rounded-circle help-icon mb-1"
                                 style="background: rgba(0,0,0,.05);">
                                <i class="bx {{ $item['icon'] }} text-{{ $item['color'] }}"
                                   style="font-size: 1.4rem;"></i>
                            </div>
                            <small class="fw-semibold">{{ $item['label'] }}</small>
                        </div>

                        @if(!$loop->last)
                            <i class="bx bx-chevron-right text-muted d-none d-md-block"></i>
                        @endif
                    @endforeach

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Como usar --}}
<div class="row mb-4">
    <div class="col-12">
        <h4 class="text-success mb-2">
            <i class="bx bx-bookmark-star me-2"></i>Fluxo Recomendado de Uso
        </h4>
        <p class="text-muted">
            Sequência ideal para um acompanhamento eficiente do discípulo.
        </p>

        <div class="row g-3">
            @php
                $passos = [
                    ['titulo' => 'Criar indicadores e um ciclo', 'desc' => 'Configure métricas e abra um novo período de discipulado.', 'exemplo' => 'Ex: Indicadores "Oração", "Leitura Bíblica", "Compromisso" e Ciclo "2025 - Iniciantes".'],
                    ['titulo' => 'Vincular membros ao ciclo', 'desc' => 'Associe cada discípulo ao ciclo e defina o discipulador responsável.', 'exemplo' => 'Ex: João Silva no Ciclo 2025, discipulador: Maria Santos.'],
                    ['titulo' => 'Criar propósitos iniciais', 'desc' => 'Defina metas de jejum, oração e leitura com o discípulo.', 'exemplo' => 'Ex: Jejum 12h/semana, oração 15 min/dia, ler 2 capítulos da Bíblia por dia.'],
                    ['titulo' => 'Registrar encontros', 'desc' => 'Registre cada reunião, vincule propósitos e preencha o questionário espiritual.', 'exemplo' => 'Ex: Encontro presencial em 15/02, discutiu o propósito de leitura e avanço na oração.'],
                    ['titulo' => 'Avaliar progresso', 'desc' => 'Registre valores dos indicadores e feedbacks quando necessário.', 'exemplo' => 'Ex: Indicador "Oração" = 4; feedback: "Evolução visível no compromisso diário".'],
                    ['titulo' => 'Usar o dashboard', 'desc' => 'Acompanhe alertas e visão geral (sem encontro, propósitos vencidos).', 'exemplo' => 'Ex: Alerta "João sem encontro há 15 dias"; último encontro: 01/02.'],
                ];
            @endphp
            @foreach($passos as $i => $item)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm border-start border-4 border-success help-card">
                        <div class="card-body">
                            <span class="badge bg-success rounded-circle mb-2">{{ $i+1 }}</span>
                            <h6 class="mb-1">{{ $item['titulo'] }}</h6>
                            <p class="mb-1 small text-muted">{{ $item['desc'] }}</p>
                            <p class="mb-0 small text-primary" style="font-size: 0.8rem;"><i class="bx bx-bulb me-1"></i>{{ $item['exemplo'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Submenus --}}
<h4 class="text-primary mb-3">
    <i class="bx bx-grid-alt me-2"></i>Submenus do Discipulado
</h4>

<div class="row g-3 mb-4">
@php
$menus = [
    ['Ciclos','bx-calendar-alt','primary','Criar e gerenciar períodos ou turmas de discipulado'],
    ['Membros','bx-user','success','Vincular membros aos ciclos e acompanhar individualmente'],
    ['Encontros','bx-calendar','info','Registrar reuniões presenciais ou online'],
    ['Propósitos','bx-target-lock','purple','Definir e acompanhar metas espirituais e materiais'],
    ['Feedbacks','bx-message','warning','Registrar observações e avaliações do acompanhamento'],
    ['Indicadores','bx-bar-chart','secondary','Configurar e avaliar métricas de progresso'],
    ['Dashboard','bx-dashboard','dark','Visão geral para discipulador e liderança']
];
@endphp

@foreach($menus as $menu)
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm border-start border-4 border-{{ $menu[2] }} help-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-circle help-icon bg-{{ $menu[2] }} bg-opacity-10">
                        <i class="bx {{ $menu[1] }} text-{{ $menu[2] }}"></i>
                    </div>
                    <h6 class="mb-0">{{ $menu[0] }}</h6>
                </div>
                <p class="small text-muted mb-0">{{ $menu[3] }}</p>
            </div>
        </div>
    </div>
@endforeach
</div>

{{-- Permissões --}}
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-4 border-primary">
            <div class="card-body">
                <h6 class="mb-1">
                    <i class="bx bx-lock-alt me-1 text-primary"></i>Permissões de Acesso
                </h6>
                <p class="small text-muted mb-0">
                    O acesso aos submenus depende das permissões do usuário
                    (discipulador, liderança ou administrador).
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
