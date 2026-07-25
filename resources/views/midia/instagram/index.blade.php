@extends('layouts.porto')

@section('title', 'Publicações Instagram')
@section('page-title', 'Publicações Instagram')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><span>Instagram</span></li>
@endsection

@section('content')
@include('midia.partials.nav', ['active' => 'instagram'])

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@unless($instagramConnected)
    <div class="alert alert-warning">
        Instagram não conectado.
        <a href="{{ route('midia.settings') }}">Configurações</a>
    </div>
@endunless

@if($instagram->isTokenExpiringSoon())
    <div class="alert alert-warning">O token do Instagram expira em breve ({{ optional($instagram->token_expires_at)->format('d/m/Y') }}). Reconecte nas configurações.</div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            @foreach(\App\Models\ScheduledPost::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group midia-ig-view-toggle d-none d-md-inline-flex" role="group" aria-label="Modo de visualização">
            <button type="button" class="btn btn-sm btn-primary" data-ig-view="cards" title="Cards">
                <i class="bx bx-grid-alt"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-ig-view="lista" title="Tabela">
                <i class="bx bx-list-ul"></i>
            </button>
        </div>
        @can('midia.instagram.schedule')
            <a href="{{ route('midia.instagram.posts.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus"></i> Agendar publicação
            </a>
        @endcan
    </div>
</div>

@if($posts->count() === 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body midia-ig-empty text-center py-5">
            <div class="midia-ig-empty-icon mb-3"><i class="bx bx-calendar-event"></i></div>
            <p class="text-muted mb-3">Nenhuma publicação agendada.</p>
            @can('midia.instagram.schedule')
                <a href="{{ route('midia.instagram.posts.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus"></i> Agendar publicação
                </a>
            @endcan
        </div>
    </div>
@else
    {{-- Modo Cards (também fallback mobile) --}}
    <div id="igPostsCards" class="row g-3 midia-ig-view midia-ig-view-cards">
        @foreach($posts as $post)
            <div class="col-12 col-md-6 col-xl-3">
                @include('midia.instagram.partials.post-card', ['post' => $post])
            </div>
        @endforeach
    </div>

    {{-- Modo Tabela (desktop) --}}
    <div id="igPostsTable" class="card border-0 shadow-sm midia-ig-view midia-ig-view-lista d-none">
        <div class="table-responsive">
            <table class="table midia-posts-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:64px;"></th>
                        <th>Quando</th>
                        <th>Legenda</th>
                        <th>Evento</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Destinos</th>
                        <th class="text-end" style="width:56px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($posts as $post)
                        @php
                            $statusClass = match($post->status) {
                                'concluido' => 'ok',
                                'erro' => 'err',
                                'erro_parcial', 'publicando' => 'warn',
                                default => 'info',
                            };
                            $media = $post->mediaFile;
                            $caption = $post->caption ?: '';
                        @endphp
                        <tr class="midia-post-row">
                            <td>
                                <div class="midia-post-thumb">
                                    @if($media && $media->isPhoto())
                                        <img src="{{ route('midia.thumbnail', $media) }}" alt="" loading="lazy">
                                    @elseif($post->image_path && $post->media_kind !== 'video')
                                        <img src="{{ asset('storage/' . $post->image_path) }}" alt="" loading="lazy">
                                    @elseif(($media && $media->isVideo()) || $post->media_kind === 'video')
                                        <i class="bx bx-video"></i>
                                    @else
                                        <i class="bx bx-image"></i>
                                    @endif
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $post->scheduled_for->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($caption !== '')
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 text-start midia-caption-trigger text-decoration-none"
                                            style="color:var(--midia-text);max-width:220px;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#igLegendaModal"
                                            data-legenda="{{ $caption }}"
                                            data-quando="{{ $post->scheduled_for->format('d/m/Y H:i') }}"
                                            title="Ver legenda completa">
                                        {{ \Illuminate\Support\Str::limit($caption, 40) }}
                                    </button>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(filled($post->event_name))
                                    <span class="midia-event-tag">{{ $post->event_name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $post->media_kind === 'video' ? 'Vídeo' : 'Foto' }}</td>
                            <td><span class="midia-status-pill {{ $statusClass }}">{{ $post->status_label }}</span></td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @foreach($post->destinations as $destination)
                                        <div>
                                            <button type="button"
                                                    class="border-0 bg-transparent p-0"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#igPostDetailsModal"
                                                    data-post-id="{{ $post->id }}">
                                                @include('midia.partials.destination-badge', ['destination' => $destination])
                                            </button>
                                            @php $removalLabel = $destination->removalSummaryLabel(); @endphp
                                            @if($removalLabel !== '')
                                                <div class="small {{ $destination->removal_status === 'erro_remocao' ? 'text-danger' : 'text-muted' }}">
                                                    {{ $removalLabel }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-end">
                                @include('midia.instagram.partials.post-actions', ['post' => $post, 'menuSuffix' => 'table'])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($posts->hasPages())
        <div class="mt-3">{{ $posts->links() }}</div>
    @endif

    {{-- Templates de detalhes (um por post) — reutilizados pelo modal --}}
    <div class="d-none" id="igPostDetailsTemplates" aria-hidden="true">
        @foreach($posts as $post)
            @include('midia.instagram.partials.post-details', ['post' => $post])
        @endforeach
    </div>
@endif

{{-- Modal legenda completa --}}
<div class="modal fade" id="igLegendaModal" tabindex="-1" aria-labelledby="igLegendaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="igLegendaModalLabel">Legenda completa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2" id="igLegendaModalQuando"></div>
                <div class="border rounded p-3 bg-light" style="white-space:pre-wrap;word-break:break-word;" id="igLegendaModalTexto"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal detalhes da publicação --}}
<div class="modal fade" id="igPostDetailsModal" tabindex="-1" aria-labelledby="igPostDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="igPostDetailsModalLabel">Detalhes da publicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="igPostDetailsModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('midia.partials.styles')
@endpush

@push('scripts')
<script>
(function () {
    const STORAGE_KEY = 'midia.instagram.view';
    const cardsEl = document.getElementById('igPostsCards');
    const tableEl = document.getElementById('igPostsTable');
    const toggleBtns = document.querySelectorAll('[data-ig-view]');

    function decodeHtml(html) {
        const ta = document.createElement('textarea');
        ta.innerHTML = html || '';
        return ta.value;
    }

    function isMobile() {
        return window.matchMedia('(max-width: 767.98px)').matches;
    }

    function applyView(view) {
        const mode = view === 'lista' ? 'lista' : 'cards';
        try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) {}

        toggleBtns.forEach((btn) => {
            const active = btn.getAttribute('data-ig-view') === mode;
            btn.classList.toggle('btn-primary', active);
            btn.classList.toggle('btn-outline-secondary', !active);
        });

        if (!cardsEl || !tableEl) return;

        if (isMobile()) {
            cardsEl.classList.remove('d-none');
            tableEl.classList.add('d-none');
            return;
        }

        cardsEl.classList.toggle('d-none', mode !== 'cards');
        tableEl.classList.toggle('d-none', mode !== 'lista');
    }

    let saved = 'cards';
    try {
        saved = localStorage.getItem(STORAGE_KEY) || 'cards';
    } catch (e) {}
    applyView(saved);

    toggleBtns.forEach((btn) => {
        btn.addEventListener('click', () => applyView(btn.getAttribute('data-ig-view')));
    });

    window.addEventListener('resize', () => {
        let current = 'cards';
        try { current = localStorage.getItem(STORAGE_KEY) || 'cards'; } catch (e) {}
        applyView(current);
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        bootstrap.Tooltip.getOrCreateInstance(el);
    });

    document.getElementById('igLegendaModal')?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        document.getElementById('igLegendaModalTexto').textContent = decodeHtml(btn.getAttribute('data-legenda')) || '';
        document.getElementById('igLegendaModalQuando').textContent = btn.getAttribute('data-quando') || '';
    });

    document.getElementById('igPostDetailsModal')?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const body = document.getElementById('igPostDetailsModalBody');
        if (!btn || !body) return;
        const postId = btn.getAttribute('data-post-id');
        const tpl = document.querySelector('[data-post-details="' + postId + '"]');
        body.innerHTML = tpl ? tpl.innerHTML : '<p class="text-muted mb-0">Detalhes indisponíveis.</p>';
        body.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getOrCreateInstance(el);
        });
    });
})();
</script>
@endpush
