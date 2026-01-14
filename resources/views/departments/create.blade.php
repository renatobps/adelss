@extends('layouts.porto')

@section('title', 'Novo Departamento')

@section('page-title', 'Novo Departamento')

@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><span>Novo</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.1; background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2250%22 r=%2240%22 fill=%22none%22 stroke=%22white%22 stroke-width=%222%22/></svg>'); background-size: cover;"></div>
                <div style="position: relative; z-index: 1; text-align: center; padding: 2rem 0;">
                    <div class="mb-3">
                        @if($selectedTemplate && isset($templates[$selectedTemplate]))
                            @php $template = $templates[$selectedTemplate]; @endphp
                            <i class="{{ $template['icon'] }}" style="font-size: 4rem; color: white;"></i>
                        @else
                            <i class="bx bx-group" style="font-size: 4rem; color: white;"></i>
                        @endif
                    </div>
                    <h2 class="mb-0" style="color: white;">Novo Departamento</h2>
                </div>
            </header>
            <div class="card-body">
                <form action="{{ route('departments.store') }}" method="POST" id="departmentForm">
                    @csrf

                    @if($selectedTemplate)
                        <input type="hidden" name="template" value="{{ $selectedTemplate }}">
                    @endif

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Nome do departamento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $selectedTemplate && isset($templates[$selectedTemplate]) ? $templates[$selectedTemplate]['name'] : '') }}" 
                                   placeholder="Ex: Departamento x..." required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Sobre o departamento</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" 
                                      placeholder="Ex: Departamento responsável por...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(!$selectedTemplate)
                        <div class="col-md-6 mb-3">
                            <label for="icon" class="form-label">Ícone</label>
                            <input type="text" class="form-control @error('icon') is-invalid @enderror" 
                                   id="icon" name="icon" value="{{ old('icon') }}" 
                                   placeholder="Ex: bx-music">
                            <small class="form-text text-muted">Use classes Box Icons (bx-*) ou Font Awesome (fas fa-*)</small>
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Cor</label>
                            <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" 
                                   id="color" name="color" value="{{ old('color', '#0088cc') }}">
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @else
                            <input type="hidden" name="icon" value="{{ $templates[$selectedTemplate]['icon'] }}">
                            <input type="hidden" name="color" value="{{ $templates[$selectedTemplate]['color'] }}">
                        @endif

                        <div class="col-md-6 mb-3">
                            <label for="leaders" class="form-label">Líderes</label>
                            <select class="form-select @error('leaders') is-invalid @enderror" 
                                    id="leaders" name="leaders[]" multiple size="5">
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}" {{ in_array($member->id, old('leaders', [])) ? 'selected' : '' }}>
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
                                <option value="ativo" {{ old('status', 'ativo') == 'ativo' ? 'selected' : '' }}>Ativo</option>
                                <option value="arquivado" {{ old('status') == 'arquivado' ? 'selected' : '' }}>Arquivado</option>
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
                                <p class="text-muted text-center mb-0" id="noParticipantsText">*Sem participantes</p>
                                <div id="participantsList"></div>
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
                                    <div class="alert alert-info mb-2" style="background-color: #e9ecef; border: none;">
                                        <small><strong>Líder</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="membersInput" value="">
                    <div id="rolesInputs"></div>

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

<!-- Modal para adicionar participante -->
<div class="modal fade" id="participantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Participante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select class="form-select" id="participantSelect">
                    <option value="">Selecione um membro...</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" data-name="{{ $member->name }}">
                            {{ $member->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmParticipantBtn">Adicionar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectedMembers = [];
    let roleCounter = 0;

    // Adicionar participante
    document.getElementById('addParticipantBtn').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('participantModal'));
        modal.show();
    });

    document.getElementById('confirmParticipantBtn').addEventListener('click', function() {
        const select = document.getElementById('participantSelect');
        const memberId = select.value;
        const memberName = select.options[select.selectedIndex].dataset.name;

        const id = parseInt(memberId);
        if (memberId && id && !selectedMembers.includes(id)) {
            selectedMembers.push(id);
            updateParticipantsList();
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('participantModal'));
            modal.hide();
            select.value = '';
        }
    });

    function updateParticipantsList() {
        const container = document.getElementById('participantsList');
        const noParticipants = document.getElementById('noParticipantsText');
        
        if (selectedMembers.length === 0) {
            container.innerHTML = '';
            noParticipants.style.display = 'block';
            document.getElementById('membersInput').value = '';
            return;
        }

        noParticipants.style.display = 'none';
        container.innerHTML = selectedMembers.map((memberId, index) => {
            const select = document.getElementById('participantSelect');
            const option = select.querySelector(`option[value="${memberId}"]`);
            const name = option ? option.dataset.name : 'Membro ' + (index + 1);
            
            return `
                <div class="alert alert-light mb-2 d-flex justify-content-between align-items-center" id="participant-${memberId}">
                    <span>${name}</span>
                    <button type="button" class="btn btn-sm btn-link text-danger" onclick="removeParticipant('${memberId}')">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            `;
        }).join('');
    }

    function removeParticipant(memberId) {
        const id = parseInt(memberId);
        selectedMembers = selectedMembers.filter(mid => mid != id && mid !== id);
        updateParticipantsList();
    }

    // Adicionar cargo/função
    document.getElementById('addRoleBtn').addEventListener('click', function() {
        roleCounter++;
        const rolesList = document.getElementById('rolesList');
        const rolesInputs = document.getElementById('rolesInputs');
        
        const roleHtml = `
            <div class="alert alert-light mb-2" id="role-${roleCounter}">
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-sm" 
                               name="roles[${roleCounter}][name]" 
                               placeholder="Nome do cargo" required>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeRole(${roleCounter})">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                <textarea class="form-control form-control-sm mt-2" 
                          name="roles[${roleCounter}][description]" 
                          placeholder="Descrição (opcional)" rows="2"></textarea>
            </div>
        `;
        
        rolesList.insertAdjacentHTML('beforeend', roleHtml);
    });

    function removeRole(id) {
        document.getElementById(`role-${id}`).remove();
    }

    // Validação do formulário
    document.getElementById('departmentForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const validationMsg = document.getElementById('validationMessage');
        
        // Garante que members seja sempre um array válido
        const membersInput = document.getElementById('membersInput');
        if (selectedMembers.length === 0) {
            // Se não houver membros, remove o campo para não validar
            membersInput.removeAttribute('name');
            membersInput.value = '';
        } else {
            // Se houver membros, garante que o campo tenha o name e valor correto
            membersInput.setAttribute('name', 'members[]');
            // Cria inputs hidden individuais para cada membro (formato array do Laravel)
            const form = document.getElementById('departmentForm');
            // Remove inputs anteriores se existirem
            document.querySelectorAll('input[name^="members"]').forEach(input => {
                if (input !== membersInput) input.remove();
            });
            
            // Cria um input hidden para cada membro
            selectedMembers.forEach(memberId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'members[]';
                input.value = memberId;
                form.appendChild(input);
            });
            membersInput.removeAttribute('name');
        }
        
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

