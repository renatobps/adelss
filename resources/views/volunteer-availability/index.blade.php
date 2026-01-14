@extends('layouts.porto')

@section('title', 'Disponibilidade de Voluntários')

@section('page-title', 'Disponibilidade de Voluntários')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.disponibilidade.index') }}">Disponibilidade</a></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bx bx-info-circle me-2"></i>{{ session('info') }}
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
                    <i class="bx bx-time me-2"></i>Disponibilidade de Voluntários
                </h2>
            </header>
            <div class="card-body">
                @if($volunteers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Voluntário</th>
                                    <th>Dias Disponíveis</th>
                                    <th>Horários</th>
                                    <th>Indisponibilidade</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($volunteers as $volunteer)
                                    <tr>
                                        <td>
                                            <strong>{{ $volunteer->member->name }}</strong>
                                        </td>
                                        <td>
                                            @if($volunteer->availability && $volunteer->availability->days_of_week)
                                                @php
                                                    $daysLabels = [
                                                        'segunda' => 'Segunda',
                                                        'terça' => 'Terça',
                                                        'quarta' => 'Quarta',
                                                        'quinta' => 'Quinta',
                                                        'sexta' => 'Sexta',
                                                        'sábado' => 'Sábado',
                                                        'domingo' => 'Domingo'
                                                    ];
                                                    $days = array_map(function($day) use ($daysLabels) {
                                                        return $daysLabels[$day] ?? $day;
                                                    }, $volunteer->availability->days_of_week);
                                                @endphp
                                                {{ implode(', ', $days) }}
                                            @else
                                                <span class="text-muted">Não definido</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($volunteer->availability && $volunteer->availability->time_start && $volunteer->availability->time_end)
                                                {{ \Carbon\Carbon::parse($volunteer->availability->time_start)->format('H:i') }} - 
                                                {{ \Carbon\Carbon::parse($volunteer->availability->time_end)->format('H:i') }}
                                            @else
                                                <span class="text-muted">Não definido</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($volunteer->availability && $volunteer->availability->unavailable_start && $volunteer->availability->unavailable_end)
                                                {{ \Carbon\Carbon::parse($volunteer->availability->unavailable_start)->format('d/m/Y') }} - 
                                                {{ \Carbon\Carbon::parse($volunteer->availability->unavailable_end)->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($volunteer->availability)
                                                <span class="badge badge-success">Cadastrada</span>
                                            @else
                                                <span class="badge badge-secondary">Pendente</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($volunteer->availability)
                                                    <a href="{{ route('voluntarios.disponibilidade.show', $volunteer->availability) }}" class="btn btn-sm btn-default" title="Ver">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                    <a href="{{ route('voluntarios.disponibilidade.edit', $volunteer->availability) }}" class="btn btn-sm btn-default" title="Editar">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('voluntarios.disponibilidade.create', ['volunteer_id' => $volunteer->id]) }}" class="btn btn-sm btn-primary" title="Cadastrar">
                                                        <i class="bx bx-plus"></i> Cadastrar
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $volunteers->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum voluntário cadastrado.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
