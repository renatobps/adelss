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
    <button type="submit" class="d-none" tabindex="-1" aria-hidden="true">Salvar configurações</button>

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

            <div class="border rounded p-3 mt-3">
                <h6 class="mb-1">Envio imediato</h6>
                <p class="text-muted small mb-3">
                    Essas mensagens aparecem prontas ao clicar em <strong>Notificar todos</strong>.
                    Dá para editar na hora do envio, sem alterar o que está salvo aqui.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="immediate_individual_template">Template individual</label>
                        <textarea class="form-control" id="immediate_individual_template" name="immediate_individual_template" rows="10">{{ old('immediate_individual_template', $settings->resolvedImmediateIndividualTemplate()) }}</textarea>
                        <div class="form-text">Enviada no WhatsApp de cada pessoa escalada.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="immediate_group_template">Template do grupo</label>
                        <textarea class="form-control" id="immediate_group_template" name="immediate_group_template" rows="10">{{ old('immediate_group_template', $settings->resolvedImmediateGroupTemplate()) }}</textarea>
                        <div class="form-text">Enviada nos grupos de WhatsApp de cada área, junto com o PDF.</div>
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
                <i class="bx bx-group me-2 text-primary"></i>Áreas e subáreas
            </h5>
            <p class="text-muted mb-3">
                A quantidade de pessoas define quantas vagas aparecem ao adicionar a escala.
                Altere o número e clique em <strong>Salvar configurações</strong> para atualizar na hora.
                O grupo de WhatsApp de cada área é escolhido em <a href="{{ route('voluntarios.areas.index') }}">Áreas de serviço</a>.
            </p>

            @forelse($serviceAreas as $area)
                @php $children = $area->children; @endphp
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <strong>{{ $area->name }}</strong>
                            @if($area->description)
                                <div class="small text-muted">{{ $area->description }}</div>
                            @endif
                        </div>
                        @if($children->isEmpty())
                            <div style="width: 180px;">
                                <label class="form-label small mb-1" for="qty_{{ $area->id }}">Pessoas</label>
                                <input type="number" min="1" max="20" class="form-control"
                                       id="qty_{{ $area->id }}"
                                       name="quantities[{{ $area->id }}]"
                                       value="{{ old("quantities.{$area->id}", $area->min_quantity) }}">
                                @if($area->isIntercession())
                                    <div class="small text-muted mt-1">3 por período (Esquerda, Direita, Atrás)</div>
                                @endif
                            </div>
                        @else
                            <div class="small text-muted">
                                Total: {{ $children->sum('min_quantity') }} {{ $children->sum('min_quantity') === 1 ? 'pessoa' : 'pessoas' }}
                            </div>
                        @endif
                    </div>

                    @if($children->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2">
                                <thead>
                                    <tr>
                                        <th>Subárea</th>
                                        <th style="width: 120px;">Pessoas</th>
                                        <th style="width: 70px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($children as $child)
                                        <tr>
                                            <td>
                                                <input type="text" class="form-control"
                                                       name="subarea_names[{{ $child->id }}]"
                                                       value="{{ old("subarea_names.{$child->id}", $child->name) }}">
                                            </td>
                                            <td>
                                                <input type="number" min="1" max="20" class="form-control"
                                                       name="quantities[{{ $child->id }}]"
                                                       value="{{ old("quantities.{$child->id}", $child->min_quantity) }}">
                                            </td>
                                            <td class="text-end">
                                                <button type="submit" form="delete-subarea-{{ $child->id }}"
                                                        class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Remover a subárea {{ $child->name }}?')">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="small text-muted mb-2">Esta escala ainda não tem subáreas.</p>
                    @endif

                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="new_sub_{{ $area->id }}">Nova subárea</label>
                            <input type="text" class="form-control" id="new_sub_{{ $area->id }}"
                                   name="new_subarea_name[{{ $area->id }}]" placeholder="Ex.: Momento profético">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1" for="new_qty_{{ $area->id }}">Pessoas</label>
                            <input type="number" min="1" max="20" class="form-control" id="new_qty_{{ $area->id }}"
                                   name="new_subarea_quantity[{{ $area->id }}]" value="1">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary w-100" name="add_parent_id" value="{{ $area->id }}">
                                <i class="bx bx-plus me-1"></i>Adicionar
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">Nenhuma área de serviço cadastrada.</p>
            @endforelse
        </div>
    </div>


    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('voluntarios.escalas-mensais.index') }}" class="btn btn-default">Voltar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i>Salvar configurações
        </button>
    </div>
</form>

@foreach($serviceAreas as $area)
    @foreach($area->children as $child)
        <form method="POST" action="{{ route('voluntarios.escalas-mensais.settings.subareas.destroy', $child) }}" id="delete-subarea-{{ $child->id }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endforeach
@endsection
