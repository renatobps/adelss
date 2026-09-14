@extends('layouts.porto')

@section('title', 'Editar Escala Mensal de Culto')

@section('page-title', 'Editar Escala Mensal de Culto')

@section('breadcrumbs')
    <li><a href="{{ route('voluntarios.escalas-mensais.index') }}">Escalas Mensais</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
@php
    $isSunday = optional($escala->event->start_date)->dayOfWeek === \Carbon\Carbon::SUNDAY;
    $rootAreas = $serviceAreas->filter(fn ($area) => !$area->parent_id);
@endphp
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">
                    <i class="bx bx-edit me-2"></i>Editar Escala Mensal de Culto
                </h2>
            </header>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="mb-3">
                    <strong>Culto:</strong> {{ $escala->event->title }}<br>
                    <strong>Data:</strong> {{ $escala->event->start_date->format('d/m/Y H:i') }} ({{ $escala->event->start_date->locale('pt_BR')->translatedFormat('l') }})<br>
                    <strong>Mês/Ano:</strong> {{ \Carbon\Carbon::create($escala->year, $escala->month, 1)->locale('pt_BR')->translatedFormat('F/Y') }}
                </div>

                @unless($isSunday)
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        Culto da Graça e demais cultos fora de domingo: Sala das Crianças e Zeladoria não entram nesta escala. Essas áreas são somente no domingo.
                    </div>
                @endunless

                <form method="POST" action="{{ route('voluntarios.escalas-mensais.update', $escala) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        @foreach($rootAreas as $area)
                            @php
                                $childAreas = $serviceAreas->where('parent_id', $area->id)->sortBy([['sort_order', 'asc'], ['name', 'asc']]);
                                $assignmentAreas = $childAreas->isNotEmpty() ? $childAreas : collect([$area]);
                                // Escalas antigas gravadas na área principal continuam editáveis.
                                if ($childAreas->isNotEmpty() && !empty($selectedVolunteersByArea[$area->id] ?? [])) {
                                    $assignmentAreas = collect([$area])->concat($childAreas);
                                }
                            @endphp
                            <div class="{{ $childAreas->isNotEmpty() ? 'col-12 col-lg-8' : 'col-12 col-md-6 col-lg-4' }} mb-4">
                                <div class="card h-100 border-2 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold">{{ $area->name }}</h6>
                                    </div>
                                    <div class="card-body">
                                        @foreach($assignmentAreas as $target)
                                            @php
                                                $options = $volunteersByArea[$target->id] ?? collect();
                                                $selected = $selectedVolunteersByArea[$target->id] ?? [];
                                                $slots = $slotLabelsByArea[$target->id] ?? ['Voluntário'];
                                                $slotCount = max(count($slots), count($selected));
                                            @endphp
                                            <div class="mb-3 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                @if($childAreas->isNotEmpty())
                                                    <strong class="d-block mb-2">{{ $target->name }}</strong>
                                                @endif

                                                @if($options->isEmpty())
                                                    <p class="text-muted mb-0">
                                                        Nenhum voluntário cadastrado nesta área.
                                                    </p>
                                                @else
                                                    @for($index = 0; $index < $slotCount; $index++)
                                                        @php
                                                            $slotLabel = $slots[$index] ?? ($slots[count($slots) - 1] ?? 'Voluntário');
                                                            $selectedId = $selected[$index] ?? null;
                                                        @endphp
                                                        <div class="mb-2">
                                                            <label class="form-label small text-muted mb-1" for="area_{{ $target->id }}_slot_{{ $index }}">
                                                                {{ $slotLabel }}
                                                            </label>
                                                            <select name="service_areas[{{ $target->id }}][]"
                                                                    id="area_{{ $target->id }}_slot_{{ $index }}"
                                                                    class="form-select">
                                                                <option value="">Vaga em aberto</option>
                                                                @foreach($options as $option)
                                                                    <option value="{{ $option['id'] }}" {{ (int) $selectedId === (int) $option['id'] ? 'selected' : '' }}>
                                                                        {{ $option['name'] }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endfor
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-muted small">
                        No mesmo culto, a mesma pessoa não pode servir em duas áreas ou subáreas. Deixe "Vaga em aberto" para tirar alguém da escala.
                    </p>

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
