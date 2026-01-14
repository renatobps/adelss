@extends('layouts.porto')

@section('title', 'Centros de Custos')

@section('page-title', 'Centros de Custos')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Centros de Custos</span></li>
@endsection

@section('content')
<!-- Header -->
<div class="alert alert-info mb-4" style="background-color: #e3f2fd; color: #1976d2; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Gerencie os centros de custos da sua instituição.
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
    <!-- Painel Esquerdo: Lista de Centros de Custos -->
    <div class="col-lg-8 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body p-0">
                @if($costCenters->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th class="text-end" style="width: 100px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($costCenters as $costCenter)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $costCenter->name }}</strong>
                                                @if($costCenter->description)
                                                    <br><small class="text-muted">{{ $costCenter->description }}</small>
                                                @endif
                                                @if($costCenter->departments->count() > 0)
                                                    <br><small class="text-info">
                                                        <i class="bx bx-building me-1"></i>
                                                        Departamentos: {{ $costCenter->departments->pluck('name')->implode(', ') }}
                                                    </small>
                                                @else
                                                    <br><small class="text-muted">Sem departamentos vinculados</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $costCenter->id }}" title="Editar">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <form action="{{ route('financial.cost-centers.destroy', $costCenter) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este centro de custo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Remover">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal de Edição -->
                                    <div class="modal fade" id="editModal{{ $costCenter->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $costCenter->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editModalLabel{{ $costCenter->id }}">
                                                        <i class="bx bx-edit me-2"></i>Editar Centro de Custo
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                </div>
                                                <form action="{{ route('financial.cost-centers.update', $costCenter) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="edit_name{{ $costCenter->id }}" class="form-label">Nome do centro de custos <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                                   id="edit_name{{ $costCenter->id }}" name="name" 
                                                                   value="{{ old('name', $costCenter->name) }}" required>
                                                            @error('name')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_description{{ $costCenter->id }}" class="form-label">Descrição</label>
                                                            <textarea class="form-control" id="edit_description{{ $costCenter->id }}" 
                                                                      name="description" rows="3">{{ old('description', $costCenter->description) }}</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_departments{{ $costCenter->id }}" class="form-label">Departamentos</label>
                                                            <select class="form-select" id="edit_departments{{ $costCenter->id }}" name="departments[]" multiple size="5">
                                                                @foreach($departments as $department)
                                                                    <option value="{{ $department->id }}" 
                                                                            {{ $costCenter->departments->contains($department->id) ? 'selected' : '' }}>
                                                                        {{ $department->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <small class="form-text text-muted">Mantenha Ctrl (ou Cmd no Mac) pressionado para selecionar múltiplos departamentos. Deixe vazio para criar um centro de custo sem departamentos.</small>
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
                        <p class="mt-2">Nenhum centro de custo cadastrado.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Painel Direito: Criar Centro de Custo -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header text-white" style="background-color: #20c997;">
                <h5 class="card-title mb-0">
                    <i class="bx bx-plus me-2"></i>+ Criar centro de custo
                </h5>
            </header>
            <div class="card-body">
                <form action="{{ route('financial.cost-centers.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do centro de custos <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="Digite o nome do centro de custos" required>
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

                    <div class="mb-3">
                        <label for="departments" class="form-label">Departamentos</label>
                        <select class="form-select @error('departments') is-invalid @enderror" 
                                id="departments" name="departments[]" multiple size="5">
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" 
                                        {{ old('departments') && in_array($department->id, old('departments')) ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Mantenha Ctrl (ou Cmd no Mac) pressionado para selecionar múltiplos departamentos. Deixe vazio para criar um centro de custo sem departamentos.</small>
                        @error('departments')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('departments.*')
                            <div class="text-danger small">{{ $message }}</div>
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
