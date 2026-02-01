@extends('layouts.porto')

@section('title', 'Importar Músicas - Moriah')

@section('page-title', 'Importar músicas')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><a href="{{ route('moriah.ministerio') }}">Moriah</a></li>
    <li><a href="{{ route('moriah.repertorio.index') }}">Repertório</a></li>
    <li><span>Importar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2 class="mb-1" style="color: #333; font-weight: 600;">Importar músicas</h2>
            <h3 class="mb-4" style="color: #666; font-size: 1.1rem; font-weight: 500;">MORIAH MUSIC</h3>
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

        @if(session('errors') && count(session('errors')) > 0)
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Erros encontrados:</strong>
                <ul class="mb-0 mt-2">
                    @foreach(session('errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Passo a passo -->
        <div class="card mb-4" style="border: 1px solid #e0e0e0; border-radius: 12px;">
            <div class="card-body p-4">
                <h4 class="mb-4" style="color: #333; font-weight: 600;">Passo a passo</h4>

                <!-- Passo 1 -->
                <div class="mb-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; flex-shrink: 0;">
                            1
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-2" style="font-size: 1rem; color: #333;">
                                Faça o download do modelo de importação.
                            </p>
                            <p class="text-muted small mb-3">
                                Obs.: As músicas presentes no modelo são apenas para exemplo.
                            </p>
                            <a href="{{ route('moriah.repertorio.import.template') }}" class="btn btn-outline-primary">
                                <i class="bx bx-download me-2"></i>Baixar modelo de importação (.csv)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Passo 2 -->
                <div class="mb-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; flex-shrink: 0;">
                            2
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-2" style="font-size: 1rem; color: #333;">
                                Preencha as colunas com as informações das músicas.
                            </p>
                            <ul class="mb-0" style="padding-left: 20px; color: #666;">
                                <li>Apenas as colunas "nomeMusica" e "nomeArtista" são obrigatórias.</li>
                                <li>Não renomeie ou altere a ordem das colunas.</li>
                                <li>Para adicionar mais versões de uma música, basta inserir uma nova linha repetindo "nomeMusica" e "nomeArtista".</li>
                                <li>Para adicionar referências customizadas na coluna "referencias", siga o padrão: <code>[descricao1](https://link.com); [descricao2](https://link2.com)</code></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Passo 3 -->
                <div class="mb-4">
                    <div class="d-flex align-items-start">
                        <div class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; flex-shrink: 0;">
                            3
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-0" style="font-size: 1rem; color: #333;">
                                Após preencher a planilha, toque no botão "Importar Músicas" no final dessa página.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Autopreenchimento -->
        <div class="card mb-4" style="border: 1px solid #e0e0e0; border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1" style="color: #333; font-weight: 600;">Autopreenchimento</h5>
                        <p class="mb-0 text-muted small">
                            Permitir que o sistema preencha os campos em branco automaticamente.
                        </p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autofill" name="autofill" checked style="width: 3rem; height: 1.5rem;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulário de Importação -->
        <div class="card" style="border: 1px solid #e0e0e0; border-radius: 12px;">
            <div class="card-body p-4">
                <form action="{{ route('moriah.repertorio.import.process') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label" style="font-weight: 600;">Selecione o arquivo para importar</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".csv" required>
                        <small class="form-text text-muted">Formato aceito: .csv (máximo 10MB). Se você tem um arquivo Excel, salve-o como CSV primeiro.</small>
                    </div>
                    <input type="hidden" name="autofill" id="autofillValue" value="1">
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bx bx-upload me-2"></i>Importar músicas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const autofillSwitch = document.getElementById('autofill');
    const autofillValue = document.getElementById('autofillValue');

    autofillSwitch.addEventListener('change', function() {
        autofillValue.value = this.checked ? '1' : '0';
    });

    document.getElementById('importForm').addEventListener('submit', function(e) {
        const fileInput = document.getElementById('file');
        if (!fileInput.files || !fileInput.files[0]) {
            e.preventDefault();
            alert('Por favor, selecione um arquivo para importar.');
            return false;
        }

        const file = fileInput.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB

        if (file.size > maxSize) {
            e.preventDefault();
            alert('O arquivo é muito grande. O tamanho máximo permitido é 10MB.');
            return false;
        }

        // Mostrar loading
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bx bx-loader-alt bx-spin me-2"></i>Importando...';
    });
});
</script>
@endsection
