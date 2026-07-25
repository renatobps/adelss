@php
    $media = $post->mediaFile;
@endphp
<div class="midia-ig-details" data-post-details="{{ $post->id }}">
    <div class="d-flex gap-3 mb-3">
        <div class="midia-post-thumb flex-shrink-0" style="width:72px;height:72px;">
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
        <div class="min-w-0">
            <div class="fw-semibold" style="color:var(--midia-text)">{{ $post->scheduled_for->format('d/m/Y H:i') }}</div>
            <div class="small text-muted mb-1">{{ $post->media_kind === 'video' ? 'Vídeo' : 'Foto' }}</div>
            @php
                $statusClass = match($post->status) {
                    'concluido' => 'ok',
                    'erro' => 'err',
                    'erro_parcial', 'publicando' => 'warn',
                    default => 'info',
                };
            @endphp
            <span class="midia-status-pill {{ $statusClass }}">{{ $post->status_label }}</span>
            @if(filled($post->event_name))
                <span class="midia-event-tag ms-1">{{ $post->event_name }}</span>
            @endif
        </div>
    </div>

    @if(filled($post->caption))
        <div class="mb-3">
            <div class="small text-uppercase text-secondary mb-1" style="letter-spacing:.04em;font-size:.72rem;">Legenda</div>
            <div class="border rounded p-3 bg-light" style="white-space:pre-wrap;word-break:break-word;">{{ $post->caption }}</div>
        </div>
    @endif

    <div class="small text-uppercase text-secondary mb-2" style="letter-spacing:.04em;font-size:.72rem;">Destinos</div>
    @forelse($post->destinations as $destination)
        @php
            $dClass = match($destination->status) {
                'publicado' => 'ok',
                'erro' => 'err',
                'publicando' => 'warn',
                default => 'info',
            };
        @endphp
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
            <div class="flex-grow-1 min-w-0">
                @include('midia.partials.destination-badge', ['destination' => $destination])
                <span class="midia-status-pill {{ $dClass }} ms-2">{{ $destination->status_label }}</span>
                @if($destination->published_at)
                    <span class="small text-muted ms-2">{{ $destination->published_at->format('d/m/Y H:i') }}</span>
                @endif
                @php $removalLabel = $destination->removalSummaryLabel(); @endphp
                @if($removalLabel !== '')
                    <div class="small mt-1 {{ $destination->removal_status === 'erro_remocao' ? 'text-danger' : 'text-muted' }}">
                        {{ $destination->destination_label }} — {{ $removalLabel }}
                        @if($destination->removal_status === 'erro_remocao')
                            <span class="midia-status-pill err ms-1">erro_remocao</span>
                            <div class="mt-1">
                                {{ $destination->removal_error ?: 'Não foi possível remover via API.' }}
                                Remova manualmente pelo app do Instagram.
                            </div>
                        @endif
                    </div>
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
