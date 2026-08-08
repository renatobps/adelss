@php
    $membersTotal = $membersTotal ?? $pgi->members->count();
    $registered = $meeting->hasAttendanceRegistered();
@endphp
<div class="pgi-meeting">
    <div class="pgi-meeting__date">
        <span class="pgi-meeting__day">{{ $meeting->meeting_date->format('d') }}</span>
        <span class="pgi-meeting__month">{{ $meeting->meeting_date->locale('pt_BR')->translatedFormat('M') }}</span>
    </div>

    <div class="pgi-meeting__body">
        <div class="pgi-meeting__title">
            <a href="{{ route('pgis.meetings.show', [$pgi, $meeting]) }}" class="text-decoration-none">
                {{ $meeting->subject ?: 'Reunião de ' . $meeting->meeting_date->format('d/m/Y') }}
            </a>
        </div>
        <div class="pgi-meeting__meta">
            @if($registered)
                {{ $meeting->participants_count }}/{{ $membersTotal }} participantes
                @if($meeting->visitors_count > 0)
                    · {{ $meeting->visitors_count }} {{ $meeting->visitors_count === 1 ? 'visitante' : 'visitantes' }}
                @endif
            @else
                Chamada ainda não realizada
            @endif
        </div>
        <div class="mt-1">
            @if($registered)
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bx bx-check me-1"></i>Presença registrada
                </span>
            @else
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                    <i class="bx bx-time-five me-1"></i>Chamada pendente
                </span>
            @endif
        </div>
    </div>

    <div class="dropdown flex-shrink-0">
        <button type="button" class="btn btn-sm btn-light pgi-touch" data-bs-toggle="dropdown"
                data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Ações da reunião">
            <i class="bx bx-dots-vertical-rounded"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item" href="{{ route('pgis.meetings.show', [$pgi, $meeting]) }}">
                    <i class="bx bx-show me-2"></i>Ver detalhes
                </a>
            </li>
            @if($canManageMeetings)
                <li>
                    <a class="dropdown-item" href="{{ route('pgis.meetings.attendance', [$pgi, $meeting]) }}">
                        <i class="bx bx-list-check me-2"></i>{{ $registered ? 'Editar presença' : 'Registrar presença' }}
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('pgis.meetings.edit', [$pgi, $meeting]) }}">
                        <i class="bx bx-edit me-2"></i>Editar reunião
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('pgis.meetings.destroy', [$pgi, $meeting]) }}" method="POST"
                          onsubmit="return confirm('Excluir esta reunião? A lista de presença será perdida.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bx bx-trash me-2"></i>Excluir
                        </button>
                    </form>
                </li>
            @endif
        </ul>
    </div>
</div>
