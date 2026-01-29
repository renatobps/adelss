@extends('layouts.porto')

@section('title', 'Gestão de Permissões')

@section('page-title', 'Gestão de Permissões')

@section('breadcrumbs')
    <li><span>Permissões</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-shield-quarter me-2"></i>Gestão de Permissões
                </h2>
                <p class="card-subtitle">Defina quais módulos e ações cada membro pode acessar.</p>
            </header>
            <div class="card-body">
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

                <div class="row mb-4">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('permissions.index') }}">
                            <div class="mb-3">
                                <label for="member_id" class="form-label">Selecione o membro</label>
                                <select name="member_id" id="member_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Escolha um membro --</option>
                                    @foreach($members as $m)
                                        <option value="{{ $m->id }}" {{ optional($selectedMember)->id == $m->id ? 'selected' : '' }}>
                                            {{ $m->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('permissions.index') }}">
                            <div class="mb-3">
                                <label for="role_id" class="form-label">Ou selecione uma função (cargo)</label>
                                <select name="role_id" id="role_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Escolha um cargo --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ optional($selectedRole)->id == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        @if($selectedMember)
                            <div class="alert alert-info mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-user-check fs-3 me-3"></i>
                                    <div>
                                        <h5 class="mb-1">{{ $selectedMember->name }}</h5>
                                        <p class="mb-0 small">
                                            E-mail: <strong>{{ $selectedMember->email ?? 'sem e-mail' }}</strong>
                                            @if($user)
                                                <span class="ms-3">Usuário do sistema: <strong>{{ $user->email }}</strong></span>
                                            @else
                                                <span class="ms-3 text-danger">Este membro ainda não possui usuário de acesso.</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @elseif($selectedRole)
                            <div class="alert alert-secondary mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-id-card fs-3 me-3"></i>
                                    <div>
                                        <h5 class="mb-1">{{ $selectedRole->name }}</h5>
                                        <p class="mb-0 small text-muted">
                                            Gerenciando permissões por função (cargo). Todos os usuários com este cargo herdarão essas permissões.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-muted mt-2">Selecione um membro ou uma função para gerenciar permissões.</p>
                        @endif
                    </div>
                </div>

                {{-- Debug: Verificar se módulos estão sendo carregados --}}
                @if($modules->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Atenção:</strong> Nenhum módulo foi encontrado. Execute o seeder de permissões:
                        <code>php artisan db:seed --class=PermissionSeeder</code>
                    </div>
                @endif

                {{-- Gestão por membro (usuário específico) --}}
                @if($selectedMember)
                    @if(!$user)
                        <div class="alert alert-warning">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Atenção:</strong> Este membro ainda não possui usuário de acesso ao sistema. 
                            Para configurar permissões, é necessário que o membro tenha um e-mail cadastrado e um usuário criado.
                        </div>
                    @else
                        <form method="POST" action="{{ route('permissions.update', $selectedMember) }}">
                            @csrf
                            @method('PUT')

                            {{-- Checkbox Super Administrador --}}
                            <div class="card mb-4 border-warning">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="is_admin" 
                                               id="is_admin" 
                                               value="1"
                                               {{ $user->is_admin ? 'checked' : '' }}
                                               onchange="toggleAdminPermissions(this)">
                                        <label class="form-check-label fw-bold text-warning" for="is_admin">
                                            SUPER ADMINISTRADOR
                                        </label>
                                        <small class="d-block text-muted mt-1">
                                            Usuário com acesso total ao sistema. Todas as permissões serão ignoradas.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div id="permissions-section" style="{{ $user->is_admin ? 'display:none;' : '' }}">
                                @foreach($modules as $module)
                            <div class="card mb-4 border-primary">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input module-checkbox" 
                                               type="checkbox" 
                                               id="module_{{ $module->id }}"
                                               data-module-id="{{ $module->id }}">
                                        <label class="form-check-label text-white fw-bold" for="module_{{ $module->id }}">
                                            {{ strtoupper($module->name) }}
                                        </label>
                                    </div>
                                    <button class="btn btn-sm btn-light" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#moduleCollapse{{ $module->id }}" 
                                            aria-expanded="false" 
                                            aria-controls="moduleCollapse{{ $module->id }}">
                                        <i class="bx bx-chevron-up"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="moduleCollapse{{ $module->id }}">
                                <div class="card-body">
                                    @php
                                        $moduleChildren = $module->children;
                                    @endphp
                                    
                                    @foreach($moduleChildren as $group)
                                        <div class="mb-3 ps-3 border-start border-2 border-primary">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input group-checkbox" 
                                                       type="checkbox" 
                                                       id="group_{{ $group->id }}"
                                                       data-group-id="{{ $group->id }}"
                                                       data-module-id="{{ $module->id }}">
                                                <label class="form-check-label fw-bold" for="group_{{ $group->id }}">
                                                    {{ $group->name }}
                                                </label>
                                            </div>
                                            
                                            @php
                                                $groupActions = $group->children ?? collect();
                                            @endphp
                                            
                                            @if($groupActions->count() > 0)
                                                <div class="ms-4 mt-2">
                                                    @foreach($groupActions as $action)
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input action-checkbox" 
                                                                   type="checkbox" 
                                                                   name="permissions[]"
                                                                   id="perm_{{ $action->id }}"
                                                                   value="{{ $action->id }}"
                                                                   data-group-id="{{ $group->id }}"
                                                                   data-module-id="{{ $module->id }}"
                                                                   {{ in_array($action->id, $assignedPermissions) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="perm_{{ $action->id }}">
                                                                {{ $action->name }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                </div>
                                </div>
                            </div>
                                @endforeach
                            </div>

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Salvar Permissões
                                </button>
                            </div>
                        </form>
                    @endif
                @endif

                {{-- Gestão por função/cargo --}}
                @if($selectedRole)
                    <hr class="my-4">
                    <h4 class="mb-3">
                        <i class="bx bx-id-card me-2"></i>Permissões por função: {{ $selectedRole->name }}
                    </h4>
                    <form method="POST" action="{{ route('permissions.update-role', $selectedRole) }}">
                        @csrf
                        @method('PUT')

                        @foreach($modules as $module)
                            <div class="card mb-4 border-secondary">
                                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                    <div class="form-check form-check-inline mb-0">
                                        <input class="form-check-input module-checkbox-role" 
                                               type="checkbox" 
                                               id="role_module_{{ $module->id }}"
                                               data-module-id="{{ $module->id }}">
                                        <label class="form-check-label text-white fw-bold" for="role_module_{{ $module->id }}">
                                            {{ strtoupper($module->name) }}
                                        </label>
                                    </div>
                                    <button class="btn btn-sm btn-light" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#roleModuleCollapse{{ $module->id }}" 
                                            aria-expanded="false" 
                                            aria-controls="roleModuleCollapse{{ $module->id }}">
                                        <i class="bx bx-chevron-up"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="roleModuleCollapse{{ $module->id }}">
                                <div class="card-body">
                                    @php
                                        $moduleChildren = $module->children;
                                    @endphp
                                    
                                    @foreach($moduleChildren as $group)
                                        <div class="mb-3 ps-3 border-start border-2 border-secondary">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input group-checkbox-role" 
                                                       type="checkbox" 
                                                       id="role_group_{{ $group->id }}"
                                                       data-group-id="{{ $group->id }}"
                                                       data-module-id="{{ $module->id }}">
                                                <label class="form-check-label fw-bold" for="role_group_{{ $group->id }}">
                                                    {{ $group->name }}
                                                </label>
                                            </div>
                                            
                                            @php
                                                $groupActions = $group->children ?? collect();
                                            @endphp
                                            
                                            @if($groupActions->count() > 0)
                                                <div class="ms-4 mt-2">
                                                    @foreach($groupActions as $action)
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input action-checkbox-role" 
                                                                   type="checkbox" 
                                                                   name="permissions[]"
                                                                   id="role_perm_{{ $action->id }}"
                                                                   value="{{ $action->id }}"
                                                                   data-group-id="{{ $group->id }}"
                                                                   data-module-id="{{ $module->id }}"
                                                                   {{ in_array($action->id, $assignedRolePermissions) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="role_perm_{{ $action->id }}">
                                                                {{ $action->name }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-secondary">
                                <i class="bx bx-save me-1"></i>Salvar Permissões da Função
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
// Função para mostrar/ocultar permissões quando Super Admin é marcado
function toggleAdminPermissions(checkbox) {
    const permissionsSection = document.getElementById('permissions-section');
    if (checkbox.checked) {
        permissionsSection.style.display = 'none';
        // Desmarcar todas as permissões quando vira super admin
        document.querySelectorAll('.action-checkbox').forEach(cb => cb.checked = false);
    } else {
        permissionsSection.style.display = 'block';
    }
}

(function() {
    // Função para atualizar estado dos checkboxes do módulo
    function updateModuleCheckbox(moduleId, isMember = true) {
        const prefix = isMember ? '' : 'role_';
        const moduleCheckbox = document.getElementById(prefix + 'module_' + moduleId);
        const groupCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].${prefix}group-checkbox`);
        const actionCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].${prefix}action-checkbox`);
        
        let allChecked = true;
        let someChecked = false;
        
        actionCheckboxes.forEach(cb => {
            if (cb.checked) someChecked = true;
            else allChecked = false;
        });
        
        if (moduleCheckbox) {
            moduleCheckbox.checked = allChecked;
            moduleCheckbox.indeterminate = someChecked && !allChecked;
        }
    }

    // Função para atualizar estado dos checkboxes do grupo
    function updateGroupCheckbox(groupId, moduleId, isMember = true) {
        const prefix = isMember ? '' : 'role_';
        const groupCheckbox = document.getElementById(prefix + 'group_' + groupId);
        const actionCheckboxes = document.querySelectorAll(`input[data-group-id="${groupId}"].${prefix}action-checkbox`);
        
        let allChecked = true;
        
        actionCheckboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });
        
        if (groupCheckbox) {
            groupCheckbox.checked = allChecked;
        }
        
        // Atualizar módulo também
        updateModuleCheckbox(moduleId, isMember);
    }

    // Event listeners para membros (usuários específicos)
    document.addEventListener('change', function(e) {
        // Checkbox do módulo
        if (e.target.classList.contains('module-checkbox')) {
            const moduleId = e.target.dataset.moduleId;
            const groupCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].group-checkbox`);
            const actionCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].action-checkbox`);
            
            groupCheckboxes.forEach(cb => cb.checked = e.target.checked);
            actionCheckboxes.forEach(cb => cb.checked = e.target.checked);
        }
        
        // Checkbox do grupo
        if (e.target.classList.contains('group-checkbox')) {
            const groupId = e.target.dataset.groupId;
            const moduleId = e.target.dataset.moduleId;
            const actionCheckboxes = document.querySelectorAll(`input[data-group-id="${groupId}"].action-checkbox`);
            
            actionCheckboxes.forEach(cb => cb.checked = e.target.checked);
            updateModuleCheckbox(moduleId, true);
        }
        
        // Checkbox de ação
        if (e.target.classList.contains('action-checkbox')) {
            const groupId = e.target.dataset.groupId;
            const moduleId = e.target.dataset.moduleId;
            updateGroupCheckbox(groupId, moduleId, true);
        }
    });

    // Event listeners para funções/cargos
    document.addEventListener('change', function(e) {
        // Checkbox do módulo
        if (e.target.classList.contains('module-checkbox-role')) {
            const moduleId = e.target.dataset.moduleId;
            const groupCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].group-checkbox-role`);
            const actionCheckboxes = document.querySelectorAll(`input[data-module-id="${moduleId}"].action-checkbox-role`);
            
            groupCheckboxes.forEach(cb => cb.checked = e.target.checked);
            actionCheckboxes.forEach(cb => cb.checked = e.target.checked);
        }
        
        // Checkbox do grupo
        if (e.target.classList.contains('group-checkbox-role')) {
            const groupId = e.target.dataset.groupId;
            const moduleId = e.target.dataset.moduleId;
            const actionCheckboxes = document.querySelectorAll(`input[data-group-id="${groupId}"].action-checkbox-role`);
            
            actionCheckboxes.forEach(cb => cb.checked = e.target.checked);
            updateModuleCheckbox(moduleId, false);
        }
        
        // Checkbox de ação
        if (e.target.classList.contains('action-checkbox-role')) {
            const groupId = e.target.dataset.groupId;
            const moduleId = e.target.dataset.moduleId;
            updateGroupCheckbox(groupId, moduleId, false);
        }
    });

    // Inicializar estados dos checkboxes ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        // Para membros
        const memberModules = document.querySelectorAll('.module-checkbox');
        memberModules.forEach(cb => {
            const moduleId = cb.dataset.moduleId;
            updateModuleCheckbox(moduleId, true);
        });

        // Para funções
        const roleModules = document.querySelectorAll('.module-checkbox-role');
        roleModules.forEach(cb => {
            const moduleId = cb.dataset.moduleId;
            updateModuleCheckbox(moduleId, false);
        });

        // Rotacionar ícone quando card é expandido/recolhido
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
            const targetId = button.getAttribute('data-bs-target');
            const targetElement = document.querySelector(targetId);
            const icon = button.querySelector('i');
            
            if (targetElement && icon) {
                // Inicializar ícone baseado no estado inicial (colapsado = seta para baixo)
                if (targetElement.classList.contains('show')) {
                    icon.classList.remove('bx-chevron-up');
                    icon.classList.add('bx-chevron-down');
                } else {
                    icon.classList.remove('bx-chevron-down');
                    icon.classList.add('bx-chevron-up');
                }
                
                targetElement.addEventListener('shown.bs.collapse', function() {
                    icon.classList.remove('bx-chevron-up');
                    icon.classList.add('bx-chevron-down');
                });
                targetElement.addEventListener('hidden.bs.collapse', function() {
                    icon.classList.remove('bx-chevron-down');
                    icon.classList.add('bx-chevron-up');
                });
            }
        });
    });
})();
</script>
@endpush
@endsection

