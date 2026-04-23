@extends('layouts.porto')

@section('title', 'Rifas')
@section('page-title', 'Gestão de Rifas')

@section('breadcrumbs')
    <li><span>Rifas</span></li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-9">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nome da rifa">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-control">
                    <option value="">Todos os status</option>
                    <option value="ativa" @selected(request('status') === 'ativa')>Ativa</option>
                    <option value="finalizada" @selected(request('status') === 'finalizada')>Finalizada</option>
                    <option value="cancelada" @selected(request('status') === 'cancelada')>Cancelada</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Filtrar</button>
                <a href="{{ route('rifas.index') }}" class="btn btn-default">Limpar</a>
            </div>
        </form>
    </div>
    <div class="col-md-3 text-end">
        @can('create', App\Models\Rifa::class)
            <a href="{{ route('rifas.create') }}" class="btn btn-success">Nova rifa</a>
        @endcan
    </div>
</div>

<section class="card">
    <header class="card-header">
        <h2 class="card-title">Rifas cadastradas</h2>
    </header>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Total números</th>
                        <th>Valor número</th>
                        <th>Data sorteio</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rifas as $rifa)
                        <tr>
                            <td>{{ $rifa->nome }}</td>
                            <td><span class="badge bg-{{ $rifa->status === 'ativa' ? 'success' : ($rifa->status === 'finalizada' ? 'info' : 'danger') }}">{{ ucfirst($rifa->status) }}</span></td>
                            <td>{{ $rifa->quantidade_numeros }}</td>
                            <td>R$ {{ number_format((float) $rifa->valor_numero, 2, ',', '.') }}</td>
                            <td>{{ optional($rifa->data_sorteio)->format('d/m/Y') ?: '-' }}</td>
                            <td class="text-end">
                                <a class="btn btn-xs btn-primary" href="{{ route('rifas.show', $rifa) }}">Abrir</a>
                                <a class="btn btn-xs btn-warning" href="{{ route('rifas.sorteios.index', $rifa) }}">Ver sorteios</a>
                                <a class="btn btn-xs btn-default" href="{{ route('rifas.cartelas.index', $rifa) }}">Cartelas</a>
                                <a class="btn btn-xs btn-success" href="{{ route('rifas.vendas.rapida.create', $rifa) }}">Venda rápida</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Nenhuma rifa encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rifas->links() }}
    </div>
</section>
@endsection
