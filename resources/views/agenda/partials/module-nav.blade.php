@php
    $items = [
        [
            'label' => 'Calendário',
            'icon' => 'bx bx-calendar',
            'url' => route('agenda.calendario.index'),
            'active' => request()->routeIs('agenda.calendario.*') || request()->routeIs('agenda.events.*') || request()->routeIs('agenda.categories.*'),
        ],
        [
            'label' => 'Eventos',
            'icon' => 'bx bx-calendar-event',
            'url' => route('agenda.eventos.index'),
            'active' => request()->routeIs('agenda.eventos.*'),
        ],
    ];
@endphp

@include('partials.module-nav', [
    'title' => 'Agenda',
    'subtitle' => 'Calendário e eventos da igreja',
    'items' => $items,
    'columns' => 2,
])
