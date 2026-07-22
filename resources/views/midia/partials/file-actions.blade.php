@can('midia.arquivos.view')
    <a href="{{ route('midia.download', $file) }}" class="btn btn-sm btn-light" title="Download"><i class="bx bx-download"></i></a>
@endcan
@can('midia.arquivos.upload')
    <button type="button" class="btn btn-sm btn-light" title="Mover" data-move-url="{{ route('midia.move', $file) }}"><i class="bx bx-move"></i></button>
@endcan
@can('midia.arquivos.delete')
    <form method="POST" action="{{ route('midia.destroy', $file) }}" class="d-inline" onsubmit="return confirm('Excluir este arquivo do Drive?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm btn-light text-danger" title="Excluir"><i class="bx bx-trash"></i></button>
    </form>
@endcan
