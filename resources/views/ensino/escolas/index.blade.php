@extends('layouts.porto')

@section('title', 'Escolas')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.escolas.index') }}">Ensino</a></li>
    <li><span>Escolas</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canCreateEscolas = $isAdmin || 
                       ($user && ($user->hasPermission('ensino.escolas.create') || 
                                  $user->hasPermission('ensino.escolas.manage')));
    $canEditEscolas = $isAdmin || 
                     ($user && ($user->hasPermission('ensino.escolas.edit') || 
                                $user->hasPermission('ensino.escolas.manage')));
    $canDeleteEscolas = $isAdmin || 
                       ($user && ($user->hasPermission('ensino.escolas.delete') || 
                                  $user->hasPermission('ensino.escolas.manage')));
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Sucesso!</strong> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Erro!</strong> {{ session('error') }}
    </div>
@endif

<div class="row">
    <!-- Listagem de Escolas (Lado Esquerdo) -->
    <div class="col-lg-7">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0" style="color: #2c3e50; font-weight: 600;">Escolas</h5>
            </div>
            <div class="card-body">
                <!-- Contador de resultados -->
                <div class="mb-3">
                    <strong style="color: #495057;">Resultados: {{ $schools->total() }}</strong>
                </div>

                <!-- Tabela de Escolas -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="cursor: pointer;" onclick="sortTable('name')">
                                    Nome
                                    <i class="bx bx-up-arrow-alt"></i>
                                    <i class="bx bx-down-arrow-alt"></i>
                                </th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schools as $school)
                                <tr>
                                    <td>
                                        <a href="{{ route('ensino.escolas.show', $school) }}" class="text-decoration-none fw-bold" style="color: #2c3e50;">
                                            {{ $school->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('ensino.escolas.show', $school) }}" class="btn btn-info btn-sm" title="Visualizar">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            @if($canEditEscolas)
                                            <button type="button" class="btn btn-primary btn-sm" onclick="editSchool({{ $school->id }}, {!! json_encode($school->name) !!}, {{ $school->manager_id ? $school->manager_id : 'null' }}, {!! json_encode($school->description ?? '') !!})" title="Editar">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            @endif
                                            @if($canDeleteEscolas)
                                            <form action="{{ route('ensino.escolas.destroy', $school) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta escola?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Remover">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">
                                        Nenhuma escola encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                @if($schools->hasPages())
                    <div class="mt-3">
                        {{ $schools->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Formulário de Criação/Edição (Lado Direito) -->
    @if($canCreateEscolas)
    <div class="col-lg-5">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bx bx-plus me-2"></i><span id="formTitle">Criar categoria</span>
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('ensino.escolas.store') }}" method="POST" id="schoolForm">
                    @csrf
                    <input type="hidden" id="formMethod" name="_method" value="POST">

                    <!-- Nome da escola -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Nome da escola</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Gestores -->
                    <div class="mb-3">
                        <label for="manager_id" class="form-label fw-bold">Gestores</label>
                        <select class="form-select @error('manager_id') is-invalid @enderror" 
                                id="manager_id" name="manager_id">
                            <option value="">Selecione</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('manager_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Descrição -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-fill" id="submitButton">
                            <i class="bx bx-save me-1"></i>Criar
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancelButton" onclick="resetForm()" style="display: none;">
                            <i class="bx bx-x me-1"></i>Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    function sortTable(field) {
        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get('sort');
        const currentDirection = url.searchParams.get('direction');
        
        if (currentSort === field && currentDirection === 'asc') {
            url.searchParams.set('direction', 'desc');
        } else {
            url.searchParams.set('direction', 'asc');
        }
        url.searchParams.set('sort', field);
        
        window.location.href = url.toString();
    }

    let currentEditId = null;

    function editSchool(id, name, managerId, description) {
        // Preencher formulário
        currentEditId = id;
        document.getElementById('name').value = name || '';
        document.getElementById('manager_id').value = managerId || '';
        document.getElementById('description').value = description || '';
        
        // Mudar título e ação do formulário
        document.getElementById('formTitle').textContent = 'Editar categoria';
        document.getElementById('submitButton').innerHTML = '<i class="bx bx-save me-1"></i>Salvar';
        document.getElementById('cancelButton').style.display = 'block';
        
        // Mudar action do formulário usando a URL correta
        const form = document.getElementById('schoolForm');
        const updateUrl = '{{ url("ensino/escolas") }}/' + id;
        form.action = updateUrl;
        document.getElementById('formMethod').value = 'PUT';
        
        // Scroll para o formulário
        document.querySelector('.col-lg-5').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function resetForm() {
        // Limpar formulário
        document.getElementById('schoolForm').reset();
        currentEditId = null;
        document.getElementById('formMethod').value = 'POST';
        
        // Restaurar título e botões
        document.getElementById('formTitle').textContent = 'Criar categoria';
        document.getElementById('submitButton').innerHTML = '<i class="bx bx-save me-1"></i>Criar';
        document.getElementById('cancelButton').style.display = 'none';
        
        // Restaurar action
        document.getElementById('schoolForm').action = '{{ route("ensino.escolas.store") }}';
    }

    // Limpar formulário ao carregar a página se houver mensagem de sucesso
    @if(session('success'))
        setTimeout(function() {
            if (currentEditId === null) {
                resetForm();
            }
        }, 100);
    @endif
</script>
@endpush
@endsection
