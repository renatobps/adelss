@extends('layouts.porto')

@section('title', 'Histórico de Sorteios')
@section('page-title', 'Sorteios da Rifa')

@section('breadcrumbs')
    <li><a href="{{ route('rifas.index') }}">Rifas</a></li>
    <li><a href="{{ route('rifas.show', $rifa) }}">{{ $rifa->nome }}</a></li>
    <li><span>Sorteios</span></li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <h4 class="mb-1">{{ $rifa->nome }}</h4>
        <p class="mb-0 text-muted">Histórico dos ganhadores sorteados</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('rifas.show', $rifa) }}" class="btn btn-default">Voltar para rifa</a>
    </div>
</div>

<section class="card">
    <header class="card-header">
        <h2 class="card-title">Sorteios registrados</h2>
    </header>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Número</th>
                        <th>Comprador</th>
                        <th>Vendedor</th>
                        <th>Sorteado por</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sorteios as $sorteio)
                        <tr>
                            <td>{{ optional($sorteio->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td><strong>{{ $sorteio->numero }}</strong></td>
                            <td>{{ $sorteio->comprador_nome }}</td>
                            <td>{{ $sorteio->vendedor?->name ?: ($sorteio->vendedor_nome ?: 'Não informado') }}</td>
                            <td>{{ $sorteio->sorteadoPor?->name ?: 'Sistema' }}</td>
                            <td class="text-end">
                                @can('update', $rifa)
                                <form action="{{ route('rifas.sorteios.destroy', [$rifa, $sorteio]) }}" method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este sorteio?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Excluir</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Nenhum sorteio registrado para esta rifa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $sorteios->links() }}
    </div>
</section>
@endsection
