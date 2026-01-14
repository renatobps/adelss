@extends('layouts.porto')

@section('title', 'Relatórios Financeiros')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Relatórios</span></li>
@endsection

@section('content')
<!-- Header -->
<div class="alert alert-info mb-4" style="background-color: #e3f2fd; color: #1976d2; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Visualize seus relatórios financeiros.
</div>

<div class="row">
    <!-- Menu Lateral -->
    <div class="col-lg-3 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h5 class="mb-3" style="border-bottom: 2px solid #007bff; padding-bottom: 10px;">Relatórios</h5>
                
                <!-- Fluxo de Caixa -->
                <div class="mb-4">
                    <h6 class="text-primary mb-2">Fluxo de caixa</h6>
                    <div class="d-grid gap-1">
                        <a href="{{ route('financial.reports.cash-flow.extract') }}" 
                           class="btn btn-sm text-start {{ request()->routeIs('financial.reports.cash-flow.extract') ? 'btn-primary' : 'btn-light' }}">
                            Extrato
                        </a>
                        <a href="{{ route('financial.reports.cash-flow.revenues-expenses') }}" 
                           class="btn btn-sm text-start {{ request()->routeIs('financial.reports.cash-flow.revenues-expenses') ? 'btn-primary' : 'btn-light' }}">
                            Receitas / Despesas
                        </a>
                    </div>
                </div>

                <!-- Receitas -->
                <div class="mb-4">
                    <h6 class="text-success mb-2">Receitas</h6>
                    <div class="d-grid gap-1">
                        <a href="{{ route('financial.reports.revenues.daily-extract') }}" 
                           class="btn btn-sm text-start {{ request()->routeIs('financial.reports.revenues.daily-extract') ? 'btn-primary' : 'btn-light' }}">
                            Extrato diário
                        </a>
                        <a href="{{ route('financial.reports.revenues-expenses.by-category') }}" 
                           class="btn btn-sm text-start {{ request()->routeIs('financial.reports.revenues-expenses.by-category') ? 'btn-primary' : 'btn-light' }}">
                            Por categoria
                        </a>
                        <a href="{{ route('financial.reports.revenues.annual-summary') }}" 
                           class="btn btn-sm text-start {{ request()->routeIs('financial.reports.revenues.annual-summary') ? 'btn-primary' : 'btn-light' }}">
                            Resumo anual por categoria
                        </a>
                    </div>
                </div>

                <!-- Despesas -->
                <div class="mb-4">
                    <h6 class="text-danger mb-2">Despesas</h6>
                    <div class="d-grid gap-1">
                        <a href="{{ route('financial.reports.expenses.daily-extract') }}" 
                           class="btn btn-sm text-start {{ request()->routeIs('financial.reports.expenses.daily-extract') ? 'btn-primary' : 'btn-light' }}">
                            Extrato diário
                        </a>
                        <a href="{{ route('financial.reports.revenues-expenses.by-category') }}" 
                           class="btn btn-sm text-start {{ request()->routeIs('financial.reports.revenues-expenses.by-category') ? 'btn-primary' : 'btn-light' }}">
                            Por categoria
                        </a>
                        <a href="{{ route('financial.reports.expenses.annual-summary') }}" 
                           class="btn btn-sm text-start {{ request()->routeIs('financial.reports.expenses.annual-summary') ? 'btn-primary' : 'btn-light' }}">
                            Resumo anual por categoria
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="col-lg-9">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-height: 500px;">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center text-muted py-5">
                <div class="mb-4">
                    <i class="bx bx-pie-chart-alt-2" style="font-size: 4rem; opacity: 0.3;"></i>
                    <i class="bx bx-line-chart" style="font-size: 4rem; opacity: 0.3;"></i>
                    <i class="bx bx-bar-chart-alt-2" style="font-size: 4rem; opacity: 0.3;"></i>
                    <i class="bx bx-pie-chart" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
                <p class="mb-0" style="font-size: 1.1rem;">Selecione um dos relatórios no menu ao lado.</p>
            </div>
        </div>
    </div>
</div>
@endsection
