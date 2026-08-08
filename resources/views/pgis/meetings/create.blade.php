@extends('layouts.porto')

@section('title', 'Nova reunião')

@section('page-title', 'Nova reunião')

@section('breadcrumbs')
    <li><a href="{{ route('pgis.index') }}">PGIs</a></li>
    <li><a href="{{ route('pgis.show', $pgi) }}">{{ $pgi->name }}</a></li>
    <li><span>Nova reunião</span></li>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/css/pgis.css') }}?v={{ @filemtime(public_path('css/css/pgis.css')) ?: '1' }}">
@endpush

@section('content')
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card pgi-card">
            <header class="card-header">
                <h5 class="card-title mb-0"><i class="bx bx-calendar-plus me-2"></i>Dados da reunião</h5>
            </header>
            <div class="card-body">
                <form action="{{ route('pgis.meetings.store', $pgi) }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="meeting_date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('meeting_date') is-invalid @enderror"
                                   id="meeting_date" name="meeting_date"
                                   value="{{ old('meeting_date', $suggestedDate) }}" required>
                            @error('meeting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="subject" class="form-label">Tema / assunto</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                   id="subject" name="subject" value="{{ old('subject') }}"
                                   placeholder="Ex.: Estudo sobre gratidão">
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="total_value" class="form-label">Oferta arrecadada</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control @error('total_value') is-invalid @enderror"
                                   id="total_value" name="total_value" value="{{ old('total_value', '0.00') }}">
                            @error('total_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                                  rows="4" placeholder="Anotações da reunião...">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="skip_attendance" name="skip_attendance">
                        <label class="form-check-label" for="skip_attendance">
                            Salvar sem fazer a chamada agora
                        </label>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Salvar e fazer a chamada
                        </button>
                        <a href="{{ route('pgis.show', $pgi) }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card pgi-card">
            <header class="card-header">
                <h5 class="card-title mb-0"><i class="bx bx-repeat me-2"></i>Criar reuniões recorrentes</h5>
            </header>
            <div class="card-body">
                <p class="text-muted small">
                    @if($pgi->day_of_week)
                        Cria as próximas reuniões toda <strong>{{ ucfirst($pgi->day_of_week) }}</strong>{{ $pgi->time_schedule ? ' à ' . mb_strtolower($pgi->time_schedule) : '' }},
                        já com a chamada pendente. Datas que já tenham reunião são ignoradas.
                    @else
                        Cria as próximas reuniões semanalmente a partir da data escolhida. Datas que já tenham reunião são ignoradas.
                    @endif
                </p>

                <form action="{{ route('pgis.meetings.recurring', $pgi) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Primeira reunião <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date"
                               value="{{ old('start_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="occurrences" class="form-label">Quantidade <span class="text-danger">*</span></label>
                        <select class="form-select" id="occurrences" name="occurrences" required>
                            @for($i = 1; $i <= $recurringLimit; $i++)
                                <option value="{{ $i }}" {{ (int) old('occurrences', 4) === $i ? 'selected' : '' }}>
                                    {{ $i }} {{ $i === 1 ? 'reunião' : 'reuniões' }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="recurring_subject" class="form-label">Tema padrão</label>
                        <input type="text" class="form-control" id="recurring_subject" name="subject"
                               placeholder="Opcional">
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bx bx-calendar-check me-1"></i>Criar recorrência
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
