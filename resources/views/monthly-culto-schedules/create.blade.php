@extends('layouts.porto')

@section('title', 'Cadastrar Escala Mensal de Cultos')

@section('page-title', 'Cadastrar Escala Mensal de Cultos')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas-mensais.index') }}">Escalas Mensais</a></li>
    <li><span>Cadastrar</span></li>
@endsection

@section('content')
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-calendar me-2"></i>Cadastrar Escala Mensal de Cultos
                </h2>
            </header>
            <div class="card-body">
                <!-- Seletor de Mês/Ano -->
                <form method="GET" action="{{ route('voluntarios.escalas-mensais.create') }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="month" class="form-label">Mês</label>
                            <select name="month" id="month" class="form-select" onchange="this.form.submit()">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(null, $m, 1)->locale('pt_BR')->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Ano</label>
                            <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                                @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </form>

                @if($cultos->count() > 0)
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        Selecione um culto abaixo para cadastrar a escala (preletores, dirigentes e portaria).
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Título</th>
                                    <th>Horário</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cultos as $culto)
                                    <tr>
                                        <td>{{ $culto->start_date->format('d/m/Y') }}</td>
                                        <td>{{ $culto->title }}</td>
                                        <td>{{ $culto->start_date->format('H:i') }}</td>
                                        <td>
                                            @if(in_array($culto->id, $existingSchedules))
                                                <span class="badge bg-success">Escala Cadastrada</span>
                                            @else
                                                <span class="badge bg-secondary">Sem Escala</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(in_array($culto->id, $existingSchedules))
                                                @php
                                                    $schedule = \App\Models\MonthlyCultoSchedule::where('event_id', $culto->id)->where('month', $month)->where('year', $year)->first();
                                                @endphp
                                                <a href="{{ route('voluntarios.escalas-mensais.edit', $schedule) }}" class="btn btn-sm btn-primary">
                                                    <i class="bx bx-edit"></i> Editar
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-sm btn-success" onclick="openScheduleModal({{ $culto->id }}, '{{ $culto->title }}', '{{ $culto->start_date->format('d/m/Y') }}')">
                                                    <i class="bx bx-plus"></i> Cadastrar
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-2"></i>
                        Nenhum culto encontrado para o mês selecionado. Verifique se há eventos do tipo "culto" cadastrados na agenda.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

<!-- Modal para Cadastrar Escala -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">Cadastrar Escala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="scheduleForm" method="POST" action="{{ route('voluntarios.escalas-mensais.store') }}">
                @csrf
                <input type="hidden" name="event_id" id="modal_event_id">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Culto:</strong> <span id="modal_culto_title"></span><br>
                        <strong>Data:</strong> <span id="modal_culto_date"></span>
                    </div>

                    <hr>

                    <!-- Todas as Áreas de Serviço Cadastradas -->
                    @foreach($serviceAreas as $area)
                        @if(isset($volunteersByArea[$area->id]) && $volunteersByArea[$area->id]->count() > 0)
                        <div class="mb-4">
                            <label class="form-label fw-bold">{{ $area->name }}</label>
                            <select name="service_areas[{{ $area->id }}][]" id="service_area_{{ $area->id }}" class="form-select" multiple size="5">
                                @foreach($volunteersByArea[$area->id] as $volunteer)
                                    <option value="{{ $volunteer['id'] }}">{{ $volunteer['name'] }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos voluntários</small>
                        </div>
                        @endif
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Escala</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openScheduleModal(eventId, title, date) {
    document.getElementById('modal_event_id').value = eventId;
    document.getElementById('modal_culto_title').textContent = title;
    document.getElementById('modal_culto_date').textContent = date;
    
    // Limpar seleções
    @foreach($serviceAreas as $area)
        @if(isset($volunteersByArea[$area->id]) && $volunteersByArea[$area->id]->count() > 0)
        if (document.getElementById('service_area_{{ $area->id }}')) {
            document.getElementById('service_area_{{ $area->id }}').selectedIndex = -1;
        }
        @endif
    @endforeach
    
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    modal.show();
}
</script>
@endsection
