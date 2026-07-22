@extends('layouts.porto')

@section('title', 'Análises — Relatórios de Culto')
@section('page-title', 'Análises')

@section('breadcrumbs')
    <li><a href="{{ route('cultos.index') }}">Relatórios de Culto</a></li>
    <li><span>Análises</span></li>
@endsection

@section('content')
@include('cultos.partials.module-nav', ['active' => 'analyses'])

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Por tipo de culto</h5>
                @forelse($byType as $row)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>{{ \App\Models\ServiceReport::TYPES[$row->service_type] ?? $row->service_type }}</span>
                        <strong>{{ $row->total }}</strong>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sem dados ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Últimos meses</h5>
                @forelse($monthly as $row)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $row['month'] }}</strong>
                            <span>{{ $row['count'] }} relatório(s)</span>
                        </div>
                        <div class="small text-muted">
                            {{ $row['present'] }} presentes · R$ {{ number_format($row['offering'], 2, ',', '.') }}
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Sem dados ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
