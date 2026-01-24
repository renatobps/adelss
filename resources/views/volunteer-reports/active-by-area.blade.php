@extends('layouts.porto')

@section('title', 'Voluntários Ativos por Área')

@section('page-title', 'Voluntários Ativos por Área')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.relatorios.dashboard') }}">Relatórios</a></li>
    <li>Voluntários Ativos por Área</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-group me-2"></i>Voluntários Ativos por Área
                </h2>
            </header>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="{{ route('voluntarios.relatorios.active-by-area') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Data Início</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Data Fim</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
                        <div class="col-md-3 mt-4">
                            <button type="submit" class="btn btn-default">
                                <i class="bx bx-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                @if(count($reportData) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Área</th>
                                    <th>Total de Voluntários</th>
                                    <th>Serviram no Período</th>
                                    <th>Taxa de Participação</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData as $data)
                                    <tr>
                                        <td><strong>{{ $data['area']->name }}</strong></td>
                                        <td>{{ $data['total_volunteers'] }}</td>
                                        <td>{{ $data['served_count'] }}</td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar 
                                                    @if($data['participation_rate'] >= 70) bg-success
                                                    @elseif($data['participation_rate'] >= 50) bg-warning
                                                    @else bg-danger
                                                    @endif" 
                                                    role="progressbar" 
                                                    style="width: {{ min($data['participation_rate'], 100) }}%">
                                                    {{ $data['participation_rate'] }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($data['participation_rate'] >= 70)
                                                <span class="badge badge-success">✅ Saudável</span>
                                            @elseif($data['participation_rate'] >= 50)
                                                <span class="badge badge-warning">⚠ Atenção</span>
                                            @else
                                                <span class="badge badge-danger">❌ Crítico</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum dado encontrado para o período selecionado.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
