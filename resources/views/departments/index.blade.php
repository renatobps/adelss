@extends('layouts.porto')
@include('departments.partials.styles')
@section('title', 'Departamentos')
@section('page-title', 'Departamentos')
@section('breadcrumbs')<li><span>Departamentos</span></li>@endsection
@section('content')
<div class="dept-page">
    <header class="dept-hero">
        <span class="dept-avatar"><i class="bx bx-building" aria-hidden="true"></i></span>
        <div class="dept-hero-copy"><div class="dept-eyebrow">Pessoas e propósito</div><h1>Departamentos</h1><p>Encontre sua equipe e acompanhe sua administração, projetos e eventos.</p></div>
        @can('create', App\Models\Department::class)
            <a href="{{ route('departments.create') }}" class="btn btn-primary"><i class="bx bx-plus" aria-hidden="true"></i>Novo departamento</a>
        @endcan
    </header>
    <div class="dept-catalog-toolbar">
        <nav class="dept-filter-tabs" aria-label="Situação dos departamentos">
            <a href="{{ route('departments.index', ['filter' => 'ativo', 'q' => $search ?? '']) }}" @if($filter !== 'arquivado') aria-current="page" @endif>Ativos</a>
            <a href="{{ route('departments.index', ['filter' => 'arquivado', 'q' => $search ?? '']) }}" @if($filter === 'arquivado') aria-current="page" @endif>Arquivados</a>
        </nav>
        <span class="dept-muted">{{ $departments->total() }} {{ $departments->total() === 1 ? 'departamento' : 'departamentos' }}</span>
        <form class="dept-catalog-search" method="GET" action="{{ route('departments.index') }}" role="search">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <div class="dept-search"><i class="bx bx-search" aria-hidden="true"></i><label for="department-search" class="visually-hidden">Buscar departamentos</label><input id="department-search" class="form-control" type="search" name="q" value="{{ $search ?? '' }}" placeholder="Buscar departamentos..." maxlength="191"></div>
            <button class="btn btn-outline-secondary" type="submit">Buscar</button>
        </form>
    </div>
    <div class="row g-4">
        @forelse($departments as $department)
            @php
                $color = preg_match('/^#[0-9a-f]{6}$/i', $department->color ?? '') ? $department->color : '#3458bd';
                $icon = $department->icon ?: 'bx-group';
                $leaders = $department->leaders->isNotEmpty() ? $department->leaders : collect($department->leader ? [$department->leader] : []);
            @endphp
            <div class="col-12 col-lg-6 col-xxl-4">
                <article class="dept-department-card" style="--dept-accent: {{ $color }}">
                    <div class="dept-department-card-head">
                        <span class="dept-avatar">
                            @if($department->logo_url)<img src="{{ asset('storage/'.$department->logo_url) }}" alt="Logo de {{ $department->name }}">@else<i class="{{ str_starts_with($icon, 'bx-') ? 'bx '.$icon : $icon }}" aria-hidden="true"></i>@endif
                        </span>
                        <div class="flex-grow-1" style="min-width:0"><h2>{{ $department->name }}</h2><span class="dept-status {{ $department->status === 'arquivado' ? 'dept-status--archived' : '' }}">{{ $department->status === 'arquivado' ? 'Arquivado' : 'Ativo' }}</span></div>
                        <div class="dropdown">
                            <button class="dept-icon-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mais opções para {{ $department->name }}"><i class="bx bx-dots-vertical-rounded" aria-hidden="true"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('departments.administration.index', $department) }}">Administração</a></li>
                                <li><a class="dropdown-item" href="{{ route('departments.projects.index', $department) }}">Projetos</a></li>
                                @can('update', $department)<li><a class="dropdown-item" href="{{ route('departments.edit', $department) }}">Editar departamento</a></li>@endcan
                                @can('delete', $department)<li><hr class="dropdown-divider"></li><li><button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#delete-department-{{ $department->id }}">Excluir departamento</button></li>@endcan
                            </ul>
                        </div>
                    </div>
                    <div class="dept-department-card-body">
                        <p>{{ \Illuminate\Support\Str::limit($department->description ?: 'Uma equipe para servir e caminhar em comunidade.', 150) }}</p>
                        <div class="d-flex flex-wrap gap-2 mb-3"><span class="dept-count">{{ $department->members->count() }} participantes</span><span class="dept-count">{{ $department->roles->count() }} cargos</span></div>
                        <span class="dept-muted">Liderança</span><div class="dept-person-name mt-1">{{ $leaders->pluck('name')->take(2)->join(' · ') ?: 'Ainda não definida' }}@if($leaders->count() > 2) <span class="dept-muted">+{{ $leaders->count() - 2 }}</span>@endif</div>
                    </div>
                    <footer class="dept-department-card-foot"><a href="{{ route('departments.show', $department) }}" class="btn btn-primary">Ver departamento<i class="bx bx-right-arrow-alt" aria-hidden="true"></i></a><a href="{{ route('departments.events.index', $department) }}">Calendário anual</a></footer>
                </article>
            </div>
            @can('delete', $department)
                @include('departments.partials.delete-modal', ['modalId' => 'delete-department-'.$department->id, 'deleteTitle' => 'Excluir departamento', 'deleteMessage' => 'Excluir o departamento '.$department->name.'? Os vínculos de participantes e cargos serão removidos. Esta ação não pode ser desfeita nesta tela.', 'deleteUrl' => route('departments.destroy', $department)])
            @endcan
        @empty
            <div class="col-12"><div class="dept-empty"><i class="bx bx-search-alt" aria-hidden="true"></i>{{ ($search ?? '') !== '' ? 'Nenhum departamento encontrado para esta busca.' : 'Nenhum departamento nesta lista.' }}@if(($search ?? '') !== '')<br><a href="{{ route('departments.index', ['filter' => $filter]) }}">Limpar busca</a>@endif</div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $departments->links() }}</div>
</div>
@endsection
