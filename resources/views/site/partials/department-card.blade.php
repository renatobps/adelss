@php
    $icon = $department->icon ?? 'bx-group';
    $iconClass = str_contains($icon, ' ') ? $icon : (str_starts_with($icon, 'bx') ? 'bx ' . $icon : $icon);
    $isBx = str_contains($iconClass, 'bx');
@endphp

<article class="site-card">
    @if($department->logo_url)
        <div class="site-card__icon site-card__icon--logo" style="border-color: {{ $department->color ?: '#0088CC' }};">
            <img src="{{ asset('storage/' . $department->logo_url) }}" alt="{{ $department->name }}">
        </div>
    @else
        <div class="site-card__icon" style="background: {{ $department->color ?: '#0088CC' }};">
            <i class="{{ $iconClass }}{{ $isBx ? '' : ' fa-fw' }}"></i>
        </div>
    @endif
    <h3 class="site-card__title">{{ $department->name }}</h3>
    @if($department->description)
        <p class="site-card__text">{{ $department->description }}</p>
    @endif
    @if($department->homepage_url)
        <a href="{{ $department->homepage_url }}" class="site-card__link" target="_blank" rel="noopener">
            Saiba mais <i class="fas fa-arrow-right"></i>
        </a>
    @endif
</article>
