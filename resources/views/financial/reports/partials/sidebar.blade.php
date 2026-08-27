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
                <a href="{{ route('financial.reports.cash-flow.revenues-expenses') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.cash-flow.revenues-expenses') ? 'is-active' : '' }}">Receitas / Despesas</a>
            </div>

            <div class="fr-nav-group">
                <div class="fr-nav-group__label text-success">Receitas</div>
                <a href="{{ route('financial.reports.revenues.daily-extract') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.revenues.daily-extract') ? 'is-active' : '' }}">Extrato diário</a>
                <a href="{{ route('financial.reports.revenues-expenses.by-category') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.revenues-expenses.by-category') ? 'is-active' : '' }}">Por categoria</a>
                <a href="{{ route('financial.reports.revenues.annual-summary') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.revenues.annual-summary') ? 'is-active' : '' }}">Resumo anual por categoria</a>
            </div>

            <div class="fr-nav-group">
                <div class="fr-nav-group__label text-danger">Despesas</div>
                <a href="{{ route('financial.reports.expenses.daily-extract') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.expenses.daily-extract') ? 'is-active' : '' }}">Extrato diário</a>
                <a href="{{ route('financial.reports.revenues-expenses.by-category') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.revenues-expenses.by-category') ? 'is-active' : '' }}">Por categoria</a>
                <a href="{{ route('financial.reports.expenses.annual-summary') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.expenses.annual-summary') ? 'is-active' : '' }}">Resumo anual por categoria</a>
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

    document.querySelectorAll('.fr-pill').forEach(function (pill) {
        var input = pill.querySelector('input');
        if (!input) return;
        var sync = function () { pill.classList.toggle('is-checked', input.checked); };
        input.addEventListener('change', sync);
        sync();
    });
});
</script>
@endpush
@endonce
