@extends('layouts.porto')
@include('departments.partials.workspace-styles')
@section('title', $project->exists ? 'Editar projeto' : 'Novo projeto')
@section('page-title', $project->exists ? 'Editar projeto' : 'Novo projeto')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.projects.index', $department) }}">{{ $department->name }} · Projetos</a></li>
    <li><span>{{ $project->exists ? 'Editar' : 'Cadastrar' }}</span></li>
@endsection
@section('content')
<section class="card department-workspace">
    <header class="card-header"><h2 class="card-title">Projeto de {{ $department->name }}</h2></header>
    <form method="POST" action="{{ $project->exists ? route('departments.projects.update', [$department, $project]) : route('departments.projects.store', $department) }}">
        @csrf
        @if($project->exists) @method('PUT') @endif
        <div class="card-body">
            <label for="project-name" class="form-label">Nome do projeto *</label>
            <input id="project-name" name="name" class="form-control mb-3 @error('name') is-invalid @enderror" value="{{ old('name', $project->name) }}" required maxlength="191">
            @error('name')<div class="text-danger">{{ $message }}</div>@enderror
            <label for="project-description" class="form-label">Descrição do projeto</label>
            <textarea id="project-description" name="description" class="form-control @error('description') is-invalid @enderror" rows="6" maxlength="10000">{{ old('description', $project->description) }}</textarea>
            @error('description')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <footer class="card-footer d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="submit">Salvar projeto</button>
            <a href="{{ $project->exists ? route('departments.projects.show', [$department, $project]) : route('departments.projects.index', $department) }}" class="btn btn-default">Cancelar</a>
        </footer>
    </form>
</section>
@endsection
