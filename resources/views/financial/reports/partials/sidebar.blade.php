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
            </div>

            @if(auth()->user()?->can('financial.fechamento.view'))
            <div class="fr-nav-group">
                <div class="fr-nav-group__label text-primary">Fechamento de caixa</div>
                <a href="{{ route('financial.reports.cultos') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.cultos*') ? 'is-active' : '' }}">Dízimos e Ofertas</a>
                <a href="{{ route('financial.reports.weekly-closing') }}"
                   class="fr-nav-link {{ request()->routeIs('financial.reports.weekly-closing*') ? 'is-active' : '' }}">Fechamento Semanal</a>
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

    document.querySelectorAll('.fr-pill').forEach(function (pill) {
        var input = pill.querySelector('input');
        if (!input) return;
        var sync = function () { pill.classList.toggle('is-checked', input.checked); };
        input.addEventListener('change', sync);
        sync();
    });

    var periodRoot = document.getElementById('frPeriod');
    if (periodRoot) {
        var startInput = document.getElementById('frStartDate');
        var endInput = document.getElementById('frEndDate');
        var monthInput = document.getElementById('frPeriodMonth');
        var yearSelect = document.getElementById('frPeriodYear');
        var mode = periodRoot.querySelector('.fr-period-modes .is-active')
            ? periodRoot.querySelector('.fr-period-modes .is-active').getAttribute('data-period-mode')
            : 'custom';

        function pad(n) { return String(n).padStart(2, '0'); }
        function ymd(y, m, d) { return y + '-' + pad(m) + '-' + pad(d); }
        function lastDay(y, m) { return new Date(y, m, 0).getDate(); }
        function setRange(from, to) {
            startInput.value = from;
            endInput.value = to;
        }
        function applyMonth(value) {
            if (!value) return;
            var parts = value.split('-');
            var y = parseInt(parts[0], 10);
            var m = parseInt(parts[1], 10);
            setRange(ymd(y, m, 1), ymd(y, m, lastDay(y, m)));
        }
        function applyYear(year) {
            var y = parseInt(year, 10);
            if (!y) return;
            setRange(ymd(y, 1, 1), ymd(y, 12, 31));
        }
        function showMode(next) {
            mode = next;
            periodRoot.querySelectorAll('[data-period-mode]').forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-period-mode') === next);
            });
            periodRoot.querySelectorAll('[data-period-panel]').forEach(function (panel) {
                panel.style.display = panel.getAttribute('data-period-panel') === next ? '' : 'none';
            });
            if (next === 'month') applyMonth(monthInput.value);
            if (next === 'year') applyYear(yearSelect.value);
        }

        periodRoot.querySelectorAll('[data-period-mode]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showMode(btn.getAttribute('data-period-mode'));
            });
        });
        if (monthInput) {
            monthInput.addEventListener('change', function () { applyMonth(monthInput.value); });
        }
        if (yearSelect) {
            yearSelect.addEventListener('change', function () { applyYear(yearSelect.value); });
        }
        periodRoot.querySelectorAll('[data-period-shortcut]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var now = new Date();
                var y = now.getFullYear();
                var m = now.getMonth() + 1;
                var kind = btn.getAttribute('data-period-shortcut');
                if (kind === 'this-month') {
                    monthInput.value = y + '-' + pad(m);
                    showMode('month');
                } else if (kind === 'last-month') {
                    var d = new Date(y, now.getMonth() - 1, 1);
                    monthInput.value = d.getFullYear() + '-' + pad(d.getMonth() + 1);
                    showMode('month');
                } else if (kind === 'this-year') {
                    yearSelect.value = String(y);
                    showMode('year');
                }
            });
        });

        var form = document.getElementById('filterForm');
        if (form) {
            form.addEventListener('submit', function () {
                if (mode === 'month') applyMonth(monthInput.value);
                if (mode === 'year') applyYear(yearSelect.value);
            });
        }
    }
});
</script>
@endpush
@endonce
