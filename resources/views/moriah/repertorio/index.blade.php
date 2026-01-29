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
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#songModal" onclick="resetSongForm()">
                        <i class="bx bx-plus me-1"></i>Adicionar Música
                    </button>
                </div>

                <div class="row">
                    @foreach($songs as $song)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
                                <div class="position-relative" style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    @if($song->thumbnail_url)
                                        <img src="{{ Storage::url($song->thumbnail_url) }}" alt="{{ $song->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <i class="bx bx-music" style="font-size: 4rem; color: white; opacity: 0.5;"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title mb-1" style="font-size: 1rem; font-weight: 600;">{{ $song->title }}</h5>
                                    <p class="card-text mb-2" style="font-size: 0.85rem; color: #666;">
                                        <strong>{{ $song->artist ?? 'Artista não informado' }}</strong>
                                    </p>
                                    <p class="card-text mb-3" style="font-size: 0.8rem; color: #999;">
                                        {{ $song->genre }} @if($song->key), Tom: {{ $song->key }}@endif
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex gap-2">
                                            <i class="bx bx-text" style="font-size: 1.2rem; color: {{ $song->has_lyrics ? '#ff9800' : '#ccc' }};" title="Letra"></i>
                                            <i class="bx bx-equalizer" style="font-size: 1.2rem; color: {{ $song->has_chords ? '#4caf50' : '#ccc' }};" title="Cifra"></i>
                                            <i class="bx bx-music" style="font-size: 1.2rem; color: {{ $song->has_audio ? '#2196f3' : '#ccc' }};" title="Áudio"></i>
                                            <i class="bx bx-video" style="font-size: 1.2rem; color: {{ $song->has_video ? '#f44336' : '#ccc' }};" title="Vídeo"></i>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-vertical" style="font-size: 1.5rem; color: #666;"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="editSong({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist ?? '') }}', '{{ $song->genre }}', '{{ $song->key ?? '' }}', {{ $song->folder_id ?? 'null' }}, {{ $song->has_lyrics ? 'true' : 'false' }}, {{ $song->has_chords ? 'true' : 'false' }}, {{ $song->has_audio ? 'true' : 'false' }}, {{ $song->has_video ? 'true' : 'false' }}); return false;">
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
                                </div>
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
                <h5 class="modal-title" id="songModalLabel">Nova Música</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="songForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="artist" class="form-label">Artista</label>
                            <input type="text" class="form-control" id="artist" name="artist">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="genre" class="form-label">Gênero</label>
                            <input type="text" class="form-control" id="genre" name="genre" value="Louvor">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="key" class="form-label">Tom</label>
                            <input type="text" class="form-control" id="key" name="key" placeholder="Ex: C, Dm, E">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="folder_id" class="form-label">Pasta</label>
                        <select class="form-control" id="folder_id" name="folder_id">
                            <option value="">Nenhuma</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="thumbnail" class="form-label">Thumbnail</label>
                        <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="has_lyrics" name="has_lyrics" value="1">
                                <label class="form-check-label" for="has_lyrics">Letra</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="has_chords" name="has_chords" value="1">
                                <label class="form-check-label" for="has_chords">Cifra</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="has_audio" name="has_audio" value="1">
                                <label class="form-check-label" for="has_audio">Áudio</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="has_video" name="has_video" value="1">
                                <label class="form-check-label" for="has_video">Vídeo</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="lyrics" class="form-label">Letra</label>
                        <textarea class="form-control" id="lyrics" name="lyrics" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="chords" class="form-label">Cifra</label>
                        <textarea class="form-control" id="chords" name="chords" rows="5"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="audio" class="form-label">Arquivo de Áudio</label>
                            <input type="file" class="form-control" id="audio" name="audio" accept="audio/*">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="video" class="form-label">Arquivo de Vídeo</label>
                            <input type="file" class="form-control" id="video" name="video" accept="video/*">
                        </div>
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
</style>

<script>
let currentSongId = null;
let currentFolderId = null;

// Funções para Música
function resetSongForm() {
    currentSongId = null;
    document.getElementById('songModalLabel').textContent = 'Nova Música';
    document.getElementById('songForm').reset();
    document.getElementById('songForm').action = '{{ route('moriah.repertorio.songs.store') }}';
    document.getElementById('songForm').method = 'POST';
}

function editSong(id, title, artist, genre, key, folderId, hasLyrics, hasChords, hasAudio, hasVideo) {
    currentSongId = id;
    document.getElementById('songModalLabel').textContent = 'Editar Música';
    document.getElementById('title').value = title;
    document.getElementById('artist').value = artist;
    document.getElementById('genre').value = genre;
    document.getElementById('key').value = key;
    document.getElementById('folder_id').value = folderId || '';
    document.getElementById('has_lyrics').checked = hasLyrics;
    document.getElementById('has_chords').checked = hasChords;
    document.getElementById('has_audio').checked = hasAudio;
    document.getElementById('has_video').checked = hasVideo;
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
