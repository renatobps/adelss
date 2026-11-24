@extends('layouts.porto')
@section('title', 'Calendário anual — '.$department->name)
@section('page-title', 'Calendário anual')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.show', $department) }}">{{ $department->name }}</a></li>
    <li><span>Calendário anual</span></li>
@endsection
@push('styles')
<style>
    .department-calendar { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); text-align: center; gap: 2px; }
    .department-calendar .calendar-day { min-height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 4px; }
    .department-calendar .has-events { background: #e8f0fc; font-weight: 600; }
    .department-calendar .today { outline: 2px solid #235ba8; outline-offset: -2px; }
    .department-month-events a { min-height: 44px; display: block; padding: 8px 0; overflow-wrap: anywhere; }
    .department-calendar-actions .btn { min-height: 44px; }
</style>
@endpush
@section('content')
<div class="card mb-4 department-calendar-actions">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h4 mb-1">{{ $department->name }} · {{ $year }}</h2>
                <p class="text-muted mb-0">{{ $events->count() }} evento(s) neste ano. Selecione um evento para consultar seus detalhes.</p>
            </div>
            @can('create', App\Models\Department::class)
                <a href="{{ route('departments.events.create', [$department, 'date' => sprintf('%04d-01-01', $year)]) }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Novo evento</a>
            @endcan
        </div>
        <div class="btn-group mt-3" role="group" aria-label="Visualização dos eventos">
            <a href="{{ route('departments.events.index', [$department, 'year' => $year, 'view' => 'calendar']) }}" class="btn {{ $display === 'calendar' ? 'btn-primary' : 'btn-outline-primary' }}" @if($display === 'calendar') aria-current="page" @endif><i class="bx bx-calendar me-1" aria-hidden="true"></i>Calendário</a>
            <a href="{{ route('departments.events.index', [$department, 'year' => $year, 'view' => 'list']) }}" class="btn {{ $display === 'list' ? 'btn-primary' : 'btn-outline-primary' }}" @if($display === 'list') aria-current="page" @endif><i class="bx bx-list-ul me-1" aria-hidden="true"></i>Lista do ano</a>
        </div>
        <form method="GET" action="{{ route('departments.events.index', $department) }}" class="d-flex flex-wrap align-items-end gap-2 mt-3">
            <input type="hidden" name="view" value="{{ $display }}">
            @if($year > 1900)
                <a class="btn btn-default" href="{{ route('departments.events.index', [$department, 'year' => $year - 1, 'view' => $display]) }}" aria-label="Ano anterior">‹ {{ $year - 1 }}</a>
            @endif
            <div>
                <label for="year" class="form-label">Ano</label>
                <input id="year" type="number" name="year" min="1900" max="9998" required value="{{ $year }}" class="form-control" style="max-width: 110px; min-height: 44px;">
            </div>
            <button class="btn btn-default" type="submit">Visualizar</button>
            @if($year < 9998)
                <a class="btn btn-default" href="{{ route('departments.events.index', [$department, 'year' => $year + 1, 'view' => $display]) }}" aria-label="Próximo ano">{{ $year + 1 }} ›</a>
            @endif
            <a class="btn btn-default" href="{{ route('departments.events.index', [$department, 'view' => $display]) }}">Ano atual</a>
        </form>
        @if($events->isEmpty())
            <p class="alert alert-info mt-3 mb-0">Nenhum evento cadastrado para este departamento em {{ $year }}.</p>
        @endif
        @if($display === 'calendar')
        @can('create', App\Models\Department::class)
            <p class="text-muted mt-3 mb-0">Clique em um dia do calendário para cadastrar um evento nessa data. Os dias destacados possuem eventos.</p>
        @endcan
        @endif
    </div>
</div>
@if($display === 'list')
    <div class="row g-3" aria-label="Todos os eventos de {{ $year }}">
        @foreach($events as $event)
            <div class="col-12">
                <article class="card">
                    <div class="card-body d-flex flex-wrap align-items-center gap-3">
                        @if($event->banner_url)<img src="{{ $event->banner_url }}" alt="" class="rounded" style="width:96px;height:72px;object-fit:cover" loading="lazy">@endif
                        <div class="flex-grow-1" style="min-width:0;overflow-wrap:anywhere">
                            <h3 class="h5 mb-1"><a href="{{ route('departments.events.show', [$department, $event]) }}">{{ $event->title }}</a></h3>
                            <p class="mb-1 text-muted">{{ $event->start_date->format($event->all_day ? 'd/m/Y' : 'd/m/Y H:i') }}@if($event->end_date) — {{ $event->end_date->format($event->all_day ? 'd/m/Y' : 'd/m/Y H:i') }}@endif @if($event->all_day) · Dia inteiro @endif</p>
                            <p class="mb-0 small">{{ $event->location ?: 'Local não informado' }} @if($event->cost !== null) · R$ {{ number_format((float) $event->cost, 2, ',', '.') }} @endif</p>
                        </div>
                        <a class="btn btn-outline-primary" style="min-height:44px" href="{{ route('departments.events.show', [$department, $event]) }}">Ver evento</a>
                        @can('update', $department)<a class="btn btn-outline-secondary" style="min-height:44px" href="{{ route('departments.events.notifications.edit', [$department, $event]) }}"><i class="bx bx-bell me-1" aria-hidden="true"></i>Notificações</a>@endcan
                    </div>
                </article>
            </div>
        @endforeach
    </div>
@else
<div class="row">
    @foreach($months as $month)
        @php
            $date = $month['date'];
        @endphp
        <div class="col-12 col-lg-6 col-xxl-4 mb-4">
            <section class="card h-100" aria-label="{{ $date->translatedFormat('F Y') }}">
                <header class="card-header"><h3 class="card-title text-capitalize">{{ $date->translatedFormat('F') }}</h3></header>
                <div class="card-body">
                    <div class="department-calendar">
                        @foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $weekday)
                            <span class="text-muted small" aria-hidden="true">{{ $weekday }}</span>
                        @endforeach
                        @for($blank = 0; $blank < $date->dayOfWeek; $blank++)
                            <span aria-hidden="true"></span>
                        @endfor
                        @for($day = 1; $day <= $date->daysInMonth; $day++)
                            @php
                                $current = $date->day($day);
                                $dayEvents = $month['events']->filter(fn ($event) => $event->start_date->lte($current->endOfDay()) && ($event->end_date ?? $event->start_date)->gte($current));
                                $label = $current->format('d/m/Y').' · '.$dayEvents->count().' evento(s)';
                            @endphp
                            @if($dayEvents->isNotEmpty())
                                <a class="calendar-day has-events {{ $current->isToday() ? 'today' : '' }}" href="#month-{{ $date->month }}" title="{{ $label }}" aria-label="{{ $label }}">{{ $day }} <span aria-hidden="true">•</span></a>
                            @elsecan('create', App\Models\Department::class)
                                <a class="calendar-day {{ $current->isToday() ? 'today' : '' }}" href="{{ route('departments.events.create', [$department, 'date' => $current->toDateString()]) }}" aria-label="Cadastrar evento em {{ $current->format('d/m/Y') }}">{{ $day }}</a>
                            @else
                                <span class="calendar-day {{ $current->isToday() ? 'today' : '' }}">{{ $day }}</span>
                            @endif
                        @endfor
                    </div>
                    <div id="month-{{ $date->month }}" class="department-month-events border-top mt-3 pt-2">
                        @forelse($month['events'] as $event)
                            <a href="{{ route('departments.events.show', [$department, $event]) }}">
                                <strong>{{ $event->title }}</strong><br>
                                <span class="text-muted small">
                                    {{ $event->start_date->format($event->all_day ? 'd/m/Y' : 'd/m/Y H:i') }}
                                    @if($event->end_date) — {{ $event->end_date->format($event->all_day ? 'd/m/Y' : 'd/m/Y H:i') }} @endif
                                    @if($event->all_day) · Dia inteiro @endif
                                </span>
                            </a>
                        @empty
                            <p class="text-muted small mb-0">Sem eventos neste mês.</p>
                        @endforelse
                        @can('create', App\Models\Department::class)
                            <a href="{{ route('departments.events.create', [$department, 'date' => $date->toDateString()]) }}" class="small"><i class="bx bx-plus me-1"></i>Adicionar evento em {{ $date->translatedFormat('F') }}</a>
                        @endcan
                    </div>
                </div>
            </section>
        </div>
    @endforeach
</div>
@endif
@endsection
