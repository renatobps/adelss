@extends('layouts.porto')

@section('title', 'Nova Escala')

@section('page-title', 'Nova Escala')

@section('breadcrumbs')
    <li><a href="{{ route('servico.escalas.index') }}">Escalas</a></li>
    <li><span>Nova</span></li>
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
                    <i class="bx bx-calendar-plus me-2"></i>Nova Escala de Serviço
                </h2>
            </header>
            <div class="card-body">
                <!-- Barra de Progresso -->
                <div class="mb-4">
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: {{ ($step / 5) * 100 }}%"
                             aria-valuenow="{{ ($step / 5) * 100 }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            Etapa {{ $step }} de 5
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="{{ $step >= 1 ? 'text-success' : 'text-muted' }}">1. Dados Gerais</small>
                        <small class="{{ $step >= 2 ? 'text-success' : 'text-muted' }}">2. Áreas</small>
                        <small class="{{ $step >= 3 ? 'text-success' : 'text-muted' }}">3. Voluntários</small>
                        <small class="{{ $step >= 4 ? 'text-success' : 'text-muted' }}">4. Revisão</small>
                        <small class="{{ $step >= 5 ? 'text-success' : 'text-muted' }}">5. Publicação</small>
                    </div>
                </div>

                <!-- ETAPA 1: Dados Gerais -->
                @if($step == 1)
                    <form action="{{ route('servico.escalas.store.step1') }}" method="POST">
                        @csrf
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
                                                {{ old('event_select', $wizardData['step1']['event_id'] ?? '') == $event->id ? 'selected' : '' }}>
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
                                       id="title" name="title" value="{{ old('title', $wizardData['step1']['title'] ?? '') }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="date" class="form-label">Data <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                       id="date" name="date" value="{{ old('date', $wizardData['step1']['date'] ?? '') }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="start_time" class="form-label">Horário <span class="text-danger">*</span></label>
                                <input type="time" class="form-control @error('start_time') is-invalid @enderror" 
                                       id="start_time" name="start_time" value="{{ old('start_time', $wizardData['step1']['start_time'] ?? '') }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Selecione...</option>
                                <option value="culto" {{ old('type', $wizardData['step1']['type'] ?? '') == 'culto' ? 'selected' : '' }}>Culto</option>
                                <option value="evento" {{ old('type', $wizardData['step1']['type'] ?? '') == 'evento' ? 'selected' : '' }}>Evento</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <input type="hidden" id="event_id" name="event_id" value="{{ old('event_id', $wizardData['step1']['event_id'] ?? '') }}">

                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Local</label>
                                <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                       id="location" name="location" value="{{ old('location', $wizardData['step1']['location'] ?? '') }}">
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="notes" class="form-label">Observações Gerais</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3">{{ old('notes', $wizardData['step1']['notes'] ?? '') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('servico.escalas.index') }}" class="btn btn-default">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Próximo <i class="bx bx-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </form>
                @endif

                <!-- ETAPA 2: Áreas de Serviço -->
                @if($step == 2)
                    <form action="{{ route('servico.escalas.store.step2') }}" method="POST" id="areasForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <h5 class="border-bottom pb-2">Áreas de Serviço</h5>
                                <p class="text-muted">Selecione as áreas de serviço necessárias para esta escala.</p>
                            </div>

                            <div id="areasContainer">
                                @if(isset($wizardData['step2']['areas']) && count($wizardData['step2']['areas']) > 0)
                                    @foreach($wizardData['step2']['areas'] as $index => $area)
                                        <div class="area-item border rounded p-3 mb-3">
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <label class="form-label">Área de Serviço <span class="text-danger">*</span></label>
                                                    <select name="areas[{{ $index }}][service_area_id]" class="form-select area-select" required>
                                                        <option value="">Selecione...</option>
                                                        @foreach($serviceAreas as $serviceArea)
                                                            <option value="{{ $serviceArea->id }}" 
                                                                    data-leader-id="{{ $serviceArea->leader_id ?? '' }}"
                                                                    {{ $area['service_area_id'] == $serviceArea->id ? 'selected' : '' }}>
                                                                {{ $serviceArea->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Quantidade Necessária <span class="text-danger">*</span></label>
                                                    <input type="number" name="areas[{{ $index }}][required_quantity]" 
                                                           class="form-control" min="1" value="{{ $area['required_quantity'] ?? 1 }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Responsável</label>
                                                    <select name="areas[{{ $index }}][responsible_id]" class="form-select responsible-select">
                                                        <option value="">Nenhum</option>
                                                        @foreach($members as $member)
                                                            <option value="{{ $member->id }}" {{ isset($area['responsible_id']) && $area['responsible_id'] == $member->id ? 'selected' : '' }}>
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
                                        </div>
                                    @endforeach
                                @else
                                    <div class="area-item border rounded p-3 mb-3">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <label class="form-label">Área de Serviço <span class="text-danger">*</span></label>
                                                <select name="areas[0][service_area_id]" class="form-select area-select" required>
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
                                                <input type="number" name="areas[0][required_quantity]" class="form-control" min="1" value="1" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Responsável</label>
                                                <select name="areas[0][responsible_id]" class="form-select responsible-select">
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
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-12 mb-3">
                                <button type="button" class="btn btn-secondary" id="addArea">
                                    <i class="bx bx-plus me-2"></i>Adicionar Área
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('servico.escalas.create', ['step' => 1]) }}" class="btn btn-default">
                                    <i class="bx bx-arrow-back me-2"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-primary">Próximo <i class="bx bx-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </form>
                @endif

                <!-- ETAPA 3: Seleção de Voluntários -->
                @if($step == 3)
                    @php
                        $areasData = $wizardData['step2']['areas'] ?? [];
                        $volunteersData = $wizardData['step3']['volunteers'] ?? [];
                    @endphp
                    <form action="{{ route('servico.escalas.store.step3') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <h5 class="border-bottom pb-2">Seleção de Voluntários</h5>
                                <p class="text-muted">Selecione os voluntários para cada área de serviço.</p>
                            </div>

                            @foreach($areasData as $index => $areaData)
                                @php
                                    $serviceArea = $serviceAreas->firstWhere('id', $areaData['service_area_id']);
                                    $selectedVolunteers = collect($volunteersData)
                                        ->firstWhere('schedule_area_id', $index)['volunteer_ids'] ?? [];
                                @endphp
                                @if($serviceArea)
                                    <div class="col-md-12 mb-4">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">
                                                    <i class="bx bx-group me-2"></i>{{ $serviceArea->name }}
                                                    <small class="text-muted">(Necessário: {{ $areaData['required_quantity'] }})</small>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <input type="hidden" name="volunteers[{{ $index }}][schedule_area_id]" value="{{ $index }}">
                                                <label class="form-label">Selecione os voluntários:</label>
                                                <select name="volunteers[{{ $index }}][volunteer_ids][]" 
                                                        class="form-select volunteer-select" 
                                                        multiple 
                                                        size="6" 
                                                        required>
                                                    @php
                                                        $volunteers = \App\Models\Volunteer::whereHas('serviceAreas', function($q) use ($serviceArea) {
                                                            $q->where('service_areas.id', $serviceArea->id);
                                                        })
                                                        ->with('member')
                                                        ->where('status', 'ativo')
                                                        ->get();
                                                    @endphp
                                                    @foreach($volunteers as $volunteer)
                                                        <option value="{{ $volunteer->id }}" 
                                                                {{ in_array($volunteer->id, $selectedVolunteers) ? 'selected' : '' }}>
                                                            {{ $volunteer->member->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">Segure Ctrl (ou Cmd no Mac) para selecionar múltiplos voluntários</small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('servico.escalas.create', ['step' => 2]) }}" class="btn btn-default">
                                    <i class="bx bx-arrow-back me-2"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-primary">Próximo <i class="bx bx-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </form>
                @endif

                <!-- ETAPA 4: Revisão -->
                @if($step == 4)
                    @php
                        $step1Data = $wizardData['step1'] ?? [];
                        $step2Data = $wizardData['step2'] ?? [];
                        $step3Data = $wizardData['step3'] ?? [];
                    @endphp
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h5 class="border-bottom pb-2">Revisão da Escala</h5>
                            <p class="text-muted">Revise os dados antes de publicar a escala.</p>
                        </div>

                        <div class="col-md-12 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Dados Gerais</h6>
                                </div>
                                <div class="card-body" style="font-size: 14px;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong style="font-size: 14px;">Nome:</strong> 
                                                <span style="font-size: 14px;">{{ $step1Data['title'] ?? '' }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong style="font-size: 14px;">Data:</strong> 
                                                <span style="font-size: 14px;">{{ isset($step1Data['date']) ? \Carbon\Carbon::parse($step1Data['date'])->format('d/m/Y') : '' }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong style="font-size: 14px;">Horário:</strong> 
                                                <span style="font-size: 14px;">{{ $step1Data['start_time'] ?? '' }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong style="font-size: 14px;">Tipo:</strong> 
                                                <span style="font-size: 14px;">{{ $step1Data['type'] == 'culto' ? 'Culto' : 'Evento' }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <strong style="font-size: 14px;">Local:</strong> 
                                                <span style="font-size: 14px;">{{ $step1Data['location'] ?? 'Não informado' }}</span>
                                            </div>
                                            @if(isset($step1Data['notes']) && $step1Data['notes'])
                                                <div class="mb-2">
                                                    <strong style="font-size: 14px;">Observações:</strong> 
                                                    <span style="font-size: 14px;">{{ $step1Data['notes'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-4">
                            <h6 class="mb-3" style="font-size: 16px; font-weight: 600;">Áreas e Voluntários</h6>
                            @foreach($step2Data['areas'] ?? [] as $index => $areaData)
                                @php
                                    $serviceArea = $serviceAreas->firstWhere('id', $areaData['service_area_id']);
                                    $volunteerIds = collect($step3Data['volunteers'] ?? [])
                                        ->firstWhere('schedule_area_id', $index)['volunteer_ids'] ?? [];
                                    $volunteers = \App\Models\Volunteer::whereIn('id', $volunteerIds)
                                        ->with('member')
                                        ->get();
                                @endphp
                                @if($serviceArea)
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0" style="font-size: 15px; font-weight: 600;">
                                                {{ $serviceArea->name }}
                                                <span class="badge {{ count($volunteers) >= $areaData['required_quantity'] ? 'badge-success' : 'badge-warning' }}" style="font-size: 12px;">
                                                    {{ count($volunteers) }} / {{ $areaData['required_quantity'] }}
                                                </span>
                                            </h6>
                                        </div>
                                        <div class="card-body" style="font-size: 14px;">
                                            @if(count($volunteers) > 0)
                                                <ul class="mb-0" style="padding-left: 20px;">
                                                    @foreach($volunteers as $volunteer)
                                                        <li style="margin-bottom: 4px; font-size: 14px;">{{ $volunteer->member->name }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-danger mb-0" style="font-size: 14px;"><i class="bx bx-error-circle me-2"></i>Nenhum voluntário selecionado</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('servico.escalas.create', ['step' => 3]) }}" class="btn btn-default">
                                    <i class="bx bx-arrow-back me-2"></i>Voltar
                                </a>
                                <a href="{{ route('servico.escalas.create', ['step' => 5]) }}" class="btn btn-primary">
                                    Próximo <i class="bx bx-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- ETAPA 5: Publicação -->
                @if($step == 5)
                    <form action="{{ route('servico.escalas.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <h5 class="border-bottom pb-2">Publicação</h5>
                                <p class="text-muted">Escolha como deseja salvar a escala.</p>
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="status_rascunho" value="rascunho" checked>
                                    <label class="form-check-label" for="status_rascunho">
                                        <strong>Salvar como Rascunho</strong><br>
                                        <small class="text-muted">A escala ficará salva mas não será publicada. Você poderá editar depois.</small>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="status_publicada" value="publicada">
                                    <label class="form-check-label" for="status_publicada">
                                        <strong>Publicar Agora</strong><br>
                                        <small class="text-muted">A escala será publicada e os voluntários serão notificados (futuro).</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('servico.escalas.create', ['step' => 4]) }}" class="btn btn-default">
                                    <i class="bx bx-arrow-back me-2"></i>Voltar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bx bx-check me-2"></i>Finalizar Escala
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>
</div>

@if($step == 1)
<script>
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
            
            // Trigger change no tipo para garantir que está configurado
            document.getElementById('type').dispatchEvent(new Event('change'));
        }
    });
    
    document.getElementById('type').addEventListener('change', function() {
        // Se não for evento e tiver event_id, limpar
        if (this.value !== 'evento') {
            document.getElementById('event_id').value = '';
        }
    });
</script>
@endif

@if($step == 2)
<script>
    let areaIndex = {{ isset($wizardData['step2']['areas']) ? count($wizardData['step2']['areas']) : 1 }};
    
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
        `;
        container.appendChild(newArea);
        areaIndex++;
    });
    
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-area')) {
            e.target.closest('.area-item').remove();
        }
    });
    
    // Preencher responsável quando selecionar área
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('area-select')) {
            const areaItem = e.target.closest('.area-item');
            const responsibleSelect = areaItem.querySelector('.responsible-select');
            const selectedOption = e.target.options[e.target.selectedIndex];
            const leaderId = selectedOption.getAttribute('data-leader-id');
            
            if (leaderId) {
                responsibleSelect.value = leaderId;
            } else {
                responsibleSelect.value = '';
            }
        }
    });
</script>
@endif
@endsection
