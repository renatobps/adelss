@extends('layouts.porto')

@section('title', 'Resumo Financeiro')

@section('page-title', 'Resumo Financeiro')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Resumo</span></li>
@endsection

@section('content')
<!-- Cards de Resumo -->
<div class="row mb-4">
    <!-- Recebido -->
    <div class="col-md-4 mb-3">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    @if($periodFilter == 'today') Recebido hoje
                    @elseif($periodFilter == '7days') Recebido (7 dias)
                    @elseif($periodFilter == '1month') Recebido (mês atual)
                    @else Recebido (3 meses)
                    @endif
                </h6>
                <h3 class="mb-1" style="color: #28a745;">R$ {{ number_format($recebidoPeriodo, 2, ',', '.') }}</h3>
                <p class="text-muted small mb-2">Total do mês: <strong>R$ {{ number_format($recebidoMes, 2, ',', '.') }}</strong></p>
                <form method="GET" action="{{ route('financial.summary') }}" id="periodForm">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="submit" name="period" value="today" class="btn btn-outline-secondary {{ $periodFilter == 'today' ? 'active' : '' }}">Hoje</button>
                        <button type="submit" name="period" value="7days" class="btn btn-outline-secondary {{ $periodFilter == '7days' ? 'active' : '' }}">+7 Dias</button>
                        <button type="submit" name="period" value="1month" class="btn btn-outline-secondary {{ $periodFilter == '1month' ? 'active' : '' }}">+1 Mês</button>
                        <button type="submit" name="period" value="3months" class="btn btn-outline-secondary {{ $periodFilter == '3months' ? 'active' : '' }}">+3 Meses</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pago -->
    <div class="col-md-4 mb-3">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    @if($periodFilter == 'today') Pago hoje
                    @elseif($periodFilter == '7days') Pago (7 dias)
                    @elseif($periodFilter == '1month') Pago (mês atual)
                    @else Pago (3 meses)
                    @endif
                </h6>
                <h3 class="mb-1" style="color: #dc3545;">R$ {{ number_format($pagoPeriodo, 2, ',', '.') }}</h3>
                <p class="text-muted small mb-2">Total do mês: <strong>R$ {{ number_format($pagoMes, 2, ',', '.') }}</strong></p>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="submit" form="periodForm" name="period" value="today" class="btn btn-outline-secondary {{ $periodFilter == 'today' ? 'active' : '' }}">Hoje</button>
                    <button type="submit" form="periodForm" name="period" value="7days" class="btn btn-outline-secondary {{ $periodFilter == '7days' ? 'active' : '' }}">+7 Dias</button>
                    <button type="submit" form="periodForm" name="period" value="1month" class="btn btn-outline-secondary {{ $periodFilter == '1month' ? 'active' : '' }}">+1 Mês</button>
                    <button type="submit" form="periodForm" name="period" value="3months" class="btn btn-outline-secondary {{ $periodFilter == '3months' ? 'active' : '' }}">+3 Meses</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pesquisa e Navegação -->
    <div class="col-md-4 mb-3">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Pesquisar transações" id="searchTransactions">
                    <select class="form-select" style="max-width: 120px;">
                        <option>Neste ano</option>
                        <option>Último mês</option>
                        <option>Últimos 3 meses</option>
                        <option>Últimos 6 meses</option>
                    </select>
                    <button class="btn btn-primary" type="button">
                        <i class="bx bx-search"></i> Pesquisar
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('financial.transactions.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-refresh"></i> Transações
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-history"></i> Histórico
                    </a>
                    <a href="{{ route('financial.categories.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-purchase-tag"></i> Categorias
                    </a>
                    <a href="{{ route('financial.accounts.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-file"></i> Contas
                    </a>
                    <a href="{{ route('financial.contacts.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-user"></i> Contatos
                    </a>
                    <a href="{{ route('financial.cost-centers.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-folder"></i> Centros
                    </a>
                    <a href="{{ route('financial.reports.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-line-chart"></i> Relatórios
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Segunda Linha de Cards -->
<div class="row mb-4">
    <!-- A receber -->
    <div class="col-md-4 mb-3">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    @if($periodFilter == 'today') A receber hoje
                    @elseif($periodFilter == '7days') A receber (7 dias)
                    @elseif($periodFilter == '1month') A receber (mês atual)
                    @else A receber (3 meses)
                    @endif
                </h6>
                <h3 class="mb-1" style="color: #17a2b8;">R$ {{ number_format($aReceberPeriodo, 2, ',', '.') }}</h3>
                <p class="text-muted small mb-2">Total do mês: <strong>R$ {{ number_format($aReceberMes, 2, ',', '.') }}</strong></p>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="submit" form="periodForm" name="period" value="today" class="btn btn-outline-secondary {{ $periodFilter == 'today' ? 'active' : '' }}">Hoje</button>
                    <button type="submit" form="periodForm" name="period" value="7days" class="btn btn-outline-secondary {{ $periodFilter == '7days' ? 'active' : '' }}">+7 Dias</button>
                    <button type="submit" form="periodForm" name="period" value="1month" class="btn btn-outline-secondary {{ $periodFilter == '1month' ? 'active' : '' }}">+1 Mês</button>
                    <button type="submit" form="periodForm" name="period" value="3months" class="btn btn-outline-secondary {{ $periodFilter == '3months' ? 'active' : '' }}">+3 Meses</button>
                </div>
            </div>
        </div>
    </div>

    <!-- A pagar -->
    <div class="col-md-4 mb-3">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    @if($periodFilter == 'today') A pagar hoje
                    @elseif($periodFilter == '7days') A pagar (7 dias)
                    @elseif($periodFilter == '1month') A pagar (mês atual)
                    @else A pagar (3 meses)
                    @endif
                </h6>
                <h3 class="mb-1" style="color: #ffc107;">R$ {{ number_format($aPagarPeriodo, 2, ',', '.') }}</h3>
                <p class="text-muted small mb-2">Total do mês: <strong>R$ {{ number_format($aPagarMes, 2, ',', '.') }}</strong></p>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="submit" form="periodForm" name="period" value="today" class="btn btn-outline-secondary {{ $periodFilter == 'today' ? 'active' : '' }}">Hoje</button>
                    <button type="submit" form="periodForm" name="period" value="7days" class="btn btn-outline-secondary {{ $periodFilter == '7days' ? 'active' : '' }}">+7 Dias</button>
                    <button type="submit" form="periodForm" name="period" value="1month" class="btn btn-outline-secondary {{ $periodFilter == '1month' ? 'active' : '' }}">+1 Mês</button>
                    <button type="submit" form="periodForm" name="period" value="3months" class="btn btn-outline-secondary {{ $periodFilter == '3months' ? 'active' : '' }}">+3 Meses</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Espaço vazio ou outro card -->
    <div class="col-md-4 mb-3"></div>
</div>

<!-- Terceira Linha de Cards -->
<div class="row mb-4">
    <!-- Recebimentos em atraso -->
    <div class="col-md-6 mb-3">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Recebimentos em atraso</h6>
                <h3 class="mb-1" style="color: #dc3545;" id="recebimentosAtrasoValue">Mês atual: R$ {{ number_format($recebimentosAtrasoMes, 2, ',', '.') }}</h3>
                <div class="btn-group btn-group-sm mt-2" role="group">
                    <button type="button" class="btn btn-outline-secondary active" onclick="updateAtrasos('recebimentos', 'mes')">Mês Atual</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="updateAtrasos('recebimentos', 'todo')">Todo o Período</button>
                </div>
                <div id="recebimentosAtrasoTodo" style="display: none;">
                    <h3 class="mb-1 mt-2" style="color: #dc3545;">Todo o período: R$ {{ number_format($recebimentosAtrasoTodoPeriodo, 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagamentos em atraso -->
    <div class="col-md-6 mb-3">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Pagamentos em atraso</h6>
                <h3 class="mb-1" style="color: #dc3545;" id="pagamentosAtrasoValue">Mês atual: R$ {{ number_format($pagamentosAtrasoMes, 2, ',', '.') }}</h3>
                <div class="btn-group btn-group-sm mt-2" role="group">
                    <button type="button" class="btn btn-outline-secondary active" onclick="updateAtrasos('pagamentos', 'mes')">Mês Atual</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="updateAtrasos('pagamentos', 'todo')">Todo o Período</button>
                </div>
                <div id="pagamentosAtrasoTodo" style="display: none;">
                    <h3 class="mb-1 mt-2" style="color: #dc3545;">Todo o período: R$ {{ number_format($pagamentosAtrasoTodoPeriodo, 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Saldo Atual e Gráficos -->
<div class="row">
    <!-- Saldo Atual -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-wallet me-2"></i>Saldo atual (todas as contas)
                </h5>
            </header>
            <div class="card-body">
                <div class="d-grid gap-2 mb-3">
                    <a href="{{ route('financial.transactions.index') }}?type=receita" class="btn btn-success">
                        <i class="bx bx-plus me-2"></i>+ Receita
                    </a>
                    <a href="{{ route('financial.transactions.index') }}?type=despesa" class="btn btn-danger">
                        <i class="bx bx-minus me-2"></i>+ Despesa
                    </a>
                </div>
                <div class="text-center mb-3">
                    <h2 class="mb-0" style="color: {{ $totalBalance >= 0 ? '#007bff' : '#dc3545' }};">R$ {{ number_format($totalBalance, 2, ',', '.') }}</h2>
                    <small class="text-muted">Saldo Total</small>
                </div>
                <hr>
                <div class="mb-3">
                    @foreach($accountsBalance as $account)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">{{ $account['name'] }}</span>
                            <span class="fw-bold {{ $account['balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                @if($account['balance'] >= 0)
                                    R$ {{ number_format($account['balance'], 2, ',', '.') }} <i class="bx bx-up-arrow-alt"></i>
                                @else
                                    -R$ {{ number_format(abs($account['balance']), 2, ',', '.') }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="text-center">
                    <canvas id="balanceChart" style="max-height: 200px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumo Anual -->
    <div class="col-lg-8 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar me-2"></i>Resumo anual
                </h5>
                <form method="GET" action="{{ route('financial.summary') }}" id="yearForm" style="display: inline;">
                    <input type="hidden" name="period" value="{{ $periodFilter }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <select class="form-select form-select-sm" style="max-width: 150px;" name="year" id="yearSelect" onchange="this.form.submit()">
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

<!-- Resumo Mensal -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar-check me-2"></i>Resumo mensal
                </h5>
                <form method="GET" action="{{ route('financial.summary') }}" id="monthForm" style="display: inline;">
                    <input type="hidden" name="period" value="{{ $periodFilter }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <select class="form-select form-select-sm" style="max-width: 200px;" name="month" id="monthSelect" onchange="this.form.submit()">
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Dados do saldo por conta
    const accountsBalance = @json($accountsBalance);
    
    // Gráfico de Saldo (Donut Chart)
    const balanceCtx = document.getElementById('balanceChart');
    if (balanceCtx && accountsBalance.length > 0) {
        const labels = accountsBalance.map(acc => acc.name);
        const balances = accountsBalance.map(acc => Math.abs(parseFloat(acc.balance)));
        const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d', '#e83e8c', '#fd7e14'];
        
        new Chart(balanceCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: balances,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const account = accountsBalance[context.dataIndex];
                                const sign = account.balance >= 0 ? '+' : '-';
                                return account.name + ': ' + sign + 'R$ ' + Math.abs(account.balance).toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });
    }

    // Dados do gráfico anual
    const annualData = @json($annualData ?? []);
    
    // Gráfico Anual
    const annualCtx = document.getElementById('annualChart');
    if (annualCtx && annualData.labels) {
        const annualChart = new Chart(annualCtx, {
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
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'A pagar',
                        data: annualData.aPagar || [],
                        borderColor: '#dc3545',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
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
                        max: annualData.maxValue || 1000,
                        ticks: {
                            stepSize: Math.ceil((annualData.maxValue || 1000) / 5)
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });
    }

    // Dados do gráfico mensal
    const monthlyData = @json($monthlyData ?? []);
    
    // Gráfico Mensal
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx && monthlyData.labels) {
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [
                    {
                        label: 'Receitas',
                        data: monthlyData.receitas || [],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Despesas',
                        data: monthlyData.despesas || [],
                        borderColor: '#ff9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'A receber',
                        data: monthlyData.aReceber || [],
                        borderColor: '#28a745',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'A pagar',
                        data: monthlyData.aPagar || [],
                        borderColor: '#dc3545',
                        borderDash: [5, 5],
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
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
                        max: monthlyData.maxValue || 1000,
                        ticks: {
                            stepSize: Math.ceil((monthlyData.maxValue || 1000) / 5)
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Função para atualizar atrasos
    function updateAtrasos(type, periodo) {
        const recebimentosMes = {{ $recebimentosAtrasoMes }};
        const recebimentosTodo = {{ $recebimentosAtrasoTodoPeriodo }};
        const pagamentosMes = {{ $pagamentosAtrasoMes }};
        const pagamentosTodo = {{ $pagamentosAtrasoTodoPeriodo }};
        
        if (type === 'recebimentos') {
            const element = document.getElementById('recebimentosAtrasoValue');
            const todoElement = document.getElementById('recebimentosAtrasoTodo');
            const buttons = document.querySelectorAll('[onclick*="recebimentos"]');
            
            if (periodo === 'mes') {
                element.textContent = 'Mês atual: R$ ' + recebimentosMes.toFixed(2).replace('.', ',');
                todoElement.style.display = 'none';
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                element.textContent = 'Todo o período: R$ ' + recebimentosTodo.toFixed(2).replace('.', ',');
                todoElement.style.display = 'none';
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }
        } else {
            const element = document.getElementById('pagamentosAtrasoValue');
            const todoElement = document.getElementById('pagamentosAtrasoTodo');
            const buttons = document.querySelectorAll('[onclick*="pagamentos"]');
            
            if (periodo === 'mes') {
                element.textContent = 'Mês atual: R$ ' + pagamentosMes.toFixed(2).replace('.', ',');
                todoElement.style.display = 'none';
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                element.textContent = 'Todo o período: R$ ' + pagamentosTodo.toFixed(2).replace('.', ',');
                todoElement.style.display = 'none';
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }
        }
    }
</script>
@endpush
@endsection
