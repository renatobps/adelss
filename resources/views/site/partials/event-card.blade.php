@props(['event'])

@php
    $categoryName = $event->category?->name ?? 'Agenda';
    $actionLabel = $event->public_slug ? 'Saiba mais' : 'Ver detalhes';
    $eventImageUrl = $event->bannerImagePublicUrl();
@endphp

<article class="site-event{{ $eventImageUrl ? ' site-event--has-image' : '' }}">
    <div class="site-event__date">
        <span class="site-event__day">{{ $event->start_date->format('d') }}</span>
        <span class="site-event__month">{{ strtoupper($event->start_date->translatedFormat('M')) }}</span>
    </div>
    <div class="site-event__content">
        <div class="site-event__body">
            <span class="site-event__category">{{ $categoryName }}</span>
            @if($event->public_slug)
                <h3 class="site-event__title">
                    <a href="{{ route('events.public.show', $event->public_slug) }}">{{ $event->title }}</a>
                </h3>
            @else
                <h3 class="site-event__title">{{ $event->title }}</h3>
            @endif
            <p class="site-event__meta"><i class="far fa-clock"></i> {{ $event->all_day ? 'Dia inteiro' : $event->start_date->format('H:i') }}</p>
            @if($event->location)
                <p class="site-event__meta"><i class="fas fa-map-marker-alt"></i> {{ $event->location }}</p>
            @endif
            @if($event->public_slug)
                <a class="site-event__link" href="{{ route('events.public.show', $event->public_slug) }}">{{ $actionLabel }} <i class="fas fa-arrow-right"></i></a>
            @endif
        </div>
        @if($eventImageUrl)
            <div class="site-event__image" style="border-color: {{ $event->category?->color ?: '#0088CC' }};">
                <img src="{{ $eventImageUrl }}" alt="{{ $event->title }}">
            </div>
        @endif
    </div>
</article>
