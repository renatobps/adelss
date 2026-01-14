@extends('layouts.porto')

@section('title', 'Contas')

@section('page-title', 'Contas')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Contas</span></li>
@endsection

@section('content')
<!-- Header -->
<div class="alert alert-info mb-4" style="background-color: #e3f2fd; color: #1976d2; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Cadastre suas contas bancárias ou caixas.
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
    <!-- Painel Esquerdo: Lista de Contas -->
    <div class="col-lg-8 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body p-0">
                @if($accounts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th class="text-end" style="width: 100px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accounts as $account)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $account->name }}</strong>
                                                @if($account->description)
                                                    <br><small class="text-muted">{{ $account->description }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $account->id }}" title="Editar">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <form action="{{ route('financial.accounts.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover esta conta?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Remover">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal de Edição -->
                                    <div class="modal fade" id="editModal{{ $account->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $account->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editModalLabel{{ $account->id }}">
                                                        <i class="bx bx-edit me-2"></i>Editar Conta
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                </div>
                                                <form action="{{ route('financial.accounts.update', $account) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="edit_name{{ $account->id }}" class="form-label">Nome da conta <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                                   id="edit_name{{ $account->id }}" name="name" 
                                                                   value="{{ old('name', $account->name) }}" required>
                                                            @error('name')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_description{{ $account->id }}" class="form-label">Descrição</label>
                                                            <textarea class="form-control" id="edit_description{{ $account->id }}" 
                                                                      name="description" rows="3">{{ old('description', $account->description) }}</textarea>
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
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bx bx-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">Nenhuma conta cadastrada.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Painel Direito: Criar Conta -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header text-white" style="background-color: #20c997;">
                <h5 class="card-title mb-0">
                    <i class="bx bx-plus me-2"></i>+ Criar conta
                </h5>
            </header>
            <div class="card-body">
                <form action="{{ route('financial.accounts.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da conta <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="Digite o nome da conta" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4" 
                                  placeholder="Digite uma descrição (opcional)">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn w-100 text-white" style="background-color: #20c997;">
                        <i class="bx bx-check me-1"></i>Criar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
