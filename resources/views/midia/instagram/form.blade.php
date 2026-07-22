@extends('layouts.porto')

@section('title', 'Agendar publicação')
@section('page-title', 'Agendar publicação Instagram')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><a href="{{ route('midia.instagram.posts.index') }}">Instagram</a></li>
    <li><span>Agendar</span></li>
@endsection

@section('content')
@include('midia.partials.nav', ['active' => 'instagram'])

@unless($instagramConnected)
    <div class="alert alert-warning">Conecte o Instagram antes de agendar. <a href="{{ route('midia.settings') }}">Configurações</a></div>
@endunless

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $initialKind = '';
    if ($selectedMediaFile) {
        $initialKind = $selectedMediaFile->mediaKind();
    }
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('midia.instagram.posts.store') }}" enctype="multipart/form-data" id="scheduleForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Arquivo da Mídia (Google Drive)</label>
                    <input type="hidden" name="media_file_id" id="media_file_id" value="{{ old('media_file_id', $selectedMediaFile?->id) }}">
                    <input type="hidden" name="media_kind" id="media_kind" value="{{ old('media_kind', $initialKind) }}">

                    <div id="mediaPickerEmpty" class="midia-picker-empty {{ $selectedMediaFile ? 'd-none' : '' }}">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mediaPickerModal">
                            <i class="bx bx-folder-open"></i> Selecionar da Mídia
                        </button>
                        <div class="form-text mt-2">Navegue pelas pastas do Drive e escolha uma foto ou vídeo.</div>
                    </div>

                    <div id="mediaPickerSelected" class="midia-picker-selected {{ $selectedMediaFile ? '' : 'd-none' }}">
                        <div class="d-flex align-items-center gap-3 border rounded p-2">
                            <div class="midia-picker-preview-thumb" id="mediaSelectedThumb">
                                @if($selectedMediaFile && $selectedMediaFile->isPhoto())
                                    <img src="{{ route('midia.thumbnail', $selectedMediaFile) }}" alt="">
                                @elseif($selectedMediaFile && $selectedMediaFile->isVideo())
                                    <i class="bx bx-video"></i>
                                @else
                                    <i class="bx bx-file"></i>
                                @endif
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate" id="mediaSelectedName">
                                    {{ $selectedMediaFile?->name ?? '' }}
                                </div>
                                <div class="small text-muted text-truncate" id="mediaSelectedMeta">
                                    @if($selectedMediaFile)
                                        {{ $selectedMediaFile->original_filename }} · {{ $initialKind }}
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#mediaPickerModal">
                                Trocar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="mediaClearBtn" title="Limpar seleção">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-text">Ou envie um arquivo abaixo (foto ou vídeo).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload direto</label>
                    <input type="file" name="media" id="media_upload" class="form-control"
                           accept="image/*,video/mp4,video/quicktime">
                </div>

                <div class="col-12">
                    <label class="form-label">Destinos <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(\App\Models\ScheduledPostDestination::DESTINATIONS as $key => $label)
                            <div class="form-check">
                                <input class="form-check-input destination-check" type="checkbox"
                                       name="destinations[]" value="{{ $key }}" id="dest_{{ $key }}"
                                       @checked(collect(old('destinations', ['feed']))->contains($key))>
                                <label class="form-check-label" for="dest_{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text" id="destHelp">Reels exige vídeo. Feed+Reels juntos usam uma única publicação otimizada.</div>
                    <div class="text-danger small d-none" id="reelsError">Reels exige vídeo — remova essa opção ou envie um vídeo.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Legenda</label>
                    <textarea name="caption" class="form-control" rows="4" maxlength="2200">{{ old('caption') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Publicar em <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="scheduled_for" class="form-control" required
                           value="{{ old('scheduled_for') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('midia.instagram.posts.index') }}" class="btn btn-link">Cancelar</a>
                <button class="btn btn-primary" type="submit" id="submitBtn" @disabled(!$instagramConnected)>Agendar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal seletor visual (modo seleção única) --}}
<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-labelledby="mediaPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaPickerModalLabel">Selecionar da Mídia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <nav aria-label="breadcrumb" class="mb-0">
                        <ol class="breadcrumb mb-0" id="pickerBreadcrumb">
                            <li class="breadcrumb-item"><a href="#" data-pasta="">ADELSS</a></li>
                        </ol>
                    </nav>
                </div>

                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-5">
                        <input type="text" id="pickerSearch" class="form-control" placeholder="Buscar por nome...">
                    </div>
                    <div class="col-md-3">
                        <select id="pickerCategory" class="form-select">
                            <option value="">Todas as categorias</option>
                            <option value="foto">Fotos</option>
                            <option value="documento">Documentos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary w-100" id="pickerFilterBtn">Filtrar</button>
                    </div>
                </div>

                <div id="pickerLoading" class="text-center text-muted py-5 d-none">
                    <div class="spinner-border spinner-border-sm me-2"></div> Carregando...
                </div>
                <div id="pickerEmpty" class="text-center text-muted py-5 d-none">Nenhum arquivo nesta pasta.</div>
                <div class="row g-3" id="pickerGrid"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="pickerConfirmBtn" disabled>Selecionar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.midia-picker-empty {
    border: 1px dashed #cbd5e1;
    border-radius: .75rem;
    padding: 1.25rem;
    background: #f8fafc;
}
.midia-picker-preview-thumb {
    width: 64px;
    height: 64px;
    border-radius: .5rem;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    font-size: 1.75rem;
    color: #64748b;
}
.midia-picker-preview-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.midia-card {
    display: block;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: .75rem;
    padding: .75rem;
    height: 100%;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
}
.midia-card:hover { border-color: #93c5fd; }
.midia-card.is-selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .25);
    position: relative;
}
.midia-card.is-selected::after {
    content: '\2713';
    position: absolute;
    top: .4rem;
    right: .4rem;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: 999px;
    background: #2563eb;
    color: #fff;
    font-size: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.midia-card.is-folder { cursor: pointer; text-decoration: none; }
.midia-thumb {
    height: 120px;
    border-radius: .5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #f1f5f9;
    margin-bottom: .5rem;
    width: 100%;
}
.midia-thumb.folder { color: #f59e0b; font-size: 2.5rem; }
.midia-thumb.doc { color: #64748b; font-size: 2.5rem; }
.midia-thumb.video { color: #6366f1; font-size: 2.5rem; }
.midia-thumb.photo img { width: 100%; height: 100%; object-fit: cover; }
.midia-name {
    font-size: .85rem;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const browseUrl = @json($browseUrl);
    const form = document.getElementById('scheduleForm');
    const mediaFileId = document.getElementById('media_file_id');
    const mediaKind = document.getElementById('media_kind');
    const upload = document.getElementById('media_upload');
    const reels = document.getElementById('dest_reels');
    const reelsError = document.getElementById('reelsError');
    const emptyBox = document.getElementById('mediaPickerEmpty');
    const selectedBox = document.getElementById('mediaPickerSelected');
    const selectedName = document.getElementById('mediaSelectedName');
    const selectedMeta = document.getElementById('mediaSelectedMeta');
    const selectedThumb = document.getElementById('mediaSelectedThumb');
    const clearBtn = document.getElementById('mediaClearBtn');

    const modalEl = document.getElementById('mediaPickerModal');
    const grid = document.getElementById('pickerGrid');
    const breadcrumb = document.getElementById('pickerBreadcrumb');
    const loading = document.getElementById('pickerLoading');
    const emptyMsg = document.getElementById('pickerEmpty');
    const confirmBtn = document.getElementById('pickerConfirmBtn');
    const searchInput = document.getElementById('pickerSearch');
    const categorySelect = document.getElementById('pickerCategory');
    const filterBtn = document.getElementById('pickerFilterBtn');

    let currentPasta = '';
    let pendingSelection = null;
    let searchTimer = null;

    function selectedKind() {
        if (upload.files && upload.files[0]) {
            return upload.files[0].type.startsWith('video/') ? 'video' : 'foto';
        }
        return mediaKind.value || '';
    }

    function validateDestinations() {
        const checks = [...document.querySelectorAll('.destination-check:checked')];
        if (!checks.length) {
            alert('Selecione pelo menos um destino (Feed, Reels ou Stories).');
            return false;
        }
        const kind = selectedKind();
        if (reels.checked && kind !== 'video') {
            reelsError.classList.remove('d-none');
            return false;
        }
        reelsError.classList.add('d-none');
        return true;
    }

    function showSelectedUi(file) {
        mediaFileId.value = file.id;
        mediaKind.value = file.media_kind || '';
        selectedName.textContent = file.name || file.original_filename || '';
        selectedMeta.textContent = [file.original_filename, file.media_kind].filter(Boolean).join(' · ');

        if (file.is_photo && file.thumbnail_url) {
            selectedThumb.innerHTML = '<img src="' + file.thumbnail_url + '" alt="">';
        } else if (file.is_video) {
            selectedThumb.innerHTML = '<i class="bx bx-video"></i>';
        } else {
            selectedThumb.innerHTML = '<i class="bx bx-file"></i>';
        }

        emptyBox.classList.add('d-none');
        selectedBox.classList.remove('d-none');
    }

    function clearMediaSelection() {
        mediaFileId.value = '';
        mediaKind.value = '';
        selectedName.textContent = '';
        selectedMeta.textContent = '';
        selectedThumb.innerHTML = '<i class="bx bx-file"></i>';
        selectedBox.classList.add('d-none');
        emptyBox.classList.remove('d-none');
        validateDestinations();
    }

    function applyPendingSelection() {
        if (!pendingSelection) return;
        if (upload) upload.value = '';
        showSelectedUi(pendingSelection);
        pendingSelection = null;
        confirmBtn.disabled = true;
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        validateDestinations();
    }

    async function loadPicker(pasta) {
        currentPasta = pasta === undefined || pasta === null ? currentPasta : String(pasta ?? '');
        pendingSelection = null;
        confirmBtn.disabled = true;
        loading.classList.remove('d-none');
        emptyMsg.classList.add('d-none');
        grid.innerHTML = '';

        const params = new URLSearchParams({
            only_media: '1',
            q: searchInput.value.trim(),
            categoria: categorySelect.value,
        });
        if (currentPasta) params.set('pasta', currentPasta);

        try {
            const res = await fetch(browseUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Falha ao listar arquivos');
            const data = await res.json();
            renderBreadcrumb(data.breadcrumb || []);
            renderGrid(data.folders || [], data.files || []);
        } catch (e) {
            grid.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">' + (e.message || 'Erro') + '</div></div>';
        } finally {
            loading.classList.add('d-none');
        }
    }

    function renderBreadcrumb(crumbs) {
        let html = '<li class="breadcrumb-item"><a href="#" data-pasta="">ADELSS</a></li>';
        crumbs.forEach((c, idx) => {
            const last = idx === crumbs.length - 1;
            if (last) {
                html += '<li class="breadcrumb-item active">' + escapeHtml(c.name) + '</li>';
            } else {
                html += '<li class="breadcrumb-item"><a href="#" data-pasta="' + c.id + '">' + escapeHtml(c.name) + '</a></li>';
            }
        });
        breadcrumb.innerHTML = html;
        breadcrumb.querySelectorAll('a[data-pasta]').forEach((a) => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                loadPicker(a.getAttribute('data-pasta') || '');
            });
        });
    }

    function renderGrid(folders, files) {
        grid.innerHTML = '';

        if (!folders.length && !files.length) {
            emptyMsg.classList.remove('d-none');
            return;
        }

        folders.forEach((folder) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-3 col-xl-2';
            col.innerHTML = `
                <div class="midia-card is-folder" data-folder-id="${folder.id}">
                    <div class="midia-thumb folder"><i class="bx bx-folder"></i></div>
                    <div class="midia-name"></div>
                </div>`;
            col.querySelector('.midia-name').textContent = folder.name || '';
            col.querySelector('[data-folder-id]').addEventListener('click', () => {
                loadPicker(String(folder.id));
            });
            grid.appendChild(col);
        });

        files.forEach((file) => {
            let thumbHtml;
            if ((file.is_photo || file.is_video) && file.thumbnail_url) {
                thumbHtml = `<div class="midia-thumb photo"><img alt="" loading="lazy"></div>`;
            } else if (file.is_video) {
                thumbHtml = `<div class="midia-thumb video"><i class="bx bx-video"></i></div>`;
            } else {
                thumbHtml = `<div class="midia-thumb doc"><i class="bx bx-file"></i></div>`;
            }

            const col = document.createElement('div');
            col.className = 'col-6 col-md-3 col-xl-2';
            col.innerHTML = `
                <div class="midia-card midia-file-card">
                    ${thumbHtml}
                    <div class="midia-name"></div>
                    <div class="small text-muted"></div>
                </div>`;
            const fileCard = col.querySelector('.midia-file-card');
            const img = fileCard.querySelector('img');
            if (img && file.thumbnail_url) {
                img.src = file.thumbnail_url;
            }
            fileCard.querySelector('.midia-name').textContent = file.name || '';
            fileCard.querySelector('.midia-name').title = file.original_filename || file.name || '';
            fileCard.querySelector('.small').textContent = file.media_kind || '';
            fileCard._fileData = file;

            fileCard.addEventListener('click', () => {
                grid.querySelectorAll('.midia-file-card.is-selected').forEach((c) => c.classList.remove('is-selected'));
                fileCard.classList.add('is-selected');
                pendingSelection = file;
                confirmBtn.disabled = false;
            });
            fileCard.addEventListener('dblclick', () => {
                pendingSelection = file;
                applyPendingSelection();
            });

            grid.appendChild(col);
        });
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    modalEl?.addEventListener('show.bs.modal', () => {
        searchInput.value = '';
        categorySelect.value = '';
        currentPasta = '';
        loadPicker('');
    });

    filterBtn?.addEventListener('click', () => loadPicker(currentPasta));
    searchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadPicker(currentPasta);
        }
    });
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadPicker(currentPasta), 400);
    });
    confirmBtn?.addEventListener('click', applyPendingSelection);
    clearBtn?.addEventListener('click', clearMediaSelection);

    upload?.addEventListener('change', () => {
        if (upload.files[0]) {
            mediaFileId.value = '';
            mediaKind.value = upload.files[0].type.startsWith('video/') ? 'video' : 'foto';
            selectedBox.classList.add('d-none');
            emptyBox.classList.remove('d-none');
        }
        validateDestinations();
    });
    reels?.addEventListener('change', validateDestinations);

    form?.addEventListener('submit', (e) => {
        if (!validateDestinations()) {
            e.preventDefault();
        }
    });
})();
</script>
@endpush
