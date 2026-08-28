@can('manageRegistrations', $event)
    @php($size = $size ?? 'sm')
    <form method="post" action="{{ route('agenda.eventos.registrations.toggle', $event) }}" class="d-inline"
          @if($event->registration_enabled)
          onsubmit="return confirm('Encerrar as inscrições deste evento? A página pública deixa de aceitar novos inscritos.');"
          @endif>
        @csrf
        @if($event->registration_enabled)
            <button type="submit" class="btn btn-{{ $size }} btn-danger">
                <i class="bx bx-lock-alt"></i> Encerrar inscrições
            </button>
        @else
            <button type="submit" class="btn btn-{{ $size }} btn-outline-success">
                <i class="bx bx-lock-open-alt"></i> Reabrir inscrições
            </button>
        @endif
    </form>
@endcan
