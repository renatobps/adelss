@php
    $dest = $destination->destination;
    $status = $destination->status;
    $dot = match ($status) {
        'publicado' => 'ok',
        'erro' => 'err',
        'publicando' => 'wait',
        default => 'pending',
    };
    $icon = match ($dest) {
        'reels' => 'bx-movie-play',
        'stories' => 'bx-circle',
        default => 'bx-grid-alt',
    };
    $tip = $destination->destination_label . ': ' . $destination->status_label
        . ($destination->error_message ? ' — ' . $destination->error_message : '');
@endphp
<span class="midia-dest-badge {{ $dest }}" title="{{ $tip }}" data-bs-toggle="tooltip" data-bs-placement="top">
    <i class="bx {{ $icon }}"></i>
    {{ $destination->destination_label }}
    <span class="status-dot {{ $dot }}"></span>
</span>
