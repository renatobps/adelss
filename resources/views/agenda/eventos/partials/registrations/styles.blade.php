<style>
.er-wrap { max-width: 1400px; margin-inline: auto; }

/* KPIs ------------------------------------------------------------------ */
.er-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: .6rem;
    margin-bottom: .85rem;
}
.er-kpi {
    display: block;
    border: 1px solid #EEF0F2;
    border-radius: 10px;
    background: #FFFFFF;
    padding: .7rem .85rem;
    text-decoration: none;
    color: inherit;
    transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}
a.er-kpi:hover { transform: translateY(-2px); box-shadow: 0 .35rem .9rem rgba(46, 53, 62, .12); }
.er-kpi.is-active { border-color: var(--er-kpi, #0088CC); box-shadow: inset 0 0 0 1px var(--er-kpi, #0088CC); }
.er-kpi-label { display: block; font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; color: #6C757D; }
.er-kpi-value { display: block; font-size: 1.45rem; font-weight: 700; line-height: 1.2; color: var(--er-kpi, #2E353E); }
.er-kpi-hint { display: block; font-size: .74rem; color: #6C757D; }

.er-spots { border: 1px solid #EEF0F2; border-radius: 10px; padding: .7rem .85rem; margin-bottom: .85rem; }
.er-spots .progress { height: 8px; background: #EEF0F2; }

/* Barra de filtros ------------------------------------------------------ */
.er-chips { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .15rem; scrollbar-width: thin; }
.er-chip {
    flex: 0 0 auto;
    border: 1px solid #EEF0F2;
    background: #FFFFFF;
    color: #6C757D;
    border-radius: 999px;
    padding: .3rem .75rem;
    font-size: .82rem;
    line-height: 1.2;
    text-decoration: none;
    white-space: nowrap;
}
.er-chip:hover { border-color: #0088CC; color: #0088CC; }
.er-chip.is-active { background: #2E353E; border-color: #2E353E; color: #FFFFFF; }
.er-chip .er-chip-count { opacity: .75; }
.er-chip--confirmados.is-active { background: #1FA855; border-color: #1FA855; }
.er-chip--pendentes.is-active { background: #F5A623; border-color: #F5A623; }
.er-chip--presentes.is-active { background: #1FA855; border-color: #1FA855; }
.er-chip--duplicadas.is-active { background: #DC3545; border-color: #DC3545; }
.er-chip--excluidas.is-active { background: #6C757D; border-color: #6C757D; }

/* Avatar / identidade --------------------------------------------------- */
.er-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    color: #fff;
    font-size: .74rem; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    flex: 0 0 auto;
}
.er-name { font-weight: 600; color: #2E353E; }
.er-muted { color: #6C757D; font-size: .78rem; }

/* Tabela ---------------------------------------------------------------- */
.er-table { margin-bottom: 0; }
.er-table th { font-size: .76rem; text-transform: uppercase; letter-spacing: .03em; color: #6C757D; white-space: nowrap; }
.er-table td, .er-table th { padding: .5rem .55rem; vertical-align: middle; }
.er-table tbody tr { border-left: 3px solid transparent; }
.er-table tbody tr.er-row--presente { border-left-color: #1FA855; background: rgba(31, 168, 85, .04); }
.er-table tbody tr.er-row--excluida { opacity: .6; }
.er-table tbody tr.er-row--cancelada .er-name { color: #6C757D; text-decoration: line-through; }

.er-badge { font-size: .72rem; font-weight: 600; padding: .28rem .55rem; border-radius: 999px; border: 0; }
.er-badge--pendente { background: rgba(245, 166, 35, .16); color: #A56A00; }
.er-badge--confirmado { background: rgba(31, 168, 85, .14); color: #157F40; }
.er-badge--cancelado { background: #EEF0F2; color: #6C757D; }
.er-badge--presente { background: rgba(31, 168, 85, .14); color: #157F40; }
.er-badge--dup { background: rgba(220, 53, 69, .12); color: #DC3545; }

.er-receipt-ok { color: #1FA855; font-size: 1.1rem; line-height: 1; }
.er-receipt-off { color: #C7CDD4; font-size: 1.1rem; line-height: 1; }

.er-actions-btn {
    border: 0; background: none; color: #6C757D;
    width: 30px; height: 30px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
}
.er-actions-btn:hover { background: #EEF0F2; color: #2E353E; }

.er-whats { color: #1FA855; text-decoration: none; font-size: 1rem; }
.er-whats:hover { color: #157F40; }

/* Cards (mobile) -------------------------------------------------------- */
.er-cards { display: none; gap: .55rem; }
.er-card {
    border: 1px solid #EEF0F2;
    border-left: 3px solid transparent;
    border-radius: 10px;
    background: #FFFFFF;
    padding: .65rem .7rem;
}
.er-card--presente { border-left-color: #1FA855; background: rgba(31, 168, 85, .04); }
.er-card--excluida { opacity: .6; }
.er-card-head { display: flex; align-items: flex-start; gap: .6rem; }
.er-card-body { min-width: 0; flex: 1 1 auto; }
.er-card-contact { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; font-size: .82rem; }

/* Barra de ação em lote ------------------------------------------------- */
.er-bulkbar {
    position: fixed;
    left: 50%;
    bottom: 1rem;
    transform: translateX(-50%);
    z-index: 1040;
    display: none;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    justify-content: center;
    background: #2E353E;
    color: #FFFFFF;
    border-radius: 999px;
    padding: .5rem .75rem;
    box-shadow: 0 .6rem 1.4rem rgba(46, 53, 62, .3);
    max-width: calc(100vw - 1.5rem);
}
.er-bulkbar.is-visible { display: flex; }
.er-bulkbar .btn { --bs-btn-padding-y: .2rem; --bs-btn-padding-x: .55rem; --bs-btn-font-size: .8rem; }
.er-bulkbar-count { font-size: .82rem; font-weight: 600; white-space: nowrap; }

.er-fab {
    position: fixed;
    right: 1rem;
    bottom: 1rem;
    z-index: 1035;
    display: none;
    width: 56px; height: 56px;
    border-radius: 50%;
    align-items: center; justify-content: center;
    background: #1FA855; color: #FFFFFF;
    font-size: 1.5rem;
    box-shadow: 0 .5rem 1.2rem rgba(31, 168, 85, .4);
}
.er-fab:hover, .er-fab:focus { color: #FFFFFF; }

@media (max-width: 767.98px) {
    .er-table-wrap { display: none; }
    .er-cards { display: grid; }
    .er-search-sticky {
        position: sticky;
        top: 0;
        z-index: 6;
        background: #FFFFFF;
        padding: .5rem 0;
        margin-inline: -.25rem;
        padding-inline: .25rem;
    }
    .er-fab { display: inline-flex; }
    .er-bulkbar.is-visible { bottom: 5rem; }
    .er-kpi-value { font-size: 1.2rem; }
    .er-pagination .page-link { min-height: 44px; display: flex; align-items: center; }
}

.er-dropzone {
    border: 2px dashed #cfd6de;
    border-radius: .75rem;
    background: #f8fafc;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
}
.er-dropzone.is-dragover { background: rgba(0,136,204,.06); border-color: #0088CC; }
.er-dropzone__preview { display: none; align-items: center; gap: .75rem; text-align: left; }
.er-dropzone.has-file .er-dropzone__idle { display: none; }
.er-dropzone.has-file .er-dropzone__preview { display: flex; }
.er-dropzone__thumb {
    width: 56px; height: 56px; border-radius: .5rem;
    background: #eef2f6; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.er-dropzone__thumb img { width: 100%; height: 100%; object-fit: cover; }
.er-dropzone__thumb i { font-size: 1.5rem; color: #6C757D; }

.er-whats-list {
    max-height: 220px;
    overflow: auto;
    border: 1px solid #EEF0F2;
    border-radius: .5rem;
}
.er-whats-item {
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    padding: .45rem .7rem;
    border-bottom: 1px solid #EEF0F2;
    margin: 0;
    cursor: pointer;
}
.er-whats-item:last-child { border-bottom: 0; }
.er-whats-item.is-hidden { display: none; }
.er-whats-item .er-muted { line-height: 1.2; }

.er-raffle-modal { overflow: hidden; }
.er-raffle-scope { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; }
.er-raffle-scope .btn { border-radius: 999px; }
.er-raffle-stage {
    position: relative;
    min-height: 7.5rem;
    border-radius: 16px;
    background: linear-gradient(180deg, #1a2740 0%, #0f1728 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.4rem 1.1rem;
    overflow: hidden;
}
.er-raffle-glow {
    position: absolute; inset: -40%;
    background: radial-gradient(circle, rgba(245,166,35,.28), transparent 55%);
    opacity: 0;
    pointer-events: none;
}
.er-raffle-stage.is-spinning .er-raffle-glow { opacity: 1; animation: er-raffle-pulse 0.7s ease-in-out infinite; }
.er-raffle-stage.is-winner .er-raffle-glow { opacity: 1; animation: er-raffle-win 1.1s ease-out; }
.er-raffle-name {
    position: relative;
    z-index: 1;
    color: #fff;
    font-size: 1.45rem;
    font-weight: 700;
    line-height: 1.25;
    letter-spacing: .01em;
    text-align: center;
    min-height: 2.2em;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .08s ease, opacity .08s ease, filter .08s ease;
}
.er-raffle-stage.is-spinning .er-raffle-name {
    filter: blur(.4px);
    animation: er-raffle-flip .12s ease-in-out infinite;
}
.er-raffle-stage.is-winner .er-raffle-name {
    filter: none;
    color: #ffe08a;
    animation: er-raffle-pop .45s cubic-bezier(.2, 1.4, .3, 1);
}
@keyframes er-raffle-flip {
    0% { transform: translateY(-8px); opacity: .55; }
    50% { transform: translateY(8px); opacity: 1; }
    100% { transform: translateY(-8px); opacity: .55; }
}
@keyframes er-raffle-pop {
    0% { transform: scale(.7); opacity: 0; }
    70% { transform: scale(1.08); }
    100% { transform: scale(1); }
}
@keyframes er-raffle-pulse {
    0%, 100% { opacity: .45; transform: scale(1); }
    50% { opacity: .9; transform: scale(1.08); }
}
@keyframes er-raffle-win {
    0% { opacity: .2; transform: scale(.6); }
    40% { opacity: 1; transform: scale(1.15); }
    100% { opacity: .7; transform: scale(1); }
}
</style>
