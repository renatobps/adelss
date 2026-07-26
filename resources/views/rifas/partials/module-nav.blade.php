@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewRifas = $isAdmin || $user?->hasPermission('rifas.index.view') || $user?->hasPermission('rifas.index.manage');
    $canManageSales = $isAdmin
        || $user?->hasPermission('rifas.sales.create')
        || $user?->hasPermission('rifas.sales.manage')
        || $user?->hasPermission('rifas.index.manage');
    $canViewReports = $isAdmin || $user?->hasPermission('rifas.reports.view') || $user?->hasPermission('rifas.reports.manage');

    $items = array_values(array_filter([
        $canViewRifas ? [
            'label' => 'Cadastro',
            'icon' => 'bx bx-list-ul',
            'url' => route('rifas.index'),
            'active' => request()->routeIs('rifas.index')
                || request()->routeIs('rifas.create')
                || request()->routeIs('rifas.edit')
                || request()->routeIs('rifas.show')
                || request()->routeIs('rifas.store')
                || request()->routeIs('rifas.update')
                || request()->routeIs('rifas.destroy')
                || request()->routeIs('rifas.cartelas.*')
                || request()->routeIs('rifas.sorteios.*'),
        ] : null,
        $canManageSales ? [
            'label' => 'Vendas',
            'icon' => 'bx bx-credit-card',
            'url' => route('rifas.index'),
            'active' => request()->routeIs('rifas.vendas.*') || request()->routeIs('rifas.numeros.*'),
        ] : null,
        $canViewReports ? [
            'label' => 'Relatórios',
            'icon' => 'bx bx-bar-chart-alt-2',
            'url' => route('rifas.relatorios.dashboard'),
            'active' => request()->routeIs('rifas.relatorios.*'),
        ] : null,
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Rifas',
    'subtitle' => 'Cadastro, vendas e relatórios',
    'items' => $items,
    'columns' => 3,
])
