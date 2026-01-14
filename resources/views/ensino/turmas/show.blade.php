@extends('layouts.porto')

@section('title', 'Detalhes da Turma')

@section('page-title', $turma->name)

@section('breadcrumbs')
    <li><a href="{{ route('ensino.turmas.index') }}">Turma</a></li>
    <li><a href="{{ route('ensino.turmas.index') }}">Turmas</a></li>
    <li><span>Turma</span></li>
@endsection

@section('content')
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
    <!-- Sidebar Esquerda - Informações da Turma -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header" style="background: #f8f9fa;">
                <h5 class="mb-0">
                    <i class="bx bx-info-circle me-2"></i>Informações
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('ensino.turmas.update', $turma) }}" method="POST" id="turmaInfoForm">
                    @csrf
                    @method('PUT')

                    <!-- Nome da turma -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Nome da turma</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $turma->name) }}" required>
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
                                <option value="{{ $schoolOption->id }}" {{ old('school_id', $turma->school_id) == $schoolOption->id ? 'selected' : '' }}>
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
                            <option value="manhã" {{ old('schedule', $turma->schedule) == 'manhã' ? 'selected' : '' }}>Manhã</option>
                            <option value="tarde" {{ old('schedule', $turma->schedule) == 'tarde' ? 'selected' : '' }}>Tarde</option>
                            <option value="noite" {{ old('schedule', $turma->schedule) == 'noite' ? 'selected' : '' }}>Noite</option>
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
                            <option value="preparando turma" {{ old('status', $turma->status) == 'preparando turma' ? 'selected' : '' }}>Preparando turma</option>
                            <option value="em andamento" {{ old('status', $turma->status) == 'em andamento' ? 'selected' : '' }}>Em andamento</option>
                            <option value="pausada" {{ old('status', $turma->status) == 'pausada' ? 'selected' : '' }}>Pausada</option>
                            <option value="finalizada" {{ old('status', $turma->status) == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Descrição -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4">{{ old('description', $turma->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Botões -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-fill">
                            <i class="bx bx-save me-1"></i>Salvar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteTurma()">
                            <i class="bx bx-trash me-1"></i>Excluir turma
                        </button>
                    </div>
                </form>

                <!-- Formulário oculto para deletar -->
                <form action="{{ route('ensino.turmas.destroy', $turma) }}" method="POST" id="deleteForm" style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>

    <!-- Área Principal - Tabs -->
    <div class="col-lg-8">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" role="tablist" style="border-bottom: 2px solid #dee2e6;">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="alunos-tab" data-bs-toggle="tab" href="#alunos" role="tab" aria-controls="alunos" aria-selected="true">
                        Alunos
                        @if($studentsCount > 0)
                            <span class="badge bg-primary ms-1">{{ $studentsCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="disciplinas-tab" data-bs-toggle="tab" href="#disciplinas" role="tab" aria-controls="disciplinas" aria-selected="false">
                        Disciplinas
                        @if($disciplinesCount > 0)
                            <span class="badge bg-primary ms-1">{{ $disciplinesCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="aulas-tab" data-bs-toggle="tab" href="#aulas" role="tab" aria-controls="aulas" aria-selected="false">
                        Aulas
                        @if($lessonsCount > 0)
                            <span class="badge bg-primary ms-1">{{ $lessonsCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="arquivos-tab" data-bs-toggle="tab" href="#arquivos" role="tab" aria-controls="arquivos" aria-selected="false">
                        Arquivos
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="relatorios-tab" data-bs-toggle="tab" href="#relatorios" role="tab" aria-controls="relatorios" aria-selected="false">
                        Relatórios
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="turmaTabContent">
                <!-- Tab Alunos -->
                <div class="tab-pane fade show active" id="alunos" role="tabpanel" aria-labelledby="alunos-tab">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Alunos da Turma</h5>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                <i class="bx bx-plus me-1"></i>Adicionar aluno
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Foto</th>
                                        <th>Nome</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($turma->students as $student)
                                        <tr>
                                            <td>
                                                @if($student->photo)
                                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->name }}" 
                                                         class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px; color: white;">
                                                        <i class="bx bx-user"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td><strong>{{ $student->name }}</strong></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeStudent({{ $student->id }})">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                Nenhum aluno cadastrado nesta turma.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Disciplinas -->
                <div class="tab-pane fade" id="disciplinas" role="tabpanel" aria-labelledby="disciplinas-tab">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Disciplinas da Turma</h5>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addDisciplineModal">
                                <i class="bx bx-plus me-1"></i>Adicionar disciplina
                            </button>
                        </div>

                        <div class="row">
                            @forelse($turma->disciplines as $discipline)
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $discipline->name }}</h6>
                                            <p class="card-text mb-2">
                                                <strong>Professor(es):</strong>
                                                @forelse($discipline->teachers as $teacher)
                                                    <span class="badge bg-info me-1">
                                                        @if($teacher->photo)
                                                            <img src="{{ asset('storage/' . $teacher->photo) }}" alt="{{ $teacher->name }}" 
                                                                 class="rounded-circle" style="width: 20px; height: 20px; object-fit: cover;">
                                                        @else
                                                            <i class="bx bx-user"></i>
                                                        @endif
                                                        {{ $teacher->name }}
                                                    </span>
                                                @empty
                                                    <span class="text-muted">Nenhum professor atribuído</span>
                                                @endforelse
                                            </p>
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-primary btn-sm edit-discipline-btn" 
                                                        data-discipline-id="{{ $discipline->id }}"
                                                        data-discipline-name="{{ $discipline->name }}"
                                                        data-teacher-ids="{{ $discipline->teachers->pluck('id')->implode(',') }}">
                                                    <i class="bx bx-check"></i> Editar
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteDiscipline({{ $discipline->id }})">
                                                    <i class="bx bx-trash"></i> Remover
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="bx bx-info-circle me-2"></i>
                                        Nenhuma disciplina cadastrada nesta turma.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Tab Aulas -->
                <div class="tab-pane fade" id="aulas" role="tabpanel" aria-labelledby="aulas-tab">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Aulas Registradas</h5>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addLessonModal">
                                <i class="bx bx-plus me-1"></i>Registrar aula
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Disciplina</th>
                                        <th>Assunto</th>
                                        <th>Alunos</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($turma->lessons as $lesson)
                                        <tr>
                                            <td>{{ $lesson->lesson_date->format('d/m/Y') }}</td>
                                            <td>{{ $lesson->discipline->name ?? '-' }}</td>
                                            <td>{{ $lesson->subject ?? '-' }}</td>
                                            <td>{{ $lesson->attendances->where('present', true)->count() }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-primary btn-sm" onclick="viewLesson({{ $lesson->id }})">
                                                    Visualizar / Editar
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteLesson({{ $lesson->id }})">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                Nenhuma aula registrada nesta turma.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab Arquivos -->
                <div class="tab-pane fade" id="arquivos" role="tabpanel" aria-labelledby="arquivos-tab">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Arquivos da Turma</h5>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFileModal">
                                <i class="bx bx-plus me-1"></i>Adicionar
                            </button>
                        </div>

                        <div class="list-group">
                            @forelse($turma->files as $file)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $file->title }}</h6>
                                        @if($file->discipline)
                                            <small class="text-muted">Disciplina: {{ $file->discipline->name }}</small>
                                        @endif
                                        @if($file->description)
                                            <p class="mb-0 small">{{ $file->description }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        @if($file->type == 'file' && $file->file_path)
                                            <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="btn btn-sm btn-info me-2">
                                                <i class="bx bx-download"></i> Download
                                            </a>
                                        @elseif($file->type == 'external_link' && $file->external_url)
                                            <a href="{{ $file->external_url }}" target="_blank" class="btn btn-sm btn-info me-2">
                                                <i class="bx bx-link-external"></i> Abrir Link
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteFile({{ $file->id }})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-info text-center">
                                    <i class="bx bx-info-circle me-2"></i>
                                    Nenhum arquivo cadastrado nesta turma.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Tab Relatórios -->
                <div class="tab-pane fade" id="relatorios" role="tabpanel" aria-labelledby="relatorios-tab">
                    <div class="card-body">
                        <h5 class="mb-3">Disciplinas</h5>
                        <div class="row">
                            @foreach($turma->disciplines as $discipline)
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                        <span>{{ $discipline->name }}</span>
                                        <button type="button" class="btn btn-primary btn-sm frequency-btn" 
                                                data-discipline-id="{{ $discipline->id }}"
                                                data-discipline-name="{{ $discipline->name }}">
                                            Frequência mensal
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('ensino.turmas.modals.add-student')
@include('ensino.turmas.modals.add-discipline')
@include('ensino.turmas.modals.add-lesson')
@include('ensino.turmas.modals.add-file')
@include('ensino.turmas.modals.frequency-monthly')


@push('scripts')
<script>
    function deleteTurma() {
        if (confirm('Tem certeza que deseja excluir esta turma? Esta ação não pode ser desfeita.')) {
            document.getElementById('deleteForm').submit();
        }
    }

    function removeStudent(memberId) {
        if (confirm('Tem certeza que deseja remover este aluno da turma?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("ensino/turmas/{$turma->id}/students") }}/' + memberId;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Event listener para botões de editar disciplina
    document.addEventListener('DOMContentLoaded', function() {
        const editBtns = document.querySelectorAll('.edit-discipline-btn');
        editBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-discipline-id');
                const name = this.getAttribute('data-discipline-name');
                const teacherIdsStr = this.getAttribute('data-teacher-ids');
                const teacherIds = teacherIdsStr ? teacherIdsStr.split(',').map(id => parseInt(id)) : [];
                
                // Por enquanto, apenas exibir um alert. Implementar modal de edição depois
                console.log('Editar disciplina:', { id: id, name: name, teacherIds: teacherIds });
                alert('Função de editar disciplina será implementada em breve. ID: ' + id + ', Nome: ' + name);
                // TODO: Criar modal de edição de disciplina similar ao modal de adicionar
            });
        });
    });

    function deleteDiscipline(id) {
        if (confirm('Tem certeza que deseja remover esta disciplina?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("ensino/turmas/{$turma->id}/disciplines") }}/' + id;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    function viewLesson(lessonId) {
        // Implementar visualização/edição da aula
        window.location.href = '{{ url("ensino/turmas/{$turma->id}/lessons") }}/' + lessonId;
    }

    function deleteLesson(id) {
        if (confirm('Tem certeza que deseja remover esta aula?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("ensino/turmas/{$turma->id}/lessons") }}/' + id;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteFile(id) {
        if (confirm('Tem certeza que deseja remover este arquivo?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("ensino/turmas/{$turma->id}/files") }}/' + id;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    function openFrequencyModal(disciplineId, disciplineName) {
        // Preencher modal de frequência
        const disciplineIdInput = document.getElementById('frequency_discipline_id');
        if (disciplineIdInput) {
            disciplineIdInput.value = disciplineId;
        }
        const modalElement = document.getElementById('frequencyMonthlyModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
        
        // Atualizar título do modal se necessário
        const disciplineNameElement = document.getElementById('frequency_discipline_name');
        if (disciplineNameElement) {
            disciplineNameElement.textContent = disciplineName;
        }
    }

    // Event listener para botões de frequência mensal
    document.addEventListener('DOMContentLoaded', function() {
        const frequencyBtns = document.querySelectorAll('.frequency-btn');
        frequencyBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const disciplineId = this.getAttribute('data-discipline-id');
                const disciplineName = this.getAttribute('data-discipline-name');
                openFrequencyModal(disciplineId, disciplineName);
            });
        });
    });

    function printFrequency() {
        window.print();
    }

    function toggleFileType() {
        const type = document.querySelector('input[name="type"]:checked').value;
        document.getElementById('file_upload_section').style.display = type === 'file' ? 'block' : 'none';
        document.getElementById('text_content_section').style.display = type === 'text' ? 'block' : 'none';
        document.getElementById('external_url_section').style.display = type === 'external_link' ? 'block' : 'none';
        
        // Tornar campos obrigatórios conforme tipo
        const fileUpload = document.getElementById('file_upload');
        const fileContent = document.getElementById('file_content');
        const fileExternalUrl = document.getElementById('file_external_url');
        
        if (fileUpload) fileUpload.required = type === 'file';
        if (fileContent) fileContent.required = type === 'text';
        if (fileExternalUrl) fileExternalUrl.required = type === 'external_link';
    }

    // Inicializar evento de submit do formulário de frequência quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        const frequencyForm = document.getElementById('frequencyForm');
        if (frequencyForm) {
            frequencyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const url = this.action + '?' + new URLSearchParams(formData).toString();
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.html) {
                        const container = document.getElementById('frequency_table_container');
                        if (container) {
                            container.innerHTML = data.html;
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                });
            });
        }
    });
</script>
@endpush
@endsection

