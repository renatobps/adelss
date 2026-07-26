@php
    $canSettings = auth()->user()?->can('manageSettings', \App\Models\ServiceReport::class);
    $showNewButton = request()->routeIs('cultos.index')
        && auth()->user()?->can('create', \App\Models\ServiceReport::class);

    $items = array_values(array_filter([
        [
            'label' => 'Relatórios',
            'icon' => 'bx bx-file',
            'url' => route('cultos.index'),
            'active' => request()->routeIs('cultos.index') || request()->routeIs('cultos.create') || request()->routeIs('cultos.edit') || request()->routeIs('cultos.show') || request()->routeIs('cultos.store') || request()->routeIs('cultos.update'),
        ],
        [
            'label' => 'Análises',
            'icon' => 'bx bx-bar-chart-alt-2',
            'url' => route('cultos.analyses'),
            'active' => request()->routeIs('cultos.analyses'),
        ],
        [
            'label' => 'Alertas',
            'icon' => 'bx bx-bell',
            'url' => route('cultos.alerts'),
            'active' => request()->routeIs('cultos.alerts'),
        ],
        $canSettings ? [
            'label' => 'Configurações',
            'icon' => 'bx bx-slider-alt',
            'url' => route('cultos.settings.edit'),
            'active' => request()->routeIs('cultos.settings.*'),
        ] : null,
    ]));

    $actions = $showNewButton
        ? '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newServiceReportModal"><i class="bx bx-plus"></i> Novo Relatório</button>'
        : null;
@endphp

@include('partials.module-nav', [
    'title' => 'Relatórios de Culto',
    'subtitle' => 'Gerencie os relatórios pós-culto da sua igreja',
    'items' => $items,
    'columns' => 4,
    'actions' => $actions,
])
