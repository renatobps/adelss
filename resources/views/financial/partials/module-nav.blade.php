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
        ($canViewTransactions) ? [
            'label' => 'Resumo',
            'icon' => 'bx bx-bar-chart-alt-2',
            'route' => 'financial.summary',
            'active' => request()->routeIs('financial.summary'),
        ] : null,
        ($canViewTransactions) ? [
            'label' => 'Transações',
            'icon' => 'bx bx-dollar',
            'route' => 'financial.transactions.index',
            'active' => request()->routeIs('financial.transactions.*'),
        ] : null,
        ($canViewDespesas) ? [
            'label' => 'Despesas Fixas',
            'icon' => 'bx bx-refresh',
            'route' => 'financial.fixed-expenses.index',
            'active' => request()->routeIs('financial.fixed-expenses.*'),
        ] : null,
        ($canViewAccounts) ? [
            'label' => 'Contas e Caixas',
            'icon' => 'bx bx-wallet',
            'route' => 'financial.accounts.index',
            'active' => request()->routeIs('financial.accounts.*'),
        ] : null,
        ($canViewCategories) ? [
            'label' => 'Categorias',
            'icon' => 'bx bx-purchase-tag',
            'route' => 'financial.categories.index',
            'active' => request()->routeIs('financial.categories.*'),
        ] : null,
        ($canViewCostCenters) ? [
            'label' => 'Centros de Custo',
            'icon' => 'bx bx-bullseye',
            'route' => 'financial.cost-centers.index',
            'active' => request()->routeIs('financial.cost-centers.*'),
        ] : null,
        ($canViewContacts) ? [
            'label' => 'Fornecedores',
            'icon' => 'bx bx-store',
            'route' => 'financial.contacts.index',
            'active' => request()->routeIs('financial.contacts.*'),
        ] : null,
        ($canViewReports) ? [
            'label' => 'Relatórios',
            'icon' => 'bx bx-file',
            'route' => 'financial.reports.index',
            'active' => request()->routeIs('financial.reports.*'),
        ] : null,
        ($canViewTransactions) ? [
            'label' => 'Automações',
            'icon' => 'bx bxs-magic-wand',
            'route' => 'financial.automations.index',
            'active' => request()->routeIs('financial.automations.*'),
        ] : null,
    ]));
@endphp

@if(count($items) > 0)
<div class="financial-module-nav mb-4">
    <div class="financial-module-nav__intro mb-3">
        <h1 class="financial-module-nav__title">Financeiro</h1>
        <p class="financial-module-nav__subtitle mb-0">Gestão financeira completa</p>
    </div>

    <div class="financial-module-nav__grid">
        @foreach($items as $item)
            <a href="{{ route($item['route']) }}"
               class="financial-module-nav__item {{ !empty($item['active']) ? 'is-active' : '' }}">
                <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif
