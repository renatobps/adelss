@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewCampaigns = $isAdmin || $user?->hasPermission('eventos.campanhas.view') || $user?->hasPermission('eventos.campanhas.manage');

    $items = array_values(array_filter([
        $canViewCampaigns ? [
            'label' => 'Campanhas',
            'icon' => 'bx bx-donate-heart',
            'url' => route('eventos.campaigns.index'),
            'active' => request()->routeIs('eventos.campaigns.*') && !request()->routeIs('eventos.campaigns.expenses.*'),
        ] : null,
        $canViewCampaigns ? [
            'label' => 'Transações',
            'icon' => 'bx bx-transfer',
            'url' => route('eventos.expenses.all'),
            'active' => request()->routeIs('eventos.expenses.*') || request()->routeIs('eventos.campaigns.expenses.*'),
        ] : null,
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Gestor de Eventos',
    'subtitle' => 'Campanhas de arrecadação e transações dos eventos',
    'items' => $items,
    'columns' => 2,
])
