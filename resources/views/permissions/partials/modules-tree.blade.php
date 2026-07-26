@php
    /** @var \Illuminate\Support\Collection $modules */
    /** @var array $assignedPermissions */
    $isRole = ($mode ?? 'member') === 'role';
    $moduleCbClass = $isRole ? 'module-checkbox-role' : 'module-checkbox';
    $groupCbClass = $isRole ? 'group-checkbox-role' : 'group-checkbox';
    $actionCbClass = $isRole ? 'action-checkbox-role' : 'action-checkbox';
    $idPrefix = $isRole ? 'role_' : '';
    $collapsePrefix = $isRole ? 'roleModuleCollapse' : 'moduleCollapse';
    $assigned = collect($assignedPermissions ?? []);

    $moduleIcons = [
        'Membros' => 'bx bx-user',
        'PGIs' => 'bx bx-group',
        'Ensino' => 'bx bx-book-reader',
        'Financeiro' => 'bx bx-dollar',
        'Agenda' => 'bx bx-calendar',
        'Serviço' => 'bx bx-cog',
        'Moriah' => 'fa-solid fa-music',
        'Notificações' => 'bx bx-bell',
        'Discipulado' => 'bx bx-group',
        'Rifas' => 'bx bx-grid-alt',
        'Página Principal' => 'bx bx-globe',
        'Cultos' => 'bx bx-church',
        'Mídia' => 'bx bx-images',
    ];

    $categories = [
        'Administração' => ['Membros', 'Página Principal'],
        'Pastoral' => ['PGIs', 'Discipulado', 'Cultos', 'Serviço', 'Moriah'],
        'Comunicação' => ['Notificações', 'Mídia', 'Agenda'],
        'Financeiro' => ['Financeiro', 'Rifas'],
        'Conteúdo' => ['Ensino'],
    ];

    $modulesByKey = $modules->keyBy('module');
    $orderedGroups = [];
    foreach ($categories as $catLabel => $keys) {
        $items = collect($keys)->map(fn ($k) => $modulesByKey->get($k))->filter();
        if ($items->isNotEmpty()) {
            $orderedGroups[$catLabel] = $items;
        }
    }
    $categorizedKeys = collect($categories)->flatten()->all();
    $others = $modules->reject(fn ($m) => in_array($m->module, $categorizedKeys, true));
    if ($others->isNotEmpty()) {
        $orderedGroups['Outros'] = $others;
    }
@endphp

<div class="perms-toolbar mb-3">
    <div class="perms-search flex-grow-1">
        <i class="bx bx-search"></i>
        <input type="search"
               class="form-control form-control-sm perms-search-input"
               placeholder="Buscar módulo, recurso ou ação..."
               data-perms-search
               autocomplete="off">
    </div>
    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-secondary" data-perms-expand-all>
            <i class="bx bx-expand-alt"></i> Expandir tudo
        </button>
        <button type="button" class="btn btn-outline-secondary" data-perms-collapse-all>
            <i class="bx bx-collapse-alt"></i> Recolher tudo
        </button>
    </div>
</div>

<div class="perms-tree" data-perms-tree="{{ $isRole ? 'role' : 'member' }}">
    @foreach($orderedGroups as $categoryLabel => $categoryModules)
        <section class="perms-category" data-perms-category="{{ $categoryLabel }}">
            <h3 class="perms-category__title">{{ $categoryLabel }}</h3>

            @foreach($categoryModules as $module)
                @php
                    $allActionIds = $module->children->flatMap(fn ($g) => ($g->children ?? collect())->pluck('id'));
                    $totalActions = $allActionIds->count();
                    $checkedActions = $allActionIds->filter(fn ($id) => $assigned->contains($id))->count();
                    $badgeState = $checkedActions === 0 ? 'none' : ($checkedActions >= $totalActions && $totalActions > 0 ? 'all' : 'partial');
                    $icon = $moduleIcons[$module->module] ?? 'bx bx-cube';
                    $title = preg_replace('/^Módulo\s+/u', '', $module->name);
                    $searchBlob = mb_strtolower($module->name . ' ' . $module->module . ' ' . $module->children->pluck('name')->implode(' ') . ' ' . $module->children->flatMap(fn ($g) => ($g->children ?? collect())->pluck('name'))->implode(' '));
                @endphp

                <div class="perms-module"
                     data-perms-module
                     data-module-id="{{ $module->id }}"
                     data-search="{{ e($searchBlob) }}">
                    <div class="perms-module__header">
                        <div class="perms-module__main">
                            <span class="perms-module__icon"><i class="{{ $icon }}"></i></span>
                            <div class="min-w-0">
                                <div class="perms-module__title-row">
                                    <span class="perms-module__name">{{ $title }}</span>
                                    <span class="perms-badge perms-badge--{{ $badgeState }}"
                                          data-perms-badge
                                          data-total="{{ $totalActions }}">
                                        {{ $checkedActions }}/{{ $totalActions }}
                                    </span>
                                </div>
                                <div class="form-check perms-module__select-all mb-0">
                                    <input class="form-check-input {{ $moduleCbClass }}"
                                           type="checkbox"
                                           id="{{ $idPrefix }}module_{{ $module->id }}"
                                           data-module-id="{{ $module->id }}">
                                    <label class="form-check-label" for="{{ $idPrefix }}module_{{ $module->id }}">
                                        Marcar todas as permissões deste módulo
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary perms-module__toggle"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#{{ $collapsePrefix }}{{ $module->id }}"
                                aria-expanded="false"
                                aria-controls="{{ $collapsePrefix }}{{ $module->id }}">
                            <i class="bx bx-chevron-down"></i>
                        </button>
                    </div>

                    <div class="collapse perms-module__body" id="{{ $collapsePrefix }}{{ $module->id }}">
                        <div class="perms-module__content">
                            @foreach($module->children as $group)
                                @php
                                    $groupActions = $group->children ?? collect();
                                    $groupSearch = mb_strtolower($group->name . ' ' . $groupActions->pluck('name')->implode(' '));
                                @endphp
                                <div class="perms-resource"
                                     data-perms-resource
                                     data-search="{{ e($groupSearch) }}">
                                    <div class="perms-resource__head">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input {{ $groupCbClass }}"
                                                   type="checkbox"
                                                   id="{{ $idPrefix }}group_{{ $group->id }}"
                                                   data-group-id="{{ $group->id }}"
                                                   data-module-id="{{ $module->id }}">
                                            <label class="form-check-label fw-semibold" for="{{ $idPrefix }}group_{{ $group->id }}">
                                                {{ $group->name }}
                                            </label>
                                        </div>
                                        <span class="perms-resource__hint text-muted">Marcar tudo</span>
                                    </div>

                                    @if($groupActions->count() > 0)
                                        <div class="perms-actions">
                                            @foreach($groupActions as $action)
                                                <div class="form-check perms-action"
                                                     data-perms-action
                                                     data-search="{{ e(mb_strtolower($action->name)) }}">
                                                    <input class="form-check-input {{ $actionCbClass }}"
                                                           type="checkbox"
                                                           name="permissions[]"
                                                           id="{{ $idPrefix }}perm_{{ $action->id }}"
                                                           value="{{ $action->id }}"
                                                           data-group-id="{{ $group->id }}"
                                                           data-module-id="{{ $module->id }}"
                                                           {{ $assigned->contains($action->id) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="{{ $idPrefix }}perm_{{ $action->id }}">
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
        </section>
    @endforeach
</div>
