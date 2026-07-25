@php
    $dest = $destination->destination;
    $status = $destination->status;
    $dot = match ($status) {
        'publicado' => 'ok',
        'erro' => 'err',
        'publicando' => 'wait',
        default => 'pending',
    };
    $isWhatsApp = $destination->isWhatsAppGroup();
    $icon = match (true) {
        $isWhatsApp => 'bxl-whatsapp',
        $dest === 'reels' => 'bx-movie-play',
        $dest === 'stories' => 'bx-circle',
        default => 'bx-grid-alt',
    };
    $label = $destination->destination_label;
    $tip = ($isWhatsApp ? 'WhatsApp · ' : '') . $label . ': ' . $destination->status_label
        . ($destination->error_message ? ' — ' . $destination->error_message : '');
@endphp
<span class="midia-dest-badge {{ $isWhatsApp ? 'whatsapp' : $dest }}" title="{{ $tip }}" data-bs-toggle="tooltip" data-bs-placement="top">
    <i class="bx {{ $icon }}"></i>
    {{ $label }}
    <span class="status-dot {{ $dot }}"></span>
</span>
