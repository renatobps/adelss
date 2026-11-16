@can('delete', $rifa)
    <form method="POST" action="{{ route('rifas.destroy', $rifa) }}" class="d-inline-block" onsubmit="if (!confirm('Excluir esta rifa? Ela sairá da listagem e não poderá mais ser gerenciada. O histórico de números e vendas será preservado.')) return false; this.querySelector('button').disabled = true;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger {{ $buttonSize ?? '' }}">Excluir rifa</button>
    </form>
@endcan
