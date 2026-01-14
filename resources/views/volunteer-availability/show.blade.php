@extends('layouts.porto')

@section('title', 'Visualizar Disponibilidade')

@section('page-title', 'Disponibilidade - {{ $disponibilidade->volunteer->member->name }}')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.disponibilidade.index') }}">Disponibilidade</a></li>
    <li><span>Visualizar</span></li>
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
                    <i class="bx bx-time me-2"></i>Disponibilidade - {{ $disponibilidade->volunteer->member->name }}
                </h2>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-end mb-4">
                    <a href="{{ route('voluntarios.disponibilidade.edit', $disponibilidade) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-2"></i>Editar
                    </a>
                    <a href="{{ route('voluntarios.disponibilidade.index') }}" class="btn btn-default">
                        <i class="bx bx-arrow-back me-2"></i>Voltar
                    </a>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Dias Disponíveis</h2>
                            </header>
                            <div class="card-body">
                                @if($disponibilidade->days_of_week && count($disponibilidade->days_of_week) > 0)
                                    @php
                                        $daysLabels = [
                                            'segunda' => 'Segunda-feira',
                                            'terça' => 'Terça-feira',
                                            'quarta' => 'Quarta-feira',
                                            'quinta' => 'Quinta-feira',
                                            'sexta' => 'Sexta-feira',
                                            'sábado' => 'Sábado',
                                            'domingo' => 'Domingo'
                                        ];
                                        $days = array_map(function($day) use ($daysLabels) {
                                            return $daysLabels[$day] ?? $day;
                                        }, $disponibilidade->days_of_week);
                                    @endphp
                                    <ul class="list-unstyled mb-0">
                                        @foreach($days as $day)
                                            <li><i class="bx bx-check text-success me-2"></i>{{ $day }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted mb-0">Nenhum dia definido</p>
                                @endif
                            </div>
                        </section>
                    </div>

                    <div class="col-md-6 mb-4">
                        <section class="card">
                            <header class="card-header">
                                <h2 class="card-title">Horários</h2>
                            </header>
                            <div class="card-body">
                                @if($disponibilidade->time_start && $disponibilidade->time_end)
                                    <p class="mb-0">
                                        <strong>De:</strong> {{ \Carbon\Carbon::parse($disponibilidade->time_start)->format('H:i') }}<br>
                                        <strong>Até:</strong> {{ \Carbon\Carbon::parse($disponibilidade->time_end)->format('H:i') }}
                                    </p>
                                @else
                                    <p class="text-muted mb-0">Horários não definidos</p>
                                @endif
                            </div>
                        </section>
                    </div>
                </div>

                <div class="row">
                    @if($disponibilidade->unavailable_start && $disponibilidade->unavailable_end)
                        <div class="col-md-6 mb-4">
                            <section class="card border-warning">
                                <header class="card-header bg-warning text-white">
                                    <h2 class="card-title">Indisponibilidade Temporária</h2>
                                </header>
                                <div class="card-body">
                                    <p class="mb-0">
                                        <strong>De:</strong> {{ $disponibilidade->unavailable_start->format('d/m/Y') }}<br>
                                        <strong>Até:</strong> {{ $disponibilidade->unavailable_end->format('d/m/Y') }}
                                    </p>
                                </div>
                            </section>
                        </div>
                    @endif

                    @if($disponibilidade->notes)
                        <div class="col-md-6 mb-4">
                            <section class="card">
                                <header class="card-header">
                                    <h2 class="card-title">Observações</h2>
                                </header>
                                <div class="card-body">
                                    <p class="mb-0">{{ $disponibilidade->notes }}</p>
                                </div>
                            </section>
                        </div>
                    @endif
                </div>

                @if($disponibilidade->volunteer->availabilityEvents->count() > 0)
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <section class="card">
                                <header class="card-header">
                                    <h2 class="card-title">Eventos Específicos (Agenda)</h2>
                                </header>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Evento</th>
                                                    <th>Data</th>
                                                    <th>Horário</th>
                                                    <th>Local</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($disponibilidade->volunteer->availabilityEvents as $event)
                                                    <tr>
                                                        <td><strong>{{ $event->title }}</strong></td>
                                                        <td>{{ \Carbon\Carbon::parse($event->start_date)->format('d/m/Y') }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($event->start_date)->format('H:i') }}</td>
                                                        <td>{{ $event->location ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
