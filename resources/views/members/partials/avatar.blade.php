@php
    $size = (int) ($size ?? 40);
    $fontSize = max(10, (int) round($size * 0.36));
@endphp
@if($member->photo_url)
    <img src="{{ $member->photo_url }}"
         alt="{{ $member->name }}"
         class="rounded-circle members-avatar"
         width="{{ $size }}"
         height="{{ $size }}"
         style="object-fit:cover;width:{{ $size }}px;height:{{ $size }}px;">
@else
    <div class="rounded-circle members-avatar members-avatar--initials d-inline-flex align-items-center justify-content-center"
         style="width:{{ $size }}px;height:{{ $size }}px;background:{{ $member->avatar_color }};color:#fff;font-size:{{ $fontSize }}px;font-weight:700;line-height:1;"
         title="{{ $member->name }}">
        {{ $member->initials }}
    </div>
@endif
