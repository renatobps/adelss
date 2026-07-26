@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewMembers = $isAdmin
        || $user?->hasPermission('members.index.view')
        || $user?->hasPermission('members.view')
        || $user?->hasPermission('members.index.manage');
    $canManagePermissions = $isAdmin;
    $canManageFields = $isAdmin || $user?->hasPermission('members.index.manage') || $user?->hasPermission('members.index.edit');

    // "Cargos" saiu deste submenu e ficou ao lado de "+ Novo membro" na listagem.
    // Na listagem (/members) o hub fica oculto para não ocupar espaço com abas redundantes.
    $hideOnIndex = request()->routeIs('members.index');

    $items = array_values(array_filter([
        $canViewMembers ? [
            'label' => 'Ver Todos',
            'icon' => 'bx bx-user',
            'url' => route('members.index'),
            'active' => request()->routeIs('members.index') || request()->routeIs('members.show') || request()->routeIs('members.create') || request()->routeIs('members.edit'),
        ] : null,
        $canManagePermissions ? [
            'label' => 'Permissões',
            'icon' => 'bx bx-lock-alt',
            'url' => route('permissions.index'),
            'active' => request()->routeIs('permissions.*'),
        ] : null,
        $canManageFields ? [
            'label' => 'Campos',
            'icon' => 'bx bx-slider-alt',
            'url' => route('members.custom-fields.index'),
            'active' => request()->routeIs('members.custom-fields.*'),
        ] : null,
    ]));
@endphp

@unless($hideOnIndex)
    @include('partials.module-nav', [
        'title' => 'Membros',
        'subtitle' => null,
        'items' => $items,
        'columns' => max(count($items), 2),
        'compact' => true,
    ])
@endunless
