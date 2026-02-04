@extends('layouts.porto')

@section('title', 'Escalas - Moriah')

@section('page-title', 'Escalas')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Início</a></li>
    <li><a href="{{ route('moriah.ministerio') }}">Moriah</a></li>
    <li><span>Escalas</span></li>
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
                    <i class="bx bx-calendar me-2"></i>Escalas do Moriah
                </h2>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="{{ route('moriah.schedules.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-2"></i>Nova Escala
                    </a>
                </div>

                <form method="GET" action="{{ route('moriah.schedules.index') }}" class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="month" class="form-label">Mês</label>
                        <select name="month" id="month" class="form-select" onchange="this.form.submit()">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->locale('pt_BR')->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year" class="form-label">Ano</label>
                        <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                            @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="rascunho" {{ request('status') == 'rascunho' ? 'selected' : '' }}>Rascunho</option>
                            <option value="publicada" {{ request('status') == 'publicada' ? 'selected' : '' }}>Publicada</option>
                            <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                        </select>
                    </div>
                </form>

                @if($schedules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Participantes</th>
                                    <th>Músicas</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->title }}</td>
                                        <td>{{ $schedule->date->format('d/m/Y') }}</td>
                                        <td>{{ $schedule->time ? \Carbon\Carbon::parse($schedule->time)->format('H:i') : '-' }}</td>
                                        <td>{{ $schedule->members->count() }}</td>
                                        <td>{{ $schedule->songs->count() }}</td>
                                        <td>
                                            @if($schedule->status == 'publicada')
                                                <span class="badge bg-success">Publicada</span>
                                            @elseif($schedule->status == 'concluido')
                                                <span class="badge bg-info">Concluído</span>
                                            @else
                                                <span class="badge bg-secondary">Rascunho</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('moriah.schedules.show', $schedule) }}" class="btn btn-sm btn-default" title="Ver">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @if($schedule->status == 'publicada' || $schedule->status == 'concluido')
                                                    <a href="{{ route('moriah.schedules.pdf', $schedule) }}" class="btn btn-sm btn-info" title="Imprimir PDF" target="_blank">
                                                        <i class="bx bx-printer"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('moriah.schedules.edit', $schedule) }}" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <form action="{{ route('moriah.schedules.destroy', $schedule) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta escala?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-calendar" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Nenhuma escala cadastrada para este período.</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
