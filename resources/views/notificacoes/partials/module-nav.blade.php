@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canView = $isAdmin || $user?->hasPermission('notificacoes.view') || $user?->hasPermission('notificacoes.manage');
    $canManage = $isAdmin || $user?->hasPermission('notificacoes.manage');

    $items = array_values(array_filter([
        $canView ? [
            'label' => 'Grupos',
            'icon' => 'bx bx-group',
            'url' => route('notificacoes.grupos.index'),
            'active' => request()->routeIs('notificacoes.grupos.*'),
        ] : null,
        $canView ? [
            'label' => 'Enquetes',
            'icon' => 'bx bx-bar-chart-alt-2',
            'url' => route('notificacoes.enquetes.index'),
            'active' => request()->routeIs('notificacoes.enquetes.*'),
        ] : null,
        $canView ? [
            'label' => 'Notificações',
            'icon' => 'bx bx-send',
            'url' => route('notificacoes.painel.index'),
            'active' => request()->routeIs('notificacoes.painel.*'),
        ] : null,
        ($canView || $canManage) ? [
            'label' => 'Configuração WPP',
            'icon' => 'bx bxl-whatsapp',
            'url' => route('notificacoes.config.index'),
            'active' => request()->routeIs('notificacoes.config.*'),
            'brand' => 'whatsapp',
        ] : null,
        $canView ? [
            'label' => 'Templates',
            'icon' => 'bx bx-file-blank',
            'url' => route('notificacoes.templates.index'),
            'active' => request()->routeIs('notificacoes.templates.*'),
        ] : null,
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Notificações',
    'subtitle' => 'WhatsApp, grupos, enquetes e templates',
    'items' => $items,
    'columns' => 5,
])
