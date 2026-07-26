@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewReceitas = $isAdmin || $user?->hasPermission('financial.receitas.view') || $user?->hasPermission('financial.receitas.manage');
    $canViewDespesas = $isAdmin || $user?->hasPermission('financial.despesas.view') || $user?->hasPermission('financial.despesas.manage');
    $canViewTransactions = $canViewReceitas || $canViewDespesas;
    $canViewCategories = $isAdmin || $user?->hasPermission('financial.categories.view') || $user?->hasPermission('financial.categories.manage');
    $canViewAccounts = $isAdmin || $user?->hasPermission('financial.accounts.view') || $user?->hasPermission('financial.accounts.manage');
    $canViewContacts = $isAdmin || $user?->hasPermission('financial.contacts.view') || $user?->hasPermission('financial.contacts.manage');
    $canViewCostCenters = $isAdmin || $user?->hasPermission('financial.cost-centers.view') || $user?->hasPermission('financial.cost-centers.manage');
    $canViewReports = $isAdmin || $user?->hasPermission('financial.reports.view') || $user?->hasPermission('financial.reports.manage');

    $items = array_values(array_filter([
        $canViewTransactions ? [
            'label' => 'Resumo',
            'icon' => 'bx bx-bar-chart-alt-2',
            'url' => route('financial.summary'),
            'active' => request()->routeIs('financial.summary'),
        ] : null,
        $canViewTransactions ? [
            'label' => 'Transações',
            'icon' => 'bx bx-dollar',
            'url' => route('financial.transactions.index'),
            'active' => request()->routeIs('financial.transactions.*'),
        ] : null,
        $canViewDespesas ? [
            'label' => 'Despesas Fixas',
            'icon' => 'bx bx-refresh',
            'url' => route('financial.fixed-expenses.index'),
            'active' => request()->routeIs('financial.fixed-expenses.*'),
        ] : null,
        $canViewAccounts ? [
            'label' => 'Contas e Caixas',
            'icon' => 'bx bx-wallet',
            'url' => route('financial.accounts.index'),
            'active' => request()->routeIs('financial.accounts.*'),
        ] : null,
        $canViewCategories ? [
            'label' => 'Categorias',
            'icon' => 'bx bx-purchase-tag',
            'url' => route('financial.categories.index'),
            'active' => request()->routeIs('financial.categories.*'),
        ] : null,
        $canViewCostCenters ? [
            'label' => 'Centros de Custo',
            'icon' => 'bx bx-bullseye',
            'url' => route('financial.cost-centers.index'),
            'active' => request()->routeIs('financial.cost-centers.*'),
        ] : null,
        $canViewContacts ? [
            'label' => 'Fornecedores',
            'icon' => 'bx bx-store',
            'url' => route('financial.contacts.index'),
            'active' => request()->routeIs('financial.contacts.*'),
        ] : null,
        $canViewReports ? [
            'label' => 'Relatórios',
            'icon' => 'bx bx-file',
            'url' => route('financial.reports.index'),
            'active' => request()->routeIs('financial.reports.*'),
        ] : null,
        $canViewTransactions ? [
            'label' => 'Automações',
            'icon' => 'bx bxs-magic-wand',
            'url' => route('financial.automations.index'),
            'active' => request()->routeIs('financial.automations.*'),
        ] : null,
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Financeiro',
    'subtitle' => 'Gestão financeira completa',
    'items' => $items,
    'columns' => min(8, max(count($items), 2)),
])
