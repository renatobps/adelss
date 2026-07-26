@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $can = fn (string $base) => $isAdmin
        || $user?->hasPermission($base . '.view')
        || $user?->hasPermission($base . '.manage');

    $items = array_values(array_filter([
        $can('discipleship.cycles') ? [
            'label' => 'Ciclos',
            'icon' => 'bx bx-calendar-alt',
            'url' => route('discipleship.cycles.index'),
            'active' => request()->routeIs('discipleship.cycles.*'),
        ] : null,
        $can('discipleship.members') ? [
            'label' => 'Membros',
            'icon' => 'bx bx-user',
            'url' => route('discipleship.members.index'),
            'active' => request()->routeIs('discipleship.members.*'),
        ] : null,
        $can('discipleship.meetings') ? [
            'label' => 'Encontros',
            'icon' => 'bx bx-calendar',
            'url' => route('discipleship.meetings.index'),
            'active' => request()->routeIs('discipleship.meetings.*'),
        ] : null,
        $can('discipleship.indicators') ? [
            'label' => 'Indicadores',
            'icon' => 'bx bx-bar-chart',
            'url' => route('discipleship.indicators.index'),
            'active' => request()->routeIs('discipleship.indicators.*'),
        ] : null,
        $can('discipleship.goals') ? [
            'label' => 'Propósitos',
            'icon' => 'bx bx-target-lock',
            'url' => route('discipleship.goals.index'),
            'active' => request()->routeIs('discipleship.goals.*'),
        ] : null,
        $can('discipleship.feedbacks') ? [
            'label' => 'Feedbacks',
            'icon' => 'bx bx-message',
            'url' => route('discipleship.feedbacks.index'),
            'active' => request()->routeIs('discipleship.feedbacks.*'),
        ] : null,
        [
            'label' => 'Dashboard',
            'icon' => 'bx bx-grid-alt',
            'url' => route('discipleship.dashboard.discipulador'),
            'active' => request()->routeIs('discipleship.dashboard.*'),
        ],
        [
            'label' => 'Ajuda',
            'icon' => 'bx bx-help-circle',
            'url' => route('discipleship.help'),
            'active' => request()->routeIs('discipleship.help'),
        ],
    ]));
@endphp

@include('partials.module-nav', [
    'title' => 'Discipulado',
    'subtitle' => 'Ciclos, encontros e acompanhamento',
    'items' => $items,
    'columns' => 4,
])