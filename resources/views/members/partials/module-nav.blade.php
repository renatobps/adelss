@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewMembers = $isAdmin
        || $user?->hasPermission('members.index.view')
        || $user?->hasPermission('members.view')
        || $user?->hasPermission('members.index.manage');
    $canManageRoles = $isAdmin
        || $user?->hasPermission('members.roles.view')
        || $user?->hasPermission('members.roles.manage')
        || $user?->hasPermission('members.roles.create')
        || $user?->hasPermission('members.roles.edit');
    $canManagePermissions = $isAdmin;

    $items = array_values(array_filter([
        $canViewMembers ? [
            'label' => 'Ver Todos',
            'icon' => 'bx bx-user',
            'url' => route('members.index'),
            'active' => request()->routeIs('members.*') && !request()->routeIs('member-roles.*'),
        ] : null,
        $canManageRoles ? [
            'label' => 'Cargos',
            'icon' => 'bx bx-id-card',
            'url' => route('member-roles.index'),
            'active' => request()->routeIs('member-roles.*'),
        ] : null,
        $canManagePermissions ? [
            'label' => 'Permissões',
            'icon' => 'bx bx-lock-alt',
            'url' => route('permissions.index'),
            'active' => request()->routeIs('permissions.*'),
        ] : null,
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Membros',
    'subtitle' => 'Cadastro, cargos e permissões',
    'items' => $items,
    'columns' => 3,
])
