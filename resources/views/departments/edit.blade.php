@extends('layouts.porto')

@section('title', 'Editar Departamento')

@section('page-title', 'Editar Departamento')

@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.1; background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2250%22 r=%2240%22 fill=%22none%22 stroke=%22white%22 stroke-width=%222%22/></svg>'); background-size: cover;"></div>
                <div style="position: relative; z-index: 1; text-align: center; padding: 2rem 0;">
                    <div class="mb-3">
                        @if($department->icon)
                            <i class="{{ $department->icon }}" style="font-size: 4rem; color: white;"></i>
                        @else
                            <i class="bx bx-group" style="font-size: 4rem; color: white;"></i>
                        @endif
                    </div>
                    <h2 class="mb-0" style="color: white;">Editar Departamento</h2>
                </div>
            </header>
            <div class="card-body">
                <form action="{{ route('departments.update', $department) }}" method="POST" id="departmentForm">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Nome do departamento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $department->name) }}" 
                                   placeholder="Ex: Departamento x..." required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Sobre o departamento</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" 
                                      placeholder="Ex: Departamento responsável por...">{{ old('description', $department->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="icon" class="form-label">Ícone</label>
                            <input type="text" class="form-control @error('icon') is-invalid @enderror" 
                                   id="icon" name="icon" value="{{ old('icon', $department->icon) }}" 
                                   placeholder="Ex: bx-music">
                            <small class="form-text text-muted">Use classes Box Icons (bx-*) ou Font Awesome (fas fa-*)</small>
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Cor</label>
                            <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" 
                                   id="color" name="color" value="{{ old('color', $department->color ?? '#0088cc') }}">
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="leaders" class="form-label">Líderes</label>
                            <select class="form-select @error('leaders') is-invalid @enderror" 
                                    id="leaders" name="leaders[]" multiple size="5">
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}" {{ in_array($member->id, old('leaders', $department->leaders->pluck('id')->toArray())) ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos líderes</small>
                            @error('leaders')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="ativo" {{ old('status', $department->status) == 'ativo' ? 'selected' : '' }}>Ativo</option>
                                <option value="arquivado" {{ old('status', $department->status) == 'arquivado' ? 'selected' : '' }}>Arquivado</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Participantes</h5>
                                <button type="button" class="btn btn-sm btn-primary" id="addParticipantBtn">
                                    <i class="bx bx-plus"></i> Adicionar
                                </button>
                            </div>
                            <div id="participantsContainer" style="min-height: 150px; border: 2px dashed #ddd; border-radius: 4px; padding: 1rem; background-color: #f8f9fa;">
                                @if($department->members->count() > 0)
                                    <div id="participantsList">
                                        @foreach($department->members as $member)
                                            <div class="alert alert-light mb-2 d-flex justify-content-between align-items-center" id="participant-{{ $member->id }}">
                                                <span>{{ $member->name }}</span>
                                                <button type="button" class="btn btn-sm btn-link text-danger" onclick="removeParticipant({{ $member->id }})">
                                                    <i class="bx bx-x"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="text-muted text-center mb-0 d-none" id="noParticipantsText">*Sem participantes</p>
                                @else
                                    <p class="text-muted text-center mb-0" id="noParticipantsText">*Sem participantes</p>
                                    <div id="participantsList"></div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Cargos/funções</h5>
                                <button type="button" class="btn btn-sm btn-primary" id="addRoleBtn">
                                    <i class="bx bx-plus"></i> Novo
                                </button>
                            </div>
                            <div id="rolesContainer" style="min-height: 150px; border: 2px dashed #ddd; border-radius: 4px; padding: 1rem; background-color: #f8f9fa;">
                                <div id="rolesList">
                                    @foreach($department->roles as $role)
                                        @if($role->is_default)
                                            <div class="alert alert-info mb-2" style="background-color: #e9ecef; border: none;">
                                                <small><strong>{{ $role->name }}</strong></div>
                                            </div>
                                        @else
                                            <div class="alert alert-light mb-2" id="role-{{ $role->id }}">
                                                <div class="row g-2">
                                                    <div class="col-md-8">
                                                        <input type="hidden" name="roles[{{ $role->id }}][id]" value="{{ $role->id }}">
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="roles[{{ $role->id }}][name]" 
                                                               value="{{ $role->name }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <button type="button" class="btn btn-sm btn-danger w-100" onclick="markRoleForDeletion({{ $role->id }})">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <textarea class="form-control form-control-sm mt-2" 
                                                          name="roles[{{ $role->id }}][description]" 
                                                          placeholder="Descrição (opcional)" rows="2">{{ $role->description }}</textarea>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="members" id="membersInput" value="{{ json_encode($department->members->pluck('id')->toArray()) }}">
                    <div id="rolesInputs"></div>
                    <input type="hidden" name="roles_to_delete" id="rolesToDelete" value="[]">

                    <div class="mt-4">
                        <div class="text-danger small" id="validationMessage" style="display: none;">
                            <i class="bx bx-error-circle"></i> Alguns campos são obrigatórios
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('departments.index') }}" class="btn btn-default">
                                <i class="bx bx-arrow-back me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bx bx-check me-2"></i>Salvar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<!-- Modal para adicionar participantes -->
<div class="modal fade" id="participantModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-user-plus me-2"></i>Adicionar Participantes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="bx bx-info-circle me-2"></i>
                    <strong>Selecione um ou mais membros</strong> para adicionar ao departamento. Você pode selecionar múltiplos membros de uma vez.
                </div>
                <div class="mb-3">
                    <label for="searchParticipantInput" class="form-label">Buscar membro</label>
                    <input type="text" class="form-control" id="searchParticipantInput" placeholder="Digite o nome do membro..." oninput="filterParticipants()">
                    <small class="text-muted">A busca é automática conforme você digita</small>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center p-2 bg-light rounded">
                    <span id="selectedParticipantsCount" class="fw-bold text-primary">0 membro(s) selecionado(s)</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllParticipants()">
                        <i class="bx bx-check-square me-1"></i>Selecionar todos
                    </button>
                </div>
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 8px;">
                    <div id="participantsListModal">
                        @foreach($members as $member)
                            @if(!in_array($member->id, $department->members->pluck('id')->toArray()))
                                <div class="participant-item d-flex align-items-center justify-content-between p-3 border-bottom" 
                                     data-name="{{ strtolower($member->name) }}" 
                                     data-member-id="{{ $member->id }}"
                                     style="cursor: pointer; transition: background-color 0.2s; border-radius: 4px; margin-bottom: 4px;">
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <div class="form-check me-3">
                                            <input class="form-check-input participant-checkbox" 
                                                   type="checkbox" 
                                                   value="{{ $member->id }}" 
                                                   id="participant_check_{{ $member->id }}"
                                                   onchange="updateParticipantsCount()"
                                                   style="width: 18px; height: 18px; cursor: pointer;">
                                        </div>
                                        @if($member->photo_url)
                                            <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #dee2e6;">
                                        @else
                                            <div class="rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #e9ecef; color: #6c757d; border: 2px solid #dee2e6;">
                                                <i class="bx bx-user" style="font-size: 1.5rem;"></i>
                                            </div>
                                        @endif
                                        <span style="font-size: 1rem; font-weight: 500; color: #333;">{{ $member->name }}</span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmParticipantBtn">
                    <i class="bx bx-plus me-1"></i>Adicionar Selecionados (<span id="selectedCountBtn">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectedMembers = {{ json_encode($department->members->pluck('id')->toArray()) }};
    let roleCounter = {{ $department->roles->where('is_default', false)->max('id') ?? 0 }};
    let rolesToDelete = [];

    // Adicionar participante
    document.getElementById('addParticipantBtn').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('participantModal'));
        modal.show();
        // Limpar busca e seleções ao abrir
        const searchInput = document.getElementById('searchParticipantInput');
        if (searchInput) {
            searchInput.value = '';
            filterParticipants();
        }
        document.querySelectorAll('.participant-checkbox').forEach(cb => {
            cb.checked = false;
        });
        updateParticipantsCount();
    });
    
    // Permitir clicar na linha inteira para selecionar
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.participant-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.type !== 'checkbox' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'LABEL') {
                    const checkbox = this.querySelector('.participant-checkbox');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateParticipantsCount();
                    }
                }
            });
            
            item.addEventListener('mouseenter', function() {
                if (!this.querySelector('.participant-checkbox').checked) {
                    this.style.backgroundColor = '#f8f9fa';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                if (!this.querySelector('.participant-checkbox').checked) {
                    this.style.backgroundColor = 'transparent';
                }
            });
        });
    });

    // Filtrar participantes na busca
    function filterParticipants() {
        const input = document.getElementById('searchParticipantInput');
        if (!input) return;
        
        const filter = input.value.toLowerCase().trim();
        const items = document.querySelectorAll('.participant-item');
        
        items.forEach(item => {
            const name = item.getAttribute('data-name');
            if (filter === '' || name.includes(filter)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Selecionar todos os participantes
    function selectAllParticipants() {
        const checkboxes = document.querySelectorAll('.participant-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(checkbox => {
            if (checkbox.offsetParent !== null) { // Só seleciona os visíveis
                checkbox.checked = !allChecked;
                const item = checkbox.closest('.participant-item');
                if (item) {
                    if (checkbox.checked) {
                        item.style.backgroundColor = '#e7f3ff';
                    } else {
                        item.style.backgroundColor = 'transparent';
                    }
                }
            }
        });
        
        updateParticipantsCount();
    }

    // Atualizar contador de selecionados
    function updateParticipantsCount() {
        const selected = document.querySelectorAll('.participant-checkbox:checked').length;
        const countElement = document.getElementById('selectedParticipantsCount');
        const countBtnElement = document.getElementById('selectedCountBtn');
        
        if (countElement) {
            countElement.textContent = `${selected} membro(s) selecionado(s)`;
        }
        
        if (countBtnElement) {
            countBtnElement.textContent = selected;
        }
        
        // Habilitar/desabilitar botão
        const addBtn = document.getElementById('confirmParticipantBtn');
        if (addBtn) {
            if (selected > 0) {
                addBtn.disabled = false;
                addBtn.classList.remove('disabled');
            } else {
                addBtn.disabled = true;
                addBtn.classList.add('disabled');
            }
        }
        
        // Atualizar cor de fundo dos itens
        document.querySelectorAll('.participant-checkbox').forEach(checkbox => {
            const item = checkbox.closest('.participant-item');
            if (item) {
                if (checkbox.checked) {
                    item.style.backgroundColor = '#e7f3ff';
                } else {
                    item.style.backgroundColor = 'transparent';
                }
            }
        });
    }

    document.getElementById('confirmParticipantBtn').addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.participant-checkbox:checked');
        const newMemberIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
        
        if (newMemberIds.length === 0) {
            alert('Selecione pelo menos um membro para adicionar.');
            return;
        }

        // Adicionar apenas os que ainda não estão na lista
        newMemberIds.forEach(memberId => {
            if (!selectedMembers.includes(memberId)) {
                selectedMembers.push(memberId);
            }
        });
        
        updateParticipantsList();
        document.getElementById('membersInput').value = JSON.stringify(selectedMembers);
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('participantModal'));
        modal.hide();
        
        // Limpar seleções
        document.querySelectorAll('.participant-checkbox').forEach(cb => {
            cb.checked = false;
        });
        const searchInput = document.getElementById('searchParticipantInput');
        if (searchInput) {
            searchInput.value = '';
            filterParticipants();
        }
        updateParticipantsCount();
    });

    function updateParticipantsList() {
        const container = document.getElementById('participantsList');
        const noParticipants = document.getElementById('noParticipantsText');
        
        if (selectedMembers.length === 0) {
            container.innerHTML = '';
            noParticipants.classList.remove('d-none');
            document.getElementById('membersInput').value = JSON.stringify([]);
            return;
        }

        noParticipants.classList.add('d-none');
        // Recriar lista apenas com novos membros (os existentes já estão no DOM)
        @php
            $existingMemberIds = $department->members->pluck('id')->toArray();
        @endphp
        
        const existingIds = @json($existingMemberIds);
        const newMembers = selectedMembers.filter(id => !existingIds.includes(id));
        
        newMembers.forEach(memberId => {
            const checkbox = document.querySelector(`#participant_check_${memberId}`);
            const item = checkbox ? checkbox.closest('.participant-item') : null;
            const nameSpan = item ? item.querySelector('span[style*="font-weight"]') : null;
            const name = nameSpan ? nameSpan.textContent.trim() : 'Membro ' + memberId;
            
            // Verificar se já não existe no DOM
            if (!document.getElementById(`participant-${memberId}`)) {
                const memberHtml = `
                    <div class="alert alert-light mb-2 d-flex justify-content-between align-items-center" id="participant-${memberId}">
                        <span>${name}</span>
                        <button type="button" class="btn btn-sm btn-link text-danger" onclick="removeParticipant(${memberId})">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', memberHtml);
            }
        });
        
        document.getElementById('membersInput').value = JSON.stringify(selectedMembers);
    }

    function removeParticipant(memberId) {
        selectedMembers = selectedMembers.filter(id => id !== memberId);
        const element = document.getElementById(`participant-${memberId}`);
        if (element) {
            element.remove();
        }
        document.getElementById('membersInput').value = JSON.stringify(selectedMembers);
        
        const noParticipants = document.getElementById('noParticipantsText');
        if (selectedMembers.length === 0) {
            noParticipants.classList.remove('d-none');
        }
    }

    // Adicionar cargo/função
    document.getElementById('addRoleBtn').addEventListener('click', function() {
        roleCounter++;
        const rolesList = document.getElementById('rolesList');
        
        const roleHtml = `
            <div class="alert alert-light mb-2" id="role-new-${roleCounter}">
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-sm" 
                               name="roles[new-${roleCounter}][name]" 
                               placeholder="Nome do cargo" required>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeNewRole(${roleCounter})">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                <textarea class="form-control form-control-sm mt-2" 
                          name="roles[new-${roleCounter}][description]" 
                          placeholder="Descrição (opcional)" rows="2"></textarea>
            </div>
        `;
        
        rolesList.insertAdjacentHTML('beforeend', roleHtml);
    });

    function removeNewRole(id) {
        document.getElementById(`role-new-${id}`).remove();
    }

    function markRoleForDeletion(id) {
        if (!rolesToDelete.includes(id)) {
            rolesToDelete.push(id);
        }
        document.getElementById(`role-${id}`).style.display = 'none';
        document.getElementById('rolesToDelete').value = JSON.stringify(rolesToDelete);
    }

    // Validação do formulário
    document.getElementById('departmentForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const validationMsg = document.getElementById('validationMessage');
        
        if (!name) {
            e.preventDefault();
            validationMsg.style.display = 'block';
            return false;
        }
        
        validationMsg.style.display = 'none';
    });
</script>
@endpush
@endsection


