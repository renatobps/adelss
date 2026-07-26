@extends('layouts.porto')

@section('title', 'Gestão de Permissões')
@section('page-title', 'Gestão de Permissões')

@section('breadcrumbs')
    <li><span>Permissões</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card border-0 shadow-sm">
            <header class="card-header bg-white">
                <h2 class="card-title mb-1">
                    <i class="bx bx-shield-quarter me-2"></i>Gestão de Permissões
                </h2>
                <p class="card-subtitle mb-0 text-muted">Defina quais módulos e ações cada membro ou cargo pode acessar.</p>
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

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('permissions.index') }}">
                            <label for="member_id" class="form-label">Selecione o membro</label>
                            <select name="member_id" id="member_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Escolha um membro --</option>
                                @foreach($members as $m)
                                    <option value="{{ $m->id }}" @selected(optional($selectedMember)->id == $m->id)>
                                        {{ $m->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('permissions.index') }}">
                            <label for="role_id" class="form-label">Ou selecione uma função (cargo)</label>
                            <select name="role_id" id="role_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Escolha um cargo --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected(optional($selectedRole)->id == $role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="col-md-4">
                        @if($selectedMember)
                            <div class="alert alert-info mb-0 h-100">
                                <div class="d-flex align-items-start">
                                    <i class="bx bx-user-check fs-3 me-2"></i>
                                    <div>
                                        <h5 class="mb-1">{{ $selectedMember->name }}</h5>
                                        <p class="mb-0 small">
                                            {{ $selectedMember->email ?? 'sem e-mail' }}
                                            @unless($user)
                                                <span class="d-block text-danger mt-1">Este membro ainda não possui usuário de acesso.</span>
                                            @endunless
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @elseif($selectedRole)
                            <div class="alert alert-secondary mb-0 h-100">
                                <div class="d-flex align-items-start">
                                    <i class="bx bx-id-card fs-3 me-2"></i>
                                    <div>
                                        <h5 class="mb-1">{{ $selectedRole->name }}</h5>
                                        <p class="mb-0 small text-muted">Editando o template do cargo. Usuários com este cargo herdam essas permissões.</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-muted mt-2 mb-0">Selecione um membro ou uma função para gerenciar permissões.</p>
                        @endif
                    </div>
                </div>

                @if($modules->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        Nenhum módulo encontrado. Execute:
                        <code>php artisan db:seed --class=PermissionSeeder</code>
                    </div>
                @endif

                @if($selectedMember)
                    @if(!$user)
                        <div class="alert alert-warning">
                            <i class="bx bx-error-circle me-2"></i>
                            Este membro ainda não possui usuário de acesso. Cadastre um e-mail e salve o membro antes de configurar permissões.
                        </div>
                    @else
                        <form method="POST" action="{{ route('permissions.update', $selectedMember) }}" id="memberPermissionsForm" class="perms-form">
                            @csrf
                            @method('PUT')

                            <div class="perms-super-admin mb-4">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="is_admin"
                                           id="is_admin"
                                           value="1"
                                           {{ $user->is_admin ? 'checked' : '' }}
                                           onchange="toggleAdminPermissions(this)">
                                    <label class="form-check-label" for="is_admin">
                                        <span class="perms-super-admin__title">
                                            <i class="bx bx-error-alt"></i> Super Administrador
                                        </span>
                                        <small class="d-block">
                                            Opção de alto impacto: concede acesso total e ignora todas as permissões granulares abaixo.
                                        </small>
                                    </label>
                                </div>
                            </div>

                            <div id="permissions-section" style="{{ $user->is_admin ? 'display:none;' : '' }}">
                                <div class="perms-copy-role mb-3">
                                    <label class="form-label mb-1" for="copyFromRole">Copiar permissões de um cargo</label>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <select id="copyFromRole" class="form-select form-select-sm" style="max-width:280px">
                                            <option value="">-- Escolha um cargo como ponto de partida --</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnCopyFromRole">
                                            <i class="bx bx-copy"></i> Aplicar no formulário
                                        </button>
                                        <small class="text-muted">Pré-marca as checkboxes; salve para confirmar.</small>
                                    </div>
                                </div>

                                @include('permissions.partials.modules-tree', [
                                    'modules' => $modules,
                                    'assignedPermissions' => $assignedPermissions,
                                    'mode' => 'member',
                                ])
                            </div>

                            <div class="perms-sticky-save">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Salvar permissões
                                </button>
                            </div>
                        </form>
                    @endif
                @endif

                @if($selectedRole)
                    <form method="POST" action="{{ route('permissions.update-role', $selectedRole) }}" class="perms-form">
                        @csrf
                        @method('PUT')

                        @include('permissions.partials.modules-tree', [
                            'modules' => $modules,
                            'assignedPermissions' => $assignedRolePermissions,
                            'mode' => 'role',
                        ])

                        <div class="perms-sticky-save">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Salvar permissões da função
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.rolePermissionsMap = @json($rolePermissionsMap ?? new \stdClass());

function toggleAdminPermissions(checkbox) {
    const permissionsSection = document.getElementById('permissions-section');
    if (!permissionsSection) return;
    if (checkbox.checked) {
        permissionsSection.style.display = 'none';
        document.querySelectorAll('.action-checkbox').forEach(function (cb) { cb.checked = false; });
        document.querySelectorAll('[data-perms-tree="member"] [data-perms-badge]').forEach(function (badge) {
            badge.textContent = '0/' + (badge.getAttribute('data-total') || '0');
            badge.className = 'perms-badge perms-badge--none';
        });
    } else {
        permissionsSection.style.display = 'block';
    }
}

(function () {
    function actionSelector(moduleId, isMember) {
        var cls = isMember ? 'action-checkbox' : 'action-checkbox-role';
        return 'input[data-module-id="' + moduleId + '"].' + cls;
    }
    function groupSelector(moduleId, isMember) {
        var cls = isMember ? 'group-checkbox' : 'group-checkbox-role';
        return 'input[data-module-id="' + moduleId + '"].' + cls;
    }

    function updateModuleCheckbox(moduleId, isMember) {
        var prefix = isMember ? '' : 'role_';
        var moduleCheckbox = document.getElementById(prefix + 'module_' + moduleId);
        var actionCheckboxes = document.querySelectorAll(actionSelector(moduleId, isMember));
        var allChecked = actionCheckboxes.length > 0;
        var someChecked = false;

        actionCheckboxes.forEach(function (cb) {
            if (cb.checked) someChecked = true;
            else allChecked = false;
        });

        if (moduleCheckbox) {
            moduleCheckbox.checked = allChecked;
            moduleCheckbox.indeterminate = someChecked && !allChecked;
        }
        refreshModuleBadge(moduleId, isMember);
    }

    function updateGroupCheckbox(groupId, moduleId, isMember) {
        var prefix = isMember ? '' : 'role_';
        var groupCheckbox = document.getElementById(prefix + 'group_' + groupId);
        var cls = isMember ? 'action-checkbox' : 'action-checkbox-role';
        var actionCheckboxes = document.querySelectorAll('input[data-group-id="' + groupId + '"].' + cls);
        var allChecked = actionCheckboxes.length > 0;

        actionCheckboxes.forEach(function (cb) {
            if (!cb.checked) allChecked = false;
        });

        if (groupCheckbox) groupCheckbox.checked = allChecked;
        updateModuleCheckbox(moduleId, isMember);
    }

    function refreshModuleBadge(moduleId, isMember) {
        var tree = document.querySelector('[data-perms-tree="' + (isMember ? 'member' : 'role') + '"]');
        if (!tree) return;
        var moduleEl = tree.querySelector('[data-perms-module][data-module-id="' + moduleId + '"]');
        if (!moduleEl) return;
        var badge = moduleEl.querySelector('[data-perms-badge]');
        if (!badge) return;
        var actions = moduleEl.querySelectorAll(actionSelector(moduleId, isMember));
        var total = actions.length;
        var checked = 0;
        actions.forEach(function (cb) { if (cb.checked) checked++; });
        badge.textContent = checked + '/' + total;
        badge.setAttribute('data-total', String(total));
        var state = checked === 0 ? 'none' : (checked >= total && total > 0 ? 'all' : 'partial');
        badge.className = 'perms-badge perms-badge--' + state;
    }

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (t.classList.contains('module-checkbox') || t.classList.contains('module-checkbox-role')) {
            var isMember = t.classList.contains('module-checkbox');
            var moduleId = t.dataset.moduleId;
            document.querySelectorAll(groupSelector(moduleId, isMember)).forEach(function (cb) { cb.checked = t.checked; });
            document.querySelectorAll(actionSelector(moduleId, isMember)).forEach(function (cb) { cb.checked = t.checked; });
            refreshModuleBadge(moduleId, isMember);
        }
        if (t.classList.contains('group-checkbox') || t.classList.contains('group-checkbox-role')) {
            var isMemberG = t.classList.contains('group-checkbox');
            var groupId = t.dataset.groupId;
            var moduleIdG = t.dataset.moduleId;
            var cls = isMemberG ? 'action-checkbox' : 'action-checkbox-role';
            document.querySelectorAll('input[data-group-id="' + groupId + '"].' + cls).forEach(function (cb) {
                cb.checked = t.checked;
            });
            updateModuleCheckbox(moduleIdG, isMemberG);
        }
        if (t.classList.contains('action-checkbox') || t.classList.contains('action-checkbox-role')) {
            updateGroupCheckbox(t.dataset.groupId, t.dataset.moduleId, t.classList.contains('action-checkbox'));
        }
    });

    function setCollapseIcon(button, open) {
        var icon = button.querySelector('i');
        if (!icon) return;
        icon.classList.toggle('bx-chevron-up', open);
        icon.classList.toggle('bx-chevron-down', !open);
    }

    function expandAll(tree) {
        tree.querySelectorAll('.perms-module__body.collapse').forEach(function (el) {
            bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
        });
    }
    function collapseAll(tree) {
        tree.querySelectorAll('.perms-module__body.collapse').forEach(function (el) {
            bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
        });
    }

    function highlightText(root, term) {
        root.querySelectorAll('mark.perms-mark').forEach(function (m) {
            m.replaceWith(document.createTextNode(m.textContent));
        });
        if (!term) return;
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                if (!node.parentElement) return NodeFilter.FILTER_REJECT;
                if (node.parentElement.closest('script,style,mark')) return NodeFilter.FILTER_REJECT;
                if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
                return NodeFilter.FILTER_ACCEPT;
            }
        });
        var nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        var re = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        nodes.forEach(function (textNode) {
            if (!re.test(textNode.nodeValue)) return;
            var span = document.createElement('span');
            span.innerHTML = textNode.nodeValue.replace(re, '<mark class="perms-mark">$1</mark>');
            textNode.parentNode.replaceChild(span, textNode);
        });
    }

    function filterTree(tree, rawTerm) {
        var term = (rawTerm || '').trim().toLowerCase();
        var modules = tree.querySelectorAll('[data-perms-module]');
        modules.forEach(function (moduleEl) {
            var moduleMatch = !term || (moduleEl.getAttribute('data-search') || '').indexOf(term) !== -1;
            var resources = moduleEl.querySelectorAll('[data-perms-resource]');
            var anyResource = false;
            resources.forEach(function (res) {
                var resMatch = !term || (res.getAttribute('data-search') || '').indexOf(term) !== -1;
                var actions = res.querySelectorAll('[data-perms-action]');
                var anyAction = false;
                actions.forEach(function (act) {
                    var actMatch = !term || (act.getAttribute('data-search') || '').indexOf(term) !== -1 || resMatch || moduleMatch;
                    act.style.display = actMatch ? '' : 'none';
                    if (actMatch) anyAction = true;
                });
                var showRes = !term || resMatch || moduleMatch || anyAction;
                res.style.display = showRes ? '' : 'none';
                if (showRes) anyResource = true;
            });
            var showModule = !term || moduleMatch || anyResource;
            moduleEl.style.display = showModule ? '' : 'none';
            if (term && showModule) {
                var body = moduleEl.querySelector('.perms-module__body');
                if (body) bootstrap.Collapse.getOrCreateInstance(body, { toggle: false }).show();
            }
        });
        tree.querySelectorAll('[data-perms-category]').forEach(function (cat) {
            var visible = Array.from(cat.querySelectorAll('[data-perms-module]')).some(function (m) {
                return m.style.display !== 'none';
            });
            cat.style.display = visible ? '' : 'none';
        });
        highlightText(tree, term);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.module-checkbox').forEach(function (cb) {
            updateModuleCheckbox(cb.dataset.moduleId, true);
        });
        document.querySelectorAll('.module-checkbox-role').forEach(function (cb) {
            updateModuleCheckbox(cb.dataset.moduleId, false);
        });

        document.querySelectorAll('.perms-module__toggle').forEach(function (button) {
            var targetId = button.getAttribute('data-bs-target');
            var targetElement = document.querySelector(targetId);
            if (!targetElement) return;
            setCollapseIcon(button, targetElement.classList.contains('show'));
            targetElement.addEventListener('shown.bs.collapse', function () { setCollapseIcon(button, true); });
            targetElement.addEventListener('hidden.bs.collapse', function () { setCollapseIcon(button, false); });
        });

        document.querySelectorAll('[data-perms-expand-all]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tree = btn.closest('.perms-form, .card-body').querySelector('[data-perms-tree]');
                if (tree) expandAll(tree);
            });
        });
        document.querySelectorAll('[data-perms-collapse-all]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tree = btn.closest('.perms-form, .card-body').querySelector('[data-perms-tree]');
                if (tree) collapseAll(tree);
            });
        });
        document.querySelectorAll('[data-perms-search]').forEach(function (input) {
            input.addEventListener('input', function () {
                var tree = input.closest('.perms-form, .card-body').querySelector('[data-perms-tree]');
                if (tree) filterTree(tree, input.value);
            });
        });

        var copyBtn = document.getElementById('btnCopyFromRole');
        var copySelect = document.getElementById('copyFromRole');
        if (copyBtn && copySelect) {
            copyBtn.addEventListener('click', function () {
                var roleId = copySelect.value;
                if (!roleId) {
                    alert('Selecione um cargo para copiar as permissões.');
                    return;
                }
                var ids = (window.rolePermissionsMap && window.rolePermissionsMap[roleId]) || [];
                var idSet = {};
                ids.forEach(function (id) { idSet[String(id)] = true; });

                document.querySelectorAll('.action-checkbox').forEach(function (cb) {
                    cb.checked = !!idSet[String(cb.value)];
                });
                document.querySelectorAll('.module-checkbox').forEach(function (cb) {
                    updateModuleCheckbox(cb.dataset.moduleId, true);
                });
                var admin = document.getElementById('is_admin');
                if (admin && admin.checked) {
                    admin.checked = false;
                    toggleAdminPermissions(admin);
                }
            });
        }
    });
})();
</script>
@endpush
