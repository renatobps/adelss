@php
    $items = [
        [
            'label' => 'Escalas',
            'icon' => 'bx bx-calendar-check',
            'url' => route('moriah.schedules.index'),
            'active' => request()->routeIs('moriah.schedules.*'),
        ],
        [
            'label' => 'Repertório',
            'icon' => 'bx bx-music',
            'url' => route('moriah.repertorio.index'),
            'active' => request()->routeIs('moriah.repertorio.*'),
        ],
        [
            'label' => 'Ministério',
            'icon' => 'bx bx-church',
            'url' => route('moriah.ministerio'),
            'active' => request()->routeIs('moriah.ministerio')
                || request()->routeIs('moriah.members.*')
                || request()->routeIs('moriah.funcoes.*')
                || request()->routeIs('moriah.banner.*')
                || request()->routeIs('moriah.logo.*'),
        ],
        [
            'label' => 'Indisponibilidades',
            'icon' => 'bx bx-calendar-x',
            'url' => route('moriah.unavailabilities.index'),
            'active' => request()->routeIs('moriah.unavailabilities.*'),
        ],
    ];
@endphp

@include('partials.module-nav', [
    'title' => 'Moriah',
    'subtitle' => 'Ministério de louvor e escalas',
    'items' => $items,
    'columns' => 4,
])
