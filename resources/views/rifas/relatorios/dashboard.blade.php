@extends('layouts.porto')

@section('title', 'Relatórios de Rifas')
@section('page-title', 'Relatórios de Rifas')

@section('breadcrumbs')
    <li><a href="{{ route('rifas.index') }}">Rifas</a></li>
    <li><span>Relatórios</span></li>
@endsection

@section('content')
<section class="card mb-3">
    <header class="card-header">
        <h2 class="card-title">Filtros</h2>
    </header>
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="rifa_id" class="form-control">
                    <option value="">Todas as rifas</option>
                    @foreach($rifas as $rifa)
                        <option value="{{ $rifa->id }}" @selected(request('rifa_id') == $rifa->id)>{{ $rifa->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="inicio" class="form-control" value="{{ request('inicio') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="fim" class="form-control" value="{{ request('fim') }}">
            </div>
            <div class="col-md-3">
                <select name="vendedor_id" class="form-control">
                    <option value="">Todos os vendedores</option>
                    @foreach($vendedores as $vendedor)
                        <option value="{{ $vendedor->id }}" @selected((string) request('vendedor_id') === (string) $vendedor->id)>
                            {{ $vendedor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Aplicar</button>
                <a href="{{ route('rifas.relatorios.dashboard') }}" class="btn btn-default">Limpar</a>
            </div>
        </form>
        <div class="mt-3 d-flex gap-2">
            <a href="{{ route('rifas.relatorios.export.csv', request()->query()) }}" class="btn btn-success btn-sm">Exportar CSV (Excel)</a>
            <a href="{{ route('rifas.relatorios.export.pdf', request()->query()) }}" class="btn btn-danger btn-sm">Exportar PDF</a>
        </div>
    </div>
</section>

<div class="row">
    <div class="col-md-3"><section class="card card-featured-left card-featured-primary"><div class="card-body"><h5>Total movimentado</h5><h2>{{ $resumo['total'] }}</h2></div></section></div>
    <div class="col-md-3"><section class="card card-featured-left card-featured-success"><div class="card-body"><h5>Vendidos</h5><h2>{{ $resumo['vendidos'] }}</h2></div></section></div>
    <div class="col-md-3"><section class="card card-featured-left card-featured-warning"><div class="card-body"><h5>Reservados</h5><h2>{{ $resumo['reservados'] }}</h2></div></section></div>
    <div class="col-md-3"><section class="card card-featured-left card-featured-info"><div class="card-body"><h5>Arrecadado</h5><h2>R$ {{ number_format($resumo['arrecadado'], 2, ',', '.') }}</h2></div></section></div>
</div>

@if($rifaSelecionada)
<div class="row">
    <div class="col-md-3">
        <section class="card">
            <div class="card-body">
                <h5>Disponíveis na rifa selecionada</h5>
                <h2>{{ $resumo['disponiveis'] }}</h2>
            </div>
        </section>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <section class="card">
            <header class="card-header"><h2 class="card-title">Vendas por período</h2></header>
            <div class="card-body">
                <canvas id="chartVendasPeriodo" height="110"></canvas>
            </div>
        </section>
    </div>
    <div class="col-md-4">
        <section class="card">
            <header class="card-header"><h2 class="card-title">Ranking de vendedores</h2></header>
            <div class="card-body">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Vendedor</th><th>Qtd</th><th>Valor</th></tr></thead>
                    <tbody>
                        @forelse($ranking->take(10) as $item)
                            <tr>
                                <td>{{ $item['vendedor'] }}</td>
                                <td>{{ $item['quantidade'] }}</td>
                                <td>R$ {{ number_format($item['valor'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center">Sem dados no período</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = @json($periodo['labels']);
    const quantidadeData = @json($periodo['quantidades']);
    const valorData = @json($periodo['valores']);

    const ctx = document.getElementById('chartVendasPeriodo');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Números vendidos',
                        data: quantidadeData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Valor arrecadado',
                        data: valorData,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.3)',
                        type: 'line',
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left'
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }
</script>
@endpush
