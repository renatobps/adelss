@extends('layouts.porto')

@section('title', 'Voluntários que Mais Servem')

@section('page-title', 'Voluntários que Mais Servem')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.relatorios.dashboard') }}">Relatórios</a></li>
    <li>Voluntários que Mais Servem</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-trophy me-2"></i>Voluntários que Mais Servem
                </h2>
            </header>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="{{ route('voluntarios.relatorios.top-volunteers') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Data Início</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Data Fim</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
                        <div class="col-md-2">
                            <label for="limit" class="form-label">Limite</label>
                            <input type="number" name="limit" id="limit" class="form-control" value="{{ $limit }}" min="1" max="100">
                        </div>
                        <div class="col-md-4 mt-4">
                            <button type="submit" class="btn btn-default">
                                <i class="bx bx-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                @if($topVolunteers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Voluntário</th>
                                    <th>Total de Serviços</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topVolunteers as $index => $item)
                                    <tr>
                                        <td>
                                            @if($index == 0)
                                                <span class="badge badge-warning">🥇</span>
                                            @elseif($index == 1)
                                                <span class="badge badge-secondary">🥈</span>
                                            @elseif($index == 2)
                                                <span class="badge badge-info">🥉</span>
                                            @else
                                                <strong>{{ $index + 1 }}º</strong>
                                            @endif
                                        </td>
                                        <td><strong>{{ $item->volunteer->member->name }}</strong></td>
                                        <td><span class="badge badge-success badge-lg">{{ $item->total_services }}</span> serviços</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum registro encontrado para o período selecionado.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
