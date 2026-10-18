@extends('layouts.porto')

@section('title', 'Prestação de Contas — ' . $campaign->name)

@section('page-title', 'Prestação de Contas')

@section('breadcrumbs')
    <li><a href="{{ route('eventos.campaigns.index') }}">Gestor de Eventos</a></li>
    <li><a href="{{ route('eventos.campaigns.index') }}">Campanhas</a></li>
    <li><a href="{{ route('eventos.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></li>
    <li><span>Prestação de Contas</span></li>
@endsection

@push('styles')
<style>
    /* ===== Prestação de contas (pc-) ===== */
    .pc-page { max-width: 1400px; margin-inline: auto; }

    .pc-hero {
        background: linear-gradient(135deg, #2E353E 0%, #1F262E 100%);
        color: #FFFFFF;
        border-radius: 14px;
        padding: 1.25rem 1.5rem;
        position: relative;
        overflow: visible;
        z-index: 3;
    }
    .pc-hero-deco {
        position: absolute; inset: 0; overflow: hidden; border-radius: 14px; pointer-events: none;
    }
    .pc-hero-deco::after {
        content: "";
        position: absolute; right: -40px; top: -60px;
        width: 220px; height: 220px; border-radius: 50%;
        background: var(--pc-accent, #0088CC);
        opacity: .18;
    }
    .pc-hero h4 { color: #FFFFFF; font-weight: 600; margin: 0; }
    .pc-hero .pc-actions { position: relative; z-index: 4; }
    .pc-hero .dropdown-menu { z-index: 1080; }
    .pc-hero .pc-hero-meta { color: rgba(255,255,255,.72); font-size: .85rem; }
    .pc-hero .pc-hero-meta i { opacity: .8; }
    .pc-hero .badge { font-weight: 500; }
    .pc-hero .btn-light { background: rgba(255,255,255,.95); border-color: transparent; }
    .pc-hero .btn-outline-light { border-color: rgba(255,255,255,.45); }
    .pc-hero .btn-outline-light:hover { background: rgba(255,255,255,.12); border-color: #FFFFFF; color: #FFFFFF; }

    .pc-filter { border-radius: 12px; }
    .pc-filter .form-label { font-size: .78rem; color: #6C757D; text-transform: uppercase; letter-spacing: .4px; }
    .pc-filter .pc-period-badge { font-size: .8rem; }

    .pc-kpi {
        border: 1px solid #EEF0F2;
        border-radius: 12px;
        background: #FFFFFF;
        padding: .9rem 1rem;
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .pc-kpi:hover { box-shadow: 0 .4rem 1rem rgba(46,53,62,.08); transform: translateY(-1px); }
    .pc-kpi::before {
        content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
        background: var(--pc-color, #0088CC);
    }
    .pc-kpi .pc-kpi-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: color-mix(in srgb, var(--pc-color, #0088CC) 14%, #FFFFFF);
        color: var(--pc-color, #0088CC);
        font-size: 1.25rem;
    }
    .pc-kpi .pc-kpi-label { font-size: .76rem; color: #6C757D; text-transform: uppercase; letter-spacing: .4px; }
    .pc-kpi .pc-kpi-value { font-size: 1.15rem; font-weight: 700; color: #2E353E; line-height: 1.2; }
    .pc-kpi .pc-kpi-sub { font-size: .76rem; color: #6C757D; }
    .pc-kpi .progress { height: 6px; background: #EEF0F2; }

    .pc-flow { border-radius: 12px; border: 1px solid #EEF0F2; background: #FBFCFD; }
    .pc-flow .pc-flow-item { text-align: center; padding: .75rem .5rem; }
    .pc-flow .pc-flow-label { font-size: .74rem; color: #6C757D; text-transform: uppercase; letter-spacing: .4px; }
    .pc-flow .pc-flow-value { font-size: 1.2rem; font-weight: 700; }
    .pc-flow .pc-flow-op { font-size: 1.5rem; color: #B9C4CE; font-weight: 300; }

    .pc-card { border-radius: 12px; border: 1px solid #EEF0F2; }
    .pc-card > .card-header { background: transparent; border-bottom: 1px solid #EEF0F2; padding: .85rem 1.1rem; }
    .pc-card .card-title-sm { font-size: .95rem; font-weight: 600; color: #2E353E; margin: 0; display: flex; align-items: center; gap: .5rem; }
    .pc-card .card-title-sm i { color: #0088CC; font-size: 1.1rem; }
    .pc-card .card-subtitle { font-size: .78rem; color: #8A97A3; margin: .15rem 0 0; }

    .pc-bars { list-style: none; margin: 0; padding: 0; }
    .pc-bars li { padding: .55rem 1.1rem; border-bottom: 1px solid #F3F5F7; }
    .pc-bars li:last-child { border-bottom: 0; }
    .pc-bars .pc-bar-head { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; font-size: .86rem; }
    .pc-bars .pc-bar-head strong { color: #2E353E; }
    .pc-bars .pc-bar-pct { color: #8A97A3; font-size: .76rem; margin-left: .35rem; }
    .pc-bars .progress { height: 7px; background: #EEF0F2; margin-top: .35rem; }

    .pc-table { margin: 0; }
    .pc-table thead th {
        font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: #6C757D;
        background: #F7F9FB; border-bottom: 1px solid #EEF0F2; font-weight: 600; white-space: nowrap;
    }
    .pc-table td, .pc-table th { padding: .6rem .75rem; vertical-align: middle; }
    .pc-table tbody tr { border-left: 3px solid transparent; }
    .pc-table tbody tr:hover { background: rgba(0,136,204,.03); }
    .pc-table tbody tr.is-quitado { border-left-color: #1FA855; }
    .pc-table tbody tr.is-em_dia { border-left-color: #0088CC; }
    .pc-table tbody tr.is-em_atraso { border-left-color: #DC3545; }
    .pc-table tfoot td { background: #F7F9FB; font-weight: 700; border-top: 2px solid #EEF0F2; }
    .pc-table .pc-mini-progress { height: 5px; background: #EEF0F2; width: 90px; }
    .pc-table .pc-name { font-weight: 600; color: #2E353E; }
    .pc-table .pc-sub { font-size: .76rem; color: #8A97A3; }
    .pc-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }

    .pc-pill { display: inline-block; font-size: .72rem; font-weight: 600; padding: .3rem .6rem; border-radius: 999px; white-space: nowrap; }
    .pc-pill--quitado { background: rgba(31,168,85,.12); color: #1FA855; }
    .pc-pill--em_dia { background: rgba(0,136,204,.12); color: #0088CC; }
    .pc-pill--em_atraso { background: rgba(220,53,69,.12); color: #DC3545; }
    .pc-pill--neutral { background: #EEF0F2; color: #6C757D; }
    .pc-pill--method { background: rgba(46,53,62,.07); color: #2E353E; font-weight: 500; }
    .pc-pill--cat { background: rgba(245,166,35,.14); color: #A56A00; font-weight: 500; }

    .pc-legend { display: flex; flex-wrap: wrap; gap: .4rem; }
    .pc-legend .pc-pill { padding: .35rem .7rem; }
    .pc-legend .pc-pill span { opacity: .75; font-weight: 500; margin-left: .25rem; }

    .pc-empty { color: #8A97A3; text-align: center; padding: 2.25rem 1rem; font-size: .9rem; }
    .pc-empty i { font-size: 1.8rem; display: block; margin-bottom: .35rem; opacity: .6; }

    .pc-text-success { color: #1FA855 !important; }
    .pc-text-danger { color: #DC3545 !important; }
    .pc-text-warning { color: #D9850B !important; }
    .pc-text-primary { color: #0088CC !important; }

    @media (max-width: 767.98px) {
        .pc-hero { border-radius: 10px; padding: 1rem; }
        .pc-flow .pc-flow-op { display: none; }
        .pc-kpi .pc-kpi-value { font-size: 1rem; }
    }
    @media print {
        .pc-hero .pc-actions, .pc-filter, .campaign-tabs, .adelss-module-nav { display: none !important; }
    }
</style>
@endpush

@section('content')
@php
    use App\Models\CampaignInstallment;
    use App\Models\CampaignExpense;

    $money = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $accent = $campaign->accentColor();
    $hasPeriod = request('from') || request('to');
    $raisedTotal = max(0.01, (float) $byMethod->sum());
    $expensesTotal = max(0.01, (float) $expensesByCategory->sum());
    $sponsorCount = max(1, $sponsorRows->count());
    $balancePositive = $metrics['balance'] >= 0;
    $pillFor = fn (string $key) => match ($key) {
        'quitado' => 'pc-pill--quitado',
        'em_atraso' => 'pc-pill--em_atraso',
        'em_dia' => 'pc-pill--em_dia',
        default => 'pc-pill--neutral',
    };
    $methodColors = ['pix' => '#0088CC', 'dinheiro' => '#1FA855', 'cartao' => '#6F42C1', 'outro' => '#8A97A3'];
    $pdfParams = array_merge(['campaign' => $campaign->id], request()->only('from', 'to'));
@endphp

@include('eventos.campaigns.partials.alerts')
@include('eventos.campaigns.partials.campaign-tabs', ['active' => 'prestacao'])

<div class="pc-page">

    {{-- Cabeçalho --}}
    <div class="pc-hero mb-3" style="--pc-accent: {{ $accent }}">
        <div class="pc-hero-deco" aria-hidden="true"></div>
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative" style="z-index: 1">
            <div>
                <div class="pc-hero-meta mb-1"><i class="bx bx-receipt me-1"></i>Prestação de contas</div>
                <h4>{{ $campaign->name }}</h4>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="badge {{ $campaign->status === 'ativa' ? 'bg-success' : ($campaign->status === 'encerrada' ? 'bg-secondary' : 'bg-danger') }}">{{ ucfirst($campaign->status) }}</span>
                    @if($campaign->department)
                        <span class="badge bg-light text-dark"><i class="bx bx-group me-1"></i>{{ $campaign->department->name }}</span>
                    @endif
                    @if($campaign->start_date || $campaign->end_date)
                        <span class="pc-hero-meta"><i class="bx bx-calendar me-1"></i>{{ $campaign->start_date?->format('d/m/Y') ?? '—' }} a {{ $campaign->end_date?->format('d/m/Y') ?? '—' }}</span>
                    @endif
                    <span class="pc-hero-meta"><i class="bx bx-time-five me-1"></i>Gerado em {{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            <div class="pc-actions d-flex gap-2 flex-wrap">
                <div class="btn-group">
                    <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                        <i class="bx bxs-file-pdf me-1 text-danger"></i>Exportar PDF
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 280px;">
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('eventos.campaigns.report.pdf', $pdfParams) }}">
                                <div class="fw-semibold"><i class="bx bx-detail me-1 text-danger"></i>Completo</div>
                                <div class="small text-muted ms-4">Patrocinadores com telefone, parcelas, comprometido, pago, pendente e situação.</div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('eventos.campaigns.report.pdf', $pdfParams + ['modo' => 'simplificado']) }}">
                                <div class="fw-semibold"><i class="bx bx-list-ul me-1 text-danger"></i>Simplificado</div>
                                <div class="small text-muted ms-4">Patrocinadores apenas com nome e valor pago.</div>
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="{{ route('eventos.campaigns.report.excel', array_merge(['campaign' => $campaign->id], request()->only('from', 'to'))) }}" class="btn btn-outline-light btn-sm">
                    <i class="bx bxs-spreadsheet me-1"></i>Exportar Excel
                </a>
                <a href="{{ route('eventos.campaigns.expenses.index', $campaign) }}" class="btn btn-outline-light btn-sm">
                    <i class="bx bx-receipt me-1"></i>Gastos
                </a>
            </div>
        </div>
    </div>

    {{-- Filtro por período --}}
    <section class="card pc-filter mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label mb-1">Pagamentos de</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label mb-1">até</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-8 d-flex align-items-center gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-filter me-1"></i>Aplicar período</button>
                    @if($hasPeriod)
                        <a href="{{ route('eventos.campaigns.report', $campaign) }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                        <span class="pc-pill pc-pill--method pc-period-badge">
                            <i class="bx bx-calendar-check me-1"></i>
                            {{ $from?->format('d/m/Y') ?? 'início' }} → {{ $to?->format('d/m/Y') ?? 'hoje' }}
                        </span>
                    @else
                        <span class="text-muted small"><i class="bx bx-info-circle me-1"></i>Sem período: o extrato e os gastos mostram todo o histórico da campanha.</span>
                    @endif
                </div>
            </form>
        </div>
    </section>

    {{-- Indicadores --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4 col-xl-2">
            <div class="pc-kpi" style="--pc-color: {{ $accent }}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="pc-kpi-icon"><i class="bx bx-target-lock"></i></span>
                    <span class="pc-kpi-label">Meta</span>
                </div>
                <div class="pc-kpi-value">{{ $metrics['goal'] > 0 ? $money($metrics['goal']) : 'Sem meta' }}</div>
                <div class="progress mt-2">
                    <div class="progress-bar" style="width: {{ $metrics['progress'] }}%; background: {{ $accent }}"></div>
                </div>
                <div class="pc-kpi-sub mt-1">{{ number_format($metrics['progress'], 1, ',', '.') }}% atingido</div>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <div class="pc-kpi" style="--pc-color: #1FA855">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="pc-kpi-icon"><i class="bx bx-trending-up"></i></span>
                    <span class="pc-kpi-label">Arrecadado</span>
                </div>
                <div class="pc-kpi-value pc-text-success">{{ $money($metrics['raised']) }}</div>
                <div class="pc-kpi-sub mt-1">{{ $payments->count() }} recebimento(s) no período</div>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <div class="pc-kpi" style="--pc-color: #F5A623">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="pc-kpi-icon"><i class="bx bx-time-five"></i></span>
                    <span class="pc-kpi-label">A receber</span>
                </div>
                <div class="pc-kpi-value pc-text-warning">{{ $money($metrics['pending']) }}</div>
                <div class="pc-kpi-sub mt-1">parcelas pendentes</div>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <div class="pc-kpi" style="--pc-color: #DC3545">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="pc-kpi-icon"><i class="bx bx-error-circle"></i></span>
                    <span class="pc-kpi-label">Em atraso</span>
                </div>
                <div class="pc-kpi-value pc-text-danger">{{ $money($metrics['overdue']) }}</div>
                <div class="pc-kpi-sub mt-1">{{ $statusCounts['em_atraso'] }} patrocinador(es)</div>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <div class="pc-kpi" style="--pc-color: #E4572E">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="pc-kpi-icon"><i class="bx bx-trending-down"></i></span>
                    <span class="pc-kpi-label">Gastos</span>
                </div>
                <div class="pc-kpi-value pc-text-danger">{{ $money($metrics['expenses']) }}</div>
                <div class="pc-kpi-sub mt-1">{{ $expenses->count() }} lançamento(s) no período</div>
            </div>
        </div>
        <div class="col-6 col-lg-4 col-xl-2">
            <div class="pc-kpi" style="--pc-color: {{ $balancePositive ? '#0088CC' : '#DC3545' }}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="pc-kpi-icon"><i class="bx bx-wallet"></i></span>
                    <span class="pc-kpi-label">Saldo</span>
                </div>
                <div class="pc-kpi-value {{ $balancePositive ? 'pc-text-primary' : 'pc-text-danger' }}">{{ $money($metrics['balance']) }}</div>
                <div class="pc-kpi-sub mt-1">arrecadado − gastos</div>
            </div>
        </div>
    </div>

    {{-- Fluxo + situação dos patrocinadores --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="pc-flow h-100 d-flex align-items-center justify-content-around flex-wrap px-2">
                <div class="pc-flow-item">
                    <div class="pc-flow-label">Arrecadado</div>
                    <div class="pc-flow-value pc-text-success">{{ $money($metrics['raised']) }}</div>
                </div>
                <div class="pc-flow-op">−</div>
                <div class="pc-flow-item">
                    <div class="pc-flow-label">Gastos do evento</div>
                    <div class="pc-flow-value pc-text-danger">{{ $money($metrics['expenses']) }}</div>
                </div>
                <div class="pc-flow-op">=</div>
                <div class="pc-flow-item">
                    <div class="pc-flow-label">Saldo {{ $balancePositive ? 'disponível' : 'negativo' }}</div>
                    <div class="pc-flow-value {{ $balancePositive ? 'pc-text-primary' : 'pc-text-danger' }}">{{ $money($metrics['balance']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <section class="card pc-card h-100 mb-0">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="card-title-sm"><i class="bx bx-user"></i>{{ $metrics['sponsors'] }} patrocinador(es)</span>
                    </div>
                    @php
                        $segments = [
                            ['key' => 'quitado', 'label' => 'Quitados', 'color' => '#1FA855'],
                            ['key' => 'em_dia', 'label' => 'Em dia', 'color' => '#0088CC'],
                            ['key' => 'em_atraso', 'label' => 'Em atraso', 'color' => '#DC3545'],
                        ];
                    @endphp
                    <div class="progress mb-2" style="height: 10px; background: #EEF0F2; border-radius: 999px; overflow: hidden;">
                        @foreach($segments as $seg)
                            @if($statusCounts[$seg['key']] > 0)
                                <div class="progress-bar" style="width: {{ $statusCounts[$seg['key']] / $sponsorCount * 100 }}%; background: {{ $seg['color'] }}"
                                     title="{{ $seg['label'] }}: {{ $statusCounts[$seg['key']] }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="pc-legend">
                        @foreach($segments as $seg)
                            <span class="pc-pill {{ $pillFor($seg['key']) }}">{{ $seg['label'] }}<span>{{ $statusCounts[$seg['key']] }}</span></span>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Forma de pagamento + Gastos por categoria --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <section class="card pc-card h-100 mb-0">
                <header class="card-header">
                    <h5 class="card-title-sm"><i class="bx bx-credit-card"></i>Arrecadação por forma de pagamento</h5>
                    <p class="card-subtitle">Parcelas pagas {{ $hasPeriod ? 'no período' : 'em toda a campanha' }}.</p>
                </header>
                @if($byMethod->isEmpty())
                    <div class="pc-empty"><i class="bx bx-wallet"></i>Nenhum pagamento no período.</div>
                @else
                    <ul class="pc-bars">
                        @foreach($byMethod as $method => $total)
                            @php($pct = $total / $raisedTotal * 100)
                            <li>
                                <div class="pc-bar-head">
                                    <span>{{ CampaignInstallment::PAYMENT_METHODS[$method] ?? ucfirst($method) }}<span class="pc-bar-pct">{{ number_format($pct, 0) }}%</span></span>
                                    <strong>{{ $money($total) }}</strong>
                                </div>
                                <div class="progress"><div class="progress-bar" style="width: {{ $pct }}%; background: {{ $methodColors[$method] ?? '#8A97A3' }}"></div></div>
                            </li>
                        @endforeach
                        <li class="d-flex justify-content-between fw-bold" style="background: #F7F9FB">
                            <span>Total recebido</span><span class="pc-text-success">{{ $money($byMethod->sum()) }}</span>
                        </li>
                    </ul>
                @endif
            </section>
        </div>
        <div class="col-lg-6">
            <section class="card pc-card h-100 mb-0">
                <header class="card-header d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h5 class="card-title-sm"><i class="bx bx-pie-chart-alt-2"></i>Gastos por categoria</h5>
                        <p class="card-subtitle">Despesas com comprovante {{ $hasPeriod ? 'no período' : 'em toda a campanha' }}.</p>
                    </div>
                    <a href="{{ route('eventos.campaigns.expenses.index', $campaign) }}" class="btn btn-outline-secondary btn-sm text-nowrap"><i class="bx bx-cog me-1"></i>Gerenciar</a>
                </header>
                @if($expensesByCategory->isEmpty())
                    <div class="pc-empty"><i class="bx bx-receipt"></i>Nenhum gasto registrado no período.</div>
                @else
                    <ul class="pc-bars">
                        @foreach($expensesByCategory as $cat => $total)
                            @php($pct = $total / $expensesTotal * 100)
                            <li>
                                <div class="pc-bar-head">
                                    <span>{{ CampaignExpense::CATEGORIES[$cat] ?? ucfirst($cat) }}<span class="pc-bar-pct">{{ number_format($pct, 0) }}%</span></span>
                                    <strong class="pc-text-danger">{{ $money($total) }}</strong>
                                </div>
                                <div class="progress"><div class="progress-bar" style="width: {{ $pct }}%; background: #E4572E"></div></div>
                            </li>
                        @endforeach
                        <li class="d-flex justify-content-between fw-bold" style="background: #F7F9FB">
                            <span>Total gasto</span><span class="pc-text-danger">{{ $money($expensesByCategory->sum()) }}</span>
                        </li>
                    </ul>
                @endif
            </section>
        </div>
    </div>

    {{-- Patrocinadores --}}
    <section class="card pc-card mb-3">
        <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title-sm"><i class="bx bx-user"></i>Patrocinadores</h5>
                <p class="card-subtitle">Situação de cada patrocinador considerando todas as parcelas da campanha.</p>
            </div>
            <div class="pc-legend">
                <span class="pc-pill pc-pill--quitado">Quitado</span>
                <span class="pc-pill pc-pill--em_dia">Em dia</span>
                <span class="pc-pill pc-pill--em_atraso">Em atraso</span>
            </div>
        </header>
        <div class="table-responsive">
            <table class="table pc-table align-middle">
                <thead>
                    <tr>
                        <th class="ps-3">Patrocinador</th>
                        <th>Parcelas</th>
                        <th class="pc-num">Comprometido</th>
                        <th class="pc-num">Pago</th>
                        <th class="pc-num">Pendente</th>
                        <th class="text-center pe-3">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sponsorRows as $row)
                        <tr class="is-{{ $row['situacao_key'] }}">
                            <td class="ps-3">
                                <div class="pc-name">{{ $row['name'] }}</div>
                                @if($row['phone'] !== '—')
                                    <div class="pc-sub"><i class="bx bxl-whatsapp me-1"></i>{{ $row['phone'] }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress pc-mini-progress">
                                        <div class="progress-bar" style="width: {{ $row['progress'] }}%; background: {{ $row['situacao_key'] === 'em_atraso' ? '#DC3545' : ($row['situacao_key'] === 'quitado' ? '#1FA855' : '#0088CC') }}"></div>
                                    </div>
                                    <span class="pc-sub">{{ $row['paid_count'] }}/{{ $row['total_count'] }}</span>
                                </div>
                            </td>
                            <td class="pc-num">{{ $money($row['committed']) }}</td>
                            <td class="pc-num pc-text-success fw-semibold">{{ $money($row['paid']) }}</td>
                            <td class="pc-num {{ $row['pending'] > 0 ? ($row['situacao_key'] === 'em_atraso' ? 'pc-text-danger' : 'pc-text-warning') : 'text-muted' }}">{{ $money($row['pending']) }}</td>
                            <td class="text-center pe-3"><span class="pc-pill {{ $pillFor($row['situacao_key']) }}">{{ $row['situacao'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="pc-empty"><i class="bx bx-user-x"></i>Nenhum patrocinador.</div></td></tr>
                    @endforelse
                </tbody>
                @if($sponsorRows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td class="ps-3" colspan="2">Totais ({{ $sponsorRows->count() }})</td>
                            <td class="pc-num">{{ $money($sponsorRows->sum('committed')) }}</td>
                            <td class="pc-num pc-text-success">{{ $money($sponsorRows->sum('paid')) }}</td>
                            <td class="pc-num pc-text-warning">{{ $money($sponsorRows->sum('pending')) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

    {{-- Extrato de recebimentos --}}
    <section class="card pc-card mb-3">
        <header class="card-header">
            <h5 class="card-title-sm"><i class="bx bx-list-check"></i>Extrato de recebimentos</h5>
            <p class="card-subtitle">Todas as parcelas pagas em ordem cronológica — documento para prestação de contas à liderança.</p>
        </header>
        <div class="table-responsive">
            <table class="table pc-table align-middle">
                <thead>
                    <tr>
                        <th class="ps-3">Recibo</th>
                        <th>Data</th>
                        <th>Patrocinador</th>
                        <th class="text-center">Parcela</th>
                        <th>Forma</th>
                        <th class="pc-num pe-3">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $installment)
                        <tr>
                            <td class="ps-3"><span class="pc-sub" style="font-size:.82rem; color:#2E353E">{{ $installment->receipt_number ?? '—' }}</span></td>
                            <td class="text-nowrap">{{ $installment->paid_at?->format('d/m/Y') }} <span class="pc-sub">{{ $installment->paid_at?->format('H:i') }}</span></td>
                            <td class="pc-name">{{ $installment->sponsor->name }}</td>
                            <td class="text-center"><span class="pc-pill pc-pill--neutral">{{ $installment->installment_number }}ª</span></td>
                            <td><span class="pc-pill pc-pill--method">{{ $installment->paymentMethodLabel() }}</span></td>
                            <td class="pc-num pe-3 pc-text-success fw-semibold">{{ $money($installment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="pc-empty"><i class="bx bx-wallet"></i>Nenhum recebimento no período.</div></td></tr>
                    @endforelse
                </tbody>
                @if($payments->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="5" class="ps-3">Total recebido {{ $hasPeriod ? 'no período' : '' }} ({{ $payments->count() }} parcela(s))</td>
                            <td class="pc-num pe-3 pc-text-success">{{ $money($payments->sum('amount')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

    {{-- Gastos do evento --}}
    <section class="card pc-card mb-3">
        <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title-sm"><i class="bx bx-trending-down"></i>Gastos do evento</h5>
                <p class="card-subtitle">Despesas registradas com comprovante, em ordem cronológica.</p>
            </div>
            <a href="{{ route('eventos.campaigns.expenses.index', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-plus me-1"></i>Registrar gasto
            </a>
        </header>
        <div class="table-responsive">
            <table class="table pc-table align-middle">
                <thead>
                    <tr>
                        <th class="ps-3">Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Fornecedor</th>
                        <th>Forma</th>
                        <th class="text-center">Comprovantes</th>
                        <th class="pc-num pe-3">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td class="ps-3 text-nowrap">{{ $expense->expense_date?->format('d/m/Y') }}</td>
                            <td>
                                <div class="pc-name">{{ $expense->description }}</div>
                                @if($expense->notes)
                                    <div class="pc-sub">{{ \Illuminate\Support\Str::limit($expense->notes, 70) }}</div>
                                @endif
                            </td>
                            <td><span class="pc-pill pc-pill--cat">{{ $expense->categoryLabel() }}</span></td>
                            <td>{{ $expense->supplier ?: '—' }}</td>
                            <td>{{ $expense->payment_method ? $expense->paymentMethodLabel() : '—' }}</td>
                            <td class="text-center">
                                @foreach($expense->attachments as $attachment)
                                    <a href="{{ route('eventos.campaigns.expenses.attachment', $attachment) }}" target="_blank" rel="noopener"
                                       class="btn btn-sm btn-outline-secondary py-0 px-1" title="{{ $attachment->file_name }}">
                                        <i class="bx {{ $attachment->isImage() ? 'bx-image' : 'bxs-file-pdf' }}"></i>
                                    </a>
                                @endforeach
                            </td>
                            <td class="pc-num pe-3 pc-text-danger fw-semibold">{{ $money($expense->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="pc-empty"><i class="bx bx-receipt"></i>Nenhum gasto no período.</div></td></tr>
                    @endforelse
                </tbody>
                @if($expenses->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="6" class="ps-3">Total de gastos {{ $hasPeriod ? 'no período' : '' }} ({{ $expenses->count() }} lançamento(s))</td>
                            <td class="pc-num pe-3 pc-text-danger">{{ $money($expenses->sum('amount')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

    <p class="text-muted small mb-0"><i class="bx bx-info-circle me-1"></i>Relatório gerado exclusivamente a partir dos dados da campanha — não inclui valores do módulo Financeiro.</p>
</div>
@endsection
