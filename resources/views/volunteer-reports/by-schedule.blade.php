@extends('layouts.porto')

@section('title', 'Relatório por Culto/Evento')

@section('page-title', 'Relatório por Culto/Evento')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.relatorios.dashboard') }}">Relatórios</a></li>
    <li>Por Culto/Evento</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-calendar me-2"></i>Relatório por Culto/Evento
                </h2>
            </header>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="{{ route('voluntarios.relatorios.by-schedule') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="schedule_id" class="form-label">Escala</label>
                            <select name="schedule_id" id="schedule_id" class="form-select">
                                <option value="">Todas</option>
                                @foreach($allSchedules as $schedule)
                                    <option value="{{ $schedule->id }}" {{ request('schedule_id') == $schedule->id ? 'selected' : '' }}>
                                        {{ $schedule->title }} - {{ $schedule->date->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="rascunho" {{ request('status') == 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                                <option value="publicada" {{ request('status') == 'publicada' ? 'selected' : '' }}>Publicada</option>
                                <option value="cancelada" {{ request('status') == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Data Início</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Data Fim</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3 mt-4">
                            <button type="submit" class="btn btn-default">
                                <i class="bx bx-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                @if($schedules->count() > 0)
                    @foreach($schedules as $schedule)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    {{ $schedule->title }}
                                    <small class="text-muted">- {{ $schedule->date->format('d/m/Y') }}</small>
                                    <span class="badge 
                                        @if($schedule->status == 'publicada') badge-success
                                        @elseif($schedule->status == 'cancelada') badge-danger
                                        @elseif($schedule->status == 'concluido') badge-info
                                        @else badge-warning
                                        @endif float-end">
                                        {{ ucfirst($schedule->status) }}
                                    </span>
                                </h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $schedule->load(['serviceHistories.member', 'serviceHistories.serviceArea', 'areas.serviceArea']);
                                    $histories = $schedule->serviceHistories;
                                @endphp
                                
                                @if($histories->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Voluntário</th>
                                                    <th>Área</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($histories as $history)
                                                    <tr>
                                                        <td>{{ $history->member->name }}</td>
                                                        <td>{{ $history->serviceArea->name }}</td>
                                                        <td>
                                                            @if($history->status == 'serviu')
                                                                <span class="badge badge-success">Serviu</span>
                                                            @elseif($history->status == 'confirmado_nao_compareceu')
                                                                <span class="badge badge-warning">Confirmado, não compareceu</span>
                                                            @elseif($history->status == 'indisponivel')
                                                                <span class="badge badge-info">Indisponível</span>
                                                            @else
                                                                <span class="badge badge-secondary">Substituído</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    @php
                                        $servedCount = $histories->where('status', 'serviu')->count();
                                        $totalCount = $histories->count();
                                    @endphp
                                    <div class="mt-2">
                                        <strong>Resumo:</strong> {{ $servedCount }} de {{ $totalCount }} voluntários serviram.
                                    </div>
                                @else
                                    <p class="text-muted mb-0">Nenhum histórico registrado para esta escala.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $schedules->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhuma escala encontrada para o período selecionado.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
