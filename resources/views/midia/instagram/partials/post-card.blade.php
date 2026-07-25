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
<div class="midia-ig-card midia-card">
    <div class="midia-ig-card-media">
        @if($media && $media->isPhoto())
            <img src="{{ route('midia.thumbnail', $media) }}" alt="" loading="lazy">
        @elseif($post->image_path && $post->media_kind !== 'video')
            <img src="{{ asset('storage/' . $post->image_path) }}" alt="" loading="lazy">
        @elseif(($media && $media->isVideo()) || $post->media_kind === 'video')
            <div class="midia-ig-card-placeholder"><i class="bx bx-video"></i></div>
        @else
            <div class="midia-ig-card-placeholder"><i class="bx bx-image"></i></div>
        @endif

        <span class="midia-ig-status-overlay midia-status-pill {{ $statusClass }}">
            {{ $post->status_label }}
        </span>

        <div class="midia-ig-card-menu">
            @include('midia.instagram.partials.post-actions', ['post' => $post, 'menuSuffix' => 'card'])
        </div>
    </div>

    <div class="midia-ig-card-body">
        <div class="midia-ig-card-when">{{ $post->scheduled_for->format('d/m/Y H:i') }}</div>

        <div class="midia-ig-card-dests">
            @foreach($post->destinations as $destination)
                <div class="midia-ig-dest-item">
                    @include('midia.partials.destination-badge', ['destination' => $destination])
                    @php $removalLabel = $destination->removalSummaryLabel(); @endphp
                    @if($removalLabel !== '')
                        <div class="midia-ig-dest-expiry {{ $destination->removal_status === 'erro_remocao' ? 'text-danger' : '' }}">
                            {{ $removalLabel }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if(filled($post->event_name))
            <span class="midia-event-tag">{{ $post->event_name }}</span>
        @endif

        @if($caption !== '')
            <button type="button"
                    class="midia-ig-card-caption midia-caption-trigger"
                    data-bs-toggle="modal"
                    data-bs-target="#igLegendaModal"
                    data-legenda="{{ $caption }}"
                    data-quando="{{ $post->scheduled_for->format('d/m/Y H:i') }}"
                    title="Ver legenda completa">
                {{ $caption }}
            </button>
        @else
            <div class="midia-ig-card-caption text-muted">—</div>
        @endif
    </div>
</div>
