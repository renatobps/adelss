@extends('layouts.porto')

@section('title', 'Cadastrar Disponibilidade')

@section('page-title', 'Cadastrar Disponibilidade')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.disponibilidade.index') }}">Disponibilidade</a></li>
    <li><span>Novo</span></li>
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
                    <i class="bx bx-time me-2"></i>Cadastrar Disponibilidade - {{ $volunteer->member->name }}
                </h2>
            </header>
            <div class="card-body">
                <form action="{{ route('voluntarios.disponibilidade.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="volunteer_id" value="{{ $volunteer->id }}">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2">Dias da Semana</h5>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Dias Disponíveis</label>
                            <div class="row">
                                @php
                                    $days = [
                                        'segunda' => 'Segunda-feira',
                                        'terça' => 'Terça-feira',
                                        'quarta' => 'Quarta-feira',
                                        'quinta' => 'Quinta-feira',
                                        'sexta' => 'Sexta-feira',
                                        'sábado' => 'Sábado',
                                        'domingo' => 'Domingo'
                                    ];
                                @endphp
                                @foreach($days as $value => $label)
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="day_{{ $value }}" 
                                                   name="days_of_week[]" 
                                                   value="{{ $value }}"
                                                   {{ old('days_of_week') && in_array($value, old('days_of_week')) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_{{ $value }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('days_of_week')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2 mt-4">Horários</h5>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="time_start" class="form-label">Horário de Início</label>
                            <input type="time" class="form-control @error('time_start') is-invalid @enderror" 
                                   id="time_start" name="time_start" value="{{ old('time_start') }}">
                            @error('time_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="time_end" class="form-label">Horário de Término</label>
                            <input type="time" class="form-control @error('time_end') is-invalid @enderror" 
                                   id="time_end" name="time_end" value="{{ old('time_end') }}">
                            @error('time_end')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2 mt-4">Indisponibilidade Temporária</h5>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="unavailable_start" class="form-label">Data de Início</label>
                            <input type="date" class="form-control @error('unavailable_start') is-invalid @enderror" 
                                   id="unavailable_start" name="unavailable_start" value="{{ old('unavailable_start') }}">
                            @error('unavailable_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="unavailable_end" class="form-label">Data de Término</label>
                            <input type="date" class="form-control @error('unavailable_end') is-invalid @enderror" 
                                   id="unavailable_end" name="unavailable_end" value="{{ old('unavailable_end') }}">
                            @error('unavailable_end')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2 mt-4">Eventos Específicos (Agenda)</h5>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="events" class="form-label">Selecione os eventos da agenda</label>
                            <select class="form-select @error('events') is-invalid @enderror" 
                                    id="events" name="events[]" multiple size="8">
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}" {{ old('events') && in_array($event->id, old('events')) ? 'selected' : '' }}>
                                        {{ $event->title }} - {{ \Carbon\Carbon::parse($event->start_date)->format('d/m/Y H:i') }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos eventos</small>
                            @error('events')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2 mt-4">Observações</h5>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="notes" class="form-label">Observações</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="4" 
                                      placeholder="Informações adicionais sobre a disponibilidade...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('voluntarios.disponibilidade.index') }}" class="btn btn-default">
                                <i class="bx bx-arrow-back me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-check me-2"></i>Salvar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
