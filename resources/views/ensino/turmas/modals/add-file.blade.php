<!-- Modal Adicionar Arquivo -->
<div class="modal fade" id="addFileModal" tabindex="-1" aria-labelledby="addFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFileModalLabel">Adicionar arquivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('ensino.turmas.files.store', $turma) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_title" class="form-label">Título</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="file_title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="file_discipline_id" class="form-label">Disciplina</label>
                        <select class="form-select @error('discipline_id') is-invalid @enderror" 
                                id="file_discipline_id" name="discipline_id">
                            <option value="">Selecione</option>
                            @foreach($turma->disciplines as $discipline)
                                <option value="{{ $discipline->id }}">{{ $discipline->name }}</option>
                            @endforeach
                        </select>
                        @error('discipline_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_file" value="file" checked onchange="toggleFileType()">
                                <label class="form-check-label" for="type_file">Arquivo</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_text" value="text" onchange="toggleFileType()">
                                <label class="form-check-label" for="type_text">Texto</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_link" value="external_link" onchange="toggleFileType()">
                                <label class="form-check-label" for="type_link">Link externo</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="file_upload_section">
                        <label for="file_upload" class="form-label">Arquivo</label>
                        <div class="border rounded p-4 text-center" style="background: #f8f9fa; min-height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <i class="bx bx-cloud-upload" style="font-size: 3rem; color: #6c757d;"></i>
                            <p class="mt-2 mb-0">Arraste ou selecione os arquivos para enviar.</p>
                            <input type="file" class="form-control mt-3" id="file_upload" name="file" accept="*/*">
                        </div>
                    </div>
                    <div class="mb-3" id="text_content_section" style="display: none;">
                        <label for="file_content" class="form-label">Conteúdo do texto</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" 
                                  id="file_content" name="content" rows="6">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3" id="external_url_section" style="display: none;">
                        <label for="file_external_url" class="form-label">URL externa</label>
                        <input type="url" class="form-control @error('external_url') is-invalid @enderror" 
                               id="file_external_url" name="external_url" value="{{ old('external_url') }}" placeholder="https://...">
                        @error('external_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="file_description" class="form-label">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="file_description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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


