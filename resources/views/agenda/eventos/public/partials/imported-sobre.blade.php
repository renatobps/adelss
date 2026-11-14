@if($event->about_html)
    {!! clean($event->about_html) !!}
@elseif($event->description)
    <p>{{ $event->description }}</p>
@endif
