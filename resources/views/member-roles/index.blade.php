@extends('layouts.porto')

@section('title', 'Cargos')

@section('page-title', 'Cargos')

@section('breadcrumbs')
    <li><a href="{{ route('members.index') }}">Membros</a></li>
    <li><span>Cargos</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewMembers = $isAdmin || 
                      ($user && ($user->hasPermission('members.index.view') || 
                                 $user->hasPermission('members.view') ||
                                 $user->hasPermission('members.index.manage')));
    $canViewRoles = $isAdmin || 
                    ($user && ($user->hasPermission('members.roles.view') || 
                               $user->hasPermission('members.roles.manage')));
    $canCreateRoles = $isAdmin || 
                      ($user && ($user->hasPermission('members.roles.create') || 
                                 $user->hasPermission('members.roles.manage')));
    $canEditRoles = $isAdmin || 
                    ($user && ($user->hasPermission('members.roles.edit') || 
                               $user->hasPermission('members.roles.manage')));
    $canDeleteRoles = $isAdmin || 
                      ($user && ($user->hasPermission('members.roles.delete') || 
                                 $user->hasPermission('members.roles.manage')));
@endphp

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

@if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">
            <i class="bx bx-error-circle me-2"></i>Erros de Importação Detalhados
        </h5>
        <p class="mb-2">Foram encontrados <strong>{{ count(session('import_errors')) }} erro(s)</strong> durante a importação:</p>
        <div style="max-height: 400px; overflow-y: auto;">
            <ul class="mb-0">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Coluna Esquerda - Lista de Cargos -->
    <div class="{{ $canCreateRoles ? 'col-lg-8' : 'col-lg-12' }}">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-id-card me-2"></i>Cargos de Membros
                </h2>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-0">Resultados: {{ $roles->count() }}</h4>
                        <p class="text-muted mb-0">Crie cargos para atribuir às pessoas cadastradas</p>
                    </div>
                    @if($canCreateRoles)
                    <div>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bx bx-upload me-2"></i>Importar Cargos
                        </button>
                    </div>
                    @endif
                </div>
                
                @if($roles->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th>Membros</th>
                                    <th width="200">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                    <tr>
                                        <td><strong>{{ $role->name }}</strong></td>
                                        <td>{{ $role->description ?? '-' }}</td>
                                        <td>
                                            @if($canViewMembers)
                                            <a href="{{ route('members.index', ['role_id' => $role->id]) }}" 
                                               class="btn btn-sm btn-info">
                                                Ver Membros
                                            </a>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                @if($canEditRoles)
                                                <a href="{{ route('member-roles.edit', $role) }}" 
                                                   class="btn btn-primary" 
                                                   title="Editar">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @endif
                                                @if($canDeleteRoles)
                                                <form action="{{ route('member-roles.destroy', $role) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Tem certeza que deseja excluir este cargo?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger" 
                                                            title="Remover">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                @if(!$canEditRoles && !$canDeleteRoles)
                                                <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum cargo cadastrado.
                    </div>
                @endif
            </div>
        </section>
    </div>

    <!-- Coluna Direita - Formulário de Criação -->
    @if($canCreateRoles)
    <div class="col-lg-4">
        <section class="card">
            <header class="card-header" style="background-color: #20c997; color: white;">
                <h5 class="card-title mb-0">
                    <i class="bx bx-plus me-2"></i>Criar cargo
                </h5>
            </header>
            <div class="card-body">
                <form action="{{ route('member-roles.store') }}" method="POST" id="createRoleForm">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do cargo <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               placeholder="Ex: Pastor, Diácono, Secretário..." 
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3" 
                                  placeholder="Descreva as responsabilidades e funções deste cargo...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn w-100" style="background-color: #20c997; color: white; border: none;">
                        <i class="bx bx-check me-2"></i>Criar
                    </button>
                </form>
            </div>
        </section>
    </div>
    @endif
</div>

<!-- Modal: Importar Cargos -->
@if($canCreateRoles)
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="bx bx-upload me-2"></i>Importar Cargos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('member-roles.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Formato do arquivo CSV:</strong><br>
                        O arquivo deve conter as colunas: nome, descricao, ativo, separadas por vírgula (,).<br>
                        <small>Baixe o template para ver o formato correto. Campos opcionais podem ficar vazios.</small>
                    </div>
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Selecionar arquivo CSV <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('import_file') is-invalid @enderror" 
                               id="import_file" name="import_file" accept=".csv,.txt" required>
                        @error('import_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Tamanho máximo: 10MB. Formato: CSV</small>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bx bx-error me-2"></i>
                        <strong>Atenção:</strong> Cargos com nomes duplicados serão ignorados. Verifique o template antes de importar.
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('member-roles.import.template') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-download me-2"></i>Baixar Template CSV
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-upload me-2"></i>Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createRoleForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nameInput = document.getElementById('name');
            if (nameInput && nameInput.value.trim() === '') {
                e.preventDefault();
                alert('Por favor, preencha o nome do cargo.');
                nameInput.focus();
                return false;
            }
        });
    }
});
</script>
@endpush
@endsection
