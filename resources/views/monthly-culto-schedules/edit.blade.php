@extends('layouts.porto')

@section('title', 'Editar Escala Mensal de Culto')

@section('page-title', 'Editar Escala Mensal de Culto')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas-mensais.index') }}">Escalas Mensais</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-edit me-2"></i>Editar Escala Mensal de Culto
                </h2>
            </header>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Culto:</strong> {{ $escala->event->title }}<br>
                    <strong>Data:</strong> {{ $escala->event->start_date->format('d/m/Y H:i') }}<br>
                    <strong>Mês/Ano:</strong> {{ \Carbon\Carbon::create($escala->year, $escala->month, 1)->locale('pt_BR')->translatedFormat('F/Y') }}
                </div>

                <form method="POST" action="{{ route('voluntarios.escalas-mensais.update', $escala) }}">
                    @csrf
                    @method('PUT')

                    <!-- Todas as Áreas de Serviço Cadastradas -->
                    @foreach($serviceAreas as $area)
                        @if(isset($volunteersByArea[$area->id]) && $volunteersByArea[$area->id]->count() > 0)
                        <div class="mb-4">
                            <label class="form-label fw-bold">{{ $area->name }}</label>
                            <select name="service_areas[{{ $area->id }}][]" id="service_area_{{ $area->id }}" class="form-select" multiple size="5">
                                @foreach($volunteersByArea[$area->id] as $volunteer)
                                    <option value="{{ $volunteer['id'] }}" {{ in_array($volunteer['id'], $selectedVolunteersByArea[$area->id] ?? []) ? 'selected' : '' }}>
                                        {{ $volunteer['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos voluntários</small>
                        </div>
                        @endif
                    @endforeach

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('voluntarios.escalas-mensais.index', ['month' => $escala->month, 'year' => $escala->year]) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-2"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
