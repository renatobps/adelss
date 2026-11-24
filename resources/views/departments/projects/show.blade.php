@extends('layouts.porto')
@include('departments.partials.workspace-styles')
@section('title', $project->name)
@section('page-title', 'Projeto e ações')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.projects.index', $department) }}">{{ $department->name }} · Projetos</a></li>
    <li><span>{{ $project->name }}</span></li>
@endsection
@section('content')
<div class="department-workspace">
    <section class="card mb-4">
        <header class="card-header"><h2 class="card-title">{{ $project->name }}</h2></header>
        <div class="card-body">
            <p class="department-text">{{ $project->description ?: 'Sem descrição.' }}</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-default" href="{{ route('departments.projects.index', $department) }}">Voltar aos projetos</a>
                @can('update', $department)
                    <a class="btn btn-primary" href="{{ route('departments.projects.edit', [$department, $project]) }}">Editar projeto</a>
                @endcan
                @can('delete', $department)
                    <button class="btn btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#delete-project">Excluir projeto</button>
                @endcan
            </div>
        </div>
    </section>
    @can('delete', $department)
        @include('departments.partials.delete-modal', ['modalId' => 'delete-project', 'deleteTitle' => 'Excluir projeto', 'deleteMessage' => 'Excluir o projeto '.$project->name.' e todas as suas ações? Esta ação não pode ser desfeita.', 'deleteUrl' => route('departments.projects.destroy', [$department, $project])])
    @endcan
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h4 mb-0">Ações e iniciativas ({{ $actions->total() }})</h2>
        @can('create', App\Models\Department::class)
            <a class="btn btn-primary" href="{{ route('departments.projects.actions.create', [$department, $project]) }}">Nova ação</a>
        @endcan
    </div>
    @forelse($actions as $action)
        <section class="card mb-3">
            <div class="card-body">
                <h3 class="h5">{{ $action->name }}</h3>
                <p class="department-text">{{ $action->details ?: 'Sem detalhes.' }}</p>
                <div class="d-flex flex-wrap gap-2">
                    @can('update', $department)
                        <a class="btn btn-default" href="{{ route('departments.projects.actions.edit', [$department, $project, $action]) }}">Editar ação</a>
                    @endcan
                    @can('delete', $department)
                        <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#delete-action-{{ $action->id }}">Excluir ação</button>
                    @endcan
                </div>
            </div>
        </section>
        @can('delete', $department)
            @include('departments.partials.delete-modal', ['modalId' => 'delete-action-'.$action->id, 'deleteTitle' => 'Excluir ação', 'deleteMessage' => 'Excluir a ação '.$action->name.'? Esta ação não pode ser desfeita.', 'deleteUrl' => route('departments.projects.actions.destroy', [$department, $project, $action])])
        @endcan
    @empty
        <p class="alert alert-info">Nenhuma ação cadastrada neste projeto.</p>
    @endforelse
    {{ $actions->links() }}
</div>
@endsection
