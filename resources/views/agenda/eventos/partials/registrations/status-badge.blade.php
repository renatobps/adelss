@php
    $st = $r->status ?? \App\Models\EventRegistration::STATUS_PENDENTE;
    $badgeId = 'er-status-'.$r->id.'-'.($suffix ?? 'main');
@endphp
@if(($canEditRegistrations ?? false) && ! $r->trashed())
    <div class="dropdown d-inline-block">
        <button type="button" class="er-badge er-badge--{{ $st }}" id="{{ $badgeId }}"
                data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
            {{ $r->status_label }} <i class="bx bx-chevron-down"></i>
        </button>
        <ul class="dropdown-menu" aria-labelledby="{{ $badgeId }}">
            @foreach(\App\Models\EventRegistration::STATUSES as $value => $label)
                <li>
                    <button type="button" class="dropdown-item js-er-action {{ $st === $value ? 'active' : '' }}"
                            data-er-action="status" data-er-status="{{ $value }}" @disabled($st === $value)>
                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
@else
    <span class="er-badge er-badge--{{ $st }}">{{ $r->status_label }}</span>
@endif
