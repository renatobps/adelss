@extends('layouts.porto')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Ministério Moriah')

@section('page-title', 'Ministério Moriah')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><span>Moriah</span></li>
    <li><span>Ministério</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="ministerioTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="informacoes-tab" data-bs-toggle="tab" data-bs-target="#informacoes" type="button" role="tab" aria-controls="informacoes" aria-selected="true" style="border-radius: 8px 8px 0 0; background-color: #E8D5FF; color: #333; border: none; padding: 12px 24px;">
                    Informações
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="membros-tab" data-bs-toggle="tab" data-bs-target="#membros" type="button" role="tab" aria-controls="membros" aria-selected="false" style="border-radius: 8px 8px 0 0; background-color: transparent; color: #333; border: none; padding: 12px 24px;">
                    Membros ({{ $activeMembers }}/{{ $totalMembers }})
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="ministerioTabsContent">
            <!-- Tab Informações -->
            <div class="tab-pane fade show active" id="informacoes" role="tabpanel" aria-labelledby="informacoes-tab">
                <!-- Banner com Logo -->
                <div class="card mb-4" style="border: none; border-radius: 12px; overflow: hidden; position: relative;">
                    @php
                        $bannerUrl = $louvorDepartment && $louvorDepartment->banner_url 
                            ? Storage::url($louvorDepartment->banner_url) 
                            : null;
                        $bannerStyle = $bannerUrl 
                            ? "background-image: url('{$bannerUrl}'); background-size: cover; background-position: center; min-height: 300px;"
                            : "background: linear-gradient(135deg, #F5E6D3 0%, #E8D5C4 100%); min-height: 300px;";
                    @endphp
                    <div class="card-body text-center p-5" style="{{ $bannerStyle }}">
                        <div class="position-relative" style="z-index: 1;">
                            <div class="mb-3">
                                @php
                                    $logoUrl = $louvorDepartment && $louvorDepartment->logo_url 
                                        ? Storage::url($louvorDepartment->logo_url) 
                                        : null;
                                @endphp
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Logo Moriah" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                @else
                                    <div style="width: 80px; height: 80px; margin: 0 auto 15px; background-color: #2D7A7A; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                        <i class="bx bx-mountain" style="font-size: 40px; color: white;"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- Botões de edição -->
                    <div class="position-absolute top-0 end-0 p-3" style="z-index: 2;">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#updateBannerModal" title="Alterar Banner">
                                <i class="bx bx-image"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#updateLogoModal" title="Alterar Logo">
                                <i class="bx bx-camera"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Seção MORIAH MUSIC -->
                <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div class="card-body p-0">
                        <!-- Header da Seção -->
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom" style="background-color: #f8f9fa;">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-music me-2" style="font-size: 1.5rem; color: #666;"></i>
                                <h4 class="mb-0" style="color: #333; font-weight: 700; text-transform: uppercase;">MORIAH MUSIC</h4>
                            </div>
                            <button class="btn btn-link p-0" type="button" style="color: #666;">
                                <i class="bx bx-dots-vertical" style="font-size: 1.5rem;"></i>
                            </button>
                        </div>

                        <!-- Lista de Opções -->
                        <div class="list-group list-group-flush">
                            <a href="{{ route('moriah.funcoes.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="border: none; padding: 16px 20px; text-decoration: none; color: #333;">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-cog me-3" style="font-size: 1.5rem; color: #666;"></i>
                                    <span style="font-size: 1rem;">Funções</span>
                                </div>
                                <i class="bx bx-chevron-right" style="color: #999;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Membros -->
            <div class="tab-pane fade" id="membros" role="tabpanel" aria-labelledby="membros-tab">
                <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div class="card-body">
                        <!-- Botão Adicionar Membro -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                <i class="bx bx-plus me-1"></i>Adicionar Membro
                            </button>
                        </div>
                        @if($louvorDepartment)
                            <!-- Debug temporário -->
                            @if(config('app.debug'))
                                <div class="alert alert-info mb-3">
                                    <strong>Debug:</strong> Departamento encontrado: {{ $louvorDepartment->name }} (ID: {{ $louvorDepartment->id }})<br>
                                    Total de membros: {{ $members->count() }}
                                </div>
                            @endif
                        @else
                            <div class="alert alert-warning mb-3">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Atenção:</strong> O departamento "Louvor" não foi encontrado. 
                                Por favor, crie o departamento "Louvor" na seção de Departamentos primeiro.
                            </div>
                        @endif
                        
                        @if($members->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($members as $member)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($member->photo_url)
                                                            <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                        @else
                                                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: #e9ecef; color: #6c757d;">
                                                                <i class="bx bx-user" style="font-size: 2rem;"></i>
                                                            </div>
                                                        @endif
                                                        <div class="d-flex flex-column">
                                                            <span style="font-size: 1rem; font-weight: 500; color: #333; margin-bottom: 4px;">{{ $member->name }}</span>
                                                            @php
                                                                $memberFunctions = $member->moriahFunctions->pluck('name')->toArray();
                                                            @endphp
                                                            @if(count($memberFunctions) > 0)
                                                                <span style="font-size: 0.9rem; color: #666;">{{ implode(', ', $memberFunctions) }}</span>
                                                            @else
                                                                <span style="font-size: 0.9rem; color: #999; font-style: italic;">Nenhuma função atribuída</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-link p-0" type="button" id="dropdownMenuButton{{ $member->id }}" data-bs-toggle="dropdown" aria-expanded="false" style="color: #666;">
                                                            <i class="bx bx-dots-vertical" style="font-size: 1.5rem;"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton{{ $member->id }}">
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="openEditFunctionsModal({{ $member->id }}, '{{ $member->name }}'); return false;">
                                                                    <i class="bx bx-edit me-2"></i>Editar função do membro
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#" onclick="removeMemberFromMinistry({{ $member->id }}, '{{ $member->name }}'); return false;">
                                                                    <i class="bx bx-x me-2"></i>Remover do ministério
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bx bx-user-x" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p class="text-muted">Nenhum membro encontrado no departamento Louvor.</p>
                                <p class="text-muted small">Adicione membros clicando no botão "Adicionar Membro" acima.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
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
    
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
    
    .dropdown-menu {
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        border: none;
        border-radius: 8px;
    }
    
    .dropdown-item {
        padding: 10px 16px;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
</style>

<!-- Modal Atualizar Banner -->
<div class="modal fade" id="updateBannerModal" tabindex="-1" aria-labelledby="updateBannerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateBannerModalLabel">Alterar Banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bannerForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="banner" class="form-label">Selecione uma imagem para o banner</label>
                        <input type="file" class="form-control" id="banner" name="banner" accept="image/*" required>
                        <small class="text-muted d-block mt-1">Formatos aceitos: JPEG, PNG, JPG, GIF, SVG, WEBP (máx. 5MB)</small>
                        <small class="text-info d-block mt-1"><i class="bx bx-info-circle"></i> Tamanho ideal: 1920 x 600 pixels (proporção 16:5)</small>
                    </div>
                    <div id="bannerPreview" class="text-center mb-3" style="display: none;">
                        <img id="bannerPreviewImg" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
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

<!-- Modal Atualizar Logo -->
<div class="modal fade" id="updateLogoModal" tabindex="-1" aria-labelledby="updateLogoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateLogoModalLabel">Alterar Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="logoForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="logo" class="form-label">Selecione uma imagem para o logo</label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*" required>
                        <small class="text-muted d-block mt-1">Formatos aceitos: JPEG, PNG, JPG, GIF, SVG, WEBP (máx. 2MB)</small>
                        <small class="text-info d-block mt-1"><i class="bx bx-info-circle"></i> Tamanho ideal: 400 x 400 pixels (quadrado, 1:1)</small>
                    </div>
                    <div id="logoPreview" class="text-center mb-3" style="display: none;">
                        <img id="logoPreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 50%; object-fit: cover;">
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

<!-- Modal Adicionar Participantes -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">
                    <i class="bx bx-user-plus me-2"></i>Adicionar Participantes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bx bx-info-circle me-2"></i>
                    <strong>Selecione um ou mais membros</strong> para adicionar ao ministério de louvor. Você pode selecionar múltiplos membros de uma vez.
                </div>
                <div class="mb-3">
                    <label for="searchMemberInput" class="form-label">Buscar membro</label>
                    <input type="text" class="form-control" id="searchMemberInput" placeholder="Digite o nome do membro..." oninput="filterMembers()" onkeyup="filterMembers()">
                    <small class="text-muted">A busca é automática conforme você digita</small>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center p-2 bg-light rounded">
                    <span id="selectedCount" class="fw-bold text-primary">0 membro(s) selecionado(s)</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllMembers()">
                        <i class="bx bx-check-square me-1"></i>Selecionar todos
                    </button>
                </div>
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px;">
                    <div id="membersList">
                        <div class="text-center py-4 text-muted">
                            <i class="bx bx-loader-circle bx-spin" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            Carregando membros...
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="addSelectedMembersBtn" onclick="addSelectedMembers()">
                    <i class="bx bx-plus me-1"></i>Adicionar Selecionados (<span id="selectedCountBtn">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Funções do Membro -->
<div class="modal fade" id="editFunctionsModal" tabindex="-1" aria-labelledby="editFunctionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFunctionsModalLabel">Editar Funções</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3" id="memberNameInModal"></p>
                <div class="d-flex flex-column gap-2">
                    @foreach($functions as $function)
                        <div class="d-flex align-items-center justify-content-between" style="padding: 12px; border: 1px solid #dee2e6; border-radius: 8px;">
                            <div class="d-flex align-items-center">
                                @if($function->icon)
                                    <img src="{{ asset('img/img/icon8/' . $function->icon) }}" 
                                         alt="{{ $function->name }}" 
                                         style="width: 32px; height: 32px; object-fit: contain; margin-right: 12px;">
                                @else
                                    <i class="bx bx-music me-3" style="font-size: 1.5rem; color: #666;"></i>
                                @endif
                                <span style="font-size: 1rem; font-weight: 500;">{{ $function->name }}</span>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input function-toggle-edit" 
                                       type="checkbox" 
                                       data-function-id="{{ $function->id }}"
                                       id="edit_function_{{ $function->id }}">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveFunctionsBtn">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentMemberId = null;
    
    // Adicionar membro ao ministério (função antiga, mantida para compatibilidade)
    function addMemberToMinistry(memberId) {
        addSelectedMembers([memberId]);
    }

    // Adicionar múltiplos membros selecionados
    function addSelectedMembers(memberIds = null) {
        let selectedIds = memberIds;
        
        if (!selectedIds) {
            // Obter membros selecionados
            selectedIds = Array.from(document.querySelectorAll('.member-checkbox:checked'))
                .map(checkbox => parseInt(checkbox.value));
        }

        if (selectedIds.length === 0) {
            alert('Selecione pelo menos um membro para adicionar.');
            return;
        }

        fetch('{{ route('moriah.members.add') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                member_ids: selectedIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erro ao adicionar membros.');
            }
        })
        .catch(error => {
            console.error('Erro ao adicionar membros:', error);
            alert('Erro ao adicionar membros. Tente novamente.');
        });
    }

    // Selecionar todos os membros
    function selectAllMembers() {
        const checkboxes = document.querySelectorAll('.member-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
        
        updateSelectedCount();
    }

    // Atualizar contador de selecionados
    function updateSelectedCount() {
        const selected = document.querySelectorAll('.member-checkbox:checked').length;
        const countElement = document.getElementById('selectedCount');
        const countBtnElement = document.getElementById('selectedCountBtn');
        
        if (countElement) {
            countElement.textContent = `${selected} membro(s) selecionado(s)`;
        }
        
        if (countBtnElement) {
            countBtnElement.textContent = selected;
        }
        
        // Habilitar/desabilitar botão de adicionar
        const addBtn = document.getElementById('addSelectedMembersBtn');
        if (addBtn) {
            if (selected > 0) {
                addBtn.disabled = false;
                addBtn.classList.remove('disabled');
            } else {
                addBtn.disabled = true;
                addBtn.classList.add('disabled');
            }
        }
    }

    // Remover membro do ministério
    function removeMemberFromMinistry(memberId, memberName) {
        if (!confirm(`Tem certeza que deseja remover ${memberName} do ministério?`)) {
            return;
        }

        fetch(`{{ route('moriah.members.remove', ':id') }}`.replace(':id', memberId), {
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
                alert(data.message || 'Erro ao remover membro.');
            }
        })
        .catch(error => {
            console.error('Erro ao remover membro:', error);
            alert('Erro ao remover membro. Tente novamente.');
        });
    }

    // Filtrar membros na busca (automático enquanto digita)
    function filterMembers() {
        const input = document.getElementById('searchMemberInput');
        if (!input) return;
        
        const filter = input.value.toLowerCase().trim();
        const membersList = document.getElementById('membersList');
        if (!membersList) return;
        
        const items = membersList.getElementsByClassName('member-item');
        let visibleCount = 0;

        // Remover mensagem de "sem resultados" anterior se existir
        const existingNoResults = document.getElementById('noResultsMessage');
        if (existingNoResults) {
            existingNoResults.remove();
        }

        for (let i = 0; i < items.length; i++) {
            const name = items[i].getAttribute('data-name').toLowerCase();
            if (filter === '' || name.includes(filter)) {
                items[i].style.display = '';
                visibleCount++;
            } else {
                items[i].style.display = 'none';
            }
        }
        
        // Mostrar mensagem se não houver resultados e houver texto na busca
        if (visibleCount === 0 && filter !== '') {
            const noResults = document.createElement('div');
            noResults.id = 'noResultsMessage';
            noResults.className = 'text-center py-4 text-muted';
            noResults.innerHTML = '<i class="bx bx-search" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>Nenhum membro encontrado.';
            membersList.appendChild(noResults);
        }
    }

    // Carregar lista de membros disponíveis
    function loadAvailableMembers() {
        const membersList = document.getElementById('membersList');
        if (!membersList) return;
        
        const availableMembers = @json($availableMembers);
        
        if (availableMembers.length === 0) {
            membersList.innerHTML = '<p class="text-muted text-center py-3">Nenhum membro disponível para adicionar.</p>';
            return;
        }

        membersList.innerHTML = '';
        
        if (availableMembers.length === 0) {
            membersList.innerHTML = '<p class="text-muted text-center py-3">Nenhum membro disponível para adicionar.</p>';
            updateSelectedCount();
            return;
        }
        
        availableMembers.forEach(member => {
            const item = document.createElement('div');
            item.className = 'member-item d-flex align-items-center justify-content-between p-3 border-bottom';
            item.setAttribute('data-name', member.name);
            item.style.cursor = 'pointer';
            item.style.transition = 'background-color 0.2s';
            item.style.borderRadius = '4px';
            item.style.marginBottom = '4px';
            
            item.innerHTML = `
                <div class="d-flex align-items-center flex-grow-1">
                    <div class="form-check me-3">
                        <input class="form-check-input member-checkbox" 
                               type="checkbox" 
                               value="${member.id}" 
                               id="member_check_${member.id}"
                               onchange="updateSelectedCount()"
                               style="width: 18px; height: 18px; cursor: pointer;">
                    </div>
                    ${member.photo_url 
                        ? `<img src="${member.photo_url}" alt="${member.name}" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6;">`
                        : `<div class="rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #e9ecef; color: #6c757d; border: 2px solid #dee2e6;">
                            <i class="bx bx-user" style="font-size: 1.5rem;"></i>
                           </div>`
                    }
                    <span style="font-size: 1rem; font-weight: 500; color: #333;">${member.name}</span>
                </div>
            `;
            
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            item.addEventListener('mouseleave', function() {
                if (!this.querySelector('.member-checkbox').checked) {
                    this.style.backgroundColor = 'transparent';
                } else {
                    this.style.backgroundColor = '#e7f3ff';
                }
            });
            
            // Permitir clicar na linha inteira para selecionar
            item.addEventListener('click', function(e) {
                if (e.target.type !== 'checkbox' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'LABEL') {
                    const checkbox = this.querySelector('.member-checkbox');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateSelectedCount();
                        
                        // Atualizar cor de fundo baseado no estado do checkbox
                        if (checkbox.checked) {
                            this.style.backgroundColor = '#e7f3ff';
                        } else {
                            this.style.backgroundColor = 'transparent';
                        }
                    }
                }
            });
            
            // Atualizar cor quando checkbox muda
            const checkbox = item.querySelector('.member-checkbox');
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    item.style.backgroundColor = '#e7f3ff';
                } else {
                    item.style.backgroundColor = 'transparent';
                }
            });
            
            membersList.appendChild(item);
        });
        
        updateSelectedCount();
    }
    
    function openEditFunctionsModal(memberId, memberName) {
        currentMemberId = memberId;
        document.getElementById('memberNameInModal').textContent = memberName;
        
        // Resetar todos os checkboxes
        document.querySelectorAll('.function-toggle-edit').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Buscar funções do membro
        fetchMemberFunctions(memberId);
        
        const modal = new bootstrap.Modal(document.getElementById('editFunctionsModal'));
        modal.show();
    }
    
    function fetchMemberFunctions(memberId) {
        fetch(`{{ route('moriah.members.functions.get', ':id') }}`.replace(':id', memberId), {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.functions) {
                data.functions.forEach(functionId => {
                    const checkbox = document.getElementById(`edit_function_${functionId}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        })
        .catch(error => {
            console.error('Erro ao buscar funções:', error);
        });
    }
    
    // Preview de imagem para banner
    document.getElementById('banner')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('bannerPreview').style.display = 'block';
                document.getElementById('bannerPreviewImg').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Preview de imagem para logo
    document.getElementById('logo')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreview').style.display = 'block';
                document.getElementById('logoPreviewImg').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Upload do banner
    document.getElementById('bannerForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route('moriah.banner.update') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('updateBannerModal'));
                modal.hide();
                location.reload();
            } else {
                alert(data.message || 'Erro ao atualizar banner.');
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar banner:', error);
            alert('Erro ao atualizar banner. Tente novamente.');
        });
    });

    // Upload do logo
    document.getElementById('logoForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route('moriah.logo.update') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('updateLogoModal'));
                modal.hide();
                location.reload();
            } else {
                alert(data.message || 'Erro ao atualizar logo.');
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar logo:', error);
            alert('Erro ao atualizar logo. Tente novamente.');
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Carregar membros disponíveis quando modal for aberto
        const addMemberModal = document.getElementById('addMemberModal');
        if (addMemberModal) {
            addMemberModal.addEventListener('shown.bs.modal', function() {
                loadAvailableMembers();
                // Limpar busca ao abrir modal
                const searchInput = document.getElementById('searchMemberInput');
                if (searchInput) {
                    searchInput.value = '';
                    // Pequeno delay para garantir que os membros foram carregados
                    setTimeout(() => filterMembers(), 100);
                }
            });
            
            // Limpar busca ao fechar modal
            addMemberModal.addEventListener('hidden.bs.modal', function() {
                const searchInput = document.getElementById('searchMemberInput');
                if (searchInput) {
                    searchInput.value = '';
                }
                // Remover mensagem de "sem resultados" se existir
                const noResults = document.getElementById('noResultsMessage');
                if (noResults) {
                    noResults.remove();
                }
            });
        }

        // Salvar funções
        document.getElementById('saveFunctionsBtn').addEventListener('click', function() {
            if (!currentMemberId) return;
            
            const selectedFunctions = Array.from(document.querySelectorAll('.function-toggle-edit:checked'))
                .map(checkbox => checkbox.getAttribute('data-function-id'));
            
            fetch(`{{ route('moriah.members.functions.update', ':id') }}`.replace(':id', currentMemberId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    functions: selectedFunctions
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editFunctionsModal'));
                    modal.hide();
                    // Recarregar a página para atualizar a lista
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erro ao salvar funções:', error);
                alert('Erro ao salvar funções. Tente novamente.');
            });
        });
    });
</script>
@endsection
