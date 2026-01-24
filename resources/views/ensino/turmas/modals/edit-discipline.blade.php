<!-- Modal Editar Disciplina -->
<div class="modal fade" id="editDisciplineModal" tabindex="-1" aria-labelledby="editDisciplineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDisciplineModalLabel">Editar disciplina</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDisciplineForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_discipline_name" class="form-label">Nome da disciplina</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="edit_discipline_name" name="name" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_teachers" class="form-label">Professor(es)</label>
                        <select class="form-select" id="edit_teachers" name="teacher_ids[]" multiple>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos professores</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>
