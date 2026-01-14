@extends('layouts.porto')

@section('title', 'Novo Estudo')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.estudos.index') }}">Estudos</a></li>
    <li><span>Novo Estudo</span></li>
@endsection

@section('content')
<form action="{{ route('ensino.estudos.store') }}" method="POST" enctype="multipart/form-data" id="studyForm">
    @csrf
    <div class="row">
        <!-- Conteúdo Principal -->
        <div class="col-lg-8">
            <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Novo Estudo</h5>
                    <div>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bx bx-save me-1"></i>Salvar
                        </button>
                        <a href="{{ route('ensino.estudos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="bx bx-x me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Título do estudo -->
                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold">Título do estudo</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Editor de texto rico -->
                    <div class="mb-4">
                        <label for="content" class="form-label fw-bold">Conteúdo</label>
                        <div id="editor-container" style="min-height: 400px;"></div>
                        <textarea id="content" name="content" class="form-control @error('content') is-invalid @enderror" 
                                  style="display: none;">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Direita -->
        <div class="col-lg-4">
            <!-- Imagem em destaque -->
            <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div class="card-header">
                    <h6 class="mb-0">Imagem em destaque</h6>
                </div>
                <div class="card-body">
                    <div id="featuredImagePreview" class="mb-3 text-center" style="display: none;">
                        <img id="featuredImagePreviewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                        <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeFeaturedImage()">
                            <i class="bx bx-trash me-1"></i>Remover
                        </button>
                    </div>
                    <div id="featuredImagePlaceholder" class="text-center p-4 border rounded" style="background-color: #f8f9fa; min-height: 150px; display: flex; align-items: center; justify-content: center;">
                        <div>
                            <i class="bx bx-image" style="font-size: 48px; color: #6c757d; opacity: 0.3;"></i>
                            <p class="text-muted small mt-2 mb-0">Nenhuma imagem selecionada</p>
                        </div>
                    </div>
                    <input type="file" id="featured_image" name="featured_image" accept="image/*" 
                           class="form-control mt-3" onchange="previewFeaturedImage(this)">
                    @error('featured_image')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Anexar arquivo -->
            <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div class="card-header">
                    <h6 class="mb-0">Anexar arquivo</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">(Max: 10mb)</p>
                    <div id="attachmentPreview" class="mb-3" style="display: none;">
                        <div class="alert alert-info d-flex justify-content-between align-items-center">
                            <span id="attachmentName"></span>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeAttachment()">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </div>
                    <input type="file" id="attachment" name="attachment" 
                           class="form-control" onchange="previewAttachment(this)">
                    @error('attachment')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Notificações -->
            <div class="card mb-3" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div class="card-header">
                    <h6 class="mb-0">Notificações</h6>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="send_notification" 
                               name="send_notification" value="1" {{ old('send_notification') ? 'checked' : '' }}>
                        <label class="form-check-label" for="send_notification">
                            Enviar notificação push
                        </label>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<style>
    .ql-toolbar {
        border-top: 1px solid #ccc;
        border-left: 1px solid #ccc;
        border-right: 1px solid #ccc;
        border-radius: 4px 4px 0 0;
        background: #fafafa;
    }
    .ql-container {
        border-bottom: 1px solid #ccc;
        border-left: 1px solid #ccc;
        border-right: 1px solid #ccc;
        border-radius: 0 0 4px 4px;
        font-family: inherit;
        font-size: 14px;
    }
    .ql-editor {
        min-height: 400px;
    }
    .ql-editor.ql-blank::before {
        font-style: normal;
        color: #6c757d;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar se o elemento existe
        const editorContainer = document.getElementById('editor-container');
        if (!editorContainer) {
            console.error('Editor container not found');
            return;
        }

        // Inicializar editor Quill
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Insert text here...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'font': [] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'align': [] }],
                    ['blockquote', 'code-block'],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            }
        });

        // Definir valor inicial se houver
        @if(old('content'))
            quill.root.innerHTML = {!! json_encode(old('content')) !!};
        @endif

        // Atualizar textarea quando o editor mudar
        quill.on('text-change', function() {
            const textarea = document.querySelector('textarea[name="content"]');
            if (textarea) {
                textarea.value = quill.root.innerHTML;
            }
        });

        // Atualizar textarea antes do submit
        document.getElementById('studyForm').addEventListener('submit', function(e) {
            const textarea = document.querySelector('textarea[name="content"]');
            if (textarea && quill) {
                textarea.value = quill.root.innerHTML;
            }
        });

    // Preview da imagem em destaque
    function previewFeaturedImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('featuredImagePreviewImg').src = e.target.result;
                document.getElementById('featuredImagePreview').style.display = 'block';
                document.getElementById('featuredImagePlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeFeaturedImage() {
        document.getElementById('featured_image').value = '';
        document.getElementById('featuredImagePreview').style.display = 'none';
        document.getElementById('featuredImagePlaceholder').style.display = 'flex';
    }

    // Preview do anexo
    function previewAttachment(input) {
        if (input.files && input.files[0]) {
            document.getElementById('attachmentName').textContent = input.files[0].name;
            document.getElementById('attachmentPreview').style.display = 'block';
        }
    }

    function removeAttachment() {
        document.getElementById('attachment').value = '';
        document.getElementById('attachmentPreview').style.display = 'none';
    }

    });
</script>
@endpush
@endsection

