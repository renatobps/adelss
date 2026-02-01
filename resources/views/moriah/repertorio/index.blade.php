@extends('layouts.porto')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Repertório - Moriah')

@section('page-title', 'Repertório')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><a href="{{ route('moriah.ministerio') }}">Moriah</a></li>
    <li><span>Repertório</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2 class="mb-1" style="color: #333; font-weight: 600;">Repertório</h2>
            <h3 class="mb-4" style="color: #666; font-size: 1.1rem; font-weight: 500;">MORIAH MUSIC</h3>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="repertorioTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="musicas-tab" data-bs-toggle="tab" data-bs-target="#musicas" type="button" role="tab" aria-controls="musicas" aria-selected="true" style="border-radius: 8px 8px 0 0; background-color: #E8D5FF; color: #333; border: none; padding: 12px 24px;">
                    Músicas ({{ $songsCount }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pastas-tab" data-bs-toggle="tab" data-bs-target="#pastas" type="button" role="tab" aria-controls="pastas" aria-selected="false" style="border-radius: 8px 8px 0 0; background-color: transparent; color: #333; border: none; padding: 12px 24px;">
                    Pastas ({{ $foldersCount }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="artistas-tab" data-bs-toggle="tab" data-bs-target="#artistas" type="button" role="tab" aria-controls="artistas" aria-selected="false" style="border-radius: 8px 8px 0 0; background-color: transparent; color: #333; border: none; padding: 12px 24px;">
                    Artistas ({{ $artistsCount }})
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="repertorioTabsContent">
            <!-- Tab Músicas -->
            <div class="tab-pane fade show active" id="musicas" role="tabpanel" aria-labelledby="musicas-tab">
                <div class="mb-3 d-flex gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#songModal" onclick="resetSongForm()">
                        <i class="bx bx-plus me-1"></i>Adicionar Música
                    </button>
                    <a href="{{ route('moriah.repertorio.import') }}" class="btn btn-success">
                        <i class="bx bx-import me-1"></i>Importar Músicas
                    </a>
                </div>

                <div class="list-group">
                    @foreach($songs as $song)
                        <div class="list-group-item d-flex align-items-center p-3" style="border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 8px; cursor: pointer;" onclick="viewSong({{ $song->id }})">
                            <!-- Thumbnail -->
                            <div class="me-3" style="flex-shrink: 0;">
                                @if($song->thumbnail_url)
                                    <img src="{{ Storage::url($song->thumbnail_url) }}" alt="{{ $song->title }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px;">
                                        <i class="bx bx-music text-white" style="font-size: 1.5rem;"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Informações da música -->
                            <div class="flex-grow-1">
                                <h6 class="mb-1" style="color: #1e3a8a; font-weight: 600; font-size: 1rem;">{{ $song->version_name ?? $song->title }}</h6>
                                <p class="mb-1" style="color: #333; font-size: 0.9rem; font-weight: 500;">{{ $song->artist ?? 'Artista não informado' }}</p>
                                <p class="mb-0" style="color: #666; font-size: 0.85rem;">
                                    {{ $song->genre }}@if($song->key), Tom: {{ $song->key }}@endif
                                </p>
                            </div>

                            <!-- Ícones de conteúdo -->
                            <div class="me-3" style="flex-shrink: 0;">
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; width: 48px;">
                                    <i class="bx bx-text d-flex align-items-center justify-content-center" style="font-size: 1.2rem; color: {{ $song->has_lyrics ? '#ff9800' : '#ccc' }}; width: 22px; height: 22px;" title="Letra"></i>
                                    <i class="bx bx-equalizer d-flex align-items-center justify-content-center" style="font-size: 1.2rem; color: {{ $song->has_chords ? '#4caf50' : '#ccc' }}; width: 22px; height: 22px;" title="Cifra"></i>
                                    <i class="bx bx-music d-flex align-items-center justify-content-center" style="font-size: 1.2rem; color: {{ $song->has_audio ? '#2196f3' : '#ccc' }}; width: 22px; height: 22px;" title="Áudio"></i>
                                    <i class="bx bx-video d-flex align-items-center justify-content-center" style="font-size: 1.2rem; color: {{ $song->has_video ? '#f44336' : '#ccc' }}; width: 22px; height: 22px;" title="Vídeo"></i>
                                </div>
                            </div>

                            <!-- Menu de ações -->
                            <div class="dropdown" style="flex-shrink: 0;" onclick="event.stopPropagation();">
                                <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical" style="font-size: 1.5rem; color: #666;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="editSong({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist ?? '') }}', '{{ $song->genre }}', '{{ $song->key ?? '' }}', {{ $song->folder_id ?? 'null' }}, {{ $song->has_lyrics ? 'true' : 'false' }}, {{ $song->has_chords ? 'true' : 'false' }}, {{ $song->has_audio ? 'true' : 'false' }}, {{ $song->has_video ? 'true' : 'false' }}, '{{ addslashes($song->version_name ?? '') }}', '{{ addslashes($song->observations ?? '') }}', {{ $song->bpm ?? 'null' }}, {{ $song->duration_hours ?? 0 }}, {{ $song->duration_minutes ?? 0 }}, {{ $song->duration_seconds ?? 0 }}, '{{ addslashes($song->link_letra ?? '') }}', '{{ addslashes($song->link_cifra ?? '') }}', '{{ addslashes($song->link_audio ?? '') }}', '{{ addslashes($song->link_video ?? '') }}'); return false;">
                                            <i class="bx bx-edit me-2"></i>Editar
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" onclick="deleteSong({{ $song->id }}, '{{ addslashes($song->title) }}'); return false;">
                                            <i class="bx bx-trash me-2"></i>Excluir
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($songs->count() === 0)
                    <div class="text-center py-5">
                        <i class="bx bx-music" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Nenhuma música cadastrada.</p>
                    </div>
                @endif
            </div>

            <!-- Tab Pastas -->
            <div class="tab-pane fade" id="pastas" role="tabpanel" aria-labelledby="pastas-tab">
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#folderModal" onclick="resetFolderForm()">
                        <i class="bx bx-plus me-1"></i>Adicionar Pasta
                    </button>
                </div>

                <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div class="card-body p-0">
                        @if($folders->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($folders as $folder)
                                    <div class="list-group-item d-flex justify-content-between align-items-center" style="border: none; border-bottom: 1px solid #eee; padding: 16px 20px;">
                                        <div>
                                            <i class="bx bx-folder me-2" style="font-size: 1.5rem; color: #666;"></i>
                                            <span style="font-size: 1rem; font-weight: 500;">{{ $folder->name }}</span>
                                            @if($folder->description)
                                                <p class="mb-0 mt-1" style="font-size: 0.85rem; color: #666;">{{ $folder->description }}</p>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editFolder({{ $folder->id }}, '{{ addslashes($folder->name) }}', '{{ addslashes($folder->description ?? '') }}');">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <form action="{{ route('moriah.repertorio.folders.destroy', $folder) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta pasta?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bx bx-folder" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p class="text-muted">Nenhuma pasta cadastrada.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tab Artistas -->
            <div class="tab-pane fade" id="artistas" role="tabpanel" aria-labelledby="artistas-tab">
                <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div class="card-body">
                        @if($artists->count() > 0)
                            <div class="row">
                                @foreach($artists as $artist)
                                    <div class="col-md-4 col-lg-3 mb-3">
                                        <div class="card" style="border: 1px solid #eee; border-radius: 8px; padding: 16px; text-align: center;">
                                            <i class="bx bx-user" style="font-size: 2rem; color: #666; margin-bottom: 8px;"></i>
                                            <p class="mb-0" style="font-weight: 500;">{{ $artist }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bx bx-user" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p class="text-muted">Nenhum artista cadastrado.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Música -->
<div class="modal fade" id="songModal" tabindex="-1" aria-labelledby="songModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="songModalLabel">Nova versão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="songForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; flex-shrink: 0;">
                            <i class="bx bx-music text-white" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <!-- Título da música -->
                            <div class="mb-3">
                                <label for="version_name" class="form-label">Título da música</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control rounded-pill" id="version_name" name="version_name" maxlength="50" placeholder="Digite o título da música">
                                    <i class="bx bx-text position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                                </div>
                                <div class="text-end mt-1">
                                    <small class="text-muted"><span id="version_name_count">0</span>/50</small>
                                </div>
                            </div>

                            <!-- Nome do artista -->
                            <div class="mb-3">
                                <label for="artist" class="form-label">Nome do artista</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control rounded-pill" id="artist" name="artist" placeholder="Digite o nome do artista">
                                    <i class="bx bx-user position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                                </div>
                            </div>

                            <!-- Classificações -->
                            <div class="mb-3">
                                <label for="genre" class="form-label">Classificações <span class="text-danger">*</span></label>
                                <select class="form-control rounded-pill" id="genre" name="genre" required>
                                    <option value="Louvor" selected>Louvor</option>
                                    <option value="Adoração">Adoração</option>
                                    <option value="Oração">Oração</option>
                                    <option value="Comunhão">Comunhão</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>

                            <!-- Observações -->
                            <div class="mb-3">
                                <label for="observations" class="form-label">Observações</label>
                                <textarea class="form-control rounded" id="observations" name="observations" rows="3" maxlength="150" placeholder="Digite observações sobre a versão"></textarea>
                                <div class="text-end mt-1">
                                    <small class="text-muted"><span id="observations_count">0</span>/150</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tom e BPM -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="key" class="form-label">Tom</label>
                            <select class="form-control rounded-pill" id="key" name="key">
                                <option value="">Selecione o tom</option>
                                <optgroup label="Maiores">
                                    <option value="C">C (Dó)</option>
                                    <option value="C#">C# (Dó sustenido)</option>
                                    <option value="Db">Db (Ré bemol)</option>
                                    <option value="D">D (Ré)</option>
                                    <option value="D#">D# (Ré sustenido)</option>
                                    <option value="Eb">Eb (Mi bemol)</option>
                                    <option value="E">E (Mi)</option>
                                    <option value="F">F (Fá)</option>
                                    <option value="F#">F# (Fá sustenido)</option>
                                    <option value="Gb">Gb (Sol bemol)</option>
                                    <option value="G">G (Sol)</option>
                                    <option value="G#">G# (Sol sustenido)</option>
                                    <option value="Ab">Ab (Lá bemol)</option>
                                    <option value="A">A (Lá)</option>
                                    <option value="A#">A# (Lá sustenido)</option>
                                    <option value="Bb">Bb (Si bemol)</option>
                                    <option value="B">B (Si)</option>
                                </optgroup>
                                <optgroup label="Menores">
                                    <option value="Cm">Cm (Dó menor)</option>
                                    <option value="C#m">C#m (Dó sustenido menor)</option>
                                    <option value="Dbm">Dbm (Ré bemol menor)</option>
                                    <option value="Dm">Dm (Ré menor)</option>
                                    <option value="D#m">D#m (Ré sustenido menor)</option>
                                    <option value="Ebm">Ebm (Mi bemol menor)</option>
                                    <option value="Em">Em (Mi menor)</option>
                                    <option value="Fm">Fm (Fá menor)</option>
                                    <option value="F#m">F#m (Fá sustenido menor)</option>
                                    <option value="Gbm">Gbm (Sol bemol menor)</option>
                                    <option value="Gm">Gm (Sol menor)</option>
                                    <option value="G#m">G#m (Sol sustenido menor)</option>
                                    <option value="Abm">Abm (Lá bemol menor)</option>
                                    <option value="Am">Am (Lá menor)</option>
                                    <option value="A#m">A#m (Lá sustenido menor)</option>
                                    <option value="Bbm">Bbm (Si bemol menor)</option>
                                    <option value="Bm">Bm (Si menor)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bpm" class="form-label">BPM</label>
                            <select class="form-control rounded-pill" id="bpm" name="bpm">
                                <option value="">Selecione o BPM</option>
                                @for($i = 40; $i <= 240; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <!-- Duração -->
                    <div class="mb-3">
                        <label class="form-label">Duração (-)</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" class="form-control rounded-pill" id="duration_hours" name="duration_hours" placeholder="Horas" min="0" max="23" style="max-width: 100px;">
                            <span>:</span>
                            <input type="number" class="form-control rounded-pill" id="duration_minutes" name="duration_minutes" placeholder="Minutos" min="0" max="59" style="max-width: 100px;">
                            <span>:</span>
                            <input type="number" class="form-control rounded-pill" id="duration_seconds" name="duration_seconds" placeholder="Segundos" min="0" max="59" style="max-width: 100px;">
                        </div>
                    </div>

                    <!-- Link do YouTube e Preencher automaticamente -->
                    <div class="mb-3">
                        <label for="youtube_url" class="form-label">Link do YouTube</label>
                        <div class="input-group">
                            <input type="url" class="form-control rounded-pill" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
                            <button type="button" class="btn btn-outline-primary rounded-pill" id="btnPreencherYoutube">
                                <i class="bx bx-bolt me-1"></i>Preencher automaticamente
                            </button>
                        </div>
                    </div>

                    <!-- Referências -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Referências</label>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-bolt text-warning"></i>
                                <span class="small">Autopreenchimento</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="autofill_references" checked>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <label for="link_letra" class="form-label small">Link letra</label>
                            <div class="input-group">
                                <input type="url" class="form-control rounded-pill" id="link_letra" name="link_letra" placeholder="https://...">
                                <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="window.open(document.getElementById('link_letra').value, '_blank')" title="Abrir link">
                                    <i class="bx bx-link-external"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="link_cifra" class="form-label small">Link cifra</label>
                            <div class="input-group">
                                <input type="url" class="form-control rounded-pill" id="link_cifra" name="link_cifra" placeholder="https://...">
                                <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="window.open(document.getElementById('link_cifra').value, '_blank')" title="Abrir link">
                                    <i class="bx bx-link-external"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="cifra_pdf" class="form-label small">PDF da cifra</label>
                            <input type="file" class="form-control rounded-pill" id="cifra_pdf" name="cifra_pdf" accept=".pdf">
                            <small class="text-muted">Tamanho máximo: 10MB</small>
                        </div>

                        <div class="mb-2">
                            <label for="link_audio" class="form-label small">Link áudio</label>
                            <div class="input-group">
                                <input type="url" class="form-control rounded-pill" id="link_audio" name="link_audio" placeholder="https://...">
                                <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="window.open(document.getElementById('link_audio').value, '_blank')" title="Abrir link">
                                    <i class="bx bx-link-external"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="link_video" class="form-label small">Link vídeo</label>
                            <div class="input-group">
                                <input type="url" class="form-control rounded-pill" id="link_video" name="link_video" placeholder="https://...">
                                <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="window.open(document.getElementById('link_video').value, '_blank')" title="Abrir link">
                                    <i class="bx bx-link-external"></i>
                                </button>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-primary rounded-pill w-100" id="addReferenceBtn">
                            <i class="bx bx-plus me-1"></i>Adicionar referência
                        </button>
                    </div>

                    <!-- Campo oculto para compatibilidade -->
                    <input type="hidden" id="title" name="title" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pasta -->
<div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="folderModalLabel">Nova Pasta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="folderForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="folder_name" class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="folder_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="folder_description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="folder_description" name="description" rows="3"></textarea>
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

<style>
.nav-tabs .nav-link {
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    background-color: #f0f0f0 !important;
}

.nav-tabs .nav-link.active {
    background-color: #E8D5FF !important;
    color: #333 !important;
    font-weight: 600;
}

/* Estilos do formulário de música */
#songModal .form-control.rounded-pill {
    border-radius: 50px !important;
    padding-left: 15px;
    padding-right: 40px;
}

#songModal .form-control.rounded {
    border-radius: 12px !important;
}

#songModal .input-group .btn {
    border-radius: 50px !important;
    margin-left: 5px;
}

#songModal .modal-body {
    padding: 1.5rem;
}

#songModal label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #333;
}

#songModal .form-label.small {
    font-size: 0.875rem;
    font-weight: 400;
}

#songModal .bg-primary {
    background-color: #007bff !important;
}
</style>

<!-- Modal Visualizar Música -->
<div class="modal fade" id="songViewModal" tabindex="-1" aria-labelledby="songViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header" style="border-bottom: none; padding: 2rem 2rem 1rem;">
                <h5 class="modal-title" id="songViewModalLabel" style="font-weight: 600; color: #333;">Música</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 0 2rem 2rem;">
                <div id="songViewContent">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentSongId = null;
let currentFolderId = null;
let referenceCount = 0;

// Contadores de caracteres
document.addEventListener('DOMContentLoaded', function() {
    const versionNameInput = document.getElementById('version_name');
    const observationsInput = document.getElementById('observations');
    
    if (versionNameInput) {
        versionNameInput.addEventListener('input', function() {
            document.getElementById('version_name_count').textContent = this.value.length;
        });
    }
    
    if (observationsInput) {
        observationsInput.addEventListener('input', function() {
            document.getElementById('observations_count').textContent = this.value.length;
        });
    }
    
    // Botão adicionar referência
    const addReferenceBtn = document.getElementById('addReferenceBtn');
    if (addReferenceBtn) {
        addReferenceBtn.addEventListener('click', function() {
            // Por enquanto, apenas mostra um alerta
            // Pode ser expandido para adicionar campos dinamicamente
            alert('Funcionalidade de adicionar referências customizadas será implementada em breve.');
        });
    }
    
    // Preencher título automaticamente se version_name for preenchido e title estiver vazio
    if (versionNameInput) {
        versionNameInput.addEventListener('blur', function() {
            const titleInput = document.getElementById('title');
            if (this.value && !titleInput.value) {
                titleInput.value = this.value;
            }
        });
    }
    
    // Botão preencher automaticamente do YouTube
    const btnPreencherYoutube = document.getElementById('btnPreencherYoutube');
    if (btnPreencherYoutube) {
        btnPreencherYoutube.addEventListener('click', function() {
            const youtubeUrl = document.getElementById('youtube_url').value;
            
            if (!youtubeUrl) {
                alert('Informe o link do YouTube');
                return;
            }
            
            // Desabilitar botão e mostrar loading
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Preenchendo...';
            
            fetch("{{ route('moriah.repertorio.preencher.youtube') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    youtube_url: youtubeUrl
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    this.disabled = false;
                    this.innerHTML = originalText;
                    return;
                }
                
                // Preencher campos
                if (data.version_name) {
                    document.getElementById('version_name').value = data.version_name;
                    document.getElementById('version_name_count').textContent = data.version_name.length;
                }
                if (data.artist) {
                    document.getElementById('artist').value = data.artist;
                }
                if (data.link_letra) {
                    document.getElementById('link_letra').value = data.link_letra;
                }
                if (data.link_cifra) {
                    document.getElementById('link_cifra').value = data.link_cifra;
                }
                if (data.link_video) {
                    document.getElementById('link_video').value = data.link_video;
                }
                if (data.duration_hours !== undefined) {
                    document.getElementById('duration_hours').value = data.duration_hours;
                }
                if (data.duration_minutes !== undefined) {
                    document.getElementById('duration_minutes').value = data.duration_minutes;
                }
                if (data.duration_seconds !== undefined) {
                    document.getElementById('duration_seconds').value = data.duration_seconds;
                }
                
                // Preencher título oculto
                document.getElementById('title').value = data.version_name || '';
                
                this.disabled = false;
                this.innerHTML = originalText;
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao preencher automaticamente. Verifique se o link do YouTube está correto.');
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    }
});

// Funções para Música
function resetSongForm() {
    currentSongId = null;
    document.getElementById('songModalLabel').textContent = 'Nova versão';
    document.getElementById('songForm').reset();
    document.getElementById('songForm').action = '{{ route('moriah.repertorio.songs.store') }}';
    document.getElementById('songForm').method = 'POST';
    document.getElementById('version_name_count').textContent = '0';
    document.getElementById('observations_count').textContent = '0';
    document.getElementById('genre').value = 'Louvor';
}

function editSong(id, title, artist, genre, key, folderId, hasLyrics, hasChords, hasAudio, hasVideo, versionName, observations, bpm, durationHours, durationMinutes, durationSeconds, linkLetra, linkCifra, linkAudio, linkVideo) {
    currentSongId = id;
    document.getElementById('songModalLabel').textContent = 'Editar versão';
    document.getElementById('title').value = title;
    document.getElementById('artist').value = artist || '';
    document.getElementById('version_name').value = versionName || '';
    document.getElementById('observations').value = observations || '';
    document.getElementById('genre').value = genre || 'Louvor';
    document.getElementById('key').value = key || '';
    document.getElementById('bpm').value = bpm || '';
    document.getElementById('duration_hours').value = durationHours || 0;
    document.getElementById('duration_minutes').value = durationMinutes || 0;
    document.getElementById('duration_seconds').value = durationSeconds || 0;
    document.getElementById('link_letra').value = linkLetra || '';
    document.getElementById('link_cifra').value = linkCifra || '';
    document.getElementById('link_audio').value = linkAudio || '';
    document.getElementById('link_video').value = linkVideo || '';
    
    // Atualizar contadores
    document.getElementById('version_name_count').textContent = (versionName || '').length;
    document.getElementById('observations_count').textContent = (observations || '').length;
    
    document.getElementById('songForm').action = `{{ route('moriah.repertorio.songs.update', ':id') }}`.replace(':id', id);
    document.getElementById('songForm').method = 'POST';
    
    if (!document.getElementById('_method_song')) {
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.id = '_method_song';
        methodInput.value = 'PUT';
        document.getElementById('songForm').appendChild(methodInput);
    }
    
    const modal = new bootstrap.Modal(document.getElementById('songModal'));
    modal.show();
}

function viewSong(id) {
    fetch(`{{ route('moriah.repertorio.songs.show', ':id') }}`.replace(':id', id), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.song) {
            const song = data.song;
            const content = document.getElementById('songViewContent');
            
            // Formatar duração
            let duration = '';
            if (song.duration_hours > 0) {
                duration = song.duration_hours + ':';
            }
            duration += String(song.duration_minutes || 0).padStart(2, '0') + ':';
            duration += String(song.duration_seconds || 0).padStart(2, '0');
            
            // HTML do modal
            let html = `
                <div class="d-flex align-items-start mb-4">
                    <div class="me-3" style="flex-shrink: 0;">
                        ${song.thumbnail_url ? 
                            `<img src="${song.thumbnail_url}" alt="${song.version_name || song.title}" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px;">` :
                            `<div class="d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px;">
                                <i class="bx bx-music text-white" style="font-size: 3rem;"></i>
                            </div>`
                        }
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-2" style="color: #333; font-weight: 600;">${song.version_name || song.title}</h4>
                        ${song.artist ? `<p class="mb-2" style="color: #666; font-size: 0.95rem;">
                            <i class="bx bx-user me-1"></i>${song.artist}
                        </p>` : ''}
                        ${song.folder ? `<p class="mb-0" style="color: #999; font-size: 0.85rem;">
                            <i class="bx bx-folder me-1"></i>${song.folder}
                        </p>` : ''}
                    </div>
                </div>
                
                ${song.version_name ? `<div class="mb-3 p-2" style="background-color: #f5f5f5; border-radius: 8px;">
                    <small style="color: #666;">Versão: ${song.version_name}</small>
                </div>` : ''}
                
                <div class="row mb-3">
                    ${song.key ? `<div class="col-md-4 mb-2">
                        <div class="p-3" style="background-color: #f9f9f9; border-radius: 8px;">
                            <div style="color: #666; font-size: 0.85rem; margin-bottom: 4px;">Tom</div>
                            <div style="color: #333; font-weight: 600; font-size: 1.1rem;">${song.key}</div>
                        </div>
                    </div>` : ''}
                    ${duration !== '0:00' ? `<div class="col-md-4 mb-2">
                        <div class="p-3" style="background-color: #f9f9f9; border-radius: 8px;">
                            <div style="color: #666; font-size: 0.85rem; margin-bottom: 4px;">Duração</div>
                            <div style="color: #333; font-weight: 600; font-size: 1.1rem;">${duration}</div>
                        </div>
                    </div>` : ''}
                    ${song.bpm ? `<div class="col-md-4 mb-2">
                        <div class="p-3" style="background-color: #e3f2fd; border-radius: 8px;">
                            <div style="color: #666; font-size: 0.85rem; margin-bottom: 4px;">BPM</div>
                            <div style="color: #1976d2; font-weight: 600; font-size: 1.1rem;">${song.bpm}</div>
                        </div>
                    </div>` : ''}
                </div>
                
                <div class="mb-3">
                    <div style="color: #666; font-size: 0.85rem; margin-bottom: 8px;">Classificações</div>
                    <div style="color: #333; font-weight: 500;">${song.genre || 'Não informado'}</div>
                </div>
                
                ${song.observations ? `<div class="mb-3">
                    <div style="color: #666; font-size: 0.85rem; margin-bottom: 8px;">Observações</div>
                    <div style="color: #333;">${song.observations}</div>
                </div>` : ''}
                
                <div class="mt-4">
                    <div style="color: #666; font-size: 0.85rem; margin-bottom: 12px; font-weight: 600;">Referências</div>
                    <div class="d-flex flex-column gap-2">
                        ${song.link_letra ? `
                            <a href="${song.link_letra}" target="_blank" class="d-flex align-items-center p-3 text-decoration-none" style="background-color: #fff3e0; border-radius: 8px; border: 1px solid #ffe0b2;">
                                <div class="d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 6px;">
                                    <i class="bx bx-text text-white" style="font-size: 1.2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div style="color: #333; font-weight: 500;">Letra</div>
                                    <div style="color: #666; font-size: 0.85rem; word-break: break-all;">${song.link_letra}</div>
                                </div>
                                <i class="bx bx-chevron-right" style="color: #999; font-size: 1.5rem;"></i>
                            </a>
                        ` : ''}
                        ${song.link_cifra ? `
                            <a href="${song.link_cifra}" target="_blank" class="d-flex align-items-center p-3 text-decoration-none" style="background-color: #e8f5e9; border-radius: 8px; border: 1px solid #c8e6c9;">
                                <div class="d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 6px;">
                                    <i class="bx bx-equalizer text-white" style="font-size: 1.2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div style="color: #333; font-weight: 500;">Cifra (Link)</div>
                                    <div style="color: #666; font-size: 0.85rem; word-break: break-all;">${song.link_cifra}</div>
                                </div>
                                <i class="bx bx-chevron-right" style="color: #999; font-size: 1.5rem;"></i>
                            </a>
                        ` : ''}
                        ${song.cifra_pdf_url ? `
                            <a href="${song.cifra_pdf_url}" target="_blank" class="d-flex align-items-center p-3 text-decoration-none" style="background-color: #e8f5e9; border-radius: 8px; border: 1px solid #c8e6c9;">
                                <div class="d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 6px;">
                                    <i class="bx bx-file-blank text-white" style="font-size: 1.2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div style="color: #333; font-weight: 500;">Cifra (PDF)</div>
                                    <div style="color: #666; font-size: 0.85rem;">Visualizar PDF da cifra</div>
                                </div>
                                <i class="bx bx-chevron-right" style="color: #999; font-size: 1.5rem;"></i>
                            </a>
                        ` : ''}
                        ${song.link_audio ? `
                            <a href="${song.link_audio}" target="_blank" class="d-flex align-items-center p-3 text-decoration-none" style="background-color: #e3f2fd; border-radius: 8px; border: 1px solid #bbdefb;">
                                <div class="d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 6px;">
                                    <i class="bx bx-music text-white" style="font-size: 1.2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div style="color: #333; font-weight: 500;">Áudio</div>
                                    <div style="color: #666; font-size: 0.85rem; word-break: break-all;">${song.link_audio}</div>
                                </div>
                                <i class="bx bx-chevron-right" style="color: #999; font-size: 1.5rem;"></i>
                            </a>
                        ` : ''}
                        ${song.link_video ? `
                            <a href="${song.link_video}" target="_blank" class="d-flex align-items-center p-3 text-decoration-none" style="background-color: #ffebee; border-radius: 8px; border: 1px solid #ffcdd2;">
                                <div class="d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #f44336; border-radius: 6px;">
                                    <i class="bx bx-video text-white" style="font-size: 1.2rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div style="color: #333; font-weight: 500;">Vídeo</div>
                                    <div style="color: #666; font-size: 0.85rem; word-break: break-all;">${song.link_video}</div>
                                </div>
                                <i class="bx bx-chevron-right" style="color: #999; font-size: 1.5rem;"></i>
                            </a>
                        ` : ''}
                        ${!song.link_letra && !song.link_cifra && !song.cifra_pdf_url && !song.link_audio && !song.link_video ? `
                            <div class="text-center py-3" style="color: #999;">
                                <i class="bx bx-link-external" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                                Nenhuma referência cadastrada
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            
            const modal = new bootstrap.Modal(document.getElementById('songViewModal'));
            modal.show();
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar detalhes da música. Tente novamente.');
    });
}

function deleteSong(id, title) {
    if (!confirm(`Tem certeza que deseja excluir a música "${title}"?`)) {
        return;
    }
    
    fetch(`{{ route('moriah.repertorio.songs.destroy', ':id') }}`.replace(':id', id), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao excluir música.');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir música. Tente novamente.');
    });
}

// Funções para Pasta
function resetFolderForm() {
    currentFolderId = null;
    document.getElementById('folderModalLabel').textContent = 'Nova Pasta';
    document.getElementById('folderForm').reset();
    document.getElementById('folderForm').action = '{{ route('moriah.repertorio.folders.store') }}';
    document.getElementById('folderForm').method = 'POST';
}

function editFolder(id, name, description) {
    currentFolderId = id;
    document.getElementById('folderModalLabel').textContent = 'Editar Pasta';
    document.getElementById('folder_name').value = name;
    document.getElementById('folder_description').value = description || '';
    document.getElementById('folderForm').action = `{{ route('moriah.repertorio.folders.update', ':id') }}`.replace(':id', id);
    document.getElementById('folderForm').method = 'POST';
    
    if (!document.getElementById('_method_folder')) {
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.id = '_method_folder';
        methodInput.value = 'PUT';
        document.getElementById('folderForm').appendChild(methodInput);
    }
    
    const modal = new bootstrap.Modal(document.getElementById('folderModal'));
    modal.show();
}

// Submit forms
document.getElementById('songForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = this.action;
    const method = formData.get('_method') || 'POST';
    
    fetch(url, {
        method: method === 'PUT' ? 'POST' : 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao salvar música.');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar música. Tente novamente.');
    });
});

document.getElementById('folderForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = this.action;
    const method = formData.get('_method') || 'POST';
    
    const bodyData = {
        name: formData.get('name'),
        description: formData.get('description'),
    };
    
    if (method === 'PUT') {
        bodyData._method = 'PUT';
    }
    
    fetch(url, {
        method: method === 'PUT' ? 'POST' : 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(bodyData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao salvar pasta.');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar pasta. Tente novamente.');
    });
});
</script>
@endsection
