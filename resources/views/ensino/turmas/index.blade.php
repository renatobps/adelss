@extends('layouts.porto')

@section('title', 'Turmas')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.turmas.index') }}">Ensino</a></li>
    <li><span>Turmas</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canCreateTurmas = $isAdmin || 
                       ($user && ($user->hasPermission('ensino.turmas.create') || 
                                  $user->hasPermission('ensino.turmas.manage')));
    $canEditTurmas = $isAdmin || 
                     ($user && ($user->hasPermission('ensino.turmas.edit') || 
                                $user->hasPermission('ensino.turmas.manage')));
    $canDeleteTurmas = $isAdmin || 
                       ($user && ($user->hasPermission('ensino.turmas.delete') || 
                                  $user->hasPermission('ensino.turmas.manage')));
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
    <!-- Listagem de Turmas (Lado Esquerdo) -->
    <div class="col-lg-7">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0" style="color: #2c3e50; font-weight: 600;">Turmas</h5>
            </div>
            <div class="card-body">
                <!-- Descrição -->
                <p class="text-muted mb-3">Gerencie todas as turmas das suas escolas</p>

                <!-- Contador de resultados -->
                <div class="mb-3">
                    <strong style="color: #495057;">Resultados: {{ $turmas->total() }}</strong>
                </div>

                <!-- Tabela de Turmas -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="cursor: pointer;" onclick="sortTable('name')">
                                    Nome
                                    <i class="bx bx-up-arrow-alt"></i>
                                    <i class="bx bx-down-arrow-alt"></i>
                                </th>
                                <th style="cursor: pointer;" onclick="sortTable('schedule')">
                                    Horário
                                    <i class="bx bx-up-arrow-alt"></i>
                                    <i class="bx bx-down-arrow-alt"></i>
                                </th>
                                <th>Escola</th>
                                <th style="cursor: pointer;" onclick="sortTable('status')">
                                    Status
                                    <i class="bx bx-up-arrow-alt"></i>
                                    <i class="bx bx-down-arrow-alt"></i>
                                </th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($turmas as $turma)
                                <tr>
                                    <td><a href="{{ route('ensino.turmas.show', $turma) }}" style="color: #007bff; text-decoration: none;"><strong>{{ $turma->name }}</strong></a></td>
                                    <td>{{ $turma->schedule ? ucfirst($turma->schedule) : '-' }}</td>
                                    <td>{{ $turma->school->name ?? '-' }}</td>
                                    <td>
                                        @if($turma->status == 'em andamento')
                                            <span class="badge bg-success">{{ ucfirst($turma->status) }}</span>
                                        @elseif($turma->status == 'preparando turma')
                                            <span class="badge bg-warning">{{ ucfirst($turma->status) }}</span>
                                        @elseif($turma->status == 'pausada')
                                            <span class="badge bg-secondary">{{ ucfirst($turma->status) }}</span>
                                        @elseif($turma->status == 'finalizada')
                                            <span class="badge bg-dark">{{ ucfirst($turma->status) }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($turma->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            @if($canEditTurmas)
                                            <a href="{{ route('ensino.turmas.edit', $turma) }}" class="btn btn-primary btn-sm" title="Editar">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            @endif
                                            @if($canDeleteTurmas)
                                            <form action="{{ route('ensino.turmas.destroy', $turma) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta turma?');">
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
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Nenhuma turma encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                @if($turmas->hasPages())
                    <div class="mt-3">
                        {{ $turmas->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Formulário de Criação/Edição (Lado Direito -->
    @if($canCreateTurmas)
    <div class="col-lg-5">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bx bx-plus me-2"></i><span id="formTitle">Criar turma</span>
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('ensino.turmas.store') }}" method="POST" id="turmaForm">
                    @csrf
                    <input type="hidden" id="formMethod" name="_method" value="POST">

                    <!-- Nome da turma -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Nome da turma</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Escola -->
                    <div class="mb-3">
                        <label for="school_id" class="form-label fw-bold">Escola</label>
                        <select class="form-select @error('school_id') is-invalid @enderror" 
                                id="school_id" name="school_id" required>
                            <option value="">Selecione</option>
                            @foreach($schools as $schoolOption)
                                <option value="{{ $schoolOption->id }}">
                                    {{ $schoolOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Horário -->
                    <div class="mb-3">
                        <label for="schedule" class="form-label fw-bold">Horário</label>
                        <select class="form-select @error('schedule') is-invalid @enderror" 
                                id="schedule" name="schedule">
                            <option value="">Não definido</option>
                            <option value="manhã" {{ old('schedule') == 'manhã' ? 'selected' : '' }}>Manhã</option>
                            <option value="tarde" {{ old('schedule') == 'tarde' ? 'selected' : '' }}>Tarde</option>
                            <option value="noite" {{ old('schedule') == 'noite' ? 'selected' : '' }}>Noite</option>
                        </select>
                        @error('schedule')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label for="status" class="form-label fw-bold">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" name="status" required>
                            <option value="preparando turma" {{ old('status', 'preparando turma') == 'preparando turma' ? 'selected' : '' }}>Preparando turma</option>
                            <option value="em andamento" {{ old('status') == 'em andamento' ? 'selected' : '' }}>Em andamento</option>
                            <option value="pausada" {{ old('status') == 'pausada' ? 'selected' : '' }}>Pausada</option>
                            <option value="finalizada" {{ old('status') == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                        </select>
                        @error('status')
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

    function editTurma(id, name, schoolId, schedule, status, description) {
        // Preencher formulário
        currentEditId = id;
        document.getElementById('name').value = name || '';
        document.getElementById('school_id').value = schoolId || '';
        document.getElementById('schedule').value = schedule || '';
        document.getElementById('status').value = status || 'preparando turma';
        document.getElementById('description').value = description || '';
        
        // Mudar título e ação do formulário
        document.getElementById('formTitle').textContent = 'Editar turma';
        document.getElementById('submitButton').innerHTML = '<i class="bx bx-save me-1"></i>Salvar';
        document.getElementById('cancelButton').style.display = 'block';
        
        // Mudar action do formulário
        const form = document.getElementById('turmaForm');
        const updateUrl = '{{ url("ensino/turmas") }}/' + id;
        form.action = updateUrl;
        document.getElementById('formMethod').value = 'PUT';
        
        // Scroll para o formulário
        document.querySelector('.col-lg-5').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function resetForm() {
        // Limpar formulário
        document.getElementById('turmaForm').reset();
        currentEditId = null;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('status').value = 'preparando turma'; // Valor padrão
        
        // Restaurar título e botões
        document.getElementById('formTitle').textContent = 'Criar turma';
        document.getElementById('submitButton').innerHTML = '<i class="bx bx-save me-1"></i>Criar';
        document.getElementById('cancelButton').style.display = 'none';
        
        // Restaurar action
        document.getElementById('turmaForm').action = '{{ route("ensino.turmas.store") }}';
    }

    // Limpar formulário após submit bem-sucedido
    @if(session('success'))
        setTimeout(function() {
            resetForm();
        }, 500);
    @endif
</script>
@endpush
@endsection
