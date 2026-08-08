@extends('layouts.porto')

@section('title', 'Campanhas')

@section('page-title', 'Campanhas')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Campanhas</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canCreate = $isAdmin || $user->hasPermission('financial.campanhas.create') || $user->hasPermission('financial.campanhas.manage');
@endphp

@include('financial.campaigns.partials.alerts')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">Campanhas de Arrecadação</h4>
        <small class="text-muted">Arrecadações pontuais com prestação de contas própria — independentes do caixa da igreja.</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('financial.campaigns.reminders.global') }}" class="btn btn-outline-secondary">
            <i class="bx bx-bell me-1"></i> Lembretes (padrão)
        </a>
        @if($canCreate)
            <a href="{{ route('financial.campaigns.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Nova Campanha
            </a>
        @endif
    </div>
</div>

@if($campaigns->isEmpty())
    <section class="card">
        <div class="card-body text-center py-5">
            <i class="bx bx-donate-heart display-4 text-muted"></i>
            <p class="text-muted mt-2 mb-0">Nenhuma campanha cadastrada ainda.</p>
        </div>
    </section>
@else
    <div class="row">
        @foreach($campaigns as $campaign)
            @php
                $raised = $campaign->totalRaised();
                $progress = $campaign->progressPercentage();
                $statusBadge = match($campaign->status) {
                    'ativa' => 'bg-success',
                    'encerrada' => 'bg-secondary',
                    default => 'bg-danger',
                };
            @endphp
            <div class="col-md-6 col-xl-4 mb-3">
                <section class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="mb-0">
                                <a href="{{ route('financial.campaigns.show', $campaign) }}" class="text-decoration-none">{{ $campaign->name }}</a>
                            </h5>
                            <span class="badge {{ $statusBadge }}">{{ ucfirst($campaign->status) }}</span>
                        </div>
                        <p class="text-muted small mb-2">
                            @if($campaign->department)
                                <i class="bx bx-group me-1"></i>{{ $campaign->department->name }}<br>
                            @endif
                            @if($campaign->start_date || $campaign->end_date)
                                <i class="bx bx-calendar me-1"></i>
                                {{ $campaign->start_date?->format('d/m/Y') ?? '—' }} a {{ $campaign->end_date?->format('d/m/Y') ?? '—' }}
                            @endif
                        </p>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>R$ {{ number_format($raised, 2, ',', '.') }} arrecadado</span>
                                <span>{{ number_format($progress, 1, ',', '.') }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"
                                     aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            @if($campaign->goal_amount)
                                <small class="text-muted">Meta: R$ {{ number_format((float) $campaign->goal_amount, 2, ',', '.') }}</small>
                            @endif
                        </div>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bx bx-user me-1"></i>{{ $campaign->sponsors_count }} patrocinador(es)</small>
                            <a href="{{ route('financial.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-primary">Abrir</a>
                        </div>
                    </div>
                </section>
            </div>
        @endforeach
    </div>
@endif
@endsection
