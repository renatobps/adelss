@extends('layouts.porto')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Editar Escala - Moriah')

@section('page-title', 'Editar Escala')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><a href="{{ route('moriah.ministerio') }}">Moriah</a></li>
    <li><a href="{{ route('moriah.schedules.index') }}">Escalas</a></li>
    <li><span>Editar Escala</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Editar escala</h2>
            </header>
            <div class="card-body">
                <form action="{{ route('moriah.schedules.update', $moriahSchedule) }}" method="POST" id="scheduleForm">
                    @csrf
                    @method('PUT')
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" id="scheduleTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="detalhes-tab" data-bs-toggle="tab" data-bs-target="#detalhes" type="button" role="tab">
                                <i class="bx bx-info-circle me-1"></i>Detalhes <span class="badge bg-secondary ms-1" id="detalhes-count">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="participantes-tab" data-bs-toggle="tab" data-bs-target="#participantes" type="button" role="tab">
                                <i class="bx bx-group me-1"></i>Participantes <span class="badge bg-secondary ms-1" id="participantes-count">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="musicas-tab" data-bs-toggle="tab" data-bs-target="#musicas" type="button" role="tab">
                                <i class="bx bx-music me-1"></i>Músicas <span class="badge bg-secondary ms-1" id="musicas-count">0</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="scheduleTabsContent">
                        <!-- Tab Detalhes -->
                        <div class="tab-pane fade show active" id="detalhes" role="tabpanel">
                            <div class="mb-3">
                                <label for="event_id" class="form-label">Título <span class="text-danger">*</span></label>
                                <select class="form-select @error('event_id') is-invalid @enderror" id="event_id" name="event_id" required>
                                    <option value="">Selecione um culto do mês</option>
                                    @foreach($cultos as $culto)
                                        <option value="{{ $culto->id }}" data-title="{{ $culto->title }}" data-date="{{ $culto->start_date->format('Y-m-d') }}" data-time="{{ $culto->start_date->format('H:i') }}" {{ old('event_id', $moriahSchedule->event_id) == $culto->id ? 'selected' : '' }}>
                                            {{ $culto->title }} - {{ $culto->start_date->format('d/m/Y H:i') }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="title" name="title" value="{{ old('title', $moriahSchedule->title) }}">
                                @error('title')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('event_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date" class="form-label">Data <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', $moriahSchedule->date->format('Y-m-d')) }}" required>
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="time" class="form-label">Hora</label>
                                    <input type="time" class="form-control @error('time') is-invalid @enderror" id="time" name="time" value="{{ old('time', $moriahSchedule->time ? \Carbon\Carbon::parse($moriahSchedule->time)->format('H:i') : '') }}">
                                    @error('time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="observations" class="form-label">Observações</label>
                                <textarea class="form-control @error('observations') is-invalid @enderror" id="observations" name="observations" rows="3" maxlength="500">{{ old('observations', $moriahSchedule->observations) }}</textarea>
                                <small class="text-muted"><span id="observations-count">{{ strlen(old('observations', $moriahSchedule->observations ?? '')) }}</span>/500</small>
                                @error('observations')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="rascunho" {{ old('status', $moriahSchedule->status) == 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                                    <option value="publicada" {{ old('status', $moriahSchedule->status) == 'publicada' ? 'selected' : '' }}>Publicada</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="request_confirmation" name="request_confirmation" value="1" {{ old('request_confirmation', $moriahSchedule->request_confirmation) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="request_confirmation">
                                        Solicitar confirmação dos participantes
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Participantes -->
                        <div class="tab-pane fade" id="participantes" role="tabpanel">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="member-search" placeholder="Buscar por nome ou função">
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all-members">
                                    <label class="form-check-label" for="select-all-members">Selecionar todos</label>
                                </div>
                            </div>
                            <div class="list-group" id="members-list" style="max-height: 500px; overflow-y: auto;">
                                @foreach($members as $member)
                                    <div class="member-item" data-name="{{ strtolower($member->name) }}" data-role="{{ strtolower($member->role?->name ?? '') }}">
                                        <div class="list-group-item d-flex align-items-center" style="background-color: {{ $member->moriahFunctions->count() > 1 ? '#f0e6ff' : 'transparent' }};">
                                            <div class="me-3">
                                                @if($member->photo_url)
                                                    <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                                @else
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        {{ strtoupper(substr($member->name, 0, 2)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">{{ $member->name }}</div>
                                                <small class="text-muted">{{ $member->moriahFunctions->pluck('name')->join(', ') }}</small>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input member-checkbox" type="checkbox" name="members[]" value="{{ $member->id }}" id="member-{{ $member->id }}" data-member-id="{{ $member->id }}" data-functions-count="{{ $member->moriahFunctions->count() }}" {{ in_array($member->id, $moriahSchedule->members->pluck('id')->toArray()) ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                        @if($member->moriahFunctions->count() > 1)
                                            <div class="member-functions-panel" id="functions-panel-{{ $member->id }}" style="display: none; padding: 12px; background-color: #f9f9f9; border-top: 1px solid #e0e0e0;">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <small class="text-muted" id="functions-count-{{ $member->id }}">0/{{ $member->moriahFunctions->count() }} funções selecionadas.</small>
                                                    <i class="bx bx-chevron-up" id="toggle-icon-{{ $member->id }}" style="cursor: pointer;"></i>
                                                </div>
                                                <div class="functions-checklist" id="functions-checklist-{{ $member->id }}">
                                                    @foreach($member->moriahFunctions as $function)
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input function-checkbox" type="checkbox" name="member_functions[{{ $member->id }}][]" value="{{ $function->id }}" id="function-{{ $member->id }}-{{ $function->id }}" data-member-id="{{ $member->id }}" {{ isset($selectedMemberFunctions[$member->id]) && in_array($function->id, $selectedMemberFunctions[$member->id]) ? 'checked' : '' }}>
                                                            <label class="form-check-label d-flex align-items-center" for="function-{{ $member->id }}-{{ $function->id }}">
                                                                @if($function->icon)
                                                                    <i class="{{ $function->icon }} me-2"></i>
                                                                @else
                                                                    <i class="bx bx-music me-2"></i>
                                                                @endif
                                                                {{ $function->name }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @elseif($member->moriahFunctions->count() == 1)
                                            {{-- Campo oculto para membros com apenas uma função --}}
                                            @php
                                                $isMemberSelected = in_array($member->id, $moriahSchedule->members->pluck('id')->toArray());
                                                $isFunctionSelected = isset($selectedMemberFunctions[$member->id]) && in_array($member->moriahFunctions->first()->id, $selectedMemberFunctions[$member->id]);
                                            @endphp
                                            @if($isMemberSelected && $isFunctionSelected)
                                                <input type="hidden" name="member_functions[{{ $member->id }}][]" value="{{ $member->moriahFunctions->first()->id }}" id="function-{{ $member->id }}-{{ $member->moriahFunctions->first()->id }}">
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Tab Músicas -->
                        <div class="tab-pane fade" id="musicas" role="tabpanel">
                            <div class="mb-3 d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="btn-add-songs">
                                    <i class="bx bx-plus me-1"></i>Adicionar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-sortear-songs">
                                    <i class="bx bx-shuffle me-1"></i>Sortear
                                </button>
                            </div>
                            <div id="selected-songs-list" class="list-group">
                                <div class="text-center text-muted py-5" id="no-songs-message">
                                    <i class="bx bx-music" style="font-size: 4rem; display: block; margin-bottom: 1rem;"></i>
                                    <p>Para adicionar uma música, toque no botão: ( + Adicionar )</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('moriah.schedules.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<!-- Modal Selecionar Músicas -->
<div class="modal fade" id="songsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selecionar Músicas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="song-search-modal" placeholder="Buscar música...">
                </div>
                <div class="list-group" id="songs-modal-list" style="max-height: 400px; overflow-y: auto;">
                    @foreach($songs as $song)
                        <div class="list-group-item d-flex align-items-center song-item-modal" data-title="{{ strtolower($song->version_name ?? $song->title) }}" data-artist="{{ strtolower($song->artist ?? '') }}">
                            <div class="form-check flex-grow-1">
                                <input class="form-check-input song-checkbox-modal" type="checkbox" value="{{ $song->id }}" id="song-modal-{{ $song->id }}" data-song-id="{{ $song->id }}" data-song-title="{{ $song->version_name ?? $song->title }}" data-song-artist="{{ $song->artist ?? '' }}">
                                <label class="form-check-label" for="song-modal-{{ $song->id }}">
                                    <strong>{{ $song->version_name ?? $song->title }}</strong>
                                    @if($song->artist)
                                        <br><small class="text-muted">{{ $song->artist }}</small>
                                    @endif
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-songs">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar contadores
    const observationsInput = document.getElementById('observations');
    const observationsCount = document.getElementById('observations-count');
    if (observationsInput && observationsCount) {
        observationsCount.textContent = observationsInput.value.length;
        observationsInput.addEventListener('input', function() {
            observationsCount.textContent = this.value.length;
        });
    }

    // Inicializar painéis de funções para membros já selecionados
    document.querySelectorAll('.member-checkbox:checked').forEach(checkbox => {
        const memberId = checkbox.dataset.memberId;
        const functionsCount = parseInt(checkbox.dataset.functionsCount || 0);
        const panel = document.getElementById('functions-panel-' + memberId);
        
        if (panel && functionsCount > 1) {
            panel.style.display = 'block';
            updateFunctionCount(memberId);
        }
    });

    // Preencher músicas já selecionadas
    @if($moriahSchedule->songs->count() > 0)
        const selectedSongsList = document.getElementById('selected-songs-list');
        const noSongsMessage = document.getElementById('no-songs-message');
        
        @foreach($moriahSchedule->songs as $index => $song)
            if (noSongsMessage) {
                noSongsMessage.style.display = 'none';
            }
            const songItem = document.createElement('div');
            songItem.className = 'list-group-item d-flex align-items-center';
            songItem.innerHTML = `
                <div class="flex-grow-1">
                    <strong>{{ $song->version_name ?? $song->title }}</strong>
                    @if($song->artist)
                        <br><small class="text-muted">{{ $song->artist }}</small>
                    @endif
                </div>
                <input type="hidden" name="songs[]" value="{{ $song->id }}">
                <button type="button" class="btn btn-sm btn-outline-danger remove-song">
                    <i class="bx bx-trash"></i>
                </button>
            `;
            selectedSongsList.appendChild(songItem);
        @endforeach

        // Adicionar event listeners para remover músicas
        document.querySelectorAll('.remove-song').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.list-group-item').remove();
                updateCounts();
                if (selectedSongsList.children.length === 0 && noSongsMessage) {
                    noSongsMessage.style.display = 'block';
                }
            });
        });
    @endif

    // Preencher título, data e hora ao selecionar culto
    const eventSelect = document.getElementById('event_id');
    const titleInput = document.getElementById('title');
    const dateInput = document.getElementById('date');
    const timeInput = document.getElementById('time');
    
    if (eventSelect) {
        eventSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                titleInput.value = selectedOption.dataset.title;
                dateInput.value = selectedOption.dataset.date;
                timeInput.value = selectedOption.dataset.time;
            }
        });
    }

    // Busca de membros
    const memberSearch = document.getElementById('member-search');
    const memberItems = document.querySelectorAll('.member-item');
    
    if (memberSearch) {
        memberSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            memberItems.forEach(item => {
                const name = item.dataset.name;
                const role = item.dataset.role;
                if (name.includes(searchTerm) || role.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Selecionar todos os membros
    const selectAllMembers = document.getElementById('select-all-members');
    const memberCheckboxes = document.querySelectorAll('.member-checkbox');
    
    if (selectAllMembers) {
        selectAllMembers.addEventListener('change', function() {
            memberCheckboxes.forEach(checkbox => {
                if (checkbox.closest('.member-item').style.display !== 'none') {
                    checkbox.checked = this.checked;
                    toggleMemberFunctionsPanel(checkbox);
                }
            });
            updateCounts();
        });
    }

    // Função para mostrar/ocultar painel de funções
    function toggleMemberFunctionsPanel(checkbox) {
        const memberId = checkbox.dataset.memberId;
        const functionsCount = parseInt(checkbox.dataset.functionsCount || 0);
        const panel = document.getElementById('functions-panel-' + memberId);
        const toggleIcon = document.getElementById('toggle-icon-' + memberId);
        
        if (checkbox.checked) {
            // Se o membro tem apenas uma função, selecionar automaticamente
            if (functionsCount === 1) {
                const functionCheckbox = document.querySelector(`input[name="member_functions[${memberId}][]"]`);
                if (functionCheckbox) {
                    functionCheckbox.checked = true;
                }
            } else if (panel && functionsCount > 1) {
                // Se tem mais de uma função, mostrar o painel
                panel.style.display = 'block';
            }
        } else {
            if (panel) {
                panel.style.display = 'none';
                // Desmarcar todas as funções quando desmarcar o membro
                const functionCheckboxes = panel.querySelectorAll('.function-checkbox');
                functionCheckboxes.forEach(fc => fc.checked = false);
                updateFunctionCount(memberId);
            } else {
                // Se não tem painel (1 função), desmarcar a função
                const functionCheckbox = document.querySelector(`input[name="member_functions[${memberId}][]"]`);
                if (functionCheckbox) {
                    functionCheckbox.checked = false;
                }
            }
        }
    }

    // Atualizar contador de funções selecionadas
    function updateFunctionCount(memberId) {
        const panel = document.getElementById('functions-panel-' + memberId);
        if (panel) {
            const checkedFunctions = panel.querySelectorAll('.function-checkbox:checked').length;
            const totalFunctions = panel.querySelectorAll('.function-checkbox').length;
            const countElement = document.getElementById('functions-count-' + memberId);
            if (countElement) {
                countElement.textContent = `${checkedFunctions}/${totalFunctions} funções selecionadas.`;
            }
        }
    }

    // Event listeners para checkboxes de membros
    memberCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleMemberFunctionsPanel(this);
            updateCounts();
        });
    });

    // Event listeners para checkboxes de funções
    document.querySelectorAll('.function-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const memberId = this.dataset.memberId;
            updateFunctionCount(memberId);
        });
    });

    // Toggle do painel de funções (chevron)
    document.querySelectorAll('[id^="toggle-icon-"]').forEach(icon => {
        icon.addEventListener('click', function() {
            const memberId = this.id.replace('toggle-icon-', '');
            const panel = document.getElementById('functions-panel-' + memberId);
            const checklist = document.getElementById('functions-checklist-' + memberId);
            
            if (panel && checklist) {
                if (checklist.style.display === 'none') {
                    checklist.style.display = 'block';
                    this.classList.remove('bx-chevron-down');
                    this.classList.add('bx-chevron-up');
                } else {
                    checklist.style.display = 'none';
                    this.classList.remove('bx-chevron-up');
                    this.classList.add('bx-chevron-down');
                }
            }
        });
    });

    // Atualizar contadores
    function updateCounts() {
        const selectedMembers = document.querySelectorAll('.member-checkbox:checked').length;
        const selectedSongs = document.querySelectorAll('input[name="songs[]"]').length;
        
        document.getElementById('participantes-count').textContent = selectedMembers;
        document.getElementById('musicas-count').textContent = selectedSongs;
    }

    // Abrir modal de músicas
    const btnAddSongs = document.getElementById('btn-add-songs');
    if (btnAddSongs) {
        btnAddSongs.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('songsModal'));
            modal.show();
        });
    }

    // Busca de músicas no modal
    const songSearchModal = document.getElementById('song-search-modal');
    const songItemsModal = document.querySelectorAll('.song-item-modal');
    
    if (songSearchModal) {
        songSearchModal.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            songItemsModal.forEach(item => {
                const title = item.dataset.title;
                const artist = item.dataset.artist;
                if (title.includes(searchTerm) || artist.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Confirmar seleção de músicas
    const btnConfirmSongs = document.getElementById('btn-confirm-songs');
    const selectedSongsList = document.getElementById('selected-songs-list');
    const noSongsMessage = document.getElementById('no-songs-message');
    
    if (btnConfirmSongs) {
        btnConfirmSongs.addEventListener('click', function() {
            const selectedCheckboxes = document.querySelectorAll('.song-checkbox-modal:checked');
            const existingSongs = new Set(Array.from(document.querySelectorAll('input[name="songs[]"]')).map(input => input.value));
            
            selectedCheckboxes.forEach(checkbox => {
                if (!existingSongs.has(checkbox.value)) {
                    const songId = checkbox.dataset.songId;
                    const songTitle = checkbox.dataset.songTitle;
                    const songArtist = checkbox.dataset.songArtist;
                    
                    const songItem = document.createElement('div');
                    songItem.className = 'list-group-item d-flex align-items-center';
                    songItem.innerHTML = `
                        <div class="flex-grow-1">
                            <strong>${songTitle}</strong>
                            ${songArtist ? `<br><small class="text-muted">${songArtist}</small>` : ''}
                        </div>
                        <input type="hidden" name="songs[]" value="${songId}">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-song">
                            <i class="bx bx-trash"></i>
                        </button>
                    `;
                    
                    if (noSongsMessage) {
                        noSongsMessage.style.display = 'none';
                    }
                    selectedSongsList.appendChild(songItem);
                }
            });
            
            // Remover músicas
            document.querySelectorAll('.remove-song').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.list-group-item').remove();
                    updateCounts();
                    if (selectedSongsList.children.length === 0 && noSongsMessage) {
                        noSongsMessage.style.display = 'block';
                    }
                });
            });
            
            updateCounts();
            const modal = bootstrap.Modal.getInstance(document.getElementById('songsModal'));
            modal.hide();
            
            // Limpar seleções do modal
            document.querySelectorAll('.song-checkbox-modal').forEach(cb => cb.checked = false);
        });
    }

    // Atualizar contadores ao marcar/desmarcar checkboxes
    memberCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCounts);
    });

    // Sortear músicas (placeholder)
    const btnSortearSongs = document.getElementById('btn-sortear-songs');
    if (btnSortearSongs) {
        btnSortearSongs.addEventListener('click', function() {
            alert('Funcionalidade de sortear músicas será implementada em breve.');
        });
    }
});
</script>
@endpush
@endsection
