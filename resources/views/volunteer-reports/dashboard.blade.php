@extends('layouts.porto')

@section('title', 'Relatórios de Voluntários')

@section('page-title', 'Relatórios de Voluntários')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.relatorios.dashboard') }}">Relatórios</a></li>
@endsection

@section('content')
<div class="row">
    <!-- Estatísticas Gerais -->
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $totalVolunteers }}</h3>
                <p class="mb-0">Voluntários Ativos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $totalActiveAreas }}</h3>
                <p class="mb-0">Áreas de Serviço</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $servicesLastMonth }}</h3>
                <p class="mb-0">Serviços (último mês)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $inactiveCount }}</h3>
                <p class="mb-0">Sem servir há 60+ dias</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-bar-chart me-2"></i>Relatórios Disponíveis
                </h2>
            </header>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bx bx-group me-2"></i>Voluntários Ativos por Área
                                </h5>
                                <p class="card-text">Visualize a distribuição e participação de voluntários por área de serviço.</p>
                                <a href="{{ route('voluntarios.relatorios.active-by-area') }}" class="btn btn-primary">
                                    Ver Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bx bx-trophy me-2"></i>Voluntários que Mais Servem
                                </h5>
                                <p class="card-text">Ranking de voluntários com maior frequência de serviços realizados.</p>
                                <a href="{{ route('voluntarios.relatorios.top-volunteers') }}" class="btn btn-success">
                                    Ver Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bx bx-time me-2"></i>Voluntários Inativos
                                </h5>
                                <p class="card-text">Identifique voluntários que não serviram há um período específico.</p>
                                <a href="{{ route('voluntarios.relatorios.inactive') }}" class="btn btn-warning">
                                    Ver Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bx bx-error-circle me-2"></i>Déficit por Área
                                </h5>
                                <p class="card-text">Compare a quantidade mínima necessária com a média real de voluntários.</p>
                                <a href="{{ route('voluntarios.relatorios.deficit') }}" class="btn btn-danger">
                                    Ver Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bx bx-calendar me-2"></i>Por Culto/Evento
                                </h5>
                                <p class="card-text">Relatório detalhado de cada culto ou evento realizado.</p>
                                <a href="{{ route('voluntarios.relatorios.by-schedule') }}" class="btn btn-info">
                                    Ver Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Áreas -->
                @if($topAreas->count() > 0)
                    <div class="mt-4">
                        <h4 class="mb-3">Top 5 Áreas com Mais Serviços (Último Mês)</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Área</th>
                                        <th>Total de Serviços</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topAreas as $topArea)
                                        <tr>
                                            <td>{{ $topArea->serviceArea->name }}</td>
                                            <td><strong>{{ $topArea->total }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
