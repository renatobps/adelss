@extends('layouts.porto')

@section('title', 'Histórico - ' . $volunteer->member->name)

@section('page-title', 'Histórico de Serviço - ' . $volunteer->member->name)

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.historico.index') }}">Histórico de Serviço</a></li>
    <li>{{ $volunteer->member->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-user me-2"></i>{{ $volunteer->member->name }}
                </h2>
            </header>
            <div class="card-body">
                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">{{ $totalServices }}</h3>
                                <p class="mb-0">Vezes que serviu</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">
                                    @if($lastService)
                                        {{ $lastService->date->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </h3>
                                <p class="mb-0">Último serviço</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3 class="mb-0">
                                    @if($mainAreaData)
                                        {{ $mainAreaData->name }}
                                    @else
                                        -
                                    @endif
                                </h3>
                                <p class="mb-0">Área principal</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <form method="GET" action="{{ route('voluntarios.historico.volunteer', $volunteer) }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="date_from" class="form-label">Data Início</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label">Data Fim</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-4 mt-4">
                            <button type="submit" class="btn btn-default">
                                <i class="bx bx-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Lista de Histórico -->
                @if($histories->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Área</th>
                                    <th>Culto/Evento</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($histories as $history)
                                    <tr>
                                        <td>{{ $history->date->format('d/m/Y') }}</td>
                                        <td>{{ $history->serviceArea->name }}</td>
                                        <td>{{ $history->schedule ? $history->schedule->title : 'Escala não encontrada' }}</td>
                                        <td>
                                            @if($history->status == 'serviu')
                                                <span class="badge badge-success">{{ $statusLabels[$history->status] }}</span>
                                            @elseif($history->status == 'confirmado_nao_compareceu')
                                                <span class="badge badge-warning">{{ $statusLabels[$history->status] }}</span>
                                            @elseif($history->status == 'indisponivel')
                                                <span class="badge badge-info">{{ $statusLabels[$history->status] }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $statusLabels[$history->status] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $histories->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum registro de histórico encontrado para este período.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
