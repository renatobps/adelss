@extends('layouts.porto')

@section('title', 'Reunião do PGI')

@section('page-title', $pgi->name)

@section('breadcrumbs')
    <li><a href="{{ route('pgis.index') }}">PGIs</a></li>
    <li><a href="{{ route('pgis.show', $pgi) }}">{{ $pgi->name }}</a></li>
    <li><span>Reunião de {{ $meeting->meeting_date->format('d/m/Y') }}</span></li>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/css/pgis.css') }}?v={{ @filemtime(public_path('css/css/pgis.css')) ?: '1' }}">
@endpush

@section('content')
@php
    $user = Auth::user();
    $viewerMember = $user?->member;
    $canManageMeetings = ($user?->is_admin ?? false) || ($viewerMember && $pgi->isLeader($viewerMember));
    $registered = $meeting->hasAttendanceRegistered();
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

<div class="card pgi-card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h4 class="mb-1">{{ $meeting->subject ?: 'Reunião do PGI' }}</h4>
                <div class="text-muted">
                    <i class="bx bx-calendar me-1"></i>
                    {{ $meeting->meeting_date->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y') }}
                </div>
                <div class="mt-2">
                    @if($registered)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bx bx-check me-1"></i>Presença registrada
                        </span>
                        <small class="text-muted ms-2">
                            {{ $meeting->attendance_registered_at->format('d/m/Y H:i') }}
                            @if($meeting->registeredBy) por {{ $meeting->registeredBy->name }} @endif
                        </small>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                            <i class="bx bx-time-five me-1"></i>Chamada pendente
                        </span>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @if($canManageMeetings)
                    <a href="{{ route('pgis.meetings.attendance', [$pgi, $meeting]) }}" class="btn btn-primary">
                        <i class="bx bx-list-check me-1"></i>{{ $registered ? 'Refazer chamada' : 'Fazer a chamada' }}
                    </a>
                    <a href="{{ route('pgis.meetings.edit', [$pgi, $meeting]) }}" class="btn btn-outline-secondary">
                        <i class="bx bx-edit me-1"></i>Editar reunião
                    </a>
                @endif
                <a href="{{ route('pgis.show', $pgi) }}" class="btn btn-light">
                    <i class="bx bx-arrow-back me-1"></i>Voltar ao PGI
                </a>
            </div>
        </div>

        <div class="pgi-kpis mt-4">
            <div class="pgi-kpi">
                <div class="pgi-kpi__icon"><i class="bx bx-user-check"></i></div>
                <div>
                    <div class="pgi-kpi__value">{{ $presentMembers->count() }}/{{ $pgi->members->count() }}</div>
                    <div class="pgi-kpi__label">Participantes presentes</div>
                </div>
            </div>
            <div class="pgi-kpi">
                <div class="pgi-kpi__icon"><i class="bx bx-user-plus"></i></div>
                <div>
                    <div class="pgi-kpi__value">{{ $visitors->count() }}</div>
                    <div class="pgi-kpi__label">Visitantes</div>
                </div>
            </div>
            <div class="pgi-kpi">
                <div class="pgi-kpi__icon"><i class="bx bx-money"></i></div>
                <div>
                    <div class="pgi-kpi__value">R$ {{ number_format((float) $meeting->total_value, 2, ',', '.') }}</div>
                    <div class="pgi-kpi__label">Oferta</div>
                </div>
            </div>
        </div>

        @if($meeting->notes)
            <div class="mt-4">
                <h6 class="text-uppercase text-muted small">Observações</h6>
                <p class="mb-0" style="white-space: pre-line;">{{ $meeting->notes }}</p>
            </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card pgi-card h-100">
            <header class="card-header">
                <h5 class="card-title mb-0 text-success">
                    <i class="bx bx-check-circle me-2"></i>Presentes ({{ $presentMembers->count() }})
                </h5>
            </header>
            <div class="card-body">
                @forelse($presentMembers as $member)
                    <div class="pgi-member">
                        @include('members.partials.avatar', ['member' => $member, 'size' => 34])
                        <span class="pgi-member__name">{{ $member->name }}</span>
                    </div>
                @empty
                    @include('pgis.partials.empty-state', [
                        'icon' => 'bx-user-x',
                        'title' => $registered ? 'Ninguém foi marcado como presente' : 'Chamada ainda não realizada',
                        'description' => $canManageMeetings ? 'Registre quem participou desta reunião.' : null,
                        'actionUrl' => $canManageMeetings ? route('pgis.meetings.attendance', [$pgi, $meeting]) : null,
                        'actionLabel' => 'Registrar presença',
                        'actionIcon' => 'bx-list-check',
                    ])
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card pgi-card h-100">
            <header class="card-header">
                <h5 class="card-title mb-0 text-danger">
                    <i class="bx bx-x-circle me-2"></i>Ausentes ({{ $absentMembers->count() }})
                </h5>
            </header>
            <div class="card-body">
                @forelse($absentMembers as $member)
                    <div class="pgi-member">
                        @include('members.partials.avatar', ['member' => $member, 'size' => 34])
                        <span class="pgi-member__name">{{ $member->name }}</span>
                    </div>
                @empty
                    @include('pgis.partials.empty-state', [
                        'icon' => 'bx-party',
                        'title' => 'Todos os membros estiveram presentes',
                    ])
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card pgi-card h-100">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-user-plus me-2"></i>Visitantes ({{ $visitors->count() }})
                </h5>
            </header>
            <div class="card-body">
                @forelse($visitors as $visitor)
                    <div class="pgi-member">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                             style="width:34px;height:34px;background:#F59E0B;color:#fff;font-weight:700;">
                            <i class="bx bx-user"></i>
                        </div>
                        <div>
                            <div class="pgi-member__name">{{ $visitor->visitor_name }}</div>
                            @if($visitor->visitor_phone)
                                <div class="pgi-member__freq">{{ $visitor->visitor_phone }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    @include('pgis.partials.empty-state', [
                        'icon' => 'bx-user-voice',
                        'title' => 'Nenhum visitante nesta reunião',
                        'description' => $canManageMeetings ? 'Visitantes podem ser adicionados junto com a chamada.' : null,
                        'actionUrl' => $canManageMeetings ? route('pgis.meetings.attendance', [$pgi, $meeting]) : null,
                        'actionLabel' => 'Adicionar visitante',
                        'actionIcon' => 'bx-plus',
                    ])
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
