@once
@push('styles')
<style>
.fr-page,
.fr-filters-card {
    --fr-text: #2E353E;
    --fr-muted: #5c6570;
    --fr-border: #E4E8ED;
    --fr-primary: #0088CC;
    --fr-bg: #FFFFFF;
}

.fr-page { min-width: 0; }

.fr-page,
.fr-page .form-label,
.fr-page .form-control,
.fr-page .form-select,
.fr-page .btn,
.fr-page .table {
    font-size: 0.9375rem;
}

.fr-page,
.fr-page .form-label,
.fr-page .form-control,
.fr-page .form-select {
    color: var(--fr-text);
}

.fr-page .form-label {
    font-weight: 600;
    font-size: 0.8125rem;
    letter-spacing: .01em;
    color: var(--fr-text);
    margin-bottom: .35rem;
}

.fr-page .form-control,
.fr-page .form-select,
.fr-page .input-group-text {
    min-height: 2.5rem;
    font-size: 0.9375rem;
}

.fr-page .text-muted,
.fr-page small { color: var(--fr-muted) !important; font-size: 0.8125rem; }

.fr-card {
    background: var(--fr-bg);
    border: 1px solid var(--fr-border);
    border-radius: 12px;
    box-shadow: none;
}

.fr-card > .card-body { padding: 1rem 1.1rem; }

/* Sidebar -------------------------------------------------------------- */
.fr-sidebar .fr-nav-toggle {
    display: none;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    border: 1px solid var(--fr-border);
    background: #fff;
    border-radius: 10px;
    padding: .7rem 1rem;
    font-weight: 600;
    font-size: 0.9375rem;
    color: var(--fr-text);
}
.fr-nav-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin: 0 0 .85rem;
    padding-bottom: .55rem;
    border-bottom: 2px solid var(--fr-primary);
}
.fr-nav-group { margin-bottom: 1rem; }
.fr-nav-group:last-child { margin-bottom: 0; }
.fr-nav-group__label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: .4rem;
}
.fr-nav-link {
    display: block;
    padding: .45rem .7rem;
    border-radius: 8px;
    font-size: 0.875rem;
    line-height: 1.3;
    color: var(--fr-text);
    text-decoration: none;
    background: transparent;
}
.fr-nav-link:hover { background: #F4F7FA; color: var(--fr-primary); }
.fr-nav-link.is-active {
    background: var(--fr-primary);
    color: #fff;
    font-weight: 600;
}

/* Filtros -------------------------------------------------------------- */
.fr-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .85rem 1rem;
    align-items: end;
}
.fr-filters__period { grid-column: span 2; min-width: 0; }
.fr-filters__wide { grid-column: span 2; min-width: 0; }
.fr-period-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    align-items: center;
    margin-bottom: .5rem;
}
.fr-period-modes {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    min-width: 0;
}
.fr-period-modes .btn,
.fr-period-shortcuts .btn {
    font-size: 0.75rem;
    font-weight: 600;
    padding: .35rem .65rem;
    min-height: 0;
    line-height: 1.3;
    white-space: nowrap;
    flex: 0 0 auto;
}
.fr-period-modes .btn.is-active {
    background: #0088CC;
    background: var(--fr-primary, #0088CC);
    border-color: #0088CC;
    border-color: var(--fr-primary, #0088CC);
    color: #fff;
}
.fr-period-shortcuts { display: flex; flex-wrap: wrap; gap: .35rem; }
.fr-filters--compact { grid-template-columns: minmax(140px, 220px); }
.fr-filters__actions {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
}
.fr-period {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: .4rem;
    align-items: center;
}
.fr-period__sep { color: var(--fr-muted); font-size: 0.8rem; white-space: nowrap; }

.fr-pills { display: flex; flex-wrap: wrap; gap: .4rem; }
.fr-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    margin: 0;
    padding: .4rem .7rem;
    border: 1px solid var(--fr-border);
    border-radius: 999px;
    background: #fff;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
}
.fr-pill input { width: 1rem; height: 1rem; margin: 0; accent-color: var(--fr-primary); flex-shrink: 0; }
.fr-pill.is-checked,
.fr-pill:has(input:checked) {
    border-color: var(--fr-primary);
    background: rgba(0, 136, 204, .08);
    color: var(--fr-primary);
}

.fr-logo { height: 44px; width: auto; flex-shrink: 0; }

.fr-page .table-responsive { -webkit-overflow-scrolling: touch; }
.fr-page .table td { word-break: break-word; }
.fr-page .apexcharts-canvas { max-width: 100% !important; }

body:has(.fr-page) .adelss-module-nav__subtitle { max-width: 40rem; }

/* Cabeçalho do relatório ---------------------------------------------- */
.fr-report-head {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: .75rem 1rem;
    margin-bottom: 1.25rem;
}
.fr-report-head h5,
.fr-report-head h6 { font-size: 1.05rem; font-weight: 700; color: var(--fr-text); }
.fr-report-head p { font-size: 0.875rem; }
.fr-report-head__actions { display: flex; flex-wrap: wrap; gap: .5rem; }

.fr-table-toolbar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: .65rem;
    margin-bottom: 1rem;
}
.fr-table-toolbar__actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    align-items: center;
}
.fr-table-toolbar .form-control,
.fr-table-toolbar .form-select { min-width: 0; }

.fr-page .table { font-size: 0.875rem; }
.fr-page .table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .03em; color: var(--fr-muted); white-space: nowrap; }
.fr-page .table td { vertical-align: middle; }

.fr-summary h5 { font-size: 1rem; }
.fr-summary .table { font-size: 0.875rem; }

@media (max-width: 991.98px) {
    .fr-sidebar { margin-bottom: 1rem; }
    .fr-sidebar .fr-nav-toggle { display: flex; }
    .fr-sidebar .fr-nav-card { display: none; margin-top: .65rem; }
    .fr-sidebar.is-open .fr-nav-card { display: block; }
    .fr-nav-group { margin-bottom: .75rem; }
}

@media (max-width: 767.98px) {
    body:has(.fr-page) .adelss-module-nav { margin-bottom: 1rem; }
    body:has(.fr-page) .adelss-module-nav__title { font-size: 1.2rem; }
    body:has(.fr-page) .adelss-module-nav__subtitle { display: none; }

    .fr-page,
    .fr-page .form-control,
    .fr-page .form-select,
    .fr-page .btn { font-size: 1rem; }

    .fr-page .form-label { font-size: 0.875rem; }
    .fr-page .form-control,
    .fr-page .form-select { min-height: 2.75rem; }

    .fr-filters,
    .fr-filters__period,
    .fr-filters__wide { grid-template-columns: 1fr; grid-column: 1; }

    .fr-period { grid-template-columns: 1fr; }
    .fr-period__sep { display: none; }
    .fr-period-toolbar { flex-direction: column; align-items: stretch; }
    .fr-period-modes { width: 100%; display: flex; flex-wrap: wrap; }
    .fr-period-modes .btn { flex: 0 0 auto; }
    .fr-period-shortcuts .btn { flex: 1 1 auto; }

    .fr-filters__actions { width: 100%; }
    .fr-filters__actions .btn { flex: 1 1 auto; min-height: 2.75rem; }

    .fr-report-head { flex-direction: column; }
    .fr-report-head__actions,
    .fr-report-head .btn { width: 100%; }
    .fr-logo { height: 36px; }

    .fr-table-toolbar,
    .fr-table-toolbar__actions { width: 100%; }
    .fr-table-toolbar__actions .form-control,
    .fr-table-toolbar__actions .form-select,
    .fr-table-toolbar__actions .btn { flex: 1 1 100%; width: 100% !important; }

    .fr-page .table { font-size: 0.9375rem; }
    .fr-page .table th { font-size: 0.7rem; }
    .fr-summary h5 { font-size: 1rem; }
    .fr-summary h5 .float-end { float: none !important; display: block; margin-top: .35rem; }

    .fr-toolbar-stubs { display: none !important; }
}

@media print {
    .fr-sidebar,
    .fr-filters-card,
    .adelss-module-nav,
    .fr-table-toolbar,
    .btn { display: none !important; }
    .fr-main { width: 100% !important; max-width: 100% !important; flex: 0 0 100%; }
}
</style>
@endpush
@endonce
