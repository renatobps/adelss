@extends('layouts.porto')

@section('title', 'Dashboard - Discipulador')

@section('page-title', 'Dashboard - Discipulador')

@section('breadcrumbs')
    <li><a href="{{ route('discipleship.cycles.index') }}">Discipulado</a></li>
    <li><span>Dashboard</span></li>
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <h3>Meus Discípulos</h3>
    </div>
</div>

@if(count($alerts) > 0)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-warning" style="box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bx bx-error me-2"></i>Alertas</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach($alerts as $alert)
                        <li>{{ $alert['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row mb-4">
    @foreach($disciples as $disciple)
        <div class="col-md-6 mb-3">
            <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div class="card-body">
                    <h5 class="card-title">{{ $disciple->member->name }}</h5>
                    <p class="card-text">
                        <small class="text-muted">Ciclo: {{ $disciple->cycle->nome }}</small><br>
                        <small class="text-muted">
                            Último encontro: 
                            @if($disciple->meetings->count() > 0)
                                {{ $disciple->meetings->first()->data->format('d/m/Y') }}
                            @else
                                Nenhum encontro registrado
                            @endif
                        </small>
                    </p>
                    <a href="{{ route('discipleship.members.show', $disciple) }}" class="btn btn-sm btn-primary">
                        <i class="bx bx-show me-1"></i>Ver Detalhes
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($disciples->count() === 0)
    <div class="text-center text-muted py-5">
        <i class="bx bx-user" style="font-size: 3rem;"></i>
        <p class="mt-2">Você não está discipulando nenhum membro no momento.</p>
    </div>
@endif

@if($lastMeetings->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header">
                <h5 class="mb-0">Últimos Encontros</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Membro</th>
                                <th>Tipo</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lastMeetings as $meeting)
                                <tr>
                                    <td>{{ $meeting->data->format('d/m/Y') }}</td>
                                    <td>{{ $meeting->discipleshipMember->member->name }}</td>
                                    <td>
                                        @if($meeting->tipo === 'presencial')
                                            <span class="badge bg-primary">Presencial</span>
                                        @else
                                            <span class="badge bg-info">Online</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('discipleship.meetings.show', $meeting) }}" class="btn btn-sm btn-info">
                                            <i class="bx bx-show"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
