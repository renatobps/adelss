<div class="event-speakers">
@forelse($event->speakers as $speaker)
    <article class="event-speaker">
        @if($speaker->photo_path)<img src="{{ \App\Models\Event::publicStorageUrl($speaker->photo_path) }}" alt="{{ $speaker->name }}" loading="lazy">@endif
        <h3>{{ $speaker->name }}</h3><p>{{ $speaker->description }}</p>
    </article>
@empty
    <p>Os palestrantes serão divulgados em breve.</p>
@endforelse
</div>
