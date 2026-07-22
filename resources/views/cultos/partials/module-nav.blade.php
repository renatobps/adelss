@php
    $active = $active ?? 'index';
@endphp
<div class="cultos-module-nav mb-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
        <div>
            <h1 class="cultos-module-nav__title mb-0">Relatórios de Culto</h1>
            <p class="text-muted mb-0">Gerencie os relatórios pós-culto da sua igreja</p>
        </div>
        @if(($active === 'index') && auth()->user()?->can('create', \App\Models\ServiceReport::class))
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newServiceReportModal">
                <i class="bx bx-plus"></i> Novo Relatório
            </button>
        @endif
    </div>

    <div class="cultos-module-nav__grid">
        <a href="{{ route('cultos.index') }}" class="cultos-module-nav__item {{ $active === 'index' ? 'is-active' : '' }}">
            <i class="bx bx-file"></i>
            <span>Relatórios</span>
        </a>
        <a href="{{ route('cultos.analyses') }}" class="cultos-module-nav__item {{ $active === 'analyses' ? 'is-active' : '' }}">
            <i class="bx bx-bar-chart-alt-2"></i>
            <span>Análises</span>
        </a>
        <a href="{{ route('cultos.alerts') }}" class="cultos-module-nav__item {{ $active === 'alerts' ? 'is-active' : '' }}">
            <i class="bx bx-bell"></i>
            <span>Alertas</span>
        </a>
        @can('manageSettings', \App\Models\ServiceReport::class)
            <a href="{{ route('cultos.settings.edit') }}" class="cultos-module-nav__item {{ $active === 'settings' ? 'is-active' : '' }}">
                <i class="bx bx-slider-alt"></i>
                <span>Configurações</span>
            </a>
        @endcan
    </div>
</div>

@push('styles')
<style>
.cultos-module-nav__title { font-size: 1.6rem; font-weight: 700; color: #1f2937; }
.cultos-module-nav__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}
@media (min-width: 768px) {
    .cultos-module-nav__grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
.cultos-module-nav__item {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 0.45rem; min-height: 88px; padding: 0.9rem;
    text-decoration: none; color: #374151; background: #fff;
    border: 1px solid #e5e7eb; border-radius: 0.75rem;
}
.cultos-module-nav__item i { font-size: 1.45rem; color: #4b5563; }
.cultos-module-nav__item span { font-weight: 600; font-size: 0.9rem; }
.cultos-module-nav__item:hover { color: #1d4ed8; border-color: #bfdbfe; background: #f8fbff; text-decoration: none; }
.cultos-module-nav__item.is-active { color: #1d4ed8; background: #eff6ff; border-color: #93c5fd; }
.cultos-module-nav__item.is-active i { color: #2563eb; }
</style>
@endpush
