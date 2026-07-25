<style>
:root {
    --midia-bg-dark: #2E353E;
    --midia-card: #FFFFFF;
    --midia-primary: #0088CC;
    --midia-primary-hover: #0094DD;
    --midia-border: #EEF0F2;
    --midia-text: #2E353E;
    --midia-text-secondary: #6C757D;
    --midia-google-blue: #4285F4;
    --midia-google-red: #EA4335;
    --midia-google-yellow: #FBBC05;
    --midia-google-green: #34A853;
    --midia-instagram-gradient: linear-gradient(45deg, #F77737, #FD1D1D, #E4405F, #833AB4);
}

/* —— Cards de pasta / arquivo —— */
.midia-card {
    display: block;
    background: var(--midia-card);
    border: 1px solid var(--midia-border);
    border-radius: .85rem;
    padding: 0;
    height: 100%;
    overflow: hidden;
    color: inherit;
    text-decoration: none;
    transition: border-color .15s, box-shadow .15s, transform .15s;
}
.midia-card:hover {
    border-color: #cfe8f6;
    box-shadow: 0 6px 18px rgba(46, 53, 62, .08);
    transform: translateY(-1px);
    color: inherit;
}
.midia-folder-strip {
    height: 6px;
    width: 100%;
}
.midia-folder-accent-blue .midia-folder-strip { background: var(--midia-primary); }
.midia-folder-accent-teal .midia-folder-strip { background: #14b8a6; }
.midia-folder-accent-dark .midia-folder-strip { background: var(--midia-bg-dark); }
.midia-folder-accent-amber .midia-folder-strip { background: #f59e0b; }
.midia-folder-accent-ig .midia-folder-strip { background: var(--midia-instagram-gradient); }

.midia-card-body { padding: .85rem; }
.midia-thumb {
    height: 140px;
    border-radius: .55rem;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #f4f7fa;
    margin-bottom: .65rem;
    width: 100%;
}
.midia-thumb.folder { color: var(--midia-primary); font-size: 2.75rem; background: rgba(0,136,204,.06); }
.midia-thumb.photo img { width: 100%; height: 100%; object-fit: cover; }
.midia-thumb.doc { font-size: 2.75rem; }
.midia-name {
    font-size: .9rem;
    font-weight: 600;
    color: var(--midia-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.midia-meta {
    font-size: .75rem;
    color: var(--midia-text-secondary);
    margin-top: .15rem;
}

.midia-ftype-pdf { color: #EA4335; background: rgba(234,67,53,.08); }
.midia-ftype-doc { color: #4285F4; background: rgba(66,133,244,.1); }
.midia-ftype-sheet { color: #34A853; background: rgba(52,168,83,.1); }
.midia-ftype-ppt { color: #F77737; background: rgba(247,119,55,.1); }
.midia-ftype-video { color: #833AB4; background: rgba(131,58,180,.1); }
.midia-ftype-generic { color: var(--midia-text-secondary); background: #f1f5f9; }

/* —— Destinos / chips —— */
.midia-dest-badge {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .3rem .55rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    background: rgba(0,136,204,.08);
    color: var(--midia-primary);
    border: 1px solid rgba(0,136,204,.15);
    cursor: default;
}
.midia-dest-badge.reels {
    background: rgba(228,64,95,.1);
    color: #C13584;
    border-color: rgba(228,64,95,.18);
}
.midia-dest-badge.stories {
    background: rgba(131,58,180,.1);
    color: #833AB4;
    border-color: rgba(131,58,180,.18);
}
.midia-dest-badge.whatsapp {
    background: rgba(37,211,102,.12);
    color: #128C7E;
    border-color: rgba(37,211,102,.28);
}
.midia-dest-badge.whatsapp i { color: #25D366; }
.midia-dest-badge .status-dot {
    width: .45rem;
    height: .45rem;
    border-radius: 50%;
    display: inline-block;
}
.midia-dest-badge .status-dot.ok { background: #34A853; }
.midia-dest-badge .status-dot.err { background: #EA4335; }
.midia-dest-badge .status-dot.wait { background: #f59e0b; }
.midia-dest-badge .status-dot.pending { background: #94a3b8; }

.midia-status-pill {
    display: inline-flex;
    align-items: center;
    padding: .35rem .7rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
}
.midia-status-pill.ok { background: rgba(52,168,83,.12); color: #1e7e34; }
.midia-status-pill.err { background: rgba(234,67,53,.12); color: #b02a37; }
.midia-status-pill.warn { background: rgba(251,188,5,.22); color: #8a6d00; }
.midia-status-pill.info { background: rgba(0,136,204,.12); color: #006999; }

.midia-posts-table thead th {
    text-transform: uppercase;
    font-size: .78rem;
    letter-spacing: .05em;
    color: var(--midia-text-secondary);
    font-weight: 600;
    border-bottom-color: var(--midia-border);
    white-space: nowrap;
}
.midia-posts-table tbody tr.midia-post-row:nth-child(even) { background: #fafbfc; }
.midia-posts-table tbody tr.midia-post-row:hover { background: rgba(0,136,204,.05) !important; }
.midia-post-thumb {
    width: 48px;
    height: 48px;
    border-radius: .55rem;
    object-fit: cover;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    color: var(--midia-text-secondary);
    font-size: 1.35rem;
}
.midia-post-thumb img { width: 100%; height: 100%; object-fit: cover; }

.midia-event-tag {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    padding: .2rem .55rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 600;
    background: var(--midia-border);
    color: var(--midia-text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.midia-post-actions-btn {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: .5rem;
    border: 1px solid var(--midia-border);
    background: rgba(255,255,255,.92);
    color: var(--midia-text);
}
.midia-post-actions-btn:hover,
.midia-post-actions-btn:focus {
    background: #fff;
    color: var(--midia-primary);
    border-color: #cfe8f6;
}
.midia-post-actions .dropdown-item {
    font-size: .875rem;
}

/* —— Cards de publicações Instagram —— */
.midia-ig-card.midia-card {
    display: flex;
    flex-direction: column;
    overflow: visible;
}
.midia-ig-card-media {
    position: relative;
    aspect-ratio: 1 / 1;
    width: 100%;
    background: #f1f5f9;
    overflow: hidden;
    border-radius: .85rem .85rem 0 0;
}
.midia-ig-card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.midia-ig-card-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--midia-text-secondary);
    font-size: 2.5rem;
    background: #f4f7fa;
}
.midia-ig-status-overlay {
    position: absolute;
    top: .55rem;
    left: .55rem;
    z-index: 2;
    background: rgba(255,255,255,.92) !important;
    box-shadow: 0 1px 4px rgba(46,53,62,.12);
}
.midia-ig-card-menu {
    position: absolute;
    top: .45rem;
    right: .45rem;
    z-index: 3;
}
.midia-ig-card-body {
    padding: .85rem;
    display: flex;
    flex-direction: column;
    gap: .45rem;
    flex: 1;
}
.midia-ig-card-when {
    font-size: .8rem;
    font-weight: 600;
    color: var(--midia-text);
}
.midia-ig-card-dests {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem .55rem;
    align-items: flex-start;
}
.midia-ig-dest-item {
    display: flex;
    flex-direction: column;
    gap: .1rem;
}
.midia-ig-dest-expiry {
    font-size: .68rem;
    color: var(--midia-text-secondary);
    padding-left: .15rem;
}
.midia-ig-card-caption {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: .85rem;
    color: var(--midia-text-secondary);
    text-align: left;
    border: 0;
    background: transparent;
    padding: 0;
    margin: 0;
    line-height: 1.35;
    cursor: pointer;
    width: 100%;
}
.midia-ig-card-caption:hover { color: var(--midia-primary); }
.midia-ig-empty-icon {
    width: 3.5rem;
    height: 3.5rem;
    margin: 0 auto;
    border-radius: 999px;
    background: rgba(0,136,204,.08);
    color: var(--midia-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

@media (max-width: 767.98px) {
    #igPostsTable { display: none !important; }
    #igPostsCards { display: flex !important; }
}

/* —— Configurações / marca —— */
.midia-brand-icon {
    width: 44px;
    height: 44px;
    border-radius: .75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.midia-brand-icon.google { background: #fff; border: 1px solid var(--midia-border); }
.midia-brand-icon.instagram {
    background: var(--midia-instagram-gradient);
    color: #fff;
}
.midia-pulse {
    width: .65rem;
    height: .65rem;
    border-radius: 50%;
    background: #34A853;
    display: inline-block;
    position: relative;
    box-shadow: 0 0 0 0 rgba(52,168,83,.55);
    animation: midia-pulse 1.8s infinite;
}
@keyframes midia-pulse {
    0% { box-shadow: 0 0 0 0 rgba(52,168,83,.55); }
    70% { box-shadow: 0 0 0 8px rgba(52,168,83,0); }
    100% { box-shadow: 0 0 0 0 rgba(52,168,83,0); }
}
.midia-connected-box {
    display: flex;
    align-items: flex-start;
    gap: .65rem;
    background: rgba(52,168,83,.08);
    border: 1px solid rgba(52,168,83,.2);
    border-radius: .75rem;
    padding: .85rem 1rem;
}

/* —— Form chips / separador —— */
.midia-dest-chips { display: flex; flex-wrap: wrap; gap: .65rem; }
.midia-dest-chip {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    min-width: 120px;
    padding: .7rem 1rem;
    border-radius: .75rem;
    border: 1.5px solid var(--midia-border);
    background: #fff;
    color: var(--midia-text);
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
    user-select: none;
    transition: .15s;
}
.midia-dest-chip input { position: absolute; opacity: 0; pointer-events: none; }
.midia-dest-chip:hover { border-color: #b6dcf0; }
.midia-dest-chip:has(input:checked),
.midia-dest-chip.is-active {
    background: rgba(0,136,204,.08);
    border-color: var(--midia-primary);
    color: var(--midia-primary);
    box-shadow: 0 0 0 2px rgba(0,136,204,.12);
}
.midia-dest-chip.reels:has(input:checked),
.midia-dest-chip.reels.is-active {
    background: rgba(228,64,95,.08);
    border-color: #E4405F;
    color: #C13584;
    box-shadow: 0 0 0 2px rgba(228,64,95,.12);
}
.midia-dest-chip.stories:has(input:checked),
.midia-dest-chip.stories.is-active {
    background: rgba(131,58,180,.08);
    border-color: #833AB4;
    color: #833AB4;
    box-shadow: 0 0 0 2px rgba(131,58,180,.12);
}
.midia-dest-chip.grupo:has(input:checked),
.midia-dest-chip.grupo.is-active {
    background: rgba(37,211,102,.1);
    border-color: #25D366;
    color: #128C7E;
    box-shadow: 0 0 0 2px rgba(37,211,102,.18);
}
.midia-dest-chip.grupo i { color: #25D366; }
.midia-dest-chip i { font-size: 1.25rem; }

.midia-or-sep {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    color: var(--midia-text-secondary);
    font-size: .8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    height: 100%;
    min-height: 4rem;
}
.midia-or-sep::before,
.midia-or-sep::after {
    content: '';
    width: 1px;
    height: 2.5rem;
    background: var(--midia-border);
}
@media (max-width: 767.98px) {
    .midia-or-sep { flex-direction: row; min-height: auto; padding: .25rem 0; }
    .midia-or-sep::before,
    .midia-or-sep::after {
        width: 2.5rem;
        height: 1px;
    }
}

.midia-dropzone { background: #f8fafc; cursor: pointer; transition: .15s; border-color: var(--midia-border) !important; }
.midia-dropzone.is-dragover { background: rgba(0,136,204,.06); border-color: var(--midia-primary) !important; }

.midia-picker-empty {
    border: 1px dashed #cbd5e1;
    border-radius: .75rem;
    padding: 1.25rem;
    background: #f8fafc;
}
.midia-picker-preview-thumb {
    width: 64px;
    height: 64px;
    border-radius: .5rem;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    font-size: 1.75rem;
    color: #64748b;
}
.midia-picker-preview-thumb img { width: 100%; height: 100%; object-fit: cover; }
.midia-card.is-selected {
    border-color: var(--midia-primary);
    box-shadow: 0 0 0 2px rgba(0,136,204,.25);
    position: relative;
}
.midia-card.is-selected::after {
    content: '\2713';
    position: absolute;
    top: .4rem;
    right: .4rem;
    width: 1.4rem;
    height: 1.4rem;
    border-radius: 999px;
    background: var(--midia-primary);
    color: #fff;
    font-size: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}
</style>
