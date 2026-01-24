@extends('layouts.porto')

@section('title', 'Déficit por Área')

@section('page-title', 'Déficit por Área')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.relatorios.dashboard') }}">Relatórios</a></li>
    <li>Déficit por Área</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-error-circle me-2"></i>Déficit por Área
                </h2>
            </header>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="{{ route('voluntarios.relatorios.deficit') }}" class="mb-4">
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
                                    <th>Mínimo Necessário</th>
                                    <th>Média Real</th>
                                    <th>Déficit</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData as $data)
                                    <tr>
                                        <td><strong>{{ $data['area']->name }}</strong></td>
                                        <td>{{ $data['min_quantity'] }}</td>
                                        <td>{{ $data['avg_real'] }}</td>
                                        <td>
                                            @if($data['deficit'] > 0)
                                                <span class="badge badge-danger">-{{ $data['deficit'] }}</span>
                                            @else
                                                <span class="badge badge-success">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($data['status'] == 'ok')
                                                <span class="badge badge-success">✅ OK</span>
                                            @else
                                                <span class="badge badge-danger">❌ Déficit</span>
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
