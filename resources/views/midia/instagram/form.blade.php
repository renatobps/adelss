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

@if($canScheduleInstagram && !$instagramConnected)
    <div class="alert alert-warning">
        Conecte o Instagram antes de agendar para Feed/Reels/Stories.
        <a href="{{ route('midia.settings') }}">Configurações</a>
        @if($canScheduleWhatsApp)
            — você ainda pode agendar só para Grupo do WhatsApp.
        @endif
    </div>
@endif

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
            <div class="row g-3 align-items-stretch">
                <div class="col-md-5">
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
                </div>

                <div class="col-md-2">
                    <div class="midia-or-sep">ou</div>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Upload direto</label>
                    <input type="file" name="media" id="media_upload" class="form-control"
                           accept="image/*,video/mp4,video/quicktime">
                    <div class="form-text">Foto ou vídeo sem passar pela Mídia/Drive.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Destinos <span class="text-danger">*</span></label>
                    @php
                        $destIcons = [
                            'feed' => 'bx-grid-alt',
                            'reels' => 'bx-movie-play',
                            'stories' => 'bx-circle',
                            'grupo' => 'bxl-whatsapp',
                        ];
                        $defaultDest = $canScheduleInstagram ? ['feed'] : ($canScheduleWhatsApp ? ['grupo'] : []);
                        $oldDest = collect(old('destinations', $defaultDest));
                        $availableDestinations = collect(\App\Models\ScheduledPostDestination::DESTINATIONS)
                            ->when(!$canScheduleInstagram, fn ($c) => $c->except(['feed', 'reels', 'stories']))
                            ->when(!$canScheduleWhatsApp, fn ($c) => $c->except(['grupo']));
                    @endphp
                    <div class="midia-dest-chips">
                        @foreach($availableDestinations as $key => $label)
                            <label class="midia-dest-chip {{ $key }} {{ $oldDest->contains($key) ? 'is-active' : '' }}" for="dest_{{ $key }}">
                                <input class="destination-check" type="checkbox"
                                       name="destinations[]" value="{{ $key }}" id="dest_{{ $key }}"
                                       @checked($oldDest->contains($key))>
                                <i class="bx {{ $destIcons[$key] ?? 'bx-check' }}"></i>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <div class="form-text" id="destHelp">Reels exige vídeo. Feed+Reels juntos usam uma única publicação otimizada. Grupo do WhatsApp aceita foto ou vídeo.</div>
                    <div class="text-danger small d-none" id="reelsError">Reels exige vídeo — remova essa opção ou envie um vídeo.</div>
                </div>

                @if($canScheduleWhatsApp)
                    <div class="col-md-6 {{ $oldDest->contains('grupo') ? '' : 'd-none' }}" id="whatsappGroupWrap">
                        <label class="form-label">Selecione o grupo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select name="whatsapp_group_jid" id="whatsapp_group_jid" class="form-select">
                                <option value="">Carregando grupos...</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="whatsappGroupsRefresh" title="Atualizar lista">
                                <i class="bx bx-refresh"></i>
                            </button>
                        </div>
                        <input type="hidden" name="whatsapp_group_name" id="whatsapp_group_name" value="{{ old('whatsapp_group_name') }}">
                        <div class="form-text">Grupos da mesma instância WhatsApp usada em Notificações. A legenda acima será enviada ao grupo.</div>
                        <div class="text-danger small d-none" id="whatsappGroupError">Selecione o grupo do WhatsApp.</div>
                    </div>
                @endif

                <div class="col-md-6" id="removeAfterWrap">
                    <label class="form-label">Remover automaticamente após (dias)</label>
                    <input type="number" name="remove_after_days" id="remove_after_days" class="form-control"
                           min="1" max="365" step="1"
                           value="{{ old('remove_after_days') }}"
                           placeholder="Deixe em branco para nunca remover automaticamente">
                    <div class="form-text">Válido para Feed e Reels. A remoção no Instagram é tentada pela rotina automática (pode falhar no fluxo Instagram Login).</div>
                </div>
                <div class="col-md-6 d-none" id="storiesOnlyHint">
                    <label class="form-label">Remoção automática</label>
                    <div class="alert alert-info mb-0 py-2 small">
                        Stories somem automaticamente do Instagram após 24h — nenhuma ação adicional necessária.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Evento relacionado</label>
                    <input type="text" name="event_name" class="form-control" maxlength="180"
                           value="{{ old('event_name') }}"
                           placeholder="Ex: Culto de Celebração de Domingo">
                    <div class="form-text">Opcional — útil para filtrar publicações por culto/evento.</div>
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
                <button class="btn btn-primary" type="submit" id="submitBtn"
                        @disabled(!$canScheduleInstagram && !$canScheduleWhatsApp)>Agendar</button>
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
@include('midia.partials.styles')
<style>
#mediaPickerModal .midia-card { padding: .75rem; cursor: pointer; }
#mediaPickerModal .midia-thumb { height: 120px; margin-bottom: .5rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const browseUrl = @json($browseUrl);
    const whatsappGroupsUrl = @json($whatsappGroupsUrl ?? null);
    const oldWhatsappGroupJid = @json(old('whatsapp_group_jid', ''));
    const form = document.getElementById('scheduleForm');
    const mediaFileId = document.getElementById('media_file_id');
    const mediaKind = document.getElementById('media_kind');
    const upload = document.getElementById('media_upload');
    const reels = document.getElementById('dest_reels');
    const reelsError = document.getElementById('reelsError');
    const grupoCheck = document.getElementById('dest_grupo');
    const whatsappGroupWrap = document.getElementById('whatsappGroupWrap');
    const whatsappGroupSelect = document.getElementById('whatsapp_group_jid');
    const whatsappGroupName = document.getElementById('whatsapp_group_name');
    const whatsappGroupError = document.getElementById('whatsappGroupError');
    const whatsappGroupsRefresh = document.getElementById('whatsappGroupsRefresh');
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

    function syncWhatsAppGroupField() {
        const show = !!(grupoCheck && grupoCheck.checked);
        if (whatsappGroupWrap) {
            whatsappGroupWrap.classList.toggle('d-none', !show);
        }
        if (!show && whatsappGroupError) {
            whatsappGroupError.classList.add('d-none');
        }
        if (show && whatsappGroupSelect && whatsappGroupSelect.options.length <= 1) {
            loadWhatsAppGroups();
        }
    }

    function syncWhatsAppGroupName() {
        if (!whatsappGroupSelect || !whatsappGroupName) return;
        const opt = whatsappGroupSelect.options[whatsappGroupSelect.selectedIndex];
        whatsappGroupName.value = opt && opt.value ? (opt.textContent || '').trim() : '';
    }

    async function loadWhatsAppGroups() {
        if (!whatsappGroupsUrl || !whatsappGroupSelect) return;
        const previous = whatsappGroupSelect.value || oldWhatsappGroupJid || '';
        whatsappGroupSelect.innerHTML = '<option value="">Carregando grupos...</option>';
        whatsappGroupSelect.disabled = true;
        try {
            const res = await fetch(whatsappGroupsUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            whatsappGroupSelect.innerHTML = '<option value="">Selecione o grupo...</option>';
            (data.groups || []).forEach((g) => {
                const opt = document.createElement('option');
                opt.value = g.jid;
                opt.textContent = g.name || g.jid;
                if (previous && previous === g.jid) opt.selected = true;
                whatsappGroupSelect.appendChild(opt);
            });
            if (!(data.success) && (data.message || data.error)) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = data.message || data.error;
                whatsappGroupSelect.appendChild(opt);
            }
            syncWhatsAppGroupName();
        } catch (e) {
            whatsappGroupSelect.innerHTML = '<option value="">Falha ao carregar grupos</option>';
        } finally {
            whatsappGroupSelect.disabled = false;
        }
    }

    function validateDestinations() {
        const checks = [...document.querySelectorAll('.destination-check:checked')];
        if (!checks.length) {
            alert('Selecione pelo menos um destino.');
            return false;
        }
        const kind = selectedKind();
        if (reels && reels.checked && kind !== 'video') {
            reelsError.classList.remove('d-none');
            return false;
        }
        if (reelsError) reelsError.classList.add('d-none');

        if (grupoCheck && grupoCheck.checked) {
            if (!whatsappGroupSelect || !whatsappGroupSelect.value) {
                if (whatsappGroupError) whatsappGroupError.classList.remove('d-none');
                return false;
            }
            syncWhatsAppGroupName();
        }
        if (whatsappGroupError) whatsappGroupError.classList.add('d-none');
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

    const removeWrap = document.getElementById('removeAfterWrap');
    const storiesHint = document.getElementById('storiesOnlyHint');
    const removeInput = document.getElementById('remove_after_days');
    const feedCheck = document.getElementById('dest_feed');
    const storiesCheck = document.getElementById('dest_stories');

    function syncRemovalField() {
        const hasFeedOrReels = !!(feedCheck?.checked || reels?.checked);
        const onlyStories = !hasFeedOrReels && !!storiesCheck?.checked;

        if (removeWrap) removeWrap.classList.toggle('d-none', !hasFeedOrReels);
        if (storiesHint) storiesHint.classList.toggle('d-none', !onlyStories);
        if (!hasFeedOrReels && removeInput) {
            removeInput.value = '';
        }
    }

    document.querySelectorAll('.midia-dest-chip input').forEach((input) => {
        const sync = () => {
            input.closest('.midia-dest-chip')?.classList.toggle('is-active', input.checked);
            validateDestinations();
            syncRemovalField();
            syncWhatsAppGroupField();
        };
        input.addEventListener('change', sync);
        sync();
    });

    whatsappGroupSelect?.addEventListener('change', syncWhatsAppGroupName);
    whatsappGroupsRefresh?.addEventListener('click', () => loadWhatsAppGroups());

    if (grupoCheck?.checked) {
        loadWhatsAppGroups();
    }

    form?.addEventListener('submit', (e) => {
        if (!validateDestinations()) {
            e.preventDefault();
        }
    });
})();
</script>
@endpush
