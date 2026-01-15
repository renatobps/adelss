@extends('layouts.porto')

@section('title', 'Editar Escala')

@section('page-title', 'Editar Escala')

@section('breadcrumbs')
    <li><a href="{{ route('servico.escalas.index') }}">Escalas</a></li>
    <li><span>Editar</span></li>
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
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="bx bx-edit me-2"></i>Editar Escala - {{ $escala->title }}
                </h2>
            </header>
            <div class="card-body">
                <form action="{{ route('servico.escalas.update', $escala) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <!-- Dados Gerais -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2">Dados Gerais</h5>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="event_select" class="form-label">Selecionar Evento da Agenda</label>
                            <select class="form-select @error('event_select') is-invalid @enderror" 
                                    id="event_select" name="event_select">
                                <option value="">Selecione um evento ou digite manualmente...</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}" 
                                            data-title="{{ $event->title }}"
                                            data-date="{{ \Carbon\Carbon::parse($event->start_date)->format('Y-m-d') }}"
                                            data-time="{{ \Carbon\Carbon::parse($event->start_date)->format('H:i') }}"
                                            {{ old('event_select', $escala->event_id) == $event->id ? 'selected' : '' }}>
                                        {{ $event->title }} - {{ \Carbon\Carbon::parse($event->start_date)->format('d/m/Y H:i') }}@if($event->category) ({{ strtoupper($event->category->name) }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="bx bx-info-circle me-1"></i>Exibindo apenas eventos do mês corrente ({{ \Carbon\Carbon::now()->locale('pt_BR')->translatedFormat('F/Y') }}). Ao selecionar um evento, os campos abaixo serão preenchidos automaticamente. Ou você pode digitar manualmente.
                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Nome da Escala <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $escala->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                   id="date" name="date" value="{{ old('date', $escala->date->format('Y-m-d')) }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="start_time" class="form-label">Horário <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('start_time') is-invalid @enderror" 
                                   id="start_time" name="start_time" value="{{ old('start_time', \Carbon\Carbon::parse($escala->start_time)->format('H:i')) }}" required>
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Selecione...</option>
                                <option value="culto" {{ old('type', $escala->type) == 'culto' ? 'selected' : '' }}>Culto</option>
                                <option value="evento" {{ old('type', $escala->type) == 'evento' ? 'selected' : '' }}>Evento</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <input type="hidden" id="event_id" name="event_id" value="{{ old('event_id', $escala->event_id) }}">

                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Local</label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                   id="location" name="location" value="{{ old('location', $escala->location) }}">
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="notes" class="form-label">Observações Gerais</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes', $escala->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Áreas de Serviço -->
                    <div class="row mt-4">
                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2">Áreas de Serviço</h5>
                        </div>

                        <div id="areasContainer">
                            @foreach($escala->areas as $index => $area)
                                <div class="area-item border rounded p-3 mb-3" data-area-id="{{ $area->id }}">
                                    <input type="hidden" name="areas[{{ $index }}][id]" value="{{ $area->id }}">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label class="form-label">Área de Serviço <span class="text-danger">*</span></label>
                                            <select name="areas[{{ $index }}][service_area_id]" class="form-select area-select" required>
                                                <option value="">Selecione...</option>
                                                @foreach($serviceAreas as $serviceArea)
                                                    <option value="{{ $serviceArea->id }}" 
                                                            data-leader-id="{{ $serviceArea->leader_id ?? '' }}"
                                                            {{ $area->service_area_id == $serviceArea->id ? 'selected' : '' }}>
                                                        {{ $serviceArea->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Quantidade Necessária <span class="text-danger">*</span></label>
                                            <input type="number" name="areas[{{ $index }}][required_quantity]" 
                                                   class="form-control" min="1" value="{{ $area->required_quantity }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Responsável</label>
                                            <select name="areas[{{ $index }}][responsible_id]" class="form-select responsible-select">
                                                <option value="">Nenhum</option>
                                                @foreach($members as $member)
                                                    <option value="{{ $member->id }}" {{ $area->responsible_id == $member->id ? 'selected' : '' }}>
                                                        {{ $member->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-danger btn-sm w-100 remove-area">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Voluntários da Área -->
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Voluntários desta Área</label>
                                            <select name="areas[{{ $index }}][volunteer_ids][]" 
                                                    class="form-select volunteer-select" 
                                                    data-service-area-id="{{ $area->service_area_id }}"
                                                    multiple 
                                                    size="5">
                                                @php
                                                    $areaVolunteers = \App\Models\Volunteer::whereHas('serviceAreas', function($q) use ($area) {
                                                        $q->where('service_areas.id', $area->service_area_id);
                                                    })
                                                    ->with('member')
                                                    ->where('status', 'ativo')
                                                    ->get();
                                                    $selectedVolunteerIds = $area->volunteers->pluck('volunteer_id')->toArray();
                                                @endphp
                                                @foreach($areaVolunteers as $volunteer)
                                                    <option value="{{ $volunteer->id }}" 
                                                            {{ in_array($volunteer->id, $selectedVolunteerIds) ? 'selected' : '' }}>
                                                        {{ $volunteer->member->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos voluntários</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="col-md-12 mb-3">
                            <button type="button" class="btn btn-secondary" id="addArea">
                                <i class="bx bx-plus me-2"></i>Adicionar Área
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('servico.escalas.show', $escala) }}" class="btn btn-default">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-check me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<script>
    let areaIndex = {{ $escala->areas->count() }};
    
    // Preencher campos quando selecionar evento
    document.getElementById('event_select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            // Preencher campos automaticamente
            document.getElementById('title').value = selectedOption.getAttribute('data-title');
            document.getElementById('date').value = selectedOption.getAttribute('data-date');
            document.getElementById('start_time').value = selectedOption.getAttribute('data-time');
            document.getElementById('type').value = 'evento';
            document.getElementById('event_id').value = selectedOption.value;
        }
    });
    
    document.getElementById('type').addEventListener('change', function() {
        // Se não for evento e tiver event_id, limpar
        if (this.value !== 'evento') {
            document.getElementById('event_id').value = '';
        }
    });
    
    // Adicionar área
    document.getElementById('addArea').addEventListener('click', function() {
        const container = document.getElementById('areasContainer');
        const newArea = document.createElement('div');
        newArea.className = 'area-item border rounded p-3 mb-3';
        newArea.innerHTML = `
            <div class="row">
                <div class="col-md-5">
                    <label class="form-label">Área de Serviço <span class="text-danger">*</span></label>
                    <select name="areas[${areaIndex}][service_area_id]" class="form-select area-select" required>
                        <option value="">Selecione...</option>
                        @foreach($serviceAreas as $serviceArea)
                            <option value="{{ $serviceArea->id }}" 
                                    data-leader-id="{{ $serviceArea->leader_id ?? '' }}">
                                {{ $serviceArea->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantidade Necessária <span class="text-danger">*</span></label>
                    <input type="number" name="areas[${areaIndex}][required_quantity]" class="form-control" min="1" value="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Responsável</label>
                    <select name="areas[${areaIndex}][responsible_id]" class="form-select responsible-select">
                        <option value="">Nenhum</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm w-100 remove-area">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <label class="form-label">Voluntários desta Área</label>
                    <select name="areas[${areaIndex}][volunteer_ids][]" class="form-select volunteer-select" multiple size="5">
                        <option value="">Selecione uma área primeiro</option>
                    </select>
                    <small class="form-text text-muted">Selecione a área primeiro para carregar os voluntários</small>
                </div>
            </div>
        `;
        container.appendChild(newArea);
        areaIndex++;
    });
    
    // Remover área
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-area')) {
            if (confirm('Deseja remover esta área? Os voluntários associados também serão removidos.')) {
                e.target.closest('.area-item').remove();
            }
        }
    });
    
    // Carregar voluntários e preencher responsável quando área for selecionada
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('area-select')) {
            const areaItem = e.target.closest('.area-item');
            const volunteerSelect = areaItem.querySelector('.volunteer-select');
            const responsibleSelect = areaItem.querySelector('.responsible-select');
            const serviceAreaId = e.target.value;
            const selectedOption = e.target.options[e.target.selectedIndex];
            const leaderId = selectedOption.getAttribute('data-leader-id');
            
            // Preencher responsável
            if (leaderId && responsibleSelect) {
                responsibleSelect.value = leaderId;
            } else if (responsibleSelect) {
                responsibleSelect.value = '';
            }
            
            // Atualizar data attribute
            if (volunteerSelect) {
                volunteerSelect.setAttribute('data-service-area-id', serviceAreaId);
                
                if (serviceAreaId) {
                    // Fazer requisição AJAX para carregar voluntários
                    fetch(`/servico/escalas/api/suggested-volunteers?service_area_id=${serviceAreaId}&date={{ $escala->date->format('Y-m-d') }}&start_time={{ \Carbon\Carbon::parse($escala->start_time)->format('H:i') }}`)
                        .then(response => response.json())
                        .then(data => {
                            const selectedValues = Array.from(volunteerSelect.selectedOptions).map(opt => opt.value);
                            volunteerSelect.innerHTML = '';
                            data.forEach(volunteer => {
                                const option = document.createElement('option');
                                option.value = volunteer.id;
                                option.textContent = volunteer.name;
                                if (!volunteer.available) {
                                    option.style.color = 'red';
                                    option.textContent += ' - ' + volunteer.reason;
                                }
                                if (selectedValues.includes(volunteer.id.toString())) {
                                    option.selected = true;
                                }
                                volunteerSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Erro ao carregar voluntários:', error);
                        });
                } else {
                    volunteerSelect.innerHTML = '<option value="">Selecione uma área primeiro</option>';
                }
            }
        }
    });
</script>
@endsection
