@extends('layouts.porto')

@section('title', 'Relatório: Despesas - Extrato diário')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Despesas - Extrato diário</span></li>
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
                <form method="GET" action="{{ route('financial.reports.expenses.daily-extract') }}" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label class="form-label small">Período:</label>
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" name="start_date" value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}">
                                <span class="input-group-text">-</span>
                                <input type="date" class="form-control" name="end_date" value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label small">Categoria:</label>
                            <select class="form-select form-select-sm" name="category_id">
                                <option value="">Todas</option>
                                @foreach($categoriesDespesas as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">Conta:</label>
                            <select class="form-select form-select-sm" name="account_id">
                                <option value="">Todas</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">Centro de custo:</label>
                            <select class="form-select form-select-sm" name="cost_center_id">
                                <option value="">Todos</option>
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}" {{ request('cost_center_id') == $costCenter->id ? 'selected' : '' }}>
                                        {{ $costCenter->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bx bx-filter me-1"></i>Filtrar
                            </button>
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
                            <h6 class="mb-0">Relatório: Despesas - Extrato diário</h6>
                            <p class="text-muted mb-0">Período: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} à {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Imprimir
                    </button>
                </div>

                <!-- Gráfico de Área -->
                <div class="mb-4">
                    <canvas id="expensesChart" height="100"></canvas>
                </div>

                <!-- Controles da Tabela -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div></div>
                    <div class="d-flex gap-2 align-items-center">
                        <form method="GET" action="{{ route('financial.reports.expenses.daily-extract') }}" id="tableForm" style="display: contents;">
                            @foreach(request()->except(['search', 'per_page', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <select class="form-select form-select-sm" style="width: auto;" name="per_page" onchange="this.form.submit()">
                                <option value="50" {{ request('per_page', 100) == 50 ? 'selected' : '' }}>50 resultados por página</option>
                                <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100 resultados por página</option>
                                <option value="200" {{ request('per_page', 100) == 200 ? 'selected' : '' }}>200 resultados por página</option>
                            </select>
                            <input type="text" class="form-control form-control-sm" placeholder="Pesquisar" name="search" value="{{ request('search') }}" style="width: 200px;">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-search"></i>
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-save"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-download"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-upload"></i>
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Colunas <i class="bx bx-chevron-down"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Todas</a></li>
                                <li><a class="dropdown-item" href="#">Personalizar</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Despesas -->
                @if($despesas->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <a href="{{ route('financial.reports.expenses.daily-extract', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'transaction_date', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                            Data
                                            @if(request('sort_by') == 'transaction_date')
                                                <i class="bx bx-chevron-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bx bx-sort-alt-2"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('financial.reports.expenses.daily-extract', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'description', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                            Descrição
                                            @if(request('sort_by') == 'description')
                                                <i class="bx bx-chevron-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bx bx-sort-alt-2"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('financial.reports.expenses.daily-extract', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'amount', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                            Total
                                            @if(request('sort_by') == 'amount')
                                                <i class="bx bx-chevron-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bx bx-sort-alt-2"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Categoria</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($despesas as $despesa)
                                    <tr>
                                        <td>{{ $despesa->transaction_date->format('d/m/Y') }}</td>
                                        <td>{{ $despesa->description }}</td>
                                        <td>
                                            <span class="text-danger">
                                                R$ {{ number_format($despesa->amount, 2, ',', '.') }}
                                            </span>
                                            @if($despesa->is_paid)
                                                <i class="bx bx-check-circle text-success ms-1"></i>
                                            @endif
                                        </td>
                                        <td>{{ $despesa->category ? $despesa->category->name : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-3">
                        {{ $despesas->appends(request()->except('page'))->links() }}
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">Nenhuma despesa encontrada para o período selecionado.</p>
                    </div>
                @endif

                <hr class="my-4">

                <!-- Resumo -->
                <div class="card" style="background-color: #f8f9fa; border: 2px solid #dc3545; border-top: 4px solid #dc3545;">
                    <div class="card-header bg-danger text-white">
                        <h6 class="card-title mb-0">Resumo</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td><strong>Saldo anterior em {{ \Carbon\Carbon::parse($startDate)->subDay()->format('d/m/Y') }}:</strong></td>
                                        <td class="text-end"><strong class="text-primary">R$ {{ number_format($previousBalance, 2, ',', '.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Total de receitas no período:</td>
                                        <td class="text-end text-primary">R$ {{ number_format($totalReceitas, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total de despesas no período:</td>
                                        <td class="text-end text-danger">-R$ {{ number_format($totalDespesas, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top pt-2">
                                            <strong class="text-primary">= R$ {{ number_format($previousBalance + $totalReceitas - $totalDespesas, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>A receber:</td>
                                        <td class="text-end">R$ {{ number_format($aReceber, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>A pagar:</td>
                                        <td class="text-end text-danger">-R$ {{ number_format($aPagar, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top pt-2">
                                            <strong>= R$ {{ number_format($aReceber - $aPagar, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td>Transf. enviada:</td>
                                        <td class="text-end">R$ {{ number_format($transferenciasEnviadas, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Transf. recebida:</td>
                                        <td class="text-end">R$ {{ number_format($transferenciasRecebidas, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top pt-2">
                                            <strong>= R$ {{ number_format($transferenciasRecebidas - $transferenciasEnviadas, 2, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-top pt-3">
                                            <h5 class="mb-0">
                                                <strong>Saldo final em {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}:</strong>
                                                <span class="float-end text-primary">R$ {{ number_format($saldoFinal, 2, ',', '.') }}</span>
                                            </h5>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-danger">
                                <i class="bx bx-info-circle me-1"></i>
                                O resultado apresentado é baseado nos filtros selecionados no topo da página.
                            </small>
                        </div>
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
        // Gráfico de Despesas Diárias
        const expensesCtx = document.getElementById('expensesChart');
        if (expensesCtx) {
            const chartData = @json($chartData ?? []);
            
            const labels = chartData.map(item => item.day);
            const receitas = chartData.map(item => parseFloat(item.receitas || 0));
            const despesas = chartData.map(item => parseFloat(item.despesas || 0));
            const aReceber = chartData.map(item => parseFloat(item.a_receber || 0));
            const aPagar = chartData.map(item => parseFloat(item.a_pagar || 0));
            
            const allValues = [...receitas, ...despesas, ...aReceber, ...aPagar];
            const maxValue = allValues.length > 0 ? Math.max(...allValues, 0) : 0;
            const yMax = maxValue > 0 ? Math.ceil(maxValue / 200) * 200 : 1000;
            
            new Chart(expensesCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Receitas',
                            data: receitas,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Despesas',
                            data: despesas,
                            borderColor: '#ff9800',
                            backgroundColor: 'rgba(255, 152, 0, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'A receber',
                            data: aReceber,
                            borderColor: '#28a745',
                            borderDash: [5, 5],
                            backgroundColor: 'transparent',
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'A pagar',
                            data: aPagar,
                            borderColor: '#dc3545',
                            borderDash: [5, 5],
                            backgroundColor: 'transparent',
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
                            max: yMax,
                            ticks: {
                                stepSize: Math.ceil(yMax / 5)
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection

