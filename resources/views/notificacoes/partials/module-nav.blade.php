@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canView = $isAdmin || $user?->hasPermission('notificacoes.view') || $user?->hasPermission('notificacoes.manage');
    $canManage = $isAdmin || $user?->hasPermission('notificacoes.manage');

    $items = array_values(array_filter([
        $canView ? [
            'label' => 'Grupos',
            'icon' => 'bx bx-group',
            'route' => 'notificacoes.grupos.index',
            'active' => request()->routeIs('notificacoes.grupos.*'),
        ] : null,
        $canView ? [
            'label' => 'Enquetes',
            'icon' => 'bx bx-bar-chart-alt-2',
            'route' => 'notificacoes.enquetes.index',
            'active' => request()->routeIs('notificacoes.enquetes.*'),
        ] : null,
        $canView ? [
            'label' => 'Notificações',
            'icon' => 'bx bx-send',
            'route' => 'notificacoes.painel.index',
            'active' => request()->routeIs('notificacoes.painel.*'),
        ] : null,
        ($canView || $canManage) ? [
            'label' => 'Configuração WPP',
            'icon' => 'bx bxl-whatsapp',
            'route' => 'notificacoes.config.index',
            'active' => request()->routeIs('notificacoes.config.*'),
            'brand' => 'whatsapp',
        ] : null,
        $canView ? [
            'label' => 'Templates',
            'icon' => 'bx bx-file-blank',
            'route' => 'notificacoes.templates.index',
            'active' => request()->routeIs('notificacoes.templates.*'),
        ] : null,
    ]));
@endphp

@if(count($items) > 0)
<div class="notificacoes-module-nav mb-4">
    <div class="notificacoes-module-nav__intro mb-3">
        <h1 class="notificacoes-module-nav__title">Notificações</h1>
        <p class="notificacoes-module-nav__subtitle mb-0">WhatsApp, grupos, enquetes e templates</p>
    </div>

    <div class="notificacoes-module-nav__grid">
        @foreach($items as $item)
            <a href="{{ route($item['route']) }}"
               class="notificacoes-module-nav__item {{ !empty($item['active']) ? 'is-active' : '' }} {{ !empty($item['brand']) ? 'is-brand-'.$item['brand'] : '' }}">
                <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif
