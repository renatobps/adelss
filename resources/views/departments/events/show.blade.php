@extends('layouts.porto')
@section('title', $event->title)
@section('page-title', 'Detalhes do evento')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.events.index', $department) }}">{{ $department->name }}</a></li>
    <li><span>Evento</span></li>
@endsection
@section('content')
<section class="card">
    <header class="card-header"><h2 class="card-title">{{ $event->title }}</h2></header>
    <div class="card-body">
        @if($event->banner_url)
            <figure class="mb-4">
                <img src="{{ $event->banner_url }}" alt="Banner do evento {{ $event->title }}" class="img-fluid rounded border" style="max-height:420px;object-fit:contain" loading="lazy">
            </figure>
        @endif
        <dl>
            <dt>Departamento</dt><dd>{{ $department->name }}</dd>
            <dt>Início</dt><dd>{{ $event->start_date->format($event->all_day ? 'd/m/Y' : 'd/m/Y H:i') }}</dd>
            @if($event->end_date)<dt>Término</dt><dd>{{ $event->end_date->format($event->all_day ? 'd/m/Y' : 'd/m/Y H:i') }}</dd>@endif
            @if($event->all_day)<dt>Duração</dt><dd>Dia inteiro</dd>@endif
            <dt>Local</dt><dd>{{ $event->location ?: 'Não informado' }}</dd>
            <dt>Custo do evento</dt><dd>{{ $event->cost !== null ? 'R$ '.number_format((float) $event->cost, 2, ',', '.') : 'Não informado' }}</dd>
            <dt>Descrição</dt><dd style="white-space:pre-wrap; overflow-wrap:anywhere">{{ $event->description ?: 'Não informada' }}</dd>
        </dl>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('departments.events.index', [$department, 'year' => $event->start_date->year]) }}" class="btn btn-default" style="min-height:44px">Voltar ao calendário</a>
            @can('update', $department)
                <a href="{{ route('departments.events.edit', [$department, $event]) }}" class="btn btn-primary" style="min-height:44px">Editar evento</a>
                <a href="{{ route('departments.events.notifications.edit', [$department, $event]) }}" class="btn btn-outline-primary" style="min-height:44px"><i class="bx bx-bell me-1" aria-hidden="true"></i>Notificações e participantes</a>
            @endcan
            @can('delete', $department)
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteEventModal" style="min-height:44px">Excluir evento</button>
            @endcan
        </div>
    </div>
</section>
@can('delete', $department)
<div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventTitle" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h3 id="deleteEventTitle" class="modal-title h5">Excluir evento</h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body">Deseja excluir o evento <strong>{{ $event->title }}</strong>? Esta ação não pode ser desfeita.</div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button>
            <form method="POST" action="{{ route('departments.events.destroy', [$department, $event]) }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Confirmar exclusão</button>
            </form>
        </div>
    </div></div>
</div>
@endcan
@endsection
