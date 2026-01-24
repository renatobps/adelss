@extends('layouts.porto')

@section('title', 'Histórico de Serviço')

@section('page-title', 'Histórico de Serviço')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.historico.index') }}">Histórico de Serviço</a></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-history me-2"></i>Histórico de Serviço
                </h2>
            </header>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="{{ route('voluntarios.historico.index') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="volunteer" class="form-label">Voluntário</label>
                            <select name="volunteer" id="volunteer" class="form-select">
                                <option value="">Todos</option>
                                @foreach($volunteers as $volunteer)
                                    <option value="{{ $volunteer->id }}" {{ request('volunteer') == $volunteer->id ? 'selected' : '' }}>
                                        {{ $volunteer->member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="area" class="form-label">Área</label>
                            <select name="area" id="area" class="form-select">
                                <option value="">Todas</option>
                                @foreach($serviceAreas as $area)
                                    <option value="{{ $area->id }}" {{ request('area') == $area->id ? 'selected' : '' }}>
                                        {{ $area->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Todos</option>
                                @foreach($statusLabels as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="service_type" class="form-label">Tipo</label>
                            <select name="service_type" id="service_type" class="form-select">
                                <option value="">Todos</option>
                                <option value="culto" {{ request('service_type') == 'culto' ? 'selected' : '' }}>Culto</option>
                                <option value="evento" {{ request('service_type') == 'evento' ? 'selected' : '' }}>Evento</option>
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
                        <div class="col-md-2 mt-4">
                            <button type="submit" class="btn btn-default">
                                <i class="bx bx-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                @if($histories->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Voluntário</th>
                                    <th>Área</th>
                                    <th>Culto/Evento</th>
                                    <th>Status</th>
                                    <th>Observações</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($histories as $history)
                                    <tr>
                                        <td>{{ $history->date->format('d/m/Y') }}</td>
                                        <td><strong>{{ $history->member->name }}</strong></td>
                                        <td>{{ $history->serviceArea->name }}</td>
                                        <td>
                                            {{ $history->schedule ? $history->schedule->title : 'Escala não encontrada' }}
                                            @if($history->service_type == 'culto')
                                                <span class="badge badge-info">Culto</span>
                                            @else
                                                <span class="badge badge-primary">Evento</span>
                                            @endif
                                        </td>
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
                                        <td>{{ $history->notes ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('voluntarios.historico.show', $history) }}" class="btn btn-sm btn-default" title="Ver">
                                                <i class="bx bx-show"></i>
                                            </a>
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
                        Nenhum registro de histórico encontrado.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
