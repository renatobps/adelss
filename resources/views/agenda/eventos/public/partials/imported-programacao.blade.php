<div class="event-schedule">
@forelse($event->scheduleItems->groupBy('day') as $day => $items)
    <h3 class="event-day">Dia {{ $day ?: 1 }}</h3>
    @foreach($items as $item)
    <article class="event-schedule-item schedule-row">
        <time>{{ $item->time_hh !== null ? sprintf('%02d:%02d', $item->time_hh, $item->time_mm ?? 0) : 'A confirmar' }}</time>
        <div><h3>{{ $item->title }}</h3><p>{{ $item->detail }}</p>
        @if($item->responsible_name)<p class="event-responsible">{{ $item->responsible_name }}</p>@endif</div>
    </article>
    @endforeach
@empty
    <p>A programação será divulgada em breve.</p>
@endforelse
</div>
