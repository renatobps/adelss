@extends('layouts.porto')

@section('title', 'Inscrições — '.$event->title)

@section('page-title', 'Inscrições')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><a href="{{ route('agenda.eventos.index') }}">Eventos</a></li>
    <li><span>{{ $event->title }}</span></li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="card-title mb-0">Inscrições</h2>
                    <p class="text-muted small mb-0 mt-1">{{ $event->title }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('agenda.eventos.index') }}" class="btn btn-default btn-sm"><i class="bx bx-arrow-back"></i> Voltar</a>
                    @if($event->public_slug)
                        <a href="{{ route('events.public.show', $event->public_slug) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-link-external"></i> Página do evento
                        </a>
                    @endif
                    <a href="{{ route('agenda.eventos.edit', $event) }}" class="btn btn-primary btn-sm"><i class="bx bx-edit"></i> Editar evento</a>
                </div>
            </header>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if(!$canEditRegistrations)
                    <div class="alert alert-info mb-3">
                        Você pode visualizar as inscrições. Para alterar o status, é necessária permissão de edição de eventos.
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th style="min-width: 11rem;">Status</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registrations as $r)
                                @php
                                    $st = $r->status ?? \App\Models\EventRegistration::STATUS_PENDENTE;
                                @endphp
                                <tr>
                                    <td>{{ $r->name }}</td>
                                    <td>
                                        @if($canEditRegistrations)
                                            <form method="post" action="{{ route('agenda.eventos.registrations.status', [$event, $r]) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="{{ \App\Models\EventRegistration::STATUS_PENDENTE }}" @selected($st === \App\Models\EventRegistration::STATUS_PENDENTE)>Pendente</option>
                                                    <option value="{{ \App\Models\EventRegistration::STATUS_CONFIRMADO }}" @selected($st === \App\Models\EventRegistration::STATUS_CONFIRMADO)>Confirmado</option>
                                                    <option value="{{ \App\Models\EventRegistration::STATUS_CANCELADO }}" @selected($st === \App\Models\EventRegistration::STATUS_CANCELADO)>Cancelado</option>
                                                </select>
                                            </form>
                                        @else
                                            @php
                                                $label = match ($st) {
                                                    \App\Models\EventRegistration::STATUS_CONFIRMADO => 'Confirmado',
                                                    \App\Models\EventRegistration::STATUS_CANCELADO => 'Cancelado',
                                                    default => 'Pendente',
                                                };
                                                $cls = match ($st) {
                                                    \App\Models\EventRegistration::STATUS_CONFIRMADO => 'success',
                                                    \App\Models\EventRegistration::STATUS_CANCELADO => 'secondary',
                                                    default => 'warning',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $cls }}">{{ $label }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $r->email ?: '—' }}</td>
                                    <td>{{ $r->phone ?: '—' }}</td>
                                    <td>{{ $r->created_at?->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">Nenhuma inscrição neste evento.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $registrations->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
