@include('financial.reports.partials.styles')

<div class="col-12 col-lg-3 fr-sidebar" id="frSidebar">
    <button type="button" class="fr-nav-toggle" id="frNavToggle" aria-expanded="false" aria-controls="frNavCard">
        <span>Relatórios</span>
        <i class="bx bx-chevron-down"></i>
    </button>

    <div class="card fr-card fr-nav-card" id="frNavCard">
        <div class="card-body">
            <h2 class="fr-nav-title">Relatórios</h2>

            <div class="fr-nav-group">
                <div class="fr-nav-group__label text-primary">Fluxo de caixa</div>
                <a href="{{ route('financial.reports.cash-flow.extract') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.cash-flow.extract') ? 'is-active' : '' }}">Extrato</a>
                <a href="{{ route('financial.reports.cash-flow.annual') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.cash-flow.annual') ? 'is-active' : '' }}">Resumo anual</a>
            </div>

            @if(auth()->user()?->can('financial.fechamento.view'))
            <div class="fr-nav-group">
                <div class="fr-nav-group__label text-primary">Fechamento de caixa</div>
                <a href="{{ route('financial.reports.cultos') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.cultos*') ? 'is-active' : '' }}">Dízimos e Ofertas</a>
                <a href="{{ route('financial.reports.weekly-closing') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.weekly-closing*') ? 'is-active' : '' }}">Fechamento Semanal</a>
                <a href="{{ route('financial.reports.matrix-demonstrativo') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.matrix-demonstrativo*') ? 'is-active' : '' }}">Demonstrativo da matriz</a>
            </div>
            @endif

            <div class="fr-nav-group">
                <div class="fr-nav-group__label text-primary">Documentos</div>
                <a href="{{ route('financial.reports.signatures') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.signatures*') ? 'is-active' : '' }}">Assinaturas</a>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('frSidebar');
    var toggle = document.getElementById('frNavToggle');
    if (sidebar && toggle) {
        toggle.addEventListener('click', function () {
            var open = sidebar.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }
});
</script>
@endpush
@endonce
@include('financial.reports.partials.filter-scripts')
