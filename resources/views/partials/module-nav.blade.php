@php
    /** @var string $title */
    /** @var string|null $subtitle */
    /** @var array<int, array{label:string,icon:string,url:string,active?:bool,brand?:string}> $items */
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
    $items = array_values(array_filter($items ?? []));
    $actions = $actions ?? null; // HTML opcional à direita do título
    $columns = max(2, min(8, (int) ($columns ?? max(count($items), 2))));
    $compact = !empty($compact);
@endphp

@if($title !== '' && count($items) > 0)
<div class="adelss-module-nav mb-4 {{ $compact ? 'adelss-module-nav--compact' : '' }}" style="--adelss-nav-cols: {{ $columns }}">
    <div class="adelss-module-nav__intro {{ $compact ? 'mb-2' : 'mb-3' }} d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h1 class="adelss-module-nav__title">{{ $title }}</h1>
            @if(filled($subtitle) && !$compact)
                <p class="adelss-module-nav__subtitle mb-0">{{ $subtitle }}</p>
            @endif
        </div>
        @if(!empty($actions))
            <div class="adelss-module-nav__actions">
                {!! $actions !!}
            </div>
        @endif
    </div>

    <div class="adelss-module-nav__grid {{ $compact ? 'adelss-module-nav__grid--tabs' : '' }}">
        @foreach($items as $item)
            <a href="{{ $item['url'] }}"
               class="adelss-module-nav__item {{ !empty($item['active']) ? 'is-active' : '' }} {{ !empty($item['brand']) ? 'is-brand-'.$item['brand'] : '' }}">
                <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif
