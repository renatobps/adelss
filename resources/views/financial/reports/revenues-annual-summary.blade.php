@extends('layouts.porto')

@section('title', 'Relatório: Receitas - Resumo anual por categoria')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Receitas - Resumo anual por categoria</span></li>
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
        <!-- Filtros -->
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <form method="GET" action="{{ route('financial.reports.revenues.annual-summary') }}" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Ano:</label>
                            <select class="form-select form-select-sm" name="year" onchange="this.form.submit()">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Relatório -->
        <div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <!-- Cabeçalho do Relatório -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <img src="{{ asset('img/LOG SS preta.png') }}" alt="Logo" style="height: 50px;" onerror="this.style.display='none'">
                        </div>
                        <div>
                            <h5 class="mb-0">ADEL SÃO SEBASTIÃO</h5>
                            <h6 class="mb-0">Relatório: Receitas - Resumo anual por categoria</h6>
                            <p class="text-muted mb-0">Ano: {{ $year }}</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Imprimir
                    </button>
                </div>

                <!-- Gráfico Mensal -->
                <div class="mb-4">
                    <canvas id="monthlyChart" height="80"></canvas>
                </div>

                <!-- Resumo por Categoria -->
                <div class="mb-5">
                    <h5 class="mb-3">Resumo por Categoria</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byCategory as $item)
                                    <tr>
                                        <td>{{ $item['category_name'] }}</td>
                                        <td class="text-end text-primary">
                                            <strong>R$ {{ number_format($item['total'], 2, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-end">{{ $item['count'] }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-secondary">
                                    <td><strong>Total Geral</strong></td>
                                    <td class="text-end"><strong class="text-primary">R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong></td>
                                    <td class="text-end"><strong>{{ collect($byCategory)->sum('count') }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Resumo por Mês -->
                <div class="mb-5">
                    <h5 class="mb-3">Resumo Mensal</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Mês</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Quantidade</th>
                                    @foreach($categoriesReceitas as $category)
                                        <th class="text-end">{{ $category->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byMonth as $monthIndex => $monthData)
                                    <tr>
                                        <td><strong>{{ ucfirst($monthData['month_name']) }}</strong></td>
                                        <td class="text-end text-primary">
                                            <strong>R$ {{ number_format($monthData['total'], 2, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-end">{{ $monthData['count'] }}</td>
                                        @foreach($categoriesReceitas as $category)
                                            <td class="text-end">
                                                @php
                                                    $categoryItem = collect($monthData['by_category'])->first(function ($item) use ($category) {
                                                        return isset($item['category_name']) && $item['category_name'] == $category->name;
                                                    });
                                                    $categoryTotal = $categoryItem['total'] ?? 0;
                                                @endphp
                                                @if($categoryTotal > 0)
                                                    R$ {{ number_format($categoryTotal, 2, ',', '.') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="table-secondary">
                                    <td><strong>Total Geral</strong></td>
                                    <td class="text-end"><strong class="text-primary">R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong></td>
                                    <td class="text-end"><strong>{{ collect($byMonth)->sum('count') }}</strong></td>
                                    @foreach($categoriesReceitas as $category)
                                        <td class="text-end">
                                            @php
                                                $categoryYearTotal = 0;
                                                foreach($byMonth as $monthData) {
                                                    $categoryItem = collect($monthData['by_category'])->first(function ($item) use ($category) {
                                                        return isset($item['category_name']) && $item['category_name'] == $category->name;
                                                    });
                                                    $categoryYearTotal += $categoryItem['total'] ?? 0;
                                                }
                                            @endphp
                                            @if($categoryYearTotal > 0)
                                                <strong>R$ {{ number_format($categoryYearTotal, 2, ',', '.') }}</strong>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gráfico Mensal de Receitas
        const monthlyCtx = document.getElementById('monthlyChart');
        if (monthlyCtx) {
            const chartData = @json($chartData ?? []);
            
            const labels = chartData.map(item => item.month_name);
            const totals = chartData.map(item => parseFloat(item.total || 0));
            
            const maxValue = totals.length > 0 ? Math.max(...totals, 0) : 0;
            const yMax = maxValue > 0 ? Math.ceil(maxValue / 200) * 200 : 1000;
            
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Receitas por Mês',
                            data: totals,
                            backgroundColor: 'rgba(0, 123, 255, 0.8)',
                            borderColor: '#007bff',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: yMax,
                            ticks: {
                                stepSize: Math.ceil(yMax / 5)
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection


