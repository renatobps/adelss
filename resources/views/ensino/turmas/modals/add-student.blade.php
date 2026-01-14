<!-- Modal Adicionar Aluno -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">+ Membros</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('ensino.turmas.students.store', $turma) }}" method="POST" id="addStudentForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="members" class="form-label">Selecionar Membros</label>
                        <select class="form-select" id="members" name="member_ids[]" multiple size="10">
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" {{ $turma->students->contains($member->id) ? 'selected' : '' }}>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos membros</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

