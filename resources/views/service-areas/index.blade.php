@extends('layouts.porto')

@section('title', 'Áreas de Serviço')

@section('page-title', 'Áreas de Serviço')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.areas.index') }}">Áreas de Serviço</a></li>
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
                    <i class="bx bx-category me-2"></i>Áreas de Serviço
                </h2>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-4">
                    <a href="{{ route('voluntarios.areas.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-2"></i>Nova Área
                    </a>
                </div>

                @if($serviceAreas->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Responsável</th>
                                    <th>Quantidade Mínima</th>
                                    <th>Público Permitido</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($serviceAreas as $area)
                                    <tr>
                                        <td>
                                            <strong>{{ $area->name }}</strong>
                                            @if($area->description)
                                                <br><small class="text-muted">{{ mb_strlen($area->description) > 50 ? mb_substr($area->description, 0, 50) . '...' : $area->description }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($area->leader)
                                                {{ $area->leader->name }}
                                            @else
                                                <span class="text-muted">Não definido</span>
                                            @endif
                                        </td>
                                        <td>{{ $area->min_quantity }} pessoa(s)</td>
                                        <td>
                                            @if($area->allowed_audience == 'adulto')
                                                <span class="badge badge-info">Adulto</span>
                                            @elseif($area->allowed_audience == 'jovem')
                                                <span class="badge badge-warning">Jovem</span>
                                            @else
                                                <span class="badge badge-success">Ambos</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($area->status == 'ativo')
                                                <span class="badge badge-success">Ativo</span>
                                            @else
                                                <span class="badge badge-secondary">Inativo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('voluntarios.areas.show', $area) }}" class="btn btn-sm btn-default" title="Ver">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('voluntarios.areas.edit', $area) }}" class="btn btn-sm btn-default" title="Editar">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" title="Excluir" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $area->id }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal de Confirmação de Exclusão -->
                                    <div class="modal fade" id="deleteModal{{ $area->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $area->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteModalLabel{{ $area->id }}">
                                                        <i class="bx bx-error-circle me-2"></i>Confirmar Exclusão
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Tem certeza que deseja excluir a área de serviço <strong>"{{ $area->name }}"</strong>?</p>
                                                    <p class="text-muted small mb-0">Esta ação não pode ser desfeita.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">
                                                        <i class="bx bx-x me-2"></i>Cancelar
                                                    </button>
                                                    <form action="{{ route('voluntarios.areas.destroy', $area) }}" method="POST" class="d-inline">
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
                        {{ $serviceAreas->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhuma área de serviço cadastrada.
                        <br>
                        <a href="{{ route('voluntarios.areas.create') }}" class="mt-2 d-inline-block">
                            Cadastrar primeira área
                        </a>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
