@extends('layouts.porto')

@section('title', 'Arquivos — Mídia')
@section('page-title', 'Arquivos')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><span>Arquivos</span></li>
@endsection

@section('content')
@include('midia.partials.nav', ['active' => 'arquivos'])

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@unless($driveConnected)
    <div class="alert alert-warning">
        Google Drive não conectado.
        @can('midia.configuracoes.manage')
            <a href="{{ route('midia.settings') }}">Abrir configurações</a>
        @else
            Peça a um administrador para conectar o Drive.
        @endcan
    </div>
@endunless

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <nav aria-label="breadcrumb" class="mb-0">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('midia.index') }}">ADELSS</a></li>
                    @if($currentFolder)
                        @foreach($currentFolder->breadcrumb() as $crumb)
                            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                                @if($loop->last)
                                    {{ $crumb->name }}
                                @else
                                    <a href="{{ route('midia.index', ['pasta' => $crumb->id]) }}">{{ $crumb->name }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                </ol>
            </nav>
            <div class="d-flex gap-2">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grade']) }}" class="btn btn-sm {{ $filters['view'] === 'grade' ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="bx bx-grid-alt"></i></a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'lista']) }}" class="btn btn-sm {{ $filters['view'] === 'lista' ? 'btn-primary' : 'btn-outline-secondary' }}"><i class="bx bx-list-ul"></i></a>
                @can('midia.pastas.manage')
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newFolderModal">
                        <i class="bx bx-folder-plus"></i> Nova pasta
                    </button>
                @endcan
            </div>
        </div>

        <form method="GET" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="pasta" value="{{ $filters['pasta'] }}">
            <input type="hidden" name="view" value="{{ $filters['view'] }}">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Buscar por nome...">
            </div>
            <div class="col-md-3">
                <select name="categoria" class="form-select">
                    <option value="">Todas as categorias</option>
                    <option value="foto" @selected($filters['categoria'] === 'foto')>Fotos</option>
                    <option value="documento" @selected($filters['categoria'] === 'documento')>Documentos</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100" type="submit">Filtrar</button>
            </div>
        </form>

        @can('midia.arquivos.upload')
            <form method="POST" action="{{ route('midia.upload') }}" enctype="multipart/form-data" id="uploadForm"
                  class="midia-dropzone border border-2 border-dashed rounded p-4 text-center mb-3 {{ !$driveConnected ? 'opacity-50 pe-none' : '' }}">
                @csrf
                <input type="hidden" name="media_folder_id" value="{{ $filters['pasta'] }}">
                <i class="bx bx-cloud-upload fs-1 text-primary"></i>
                <div class="fw-semibold">Arraste arquivos ou clique para enviar</div>
                <div class="small text-muted mb-2">Fotos e documentos • máx. 20MB cada • salvos no Google Drive</div>
                <input type="file" name="files[]" id="filesInput" class="d-none" multiple>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('filesInput').click()">Selecionar arquivos</button>
            </form>
        @endcan

        @if($filters['view'] === 'lista')
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th>Origem</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($folders as $folder)
                            <tr>
                                <td>
                                    <a href="{{ route('midia.index', ['pasta' => $folder->id]) }}" class="text-decoration-none">
                                        <i class="bx bx-folder text-warning"></i> {{ $folder->name }}
                                    </a>
                                </td>
                                <td>Pasta</td>
                                <td>—</td>
                                <td>—</td>
                                <td></td>
                            </tr>
                        @endforeach
                        @foreach($files as $file)
                            <tr>
                                <td>{{ $file->name }}</td>
                                <td>{{ $file->category }}</td>
                                <td>{{ $file->formattedSize() }}</td>
                                <td>
                                    @if($file->module_reference)
                                        <span class="badge bg-info-subtle text-info-emphasis">{{ $file->module_reference }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    @include('midia.partials.file-actions', ['file' => $file])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="row g-3">
                @foreach($folders as $folder)
                    <div class="col-6 col-md-3 col-xl-2">
                        <a href="{{ route('midia.index', ['pasta' => $folder->id]) }}" class="midia-card text-decoration-none">
                            <div class="midia-thumb folder"><i class="bx bx-folder"></i></div>
                            <div class="midia-name">{{ $folder->name }}</div>
                        </a>
                    </div>
                @endforeach
                @foreach($files as $file)
                    <div class="col-6 col-md-3 col-xl-2">
                        <div class="midia-card">
                            @if($file->isPhoto())
                                <button type="button" class="midia-thumb photo border-0 p-0"
                                        data-bs-toggle="modal" data-bs-target="#previewModal"
                                        data-preview="{{ route('midia.preview', $file) }}"
                                        data-name="{{ $file->original_filename }}">
                                    <img src="{{ route('midia.preview', $file) }}" alt="{{ $file->name }}" loading="lazy">
                                </button>
                            @else
                                <div class="midia-thumb doc"><i class="bx bx-file"></i></div>
                            @endif
                            <div class="midia-name" title="{{ $file->original_filename }}">{{ $file->name }}</div>
                            @if($file->module_reference)
                                <span class="badge bg-info-subtle text-info-emphasis">{{ $file->module_reference }}</span>
                            @endif
                            <div class="mt-1">@include('midia.partials.file-actions', ['file' => $file])</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($files->hasPages())
            <div class="mt-3">{{ $files->links() }}</div>
        @endif
    </div>
</div>

@can('midia.pastas.manage')
<div class="modal fade" id="newFolderModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('midia.folders.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="parent_folder_id" value="{{ $filters['pasta'] }}">
            <div class="modal-header">
                <h5 class="modal-title">Nova pasta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-control" required maxlength="120">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" type="submit">Criar</button>
            </div>
        </form>
    </div>
</div>
@endcan

<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalTitle">Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="previewModalImg" class="img-fluid rounded" alt="Preview">
            </div>
        </div>
    </div>
</div>

@can('midia.arquivos.upload')
<div class="modal fade" id="moveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="moveForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Mover arquivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Pasta destino</label>
                <select name="media_folder_id" class="form-select">
                    <option value="">Raiz (ADELSS)</option>
                    @foreach($allFolders as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" type="submit">Mover</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('styles')
<style>
.midia-dropzone { background:#f8fafc; cursor:pointer; transition:.15s; }
.midia-dropzone.is-dragover { background:#eff6ff; border-color:#3b82f6 !important; }
.midia-card { display:block; background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; padding:.75rem; height:100%; }
.midia-thumb { height:120px; border-radius:.5rem; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f1f5f9; margin-bottom:.5rem; width:100%; }
.midia-thumb.folder { color:#f59e0b; font-size:2.5rem; }
.midia-thumb.doc { color:#64748b; font-size:2.5rem; }
.midia-thumb.photo img { width:100%; height:100%; object-fit:cover; }
.midia-name { font-size:.85rem; font-weight:600; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const drop = document.getElementById('uploadForm');
    const input = document.getElementById('filesInput');
    if (drop && input) {
        drop.addEventListener('click', (e) => {
            if (e.target.closest('button') || e.target === input) return;
            input.click();
        });
        ['dragenter','dragover'].forEach(ev => drop.addEventListener(ev, (e) => {
            e.preventDefault(); drop.classList.add('is-dragover');
        }));
        ['dragleave','drop'].forEach(ev => drop.addEventListener(ev, (e) => {
            e.preventDefault(); drop.classList.remove('is-dragover');
        }));
        drop.addEventListener('drop', (e) => {
            input.files = e.dataTransfer.files;
            drop.submit();
        });
        input.addEventListener('change', () => { if (input.files.length) drop.submit(); });
    }

    document.getElementById('previewModal')?.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        document.getElementById('previewModalImg').src = btn.getAttribute('data-preview');
        document.getElementById('previewModalTitle').textContent = btn.getAttribute('data-name') || 'Preview';
    });

    document.querySelectorAll('[data-move-url]').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = document.getElementById('moveForm');
            form.action = btn.getAttribute('data-move-url');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('moveModal')).show();
        });
    });
})();
</script>
@endpush
