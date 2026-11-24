@extends('layouts.porto')
@include('departments.partials.workspace-styles')
@section('title', 'Projetos — '.$department->name)
@section('page-title', 'Projetos do departamento')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.show', $department) }}">{{ $department->name }}</a></li>
    <li><span>Projetos</span></li>
@endsection
@section('content')
<div class="department-workspace">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="h4 mb-0">Projetos de {{ $department->name }}</h2>
        @can('create', App\Models\Department::class)
            <a href="{{ route('departments.projects.create', $department) }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Novo projeto</a>
        @endcan
    </div>
    <div class="row">
        @forelse($projects as $project)
            <div class="col-12 col-lg-6 mb-4">
                <section class="card h-100">
                    <div class="card-body">
                        <h3 class="h5">{{ $project->name }}</h3>
                        <p class="department-text">{{ \Illuminate\Support\Str::limit($project->description ?: 'Sem descrição.', 220) }}</p>
                        <p class="text-muted">{{ $project->actions_count }} ação(ões)</p>
                        <a href="{{ route('departments.projects.show', [$department, $project]) }}" class="btn btn-default">Ver projeto e ações</a>
                    </div>
                </section>
            </div>
        @empty
            <div class="col-12"><p class="alert alert-info">Nenhum projeto cadastrado para este departamento.</p></div>
        @endforelse
    </div>
    {{ $projects->links() }}
</div>
@endsection
