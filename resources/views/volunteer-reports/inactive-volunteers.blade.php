@extends('layouts.porto')

@section('title', 'Voluntários Inativos')

@section('page-title', 'Voluntários Sem Servir')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas.index') }}">Serviço</a></li>
    <li><a href="{{ route('voluntarios.relatorios.dashboard') }}">Relatórios</a></li>
    <li>Voluntários Inativos</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-time me-2"></i>Voluntários Sem Servir
                </h2>
            </header>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="{{ route('voluntarios.relatorios.inactive') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="days" class="form-label">Dias sem servir</label>
                            <select name="days" id="days" class="form-select">
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 dias</option>
                                <option value="60" {{ $days == 60 ? 'selected' : '' }}>60 dias</option>
                                <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 dias</option>
                                <option value="120" {{ $days == 120 ? 'selected' : '' }}>120 dias</option>
                            </select>
                        </div>
                        <div class="col-md-4 mt-4">
                            <button type="submit" class="btn btn-default">
                                <i class="bx bx-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    Este relatório serve para <strong>cuidado pastoral</strong> e não para cobrança. 
                    Use para identificar voluntários que precisam de acompanhamento ou reintegração.
                </div>

                @if(count($inactiveVolunteers) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Voluntário</th>
                                    <th>Área Principal</th>
                                    <th>Último Serviço</th>
                                    <th>Dias Sem Servir</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inactiveVolunteers as $item)
                                    <tr>
                                        <td><strong>{{ $item['volunteer']->member->name }}</strong></td>
                                        <td>
                                            @php
                                                $mainArea = \App\Models\ServiceHistory::where('volunteer_id', $item['volunteer']->id)
                                                    ->where('status', 'serviu')
                                                    ->selectRaw('service_area_id, COUNT(*) as total')
                                                    ->groupBy('service_area_id')
                                                    ->orderBy('total', 'desc')
                                                    ->first();
                                                if ($mainArea) {
                                                    $areaName = \App\Models\ServiceArea::find($mainArea->service_area_id)->name;
                                                } else {
                                                    $areaName = 'N/A';
                                                }
                                            @endphp
                                            {{ $areaName }}
                                        </td>
                                        <td>
                                            @if($item['last_service'])
                                                {{ $item['last_service']->date->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">Nunca serviu</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item['days_since'] !== null)
                                                <span class="badge badge-warning">{{ $item['days_since'] }} dias</span>
                                            @else
                                                <span class="badge badge-danger">Nunca serviu</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('voluntarios.historico.volunteer', $item['volunteer']) }}" class="btn btn-sm btn-primary" title="Ver Histórico">
                                                <i class="bx bx-history"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-success text-center">
                        <i class="bx bx-check-circle me-2"></i>
                        Nenhum voluntário inativo encontrado para o período selecionado. Todos estão servindo regularmente! 🎉
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
