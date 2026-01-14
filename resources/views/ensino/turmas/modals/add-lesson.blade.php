<!-- Modal Registrar Aula -->
<div class="modal fade" id="addLessonModal" tabindex="-1" aria-labelledby="addLessonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLessonModalLabel">Registrar aula</h5>
                <button type="button" class="btn btn-primary btn-sm me-2">
                    <i class="bx bx-printer"></i> Imprimir
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('ensino.turmas.lessons.store', $turma) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="lesson_date" class="form-label">Data</label>
                            <input type="date" class="form-control @error('lesson_date') is-invalid @enderror" 
                                   id="lesson_date" name="lesson_date" value="{{ old('lesson_date', now()->format('Y-m-d')) }}" required>
                            @error('lesson_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="discipline_id" class="form-label">Disciplina</label>
                            <select class="form-select @error('discipline_id') is-invalid @enderror" 
                                    id="discipline_id" name="discipline_id">
                                <option value="">Selecione</option>
                                @foreach($turma->disciplines as $discipline)
                                    <option value="{{ $discipline->id }}">{{ $discipline->name }}</option>
                                @endforeach
                            </select>
                            @error('discipline_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Assunto</label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                               id="subject" name="subject" value="{{ old('subject') }}">
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Participantes</label>
                        <p class="small text-muted mb-2">Nome completo</p>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px;">
                            @foreach($turma->students as $student)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="member_ids[]" value="{{ $student->id }}" id="student_{{ $student->id }}" checked>
                                    <label class="form-check-label" for="student_{{ $student->id }}">
                                        {{ $student->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>


