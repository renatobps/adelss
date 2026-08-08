@extends('layouts.porto')

@section('title', 'Chamada da reunião')

@section('page-title', 'Chamada')

@section('breadcrumbs')
    <li><a href="{{ route('pgis.index') }}">PGIs</a></li>
    <li><a href="{{ route('pgis.show', $pgi) }}">{{ $pgi->name }}</a></li>
    <li><a href="{{ route('pgis.meetings.show', [$pgi, $meeting]) }}">{{ $meeting->meeting_date->format('d/m/Y') }}</a></li>
    <li><span>Chamada</span></li>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/css/pgis.css') }}?v={{ @filemtime(public_path('css/css/pgis.css')) ?: '1' }}">
@endpush

@section('content')
@php
    $selected = collect(old('participants', $presentIds))->map(fn ($id) => (int) $id)->all();
    $oldVisitors = old('visitors');
    $existingVisitors = $oldVisitors !== null
        ? collect($oldVisitors)->map(fn ($v) => ['name' => $v['name'] ?? '', 'phone' => $v['phone'] ?? ''])
        : $visitors->map(fn ($v) => ['name' => $v->visitor_name, 'phone' => $v->visitor_phone]);
@endphp

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

<form action="{{ route('pgis.meetings.attendance.store', [$pgi, $meeting]) }}" method="POST" id="attendanceForm">
    @csrf

    <div class="card pgi-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="mb-1">{{ $meeting->subject ?: 'Reunião do PGI' }}</h5>
                    <div class="text-muted small">
                        <i class="bx bx-calendar me-1"></i>
                        {{ $meeting->meeting_date->locale('pt_BR')->translatedFormat('l, d/m/Y') }}
                    </div>
                </div>
                <a href="{{ route('pgis.meetings.show', [$pgi, $meeting]) }}" class="btn btn-light btn-sm pgi-touch">
                    <i class="bx bx-x"></i>
                </a>
            </div>

            <div class="attendance-counter mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <strong id="attendanceCounter">0 de {{ $members->count() }} presentes</strong>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary pgi-touch" id="checkAll">Marcar todos</button>
                        <button type="button" class="btn btn-outline-secondary pgi-touch" id="uncheckAll">Desmarcar todos</button>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar" id="attendanceProgress" role="progressbar" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card pgi-card">
                <header class="card-header">
                    <h5 class="card-title mb-0"><i class="bx bx-group me-2"></i>Participantes</h5>
                </header>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="attendanceSearch"
                               placeholder="Buscar membro pelo nome..." autocomplete="off">
                    </div>

                    @forelse($members as $member)
                        @php $freq = $frequency[$member->id] ?? null; @endphp
                        <label class="attendance-item" data-member-name="{{ mb_strtolower($member->name) }}">
                            <input class="form-check-input attendance-check" type="checkbox"
                                   name="participants[]" value="{{ $member->id }}"
                                   {{ in_array((int) $member->id, $selected, true) ? 'checked' : '' }}>
                            @include('members.partials.avatar', ['member' => $member, 'size' => 36])
                            <span class="attendance-item__label">
                                <span class="d-block pgi-member__name">{{ $member->name }}</span>
                                @if($freq)
                                    <span class="pgi-member__freq">{{ $freq['present'] }}/{{ $freq['total'] }} últimas reuniões</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        @include('pgis.partials.empty-state', [
                            'icon' => 'bx-user-plus',
                            'title' => 'Nenhum membro vinculado a este PGI',
                            'description' => 'Adicione membros ao grupo para poder fazer a chamada.',
                            'actionUrl' => route('pgis.show', $pgi),
                            'actionLabel' => 'Ir para o PGI',
                            'actionIcon' => 'bx-arrow-back',
                        ])
                    @endforelse

                    <p class="text-muted text-center mb-0 d-none" id="attendanceNoResults">
                        Nenhum membro encontrado para esta busca.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card pgi-card mb-4">
                <header class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bx bx-user-plus me-2"></i>Visitantes</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary pgi-touch" id="addVisitor">
                        <i class="bx bx-plus me-1"></i>Adicionar
                    </button>
                </header>
                <div class="card-body">
                    <div id="visitorsContainer">
                        @foreach($existingVisitors as $index => $visitor)
                            <div class="row g-2 mb-2 visitor-row">
                                <div class="col-7">
                                    <input type="text" class="form-control" name="visitors[{{ $index }}][name]"
                                           value="{{ $visitor['name'] }}" placeholder="Nome do visitante">
                                </div>
                                <div class="col-4">
                                    <input type="text" class="form-control" name="visitors[{{ $index }}][phone]"
                                           value="{{ $visitor['phone'] }}" placeholder="Telefone">
                                </div>
                                <div class="col-1 d-flex">
                                    <button type="button" class="btn btn-sm btn-light text-danger remove-visitor pgi-touch">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-muted small mb-0" id="visitorsEmpty" @if($existingVisitors->count()) style="display:none" @endif>
                        Nenhum visitante registrado nesta reunião.
                    </p>
                </div>
            </div>

            <div class="card pgi-card">
                <header class="card-header">
                    <h5 class="card-title mb-0"><i class="bx bx-note me-2"></i>Observações</h5>
                </header>
                <div class="card-body">
                    <textarea class="form-control" name="notes" rows="5"
                              placeholder="Como foi a reunião?">{{ old('notes', $meeting->notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="attendance-bar">
        <div class="d-flex justify-content-between align-items-center gap-2">
            <span class="text-muted small d-none d-sm-inline" id="attendanceCounterFooter"></span>
            <div class="d-flex gap-2 ms-auto">
                <a href="{{ route('pgis.meetings.show', [$pgi, $meeting]) }}" class="btn btn-light pgi-touch">Cancelar</a>
                <button type="submit" class="btn btn-primary pgi-touch">
                    <i class="bx bx-save me-1"></i>Salvar chamada
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('attendanceForm');
    if (!form) return;

    const checks = Array.from(form.querySelectorAll('.attendance-check'));
    const counter = document.getElementById('attendanceCounter');
    const counterFooter = document.getElementById('attendanceCounterFooter');
    const progress = document.getElementById('attendanceProgress');
    const total = checks.length;

    function updateCounter() {
        const checked = checks.filter((c) => c.checked).length;
        const text = checked + ' de ' + total + ' presentes';
        if (counter) counter.textContent = text;
        if (counterFooter) counterFooter.textContent = text;
        if (progress) progress.style.width = (total ? (checked / total) * 100 : 0) + '%';
    }

    checks.forEach((check) => check.addEventListener('change', updateCounter));
    updateCounter();

    document.getElementById('checkAll')?.addEventListener('click', function () {
        checks.forEach((c) => { c.checked = true; });
        updateCounter();
    });

    document.getElementById('uncheckAll')?.addEventListener('click', function () {
        checks.forEach((c) => { c.checked = false; });
        updateCounter();
    });

    const search = document.getElementById('attendanceSearch');
    const noResults = document.getElementById('attendanceNoResults');
    search?.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        let visible = 0;
        form.querySelectorAll('.attendance-item').forEach((item) => {
            const match = (item.dataset.memberName || '').includes(term);
            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        noResults?.classList.toggle('d-none', visible > 0 || total === 0);
    });

    const container = document.getElementById('visitorsContainer');
    const visitorsEmpty = document.getElementById('visitorsEmpty');
    let visitorIndex = {{ $existingVisitors->count() }};

    function updateVisitorsEmpty() {
        if (!visitorsEmpty) return;
        visitorsEmpty.style.display = container.querySelectorAll('.visitor-row').length ? 'none' : '';
    }

    document.getElementById('addVisitor')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 visitor-row';
        row.innerHTML =
            '<div class="col-7"><input type="text" class="form-control" name="visitors[' + visitorIndex + '][name]" placeholder="Nome do visitante"></div>' +
            '<div class="col-4"><input type="text" class="form-control" name="visitors[' + visitorIndex + '][phone]" placeholder="Telefone"></div>' +
            '<div class="col-1 d-flex"><button type="button" class="btn btn-sm btn-light text-danger remove-visitor pgi-touch"><i class="bx bx-trash"></i></button></div>';
        container.appendChild(row);
        row.querySelector('input')?.focus();
        visitorIndex++;
        updateVisitorsEmpty();
    });

    container?.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-visitor');
        if (!button) return;
        button.closest('.visitor-row')?.remove();
        updateVisitorsEmpty();
    });

    updateVisitorsEmpty();
})();
</script>
@endpush
