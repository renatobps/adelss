@extends('layouts.porto')

@section('title', 'Configurar Escalas')

@section('page-title', 'Configurar Escalas')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas-mensais.index') }}">Escalas</a></li>
    <li><span>Configurações</span></li>
@endsection

@section('content')
<form method="POST" action="{{ route('voluntarios.escalas-mensais.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-1">
                <i class="bx bx-bell me-2 text-primary"></i>Notificações automáticas
            </h5>
            <p class="text-muted mb-4">
                O voluntário recebe um alerta no WhatsApp no início do mês, no início da semana e no dia em que vai servir.
                Ative só os lembretes que quiser usar. O envio ocorre automaticamente no horário configurado.
            </p>

            <div class="border rounded p-3 mb-3">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="month_enabled"
                           name="month_enabled" value="1" @checked(old('month_enabled', $settings->month_enabled))>
                    <label class="form-check-label fw-semibold" for="month_enabled">Início do mês</label>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="month_day">Dia do mês</label>
                        <input type="number" min="1" max="28" class="form-control" id="month_day" name="month_day"
                               value="{{ old('month_day', $settings->month_day) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="month_time">Horário</label>
                        <input type="time" class="form-control" id="month_time" name="month_time"
                               value="{{ old('month_time', substr((string) $settings->month_time, 0, 5)) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="month_template">Mensagem</label>
                        <textarea class="form-control" id="month_template" name="month_template" rows="5">{{ old('month_template', $settings->month_template) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-3">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="week_enabled"
                           name="week_enabled" value="1" @checked(old('week_enabled', $settings->week_enabled))>
                    <label class="form-check-label fw-semibold" for="week_enabled">Início da semana</label>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="week_weekday">Dia da semana</label>
                        <select class="form-select" id="week_weekday" name="week_weekday">
                            @foreach(\App\Models\ScheduleNotificationSetting::WEEKDAYS as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('week_weekday', $settings->week_weekday) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="week_time">Horário</label>
                        <input type="time" class="form-control" id="week_time" name="week_time"
                               value="{{ old('week_time', substr((string) $settings->week_time, 0, 5)) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="week_template">Mensagem</label>
                        <textarea class="form-control" id="week_template" name="week_template" rows="5">{{ old('week_template', $settings->week_template) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="day_enabled"
                           name="day_enabled" value="1" @checked(old('day_enabled', $settings->day_enabled))>
                    <label class="form-check-label fw-semibold" for="day_enabled">Dia da escala</label>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="day_time">Horário</label>
                        <input type="time" class="form-control" id="day_time" name="day_time"
                               value="{{ old('day_time', substr((string) $settings->day_time, 0, 5)) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="day_template">Mensagem</label>
                        <textarea class="form-control" id="day_template" name="day_template" rows="5">{{ old('day_template', $settings->day_template) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                Variáveis disponíveis:
                @foreach(\App\Models\ScheduleNotificationSetting::VARIABLES as $variable => $description)
                    <code>{{ $variable }}</code> {{ $description }}@if(!$loop->last); @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-1">
                <i class="bx bx-group me-2 text-primary"></i>Quantidade de pessoas por escala
            </h5>
            <p class="text-muted mb-3">
                Define quantas vagas aparecem ao adicionar a escala de cada área.
            </p>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Área de serviço</th>
                            <th style="width: 160px;">Pessoas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceAreas as $area)
                            <tr>
                                <td>
                                    <strong>{{ $area->name }}</strong>
                                    @if($area->description)
                                        <div class="small text-muted">{{ $area->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    <input type="number" min="1" max="20" class="form-control"
                                           name="quantities[{{ $area->id }}]"
                                           value="{{ old("quantities.{$area->id}", $area->min_quantity) }}">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted">Nenhuma área de serviço ativa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('voluntarios.escalas-mensais.index') }}" class="btn btn-default">Voltar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i>Salvar configurações
        </button>
    </div>
</form>
@endsection
