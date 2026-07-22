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

<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            @foreach(\App\Models\ScheduledPost::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    @can('midia.instagram.schedule')
        <a href="{{ route('midia.instagram.posts.create') }}" class="btn btn-primary btn-sm">
            <i class="bx bx-plus"></i> Agendar publicação
        </a>
    @endcan
</div>

<div class="card border-0 shadow-sm">
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
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                    @php
                        $statusClass = match($post->status) {
                            'concluido' => 'ok',
                            'erro' => 'err',
                            'erro_parcial', 'publicando' => 'warn',
                            default => 'info',
                        };
                        $collapseId = 'post-dest-' . $post->id;
                        $media = $post->mediaFile;
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
                            <div class="text-truncate" style="max-width:240px;" title="{{ $post->caption }}">
                                {{ \Illuminate\Support\Str::limit($post->caption, 80) ?: '—' }}
                            </div>
                        </td>
                        <td>
                            @if(filled($post->event_name))
                                <span class="fw-semibold" style="color:var(--midia-text,#2E353E)">{{ $post->event_name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $post->media_kind === 'video' ? 'Vídeo' : 'Foto' }}</td>
                        <td><span class="midia-status-pill {{ $statusClass }}">{{ $post->status_label }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($post->destinations as $destination)
                                    <button type="button" class="border-0 bg-transparent p-0"
                                            data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                            aria-expanded="false">
                                        @include('midia.partials.destination-badge', ['destination' => $destination])
                                    </button>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end">
                            @if($post->canCancel())
                                @can('midia.instagram.schedule')
                                    <form method="POST" action="{{ route('midia.instagram.posts.destroy', $post) }}" class="d-inline" onsubmit="return confirm('Cancelar esta publicação?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Cancelar</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    <tr class="collapse-row">
                        <td colspan="8" class="p-0 border-0">
                            <div class="collapse" id="{{ $collapseId }}">
                                <div class="bg-light px-3 py-2 border-bottom">
                                    @forelse($post->destinations as $destination)
                                        @php
                                            $dClass = match($destination->status) {
                                                'publicado' => 'ok',
                                                'erro' => 'err',
                                                'publicando' => 'warn',
                                                default => 'info',
                                            };
                                        @endphp
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div>
                                                @include('midia.partials.destination-badge', ['destination' => $destination])
                                                <span class="midia-status-pill {{ $dClass }} ms-2">{{ $destination->status_label }}</span>
                                                @if($destination->published_at)
                                                    <span class="small text-muted ms-2">{{ $destination->published_at->format('d/m/Y H:i') }}</span>
                                                @endif
                                                @if($destination->error_message)
                                                    <div class="small text-danger mt-1">{{ $destination->error_message }}</div>
                                                @endif
                                            </div>
                                            @if($destination->canRetry())
                                                @can('midia.instagram.schedule')
                                                    <form method="POST" action="{{ route('midia.instagram.posts.destinations.retry', $destination) }}">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-warning" type="submit">Tentar novamente</button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-muted small">Sem destinos.</div>
                                    @endforelse
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma publicação agendada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($posts->hasPages())
        <div class="card-footer bg-white">{{ $posts->links() }}</div>
    @endif
</div>
@endsection

@push('styles')
@include('midia.partials.styles')
@endpush

@push('scripts')
<script>
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
    bootstrap.Tooltip.getOrCreateInstance(el);
});
</script>
@endpush
