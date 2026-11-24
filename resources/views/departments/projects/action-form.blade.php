@extends('layouts.porto')
@include('departments.partials.workspace-styles')
@section('title', $action->exists ? 'Editar ação' : 'Nova ação')
@section('page-title', $action->exists ? 'Editar ação' : 'Nova ação')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.projects.index', $department) }}">{{ $department->name }} · Projetos</a></li>
    <li><a href="{{ route('departments.projects.show', [$department, $project]) }}">{{ $project->name }}</a></li>
    <li><span>{{ $action->exists ? 'Editar ação' : 'Nova ação' }}</span></li>
@endsection
@section('content')
<section class="card department-workspace">
    <header class="card-header"><h2 class="card-title">Ação do projeto {{ $project->name }}</h2></header>
    <form method="POST" action="{{ $action->exists ? route('departments.projects.actions.update', [$department, $project, $action]) : route('departments.projects.actions.store', [$department, $project]) }}">
        @csrf
        @if($action->exists) @method('PUT') @endif
        <div class="card-body">
            <label for="action-name" class="form-label">Nome da ação ou iniciativa *</label>
            <input id="action-name" name="name" class="form-control mb-3 @error('name') is-invalid @enderror" value="{{ old('name', $action->name) }}" required maxlength="191">
            @error('name')<div class="text-danger">{{ $message }}</div>@enderror
            <label for="action-details" class="form-label">Detalhes da ação</label>
            <textarea id="action-details" name="details" class="form-control @error('details') is-invalid @enderror" rows="6" maxlength="10000">{{ old('details', $action->details) }}</textarea>
            @error('details')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <footer class="card-footer d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">Salvar ação</button>
            <a class="btn btn-default" href="{{ route('departments.projects.show', [$department, $project]) }}">Cancelar</a>
        </footer>
    </form>
</section>
@endsection
