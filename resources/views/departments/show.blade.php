@extends('layouts.porto')
@include('departments.partials.styles')
@section('title', $department->name.' — Departamento')
@section('page-title', 'Visão geral do departamento')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><span>{{ $department->name }}</span></li>
@endsection
@section('content')
@php
    $color = preg_match('/^#[0-9a-f]{6}$/i', $department->color ?? '') ? $department->color : '#3458bd';
    $icon = $department->icon ?: 'bx-group';
    $leaders = $department->leaders->isNotEmpty() ? $department->leaders : collect($department->leader ? [$department->leader] : []);
@endphp
<div class="dept-page" style="--dept-accent: {{ $color }}">
    <header class="dept-hero">
        <span class="dept-avatar">
            @if($department->logo_url)<img src="{{ asset('storage/'.$department->logo_url) }}" alt="Logo de {{ $department->name }}">@else<i class="{{ str_starts_with($icon, 'bx-') ? 'bx '.$icon : $icon }}" aria-hidden="true"></i>@endif
        </span>
        <div class="dept-hero-copy"><div class="dept-eyebrow">Conheça a equipe</div><h1>{{ $department->name }}</h1><div class="mt-2"><span class="dept-status {{ $department->status === 'arquivado' ? 'dept-status--archived' : '' }}">{{ $department->status === 'arquivado' ? 'Arquivado' : 'Ativo' }}</span></div></div>
        <div class="dept-hero-actions">
            @can('update', $department)<a href="{{ route('departments.edit', $department) }}" class="btn btn-primary"><i class="bx bx-slider-alt" aria-hidden="true"></i>Editar departamento</a>@endcan
            <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Voltar à lista</a>
        </div>
    </header>
    <div class="dept-stats" aria-label="Resumo do departamento">
        <div class="dept-stat"><i class="bx bx-group" aria-hidden="true"></i><div><strong>{{ $department->members->count() }}</strong><span>Participantes</span></div></div>
        <div class="dept-stat"><i class="bx bx-user-check" aria-hidden="true"></i><div><strong>{{ $leaders->count() }}</strong><span>Na liderança</span></div></div>
        <div class="dept-stat"><i class="bx bx-id-card" aria-hidden="true"></i><div><strong>{{ $department->roles->count() }}</strong><span>Cargos definidos</span></div></div>
    </div>
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <section class="dept-panel">
                <div class="dept-panel-heading"><h2>Sobre o departamento</h2></div>
                <div class="dept-panel-body"><p class="dept-paragraph mb-0">{{ $department->description ?: 'Este departamento ainda não tem uma apresentação. Descreva seu propósito nas configurações.' }}</p></div>
            </section>
            <section class="dept-panel" data-filter-list>
                <div class="dept-panel-heading"><div><h2>Quem faz parte</h2><p>Pessoas e responsabilidades da equipe.</p></div><span class="dept-count" data-filter-count aria-live="polite">{{ $department->members->count() }} pessoas</span></div>
                <div class="dept-panel-body">
                    @if($department->members->isNotEmpty())
                        <div class="dept-search mb-3"><i class="bx bx-search" aria-hidden="true"></i><label class="visually-hidden" for="overview-people-search">Buscar participantes por nome ou cargo</label><input id="overview-people-search" class="form-control" type="search" placeholder="Buscar por nome ou cargo..." data-filter-input></div>
                        <div class="dept-person-list dept-person-list--grid dept-scroll-list">
                            @foreach($department->members as $member)
                                @php
                                    $pivot = isset($pivots) ? $pivots->get($member->id) : null;
                                    $roleNames = collect($pivot?->role ? [$pivot->role->name] : [])
                                        ->merge($department->roles->filter(fn ($role) => $role->assigned_members->contains('id', $member->id))->pluck('name'))->unique();
                                @endphp
                                <article class="dept-person" id="department-member-{{ $member->id }}" data-filter-item>
                                    <span class="dept-avatar dept-avatar--small" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                                    <div class="dept-person-info"><span class="dept-person-name">{{ $member->name }}</span>
                                        @forelse($roleNames as $roleName)<span class="dept-tag">{{ $roleName }}</span>@empty<span class="dept-person-meta">Participante</span>@endforelse
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <p class="dept-empty mt-3" data-filter-empty hidden>Nenhuma pessoa encontrada. Tente outro nome ou cargo.</p>
                    @else
                        <div class="dept-empty"><i class="bx bx-user-plus" aria-hidden="true"></i>A equipe ainda não tem participantes.@can('update', $department)<br><a href="{{ route('departments.edit', $department) }}">Escolher participantes</a>@endcan</div>
                    @endif
                </div>
            </section>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><a href="{{ route('departments.projects.index', $department) }}" class="dept-link-card h-100 mb-0"><i class="bx bx-folder" aria-hidden="true"></i><span><strong>Projetos e iniciativas</strong><small>Acompanhe as ações do departamento</small></span><i class="bx bx-chevron-right" aria-hidden="true"></i></a></div>
                <div class="col-md-6"><a href="{{ route('departments.events.index', $department) }}" class="dept-link-card h-100 mb-0"><i class="bx bx-calendar" aria-hidden="true"></i><span><strong>Calendário anual</strong><small>Veja os eventos e planeje o ano</small></span><i class="bx bx-chevron-right" aria-hidden="true"></i></a></div>
            </div>
        </div>
        <aside class="col-12 col-xl-4">
            <section class="dept-panel">
                <div class="dept-panel-heading"><h2>Liderança</h2></div>
                <div class="dept-panel-body">
                    <div class="dept-person-list">
                        @forelse($leaders as $leader)
                            <div class="dept-person"><span class="dept-avatar dept-avatar--small" aria-hidden="true">{{ mb_strtoupper(mb_substr($leader->name, 0, 1)) }}</span><div class="dept-person-info"><span class="dept-person-name">{{ $leader->name }}</span><span class="dept-person-meta">Liderança do departamento</span></div></div>
                        @empty
                            <p class="dept-muted mb-0">Nenhum líder definido.</p>
                        @endforelse
                    </div>
                </div>
            </section>
            <section class="dept-panel">
                <div class="dept-panel-heading"><div><h2>Administração</h2><p>Cargos e responsáveis do departamento.</p></div></div>
                <div class="dept-panel-body">
                    <div class="dept-scroll-list" style="max-height:320px">
                        @forelse($department->roles as $role)
                            <div class="dept-role-row"><strong>{{ $role->name }}</strong><span>{{ $role->assigned_members->pluck('name')->join(' · ') ?: ($role->responsible_member_id ? 'Membro indisponível' : 'Nenhuma pessoa atribuída') }}</span></div>
                        @empty
                            <p class="dept-muted mb-0">Nenhum cargo definido.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('departments.administration.index', $department) }}" class="btn btn-outline-secondary w-100 mt-3">{{ Auth::user()->can('update', $department) ? 'Gerenciar administração' : 'Ver administração' }}</a>
                </div>
            </section>
        </aside>
    </div>
    <p class="dept-muted mb-0">Atualizado em {{ $department->updated_at?->format('d/m/Y') }}.</p>
</div>
@push('scripts')<script src="{{ asset('js/departments.js') }}?v={{ filemtime(public_path('js/departments.js')) }}"></script>@endpush
@endsection
