@extends('layouts.porto')

@section('title', 'Dashboard - Liderança')

@section('page-title', 'Dashboard - Liderança')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Dashboard Liderança</span></li>
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h3 class="text-primary">{{ $totalEmDiscipulado }}</h3>
                <p class="mb-0">Membros em Discipulado</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h3 class="text-success">{{ $totalCiclosAtivos }}</h3>
                <p class="mb-0">Ciclos Ativos</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h3 class="text-warning">{{ $membrosSemAcompanhamento->count() }}</h3>
                <p class="mb-0">Sem Acompanhamento</p>
            </div>
        </div>
    </div>
</div>

@if($membrosSemAcompanhamento->count() > 0)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Membros sem Acompanhamento</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($membrosSemAcompanhamento as $membro)
                                <tr>
                                    <td>{{ $membro->name }}</td>
                                    <td>{{ $membro->email ?? '-' }}</td>
                                    <td>{{ $membro->phone ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($evolucaoPorCiclo->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">Evolução por Ciclo</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ciclo</th>
                                <th>Data Início</th>
                                <th>Total de Membros</th>
                                <th>Membros Ativos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($evolucaoPorCiclo as $ciclo)
                                <tr>
                                    <td>{{ $ciclo['nome'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($ciclo['data_inicio'])->format('d/m/Y') }}</td>
                                    <td>{{ $ciclo['total_membros'] }}</td>
                                    <td>{{ $ciclo['membros_ativos'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
