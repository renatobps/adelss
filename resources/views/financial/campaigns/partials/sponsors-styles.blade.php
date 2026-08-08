<style>
.campaign-kpis .kpi-card { transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease; }
.campaign-kpis a.kpi-card { cursor: pointer; text-decoration: none; color: inherit; }
.campaign-kpis a.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 .35rem .9rem rgba(46, 53, 62, .12); }
.campaign-kpis .kpi-card.is-active { border-color: var(--kpi-color, #0088CC); box-shadow: inset 0 0 0 1px var(--kpi-color, #0088CC); }

.cs-pagination .pagination { margin-bottom: 0; }

/* Faixa de leitura: em monitores largos o conteúdo não se espalha até as bordas. */
.campaign-sponsors { max-width: 1400px; margin-inline: auto; }

/* A listagem sangra até as bordas do card, sem herdar o padding do card-body. */
.cs-listing { margin: .5rem -1rem 0; }

.cs-chips { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .15rem; scrollbar-width: thin; }
.cs-chip {
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
.cs-chip:hover { border-color: #0088CC; color: #0088CC; }
.cs-chip.is-active { background: #2E353E; border-color: #2E353E; color: #FFFFFF; }
.cs-chip .cs-chip-count { opacity: .75; }
.cs-chip--em_atraso.is-active { background: #DC3545; border-color: #DC3545; }
.cs-chip--em_dia.is-active { background: #0088CC; border-color: #0088CC; }
.cs-chip--quitado.is-active { background: #1FA855; border-color: #1FA855; }

.cs-item { border-left: 3px solid transparent; }
.cs-item--em_atraso { border-left-color: #DC3545; }
.cs-item--em_dia { border-left-color: #0088CC; }
.cs-item--quitado { border-left-color: #1FA855; }
.cs-item--quitado .cs-name { color: #6C757D; font-weight: 500; }

.cs-head { display: flex; align-items: stretch; }
.cs-toggle {
    flex: 1 1 auto;
    min-width: 0;
    display: grid;
    grid-template-columns: 36px minmax(0, 1fr) 170px 52px 108px 104px;
    grid-template-areas: "avatar identity progress count amount status";
    align-items: center;
    gap: .75rem;
    padding: .5rem .75rem;
    background: none;
    border: 0;
    text-align: left;
}
.cs-toggle:hover { background: rgba(0, 136, 204, .04); }
.cs-toggle:focus-visible { outline: 2px solid #0088CC; outline-offset: -2px; }

.cs-avatar {
    grid-area: avatar;
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #EEF0F2;
    color: #2E353E;
    display: flex; align-items: center; justify-content: center;
    font-size: .74rem; font-weight: 600;
}
.cs-identity { grid-area: identity; min-width: 0; }
.cs-name { display: block; font-weight: 600; color: #2E353E; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cs-phone { display: block; font-size: .78rem; color: #6C757D; }
.cs-progress { grid-area: progress; }
.cs-progress .progress { height: 6px; background: #EEF0F2; }
.cs-count { grid-area: count; font-size: .82rem; color: #6C757D; white-space: nowrap; }
.cs-amount { grid-area: amount; font-size: .86rem; font-weight: 600; color: #1FA855; white-space: nowrap; }
.cs-status { grid-area: status; }
.cs-menu { display: flex; align-items: center; padding-right: .4rem; }
.cs-body { padding: .75rem 1rem 1rem; background: #FBFCFD; }

.cs-badge { font-size: .72rem; font-weight: 600; padding: .3rem .5rem; border-radius: 999px; }
.cs-badge--em_atraso { background: rgba(220, 53, 69, .12); color: #DC3545; }
.cs-badge--em_dia { background: rgba(0, 136, 204, .12); color: #0088CC; }
.cs-badge--quitado { background: rgba(31, 168, 85, .12); color: #1FA855; }
.cs-badge--nao_iniciado { background: #EEF0F2; color: #6C757D; }

.cs-table td, .cs-table th { padding: .45rem .5rem; vertical-align: middle; }
.cs-table tbody tr { cursor: pointer; border-left: 3px solid transparent; }
.cs-table tbody tr:hover { background: rgba(0, 136, 204, .04); }
.cs-table tbody tr.cs-item--em_atraso { border-left-color: #DC3545; }
.cs-table tbody tr.cs-item--em_dia { border-left-color: #0088CC; }
.cs-table tbody tr.cs-item--quitado { border-left-color: #1FA855; }

#sponsorOffcanvas { width: min(680px, 100vw); }

@media (max-width: 767.98px) {
    .cs-toggle {
        grid-template-columns: 36px minmax(0, 1fr) auto;
        grid-template-areas:
            "avatar identity status"
            "progress progress progress"
            "amount amount count";
        row-gap: .4rem;
        padding: .6rem .5rem;
    }
    .cs-count { text-align: right; }
    .cs-search-sticky { position: sticky; top: 0; z-index: 5; background: #FFFFFF; padding: .5rem 0; }
    .cs-pagination .page-link { min-height: 44px; display: flex; align-items: center; }
}
</style>
