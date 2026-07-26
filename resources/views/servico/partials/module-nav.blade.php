@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewDepartments = $isAdmin || $user?->hasPermission('servico.departments.view') || $user?->hasPermission('servico.departments.manage');
    $canViewAreas = $isAdmin || $user?->hasPermission('servico.voluntarios.areas.view') || $user?->hasPermission('servico.voluntarios.areas.manage');
    $canViewEscalas = $isAdmin || $user?->hasPermission('servico.voluntarios.escalas.view') || $user?->hasPermission('servico.voluntarios.escalas.manage');
    $canViewHistorico = $isAdmin || $user?->hasPermission('servico.voluntarios.historico.view') || $user?->hasPermission('servico.voluntarios.historico.manage');
    $canViewRelatorios = $isAdmin || $user?->hasPermission('servico.voluntarios.relatorios.view') || $user?->hasPermission('servico.voluntarios.relatorios.manage');

    $items = array_values(array_filter([
        $canViewDepartments ? [
            'label' => 'Departamentos',
            'icon' => 'bx bx-building',
            'url' => route('departments.index'),
            'active' => request()->routeIs('departments.*'),
        ] : null,
        $canViewAreas ? [
            'label' => 'Áreas',
            'icon' => 'bx bx-category',
            'url' => route('voluntarios.areas.index'),
            'active' => request()->routeIs('voluntarios.areas.*'),
        ] : null,
        $canViewEscalas ? [
            'label' => 'Escalas',
            'icon' => 'bx bx-calendar',
            'url' => route('voluntarios.escalas-mensais.index'),
            'active' => request()->routeIs('voluntarios.escalas-mensais.*') || request()->routeIs('voluntarios.escalas.*'),
        ] : null,
        $canViewHistorico ? [
            'label' => 'Histórico',
            'icon' => 'bx bx-history',
            'url' => route('voluntarios.historico.index'),
            'active' => request()->routeIs('voluntarios.historico.*'),
        ] : null,
        $canViewRelatorios ? [
            'label' => 'Relatórios',
            'icon' => 'bx bx-bar-chart-alt-2',
            'url' => route('voluntarios.relatorios.dashboard'),
            'active' => request()->routeIs('voluntarios.relatorios.*'),
        ] : null,
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Serviço',
    'subtitle' => 'Departamentos, escalas e voluntários',
    'items' => $items,
    'columns' => 5,
])
