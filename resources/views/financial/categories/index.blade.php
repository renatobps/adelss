@extends('layouts.porto')

@section('title', 'Categorias')

@section('page-title', 'Categorias')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Categorias</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewCategories = $isAdmin || $user->hasPermission('financial.categories.view') || $user->hasPermission('financial.categories.manage');
    $canCreateCategories = $isAdmin || $user->hasPermission('financial.categories.create') || $user->hasPermission('financial.categories.manage');
    $canEditCategories = $isAdmin || $user->hasPermission('financial.categories.edit') || $user->hasPermission('financial.categories.manage');
    $canDeleteCategories = $isAdmin || $user->hasPermission('financial.categories.delete') || $user->hasPermission('financial.categories.manage');
@endphp

<!-- Header -->
<div class="alert alert-primary mb-4" style="background-color: #007bff; color: white; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Gerencie todas as categorias de transações financeiras.
</div>

<!-- Contador de Resultados -->
<div class="mb-3">
    <strong>Resultados: {{ $total }}</strong>
</div>

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
    <!-- Painel: Receitas -->
    <div class="col-lg-5 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header text-white" style="background-color: #28a745;">
                <h5 class="card-title mb-0">
                    <i class="bx bx-trending-up me-2"></i>Receitas ({{ $receitas->count() }})
                </h5>
            </header>
            <div class="card-body p-0">
                @if($receitas->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome da categoria</th>
                                    <th class="text-end" style="width: 100px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receitas as $receita)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $receita->name }}</strong>
                                                @if($receita->description)
                                                    <br><small class="text-muted">{{ $receita->description }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            @if($canEditCategories)
                                            <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $receita->id }}" title="Editar">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            @endif
                                            @if($canDeleteCategories)
                                            <form action="{{ route('financial.categories.destroy', $receita) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover esta categoria?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Remover">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Modal de Edição -->
                                    @if($canEditCategories)
                                    <div class="modal fade" id="editModal{{ $receita->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $receita->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editModalLabel{{ $receita->id }}">
                                                        <i class="bx bx-edit me-2"></i>Editar Categoria
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                </div>
                                                <form action="{{ route('financial.categories.update', $receita) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="edit_name{{ $receita->id }}" class="form-label">Nome da categoria <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                                   id="edit_name{{ $receita->id }}" name="name" 
                                                                   value="{{ old('name', $receita->name) }}" required>
                                                            @error('name')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_description{{ $receita->id }}" class="form-label">Descrição</label>
                                                            <textarea class="form-control" id="edit_description{{ $receita->id }}" 
                                                                      name="description" rows="3">{{ old('description', $receita->description) }}</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="type" id="edit_type_receita{{ $receita->id }}" 
                                                                       value="receita" {{ old('type', $receita->type) == 'receita' ? 'checked' : '' }} required>
                                                                <label class="form-check-label" for="edit_type_receita{{ $receita->id }}">
                                                                    Receitas
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="type" id="edit_type_despesa{{ $receita->id }}" 
                                                                       value="despesa" {{ old('type', $receita->type) == 'despesa' ? 'checked' : '' }} required>
                                                                <label class="form-check-label" for="edit_type_despesa{{ $receita->id }}">
                                                                    Despesas
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="bx bx-save me-1"></i>Salvar
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bx bx-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">Nenhuma categoria de receita cadastrada.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Painel: Despesas -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header text-white" style="background-color: #dc3545;">
                <h5 class="card-title mb-0">
                    <i class="bx bx-trending-down me-2"></i>Despesas ({{ $despesas->count() }})
                </h5>
            </header>
            <div class="card-body p-0">
                @if($despesas->count() > 0)
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th>Nome da categoria</th>
                                    <th class="text-end" style="width: 100px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($despesas as $despesa)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $despesa->name }}</strong>
                                                @if($despesa->description)
                                                    <br><small class="text-muted">{{ $despesa->description }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            @if($canEditCategories)
                                            <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $despesa->id }}" title="Editar">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            @endif
                                            @if($canDeleteCategories)
                                            <form action="{{ route('financial.categories.destroy', $despesa) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover esta categoria?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Remover">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Modal de Edição -->
                                    @if($canEditCategories)
                                    <div class="modal fade" id="editModal{{ $despesa->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $despesa->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editModalLabel{{ $despesa->id }}">
                                                        <i class="bx bx-edit me-2"></i>Editar Categoria
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                </div>
                                                <form action="{{ route('financial.categories.update', $despesa) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="edit_name{{ $despesa->id }}" class="form-label">Nome da categoria <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                                   id="edit_name{{ $despesa->id }}" name="name" 
                                                                   value="{{ old('name', $despesa->name) }}" required>
                                                            @error('name')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_description{{ $despesa->id }}" class="form-label">Descrição</label>
                                                            <textarea class="form-control" id="edit_description{{ $despesa->id }}" 
                                                                      name="description" rows="3">{{ old('description', $despesa->description) }}</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="type" id="edit_type_receita{{ $despesa->id }}" 
                                                                       value="receita" {{ old('type', $despesa->type) == 'receita' ? 'checked' : '' }} required>
                                                                <label class="form-check-label" for="edit_type_receita{{ $despesa->id }}">
                                                                    Receitas
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="type" id="edit_type_despesa{{ $despesa->id }}" 
                                                                       value="despesa" {{ old('type', $despesa->type) == 'despesa' ? 'checked' : '' }} required>
                                                                <label class="form-check-label" for="edit_type_despesa{{ $despesa->id }}">
                                                                    Despesas
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="bx bx-save me-1"></i>Salvar
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bx bx-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">Nenhuma categoria de despesa cadastrada.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Painel: Criar Categoria -->
    @if($canCreateCategories)
    <div class="col-lg-3 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="bx bx-plus me-2"></i>+ Criar categoria
                </h5>
            </header>
            <div class="card-body">
                <form action="{{ route('financial.categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da categoria <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="Digite o nome da categoria" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3" 
                                  placeholder="Digite uma descrição (opcional)">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_receita" 
                                   value="receita" {{ old('type', 'receita') == 'receita' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="type_receita">
                                Receitas
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_despesa" 
                                   value="despesa" {{ old('type') == 'despesa' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="type_despesa">
                                Despesas
                            </label>
                        </div>
                        @error('type')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bx bx-check me-1"></i>Criar
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
