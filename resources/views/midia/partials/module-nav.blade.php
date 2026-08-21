@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canFiles = $isAdmin || $user?->hasPermission('midia.arquivos.view');
    $canIg = $isAdmin || $user?->hasPermission('midia.instagram.view') || $user?->hasPermission('midia.whatsapp.schedule');
    $canSettings = $isAdmin
        || $user?->hasPermission('midia.configuracoes.manage')
        || $user?->hasPermission('midia.instagram.configuracoes.manage');
    $canForms = $isAdmin
        || $user?->hasPermission('midia.formularios.view')
        || $user?->hasPermission('midia.formularios.manage');

    $items = array_values(array_filter([
        $canFiles ? [
            'label' => 'Arquivos',
            'icon' => 'bx bx-folder',
            'url' => route('midia.index'),
            'active' => request()->routeIs('midia.index')
                || request()->routeIs('midia.upload')
                || request()->routeIs('midia.folders.*')
                || request()->routeIs('midia.download')
                || request()->routeIs('midia.preview')
                || request()->routeIs('midia.destroy')
                || request()->routeIs('midia.move')
                || request()->routeIs('midia.files.*')
                || request()->routeIs('midia.thumbnail'),
        ] : null,
        $canIg ? [
            'label' => 'Publicações',
            'icon' => 'bx bxl-instagram',
            'url' => route('midia.instagram.posts.index'),
            'active' => request()->routeIs('midia.instagram.posts.*') || request()->routeIs('midia.whatsapp.*'),
            'brand' => 'instagram',
        ] : null,
        $canForms ? [
            'label' => 'Formulários',
            'icon' => 'bx bx-list-check',
            'url' => route('midia.formularios.index'),
            'active' => request()->routeIs('midia.formularios.*'),
        ] : null,
        $canSettings ? [
            'label' => 'Configurações',
            'icon' => 'bx bx-cog',
            'url' => route('midia.settings'),
            'active' => request()->routeIs('midia.settings')
                || request()->routeIs('midia.google.*')
                || request()->routeIs('midia.instagram.redirect')
                || request()->routeIs('midia.instagram.callback')
                || request()->routeIs('midia.instagram.disconnect'),
        ] : null,
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Mídia',
    'subtitle' => 'Arquivos, publicações, formulários e configurações',
    'items' => $items,
    'columns' => 4,
])
