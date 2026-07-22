@extends('layouts.porto')

@section('title', 'Resumo Financeiro')

@section('page-title', 'Resumo Financeiro')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Resumo</span></li>
@endsection

@section('content')
@php
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $resultadoPositivo = $resultado >= 0;
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="financial-kpi-card h-100">
            <div class="financial-kpi-card__top">
                <div>
                    <div class="financial-kpi-card__title">Saldo Total</div>
                    <div class="financial-kpi-card__period">{{ $periodLabel }}</div>
                </div>
                <i class="bx bx-wallet financial-kpi-card__icon"></i>
            </div>
            <div class="financial-kpi-card__value text-success">{{ $fmt($saldoTotal) }}</div>
            <div class="financial-kpi-card__footer">Balanço do período</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="financial-kpi-card h-100">
            <div class="financial-kpi-card__top">
                <div>
                    <div class="financial-kpi-card__title">Entradas</div>
                    <div class="financial-kpi-card__period">{{ $periodLabel }}</div>
                </div>
                <i class="bx bx-trending-up financial-kpi-card__icon text-success"></i>
            </div>
            <div class="financial-kpi-card__value text-success">{{ $fmt($entradas) }}</div>
            <div class="financial-kpi-card__footer">{{ $entradasCount }} {{ $entradasCount === 1 ? 'transação' : 'transações' }}</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="financial-kpi-card h-100">
            <div class="financial-kpi-card__top">
                <div>
                    <div class="financial-kpi-card__title">Saídas</div>
                    <div class="financial-kpi-card__period">{{ $periodLabel }}</div>
                </div>
                <i class="bx bx-trending-down financial-kpi-card__icon text-danger"></i>
            </div>
            <div class="financial-kpi-card__value text-danger">{{ $fmt($saidas) }}</div>
            <div class="financial-kpi-card__footer">Despesas pagas</div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="financial-kpi-card h-100">
            <div class="financial-kpi-card__top">
                <div>
                    <div class="financial-kpi-card__title">Resultado</div>
                    <div class="financial-kpi-card__period">{{ $periodLabel }}</div>
                </div>
                <i class="bx bx-dollar financial-kpi-card__icon"></i>
            </div>
            <div class="financial-kpi-card__value {{ $resultadoPositivo ? 'text-success' : 'text-danger' }}">
                {{ $fmt($resultado) }}
            </div>
            <div class="financial-kpi-card__footer">{{ $resultadoLabel }}</div>
        </div>
    </div>
</div>

<div class="card financial-period-card mb-4">
    <div class="card-body">
        <h5 class="financial-period-card__title mb-3">
            <i class="bx bx-calendar me-2"></i>Período do Resumo
        </h5>
        <form method="GET" action="{{ route('financial.summary') }}" class="row g-3 align-items-end">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <div class="col-md-4 col-lg-3">
                <label for="periodSelect" class="form-label mb-1">Período</label>
                <select class="form-select" id="periodSelect" name="period" onchange="this.form.submit()">
                    @foreach($periodOptions as $value => $label)
                        <option value="{{ $value }}" {{ $periodFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 col-lg-4">
                <div class="financial-period-card__range">{{ $periodRangeLabel }}</div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar-check me-2"></i>Resumo mensal
                </h5>
                <form method="GET" action="{{ route('financial.summary') }}" style="display: inline;">
                    <input type="hidden" name="period" value="{{ $periodFilter }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <select class="form-select form-select-sm" style="max-width: 200px;" name="month" onchange="this.form.submit()">
                        @foreach($availableMonths as $month)
                            <option value="{{ $month['value'] }}" {{ $selectedMonth == $month['value'] ? 'selected' : '' }}>{{ $month['label'] }}</option>
                        @endforeach
                    </select>
                </form>
            </header>
            <div class="card-body">
                <canvas id="monthlyChart" height="60"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar me-2"></i>Resumo anual
                </h5>
                <form method="GET" action="{{ route('financial.summary') }}" style="display: inline;">
                    <input type="hidden" name="period" value="{{ $periodFilter }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <select class="form-select form-select-sm" style="max-width: 150px;" name="year" onchange="this.form.submit()">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </header>
            <div class="card-body">
                <canvas id="annualChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .financial-kpi-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        padding: 1.15rem 1.2rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .financial-kpi-card__top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }
    .financial-kpi-card__title {
        font-size: 0.98rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
    }
    .financial-kpi-card__period {
        margin-top: 0.2rem;
        font-size: 0.82rem;
        color: #6b7280;
    }
    .financial-kpi-card__icon {
        font-size: 1.35rem;
        color: #6b7280;
        line-height: 1;
    }
    .financial-kpi-card__value {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1.15;
        margin-bottom: 0.55rem;
    }
    .financial-kpi-card__footer {
        font-size: 0.82rem;
        color: #6b7280;
    }
    .financial-period-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .financial-period-card__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #111827;
    }
    .financial-period-card__range {
        display: inline-flex;
        align-items: center;
        min-height: 38px;
        padding: 0.45rem 0.9rem;
        border-radius: 0.55rem;
        background: #f3f4f6;
        color: #4b5563;
        font-size: 0.92rem;
        font-weight: 500;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const annualData = @json($annualData ?? []);
    const annualCtx = document.getElementById('annualChart');
    if (annualCtx && annualData.labels) {
        new Chart(annualCtx, {
            type: 'line',
            data: {
                labels: annualData.labels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: annualData.receitas || [],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Despesas',
                        data: annualData.despesas || [],
                        borderColor: '#ff9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'A receber',
                        data: annualData.aReceber || [],
                        borderColor: '#28a745',
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'A pagar',
                        data: annualData.aPagar || [],
                        borderColor: '#dc3545',
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: annualData.maxValue || 1000
                    }
                },
                plugins: {
                    legend: { display: true, position: 'bottom' }
                }
            }
        });
    }

    const monthlyData = @json($monthlyData ?? []);
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx && monthlyData.labels) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: monthlyData.receitas || [],
                        borderColor: '#007bff',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Despesas',
                        data: monthlyData.despesas || [],
                        borderColor: '#ff9800',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'A receber',
                        data: monthlyData.aReceber || [],
                        borderColor: '#28a745',
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'A pagar',
                        data: monthlyData.aPagar || [],
                        borderColor: '#dc3545',
                        borderDash: [5, 5],
                        tension: 0.4,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: monthlyData.maxValue || 1000
                    }
                },
                plugins: {
                    legend: { display: true, position: 'bottom' }
                }
            }
        });
    }
</script>
@endpush
@endsection
