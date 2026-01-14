@extends('layouts.porto')

@section('title', 'Cadastro de Voluntários')

@section('page-title', 'Cadastro de Voluntários')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.cadastro.index') }}">Cadastro de Voluntários</a></li>
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
                <h2 class="card-title">
                    <i class="bx bx-user-plus me-2"></i>Cadastro de Voluntários
                </h2>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-4">
                    <a href="{{ route('voluntarios.cadastro.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-2"></i>Novo Voluntário
                    </a>
                </div>

                @if($volunteers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Membro</th>
                                    <th>Áreas de Serviço</th>
                                    <th>Nível de Experiência</th>
                                    <th>Data de Início</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($volunteers as $volunteer)
                                    <tr>
                                        <td>
                                            <strong>{{ $volunteer->member->name }}</strong>
                                        </td>
                                        <td>
                                            @if($volunteer->serviceAreas->count() > 0)
                                                @foreach($volunteer->serviceAreas as $area)
                                                    <span class="badge badge-info me-1">{{ $area->name }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Nenhuma área definida</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($volunteer->experience_level == 'novo')
                                                <span class="badge badge-warning">Novo</span>
                                            @elseif($volunteer->experience_level == 'em_treinamento')
                                                <span class="badge badge-info">Em Treinamento</span>
                                            @else
                                                <span class="badge badge-success">Experiente</span>
                                            @endif
                                        </td>
                                        <td>{{ $volunteer->start_date->format('d/m/Y') }}</td>
                                        <td>
                                            @if($volunteer->status == 'ativo')
                                                <span class="badge badge-success">Ativo</span>
                                            @else
                                                <span class="badge badge-secondary">Inativo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('voluntarios.cadastro.show', $volunteer) }}" class="btn btn-sm btn-default" title="Ver">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('voluntarios.cadastro.edit', $volunteer) }}" class="btn btn-sm btn-default" title="Editar">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" title="Excluir" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $volunteer->id }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal de Confirmação de Exclusão -->
                                    <div class="modal fade" id="deleteModal{{ $volunteer->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $volunteer->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteModalLabel{{ $volunteer->id }}">
                                                        <i class="bx bx-error-circle me-2"></i>Confirmar Exclusão
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Tem certeza que deseja excluir o voluntário <strong>"{{ $volunteer->member->name }}"</strong>?</p>
                                                    <p class="text-muted small mb-0">Esta ação não pode ser desfeita.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">
                                                        <i class="bx bx-x me-2"></i>Cancelar
                                                    </button>
                                                    <form action="{{ route('voluntarios.cadastro.destroy', $volunteer) }}" method="POST" class="d-inline">
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
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $volunteers->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum voluntário cadastrado.
                        <br>
                        <a href="{{ route('voluntarios.cadastro.create') }}" class="mt-2 d-inline-block">
                            Cadastrar primeiro voluntário
                        </a>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
