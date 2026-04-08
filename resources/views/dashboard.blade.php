@extends('layouts.porto')

@section('title', 'Dashboard')

@section('page-title', 'Visão Geral')

@section('breadcrumbs')
    <li><span>Dashboard</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Bem-vindo ao ADELSS Sistema Web</h2>
                <p class="card-subtitle">Sistema de gestão e administração</p>
            </header>
            <div class="card-body">
                <div class="welcome-mobile-menu d-lg-none">
                    <h4 class="welcome-mobile-title">Acessos Rápidos</h4>
                    <div class="welcome-mobile-grid">
                        <a class="welcome-mobile-item" href="{{ route('dashboard') }}">
                            <i class="bx bx-home-alt"></i>
                            <span>Início</span>
                        </a>
                        <a class="welcome-mobile-item" href="{{ route('members.index') }}">
                            <i class="bx bx-user"></i>
                            <span>Membros</span>
                        </a>
                        <a class="welcome-mobile-item" href="{{ route('notificacoes.painel.index') }}">
                            <i class="bx bx-bell"></i>
                            <span>Mensagens</span>
                        </a>
                        <a class="welcome-mobile-item" href="{{ route('agenda.calendario.index') }}">
                            <i class="bx bx-calendar"></i>
                            <span>Eventos</span>
                        </a>
                        <a class="welcome-mobile-item" href="{{ route('discipleship.dashboard.discipulador') }}">
                            <i class="bx bx-group"></i>
                            <span>Discipulado</span>
                        </a>
                        <a class="welcome-mobile-item" href="{{ route('moriah.ministerio') }}">
                            <i class="bx bx-music"></i>
                            <span>Moriah</span>
                        </a>
                        <a class="welcome-mobile-item" href="{{ route('financial.summary') }}">
                            <i class="bx bx-dollar"></i>
                            <span>Financeiro</span>
                        </a>
                        <a class="welcome-mobile-item" href="{{ route('ensino.estudos.index') }}">
                            <i class="bx bx-book-reader"></i>
                            <span>Ensino</span>
                        </a>
                        <a class="welcome-mobile-item" href="{{ route('pgis.index') }}">
                            <i class="bx bx-group"></i>
                            <span>PGIs</span>
                        </a>
                    </div>
                </div>

@php
    $weekEvents = collect();
    try {
        $weekStart = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);
        $weekEnd = \Carbon\Carbon::now()->endOfWeek(\Carbon\Carbon::SUNDAY);

        if (class_exists(\App\Models\Event::class)) {
            $weekEvents = \App\Models\Event::with('category')
                ->where(function ($query) use ($weekStart, $weekEnd) {
                    $query->whereBetween('start_date', [$weekStart, $weekEnd])
                        ->orWhereBetween('end_date', [$weekStart, $weekEnd])
                        ->orWhere(function ($q) use ($weekStart, $weekEnd) {
                            $q->where('start_date', '<=', $weekStart)
                                ->where('end_date', '>=', $weekEnd);
                        });
                })
                ->orderBy('start_date')
                ->limit(12)
                ->get();
        }
    } catch (\Throwable $e) {
        $weekEvents = collect();
    }
@endphp

<div class="row mt-4">
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-primary mb-3">
            <div class="card-body">
                <div class="widget-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-primary">
                            <i class="bx bx-user"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">Membros</h4>
                            <div class="info">
                                <strong class="amount">@if(class_exists('App\Models\Member')){{ \App\Models\Member::count() }}@else 0 @endif</strong>
                            </div>
                        </div>
                        <div class="summary-footer">
                            <a class="text-muted text-uppercase" href="{{ route('members.index') }}">(ver todos)</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-success mb-3">
            <div class="card-body">
                <div class="widget-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-success">
                            <i class="bx bx-check-circle"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">Ativos</h4>
                            <div class="info">
                                <strong class="amount">@if(class_exists('App\Models\Member')){{ \App\Models\Member::active()->count() }}@else 0 @endif</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-info mb-3">
            <div class="card-body">
                <div class="widget-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-info">
                            <i class="bx bx-building"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">Departamentos</h4>
                            <div class="info">
                                <strong class="amount">@if(class_exists('App\Models\Department')){{ \App\Models\Department::active()->count() }}@else 0 @endif</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="col-md-3">
        <section class="card card-featured-left card-featured-warning mb-3">
            <div class="card-body">
                <div class="widget-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-warning">
                            <i class="bx bx-group"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">PGIs</h4>
                            <div class="info">
                                <strong class="amount">@if(class_exists('App\Models\Pgi')){{ \App\Models\Pgi::count() }}@else 0 @endif</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="dashboard-events-month mt-3">
    <div class="dashboard-events-header">
        <div>
            <h3 class="dashboard-events-title">Eventos da Semana</h3>
            <p class="dashboard-events-subtitle">
                {{ $weekStart->format('d/m') }} a {{ $weekEnd->format('d/m') }}
            </p>
        </div>
        <a href="{{ route('agenda.eventos.index') }}" class="dashboard-events-link">
            Ver agenda completa <i class="bx bx-right-arrow-alt"></i>
        </a>
    </div>

    @if($weekEvents->isEmpty())
        <div class="dashboard-events-empty">
            <i class="bx bx-calendar-x"></i>
            <span>Não há eventos cadastrados para esta semana.</span>
        </div>
    @else
        <div class="dashboard-events-grid">
            @foreach($weekEvents as $event)
                <article class="dashboard-event-card">
                    <div class="dashboard-event-date">
                        <strong>{{ optional($event->start_date)->format('d') }}</strong>
                        <span>{{ optional($event->start_date)->translatedFormat('M') }}</span>
                    </div>
                    <div class="dashboard-event-content">
                        <h4>{{ $event->title }}</h4>
                        <p>
                            <i class="bx bx-time-five"></i>
                            @if($event->all_day)
                                Dia inteiro
                            @else
                                {{ optional($event->start_date)->format('H:i') }}
                            @endif
                            @if(!empty($event->location))
                                <span class="event-separator">-</span>
                                <i class="bx bx-map"></i> {{ $event->location }}
                            @endif
                        </p>
                    </div>
                    @if($event->category)
                        <span class="dashboard-event-category" style="--event-color: {{ $event->category->color ?? '#3b82f6' }}">
                            {{ $event->category->name }}
                        </span>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
            </div>
        </section>
    </div>
</div>
@endsection

