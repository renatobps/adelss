{{-- Menu interno da campanha: Visão geral | Transações | Prestação de contas | Lembretes --}}
@php
    $active = $active ?? 'visao';
    $tabUser = Auth::user();
    $tabCanEdit = ($tabUser?->is_admin ?? false)
        || $tabUser?->hasPermission('eventos.campanhas.manage')
        || $tabUser?->hasPermission('eventos.campanhas.edit');

    $tabs = array_values(array_filter([
        ['key' => 'visao', 'label' => 'Visão geral', 'icon' => 'bx bx-donate-heart', 'url' => route('eventos.campaigns.show', $campaign)],
        ['key' => 'gastos', 'label' => 'Transações', 'icon' => 'bx bx-transfer', 'url' => route('eventos.campaigns.expenses.index', $campaign)],
        ['key' => 'prestacao', 'label' => 'Prestação de contas', 'icon' => 'bx bx-bar-chart-alt-2', 'url' => route('eventos.campaigns.report', $campaign)],
        $tabCanEdit ? ['key' => 'lembretes', 'label' => 'Lembretes', 'icon' => 'bx bx-bell', 'url' => route('eventos.campaigns.reminders.edit', $campaign)] : null,
    ]));
@endphp

<ul class="nav nav-pills campaign-tabs mb-3 gap-1">
    @foreach($tabs as $tab)
        <li class="nav-item">
            <a class="nav-link py-1 px-3 {{ $active === $tab['key'] ? 'active' : '' }}" href="{{ $tab['url'] }}">
                <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
            </a>
        </li>
    @endforeach
</ul>
