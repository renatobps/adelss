@extends('layouts.porto')
@section('title', 'Notificações — '.$department->name)
@section('page-title', 'Notificações de eventos')
@section('content')
<div class="dept-page">
    <header class="dept-hero"><div class="dept-hero-copy"><div class="dept-eyebrow">{{ $department->name }}</div><h1>Notificações de eventos</h1><p>Organize os participantes e envie avisos por WhatsApp, agora ou na data escolhida.</p></div></header>
    <div class="row g-3">
        @forelse($events as $event)
            <div class="col-12 col-xl-6">
                <section class="dept-panel h-100">
                    <div class="dept-panel-heading"><div><h2>{{ $event->title }}</h2><p>{{ $event->start_date->format($event->all_day ? 'd/m/Y' : 'd/m/Y H:i') }}</p></div><i class="bx bx-bell" aria-hidden="true"></i></div>
                    <div class="dept-panel-body">
                        <p>{{ $event->participants_count }} participante(s) selecionado(s)</p>
                        <p class="dept-muted">Instância: {{ $event->notification?->instance_name ?: 'Não selecionada' }}</p>
                        @if($event->notification?->automatic_enabled)
                            <p class="dept-tag">Automático: {{ $event->notification->scheduled_at?->format('d/m/Y H:i') }}</p>
                        @else
                            <p class="dept-muted">Envio automático desativado</p>
                        @endif
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="{{ route('departments.events.notifications.edit', [$department, $event]) }}" class="btn btn-primary" style="min-height:44px">Participantes e envios</a>
                            <a href="{{ route('departments.events.show', [$department, $event]) }}" class="btn btn-outline-secondary" style="min-height:44px">Ver evento</a>
                        </div>
                    </div>
                </section>
            </div>
        @empty
            <div class="col-12"><div class="dept-empty">Cadastre um evento no calendário anual para organizar suas notificações.</div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
</div>
@endsection
