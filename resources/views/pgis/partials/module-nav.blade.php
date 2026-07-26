@php
    $items = [[
        'label' => 'Listar PGIs',
        'icon' => 'bx bx-group',
        'url' => route('pgis.index'),
        'active' => request()->routeIs('pgis.*'),
    ]];
@endphp

@include('partials.module-nav', [
    'title' => 'PGIs',
    'subtitle' => 'Pequenos grupos de integração',
    'items' => $items,
    'columns' => 3,
])
