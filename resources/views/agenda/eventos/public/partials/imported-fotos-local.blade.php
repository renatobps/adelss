@if(count($event->locationPhotoPublicUrls()))
<div class="event-location-photos">
    @foreach($event->locationPhotoPublicUrls() as $photoUrl)<img src="{{ $photoUrl }}" alt="Local do evento" loading="lazy">@endforeach
</div>
@endif
