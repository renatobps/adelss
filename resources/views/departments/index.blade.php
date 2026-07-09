@extends('layouts.porto')

@section('title', 'Departamentos')

@section('page-title', 'Departamentos')

@section('breadcrumbs')
    <li><span>Departamentos</span></li>
@endsection

@section('content')
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
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">Departamentos</h2>
            </header>
            <div class="card-body">
                <!-- Tabs: Ativos / Arquivados -->
                <div class="nav-tabs-wrapper mb-3">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $filter === 'ativo' ? 'active' : '' }}" 
                               href="{{ route('departments.index', ['filter' => 'ativo']) }}">
                                Ativos
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $filter === 'arquivado' ? 'active' : '' }}" 
                               href="{{ route('departments.index', ['filter' => 'arquivado']) }}">
                                Arquivados
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="d-flex justify-content-end mb-4">
                    <a href="{{ route('departments.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-2"></i>Adicionar
                    </a>
                </div>

                @if($departments->count() > 0)
                    <div class="row">
                        @foreach($departments as $department)
                            <div class="col-md-4 mb-4">
                                <section class="card">
                                    <div class="card-body text-center">
                                        @if($department->logo_url)
                                            <div class="mb-3">
                                                <img src="{{ asset('storage/' . $department->logo_url) }}"
                                                     alt="{{ $department->name }}"
                                                     class="rounded-circle"
                                                     style="width: 80px; height: 80px; object-fit: cover; border: 2px solid {{ $department->color ?? '#0088cc' }};">
                                            </div>
                                        @elseif($department->icon)
                                            <div class="mb-3">
                                                <i class="{{ $department->icon }}" 
                                                   style="font-size: 3rem; color: {{ $department->color ?? '#0088cc' }};"></i>
                                            </div>
                                        @endif
                                        <h4 class="card-title">{{ $department->name }}</h4>
                                        @if($department->description)
                                            <p class="card-text text-muted">{{ mb_strlen($department->description) > 100 ? mb_substr($department->description, 0, 100) . '...' : $department->description }}</p>
                                        @endif
                                        <div class="mt-3">
                                            <span class="badge badge-primary">{{ $department->members->count() }} membros</span>
                                            @if($department->leaders && $department->leaders->count() > 0)
                                                <span class="badge badge-info">
                                                    Líder{{ $department->leaders->count() > 1 ? 'es' : '' }}: 
                                                    {{ $department->leaders->pluck('name')->join(', ') }}
                                                </span>
                                            @elseif($department->leader)
                                                <span class="badge badge-info">Líder: {{ $department->leader->name }}</span>
                                            @endif
                                        </div>
                                        <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                                            <a href="{{ route('departments.show', $department) }}" class="btn btn-default btn-sm">
                                                <i class="bx bx-show"></i> Ver
                                            </a>
                                            <a href="{{ route('departments.edit', $department) }}" class="btn btn-default btn-sm">
                                                <i class="bx bx-edit"></i> Editar
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal{{ $department->id }}">
                                                <i class="bx bx-trash"></i> Excluir
                                            </button>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <!-- Modal de Confirmação de Exclusão -->
                            <div class="modal fade" id="deleteModal{{ $department->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $department->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title" id="deleteModalLabel{{ $department->id }}">
                                                <i class="bx bx-error-circle me-2"></i>Confirmar Exclusão
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Tem certeza que deseja excluir o departamento <strong>"{{ $department->name }}"</strong>?</p>
                                            @if($department->members->count() > 0)
                                                <div class="alert alert-warning">
                                                    <i class="bx bx-error me-2"></i>
                                                    <strong>Atenção!</strong> Este departamento possui <strong>{{ $department->members->count() }}</strong> membro(s) associado(s). 
                                                    Ao excluir, essas associações serão removidas.
                                                </div>
                                            @endif
                                            <p class="text-muted small mb-0">Esta ação não pode ser desfeita.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-bs-dismiss="modal">
                                                <i class="bx bx-x me-2"></i>Cancelar
                                            </button>
                                            <form action="{{ route('departments.destroy', $department) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bx bx-trash me-2"></i>Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $departments->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum departamento {{ $filter === 'ativo' ? 'ativo' : 'arquivado' }} encontrado.
                        <br>
                        <a href="{{ route('departments.create') }}" class="mt-2 d-inline-block">
                            Cadastrar departamento
                        </a>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

@endsection

