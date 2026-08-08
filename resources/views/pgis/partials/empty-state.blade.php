@php
    $emptyIcon = $icon ?? 'bx-info-circle';
    $emptyActionIcon = $actionIcon ?? 'bx-plus';
@endphp
<div class="pgi-empty">
    <i class="bx {{ $emptyIcon }} pgi-empty__icon"></i>
    <p class="pgi-empty__title">{{ $title }}</p>
    @isset($description)
        <p class="pgi-empty__text">{{ $description }}</p>
    @endisset
    @isset($actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm">
            <i class="bx {{ $emptyActionIcon }} me-1"></i>{{ $actionLabel }}
        </a>
    @endisset
</div>
