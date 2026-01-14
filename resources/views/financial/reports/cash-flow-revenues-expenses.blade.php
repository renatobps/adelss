@extends('layouts.porto')

@section('title', 'Relatório: Fluxo de Caixa - Receitas / Despesas')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Receitas / Despesas</span></li>
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
                <form method="GET" action="{{ route('financial.reports.cash-flow.revenues-expenses') }}" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">Período:</label>
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" name="start_date" value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}">
                                <span class="input-group-text">-</span>
                                <input type="date" class="form-control" name="end_date" value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">Tipo:</label>
                            <select class="form-select form-select-sm" name="type[]" multiple size="3">
                                @php
                                    $selectedTypes = is_array(request('type')) ? request('type') : (request('type') ? [request('type')] : ['receita']);
                                @endphp
                                <option value="receita" {{ in_array('receita', $selectedTypes) ? 'selected' : '' }}>Receita</option>
                                <option value="despesa" {{ in_array('despesa', $selectedTypes) ? 'selected' : '' }}>Despesa</option>
                            </select>
                            <small class="text-muted">Ctrl+Click</small>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">Status:</label>
                            <select class="form-select form-select-sm" name="status[]" multiple size="4">
                                @php
                                    $selectedStatus = is_array(request('status')) ? request('status') : (request('status') ? [request('status')] : ['pago']);
                                @endphp
                                <option value="recebido" {{ in_array('recebido', $selectedStatus) ? 'selected' : '' }}>Recebido</option>
                                <option value="pago" {{ in_array('pago', $selectedStatus) ? 'selected' : '' }}>Pago</option>
                                <option value="a_receber" {{ in_array('a_receber', $selectedStatus) ? 'selected' : '' }}>A receber</option>
                                <option value="a_pagar" {{ in_array('a_pagar', $selectedStatus) ? 'selected' : '' }}>A pagar</option>
                            </select>
                            <small class="text-muted">Ctrl+Click</small>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">Contas:</label>
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
                            <label class="form-label small">Centros de custos:</label>
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
                            <label class="form-label small">Categorias receitas:</label>
                            <select class="form-select form-select-sm" name="category_receitas_id">
                                <option value="">Todas</option>
                                @foreach($categoriesReceitas as $category)
                                    <option value="{{ $category->id }}" {{ request('category_receitas_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-2 mb-2">
                            <label class="form-label small">Categorias despesas:</label>
                            <select class="form-select form-select-sm" name="category_despesas_id">
                                <option value="">Todas</option>
                                @foreach($categoriesDespesas as $category)
                                    <option value="{{ $category->id }}" {{ request('category_despesas_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-10 text-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bx bx-filter me-1"></i>Aplicar Filtros
                            </button>
                            @if(request()->hasAny(['type', 'status', 'category_receitas_id', 'category_despesas_id', 'account_id', 'cost_center_id', 'start_date', 'end_date']))
                                <a href="{{ route('financial.reports.cash-flow.revenues-expenses') }}" class="btn btn-default btn-sm">
                                    <i class="bx bx-x me-1"></i>Limpar
                                </a>
                            @endif
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
                            <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height: 50px;" onerror="this.style.display='none'">
                        </div>
                        <div>
                            <h5 class="mb-0">ADEL SÃO SEBASTIÃO</h5>
                            <h6 class="mb-0">Relatório: Fluxo de caixa - Receitas / Despesas</h6>
                            <p class="text-muted mb-0">Período: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} à {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Imprimir
                    </button>
                </div>

                <!-- Seção Receitas -->
                <div class="mb-5">
                    <h4 class="text-success mb-3">Receitas</h4>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div></div>
                        <div class="d-flex gap-2 align-items-center">
                            <form method="GET" action="{{ route('financial.reports.cash-flow.revenues-expenses') }}" id="filterFormReceitas" style="display: contents;">
                                @foreach(request()->except(['search_receitas', 'per_page_receitas', 'receitas_page', 'despesas_page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $v)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                        @endforeach
                                    @elseif($key !== 'search_despesas' && $key !== 'per_page_despesas' && $key !== 'sort_by_despesas' && $key !== 'sort_order_despesas')
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <select class="form-select form-select-sm" style="width: auto;" name="per_page_receitas" onchange="this.form.submit()">
                                    <option value="50" {{ request('per_page_receitas', 100) == 50 ? 'selected' : '' }}>50 resultados por página</option>
                                    <option value="100" {{ request('per_page_receitas', 100) == 100 ? 'selected' : '' }}>100 resultados por página</option>
                                    <option value="200" {{ request('per_page_receitas', 100) == 200 ? 'selected' : '' }}>200 resultados por página</option>
                                </select>
                                <input type="text" class="form-control form-control-sm" placeholder="Pesquisar" name="search_receitas" value="{{ request('search_receitas') }}" style="width: 200px;">
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

                    @if($receitas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.revenues-expenses', array_merge(request()->except(['sort_by_receitas', 'sort_order_receitas']), ['sort_by_receitas' => 'transaction_date', 'sort_order_receitas' => request('sort_order_receitas') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Data
                                                @if(request('sort_by_receitas') == 'transaction_date')
                                                    <i class="bx bx-chevron-{{ request('sort_order_receitas') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="bx bx-sort-alt-2"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.revenues-expenses', array_merge(request()->except(['sort_by_receitas', 'sort_order_receitas']), ['sort_by_receitas' => 'description', 'sort_order_receitas' => request('sort_order_receitas') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Descrição
                                                @if(request('sort_by_receitas') == 'description')
                                                    <i class="bx bx-chevron-{{ request('sort_order_receitas') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="bx bx-sort-alt-2"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.revenues-expenses', array_merge(request()->except(['sort_by_receitas', 'sort_order_receitas']), ['sort_by_receitas' => 'amount', 'sort_order_receitas' => request('sort_order_receitas') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Total
                                                @if(request('sort_by_receitas') == 'amount')
                                                    <i class="bx bx-chevron-{{ request('sort_order_receitas') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="bx bx-sort-alt-2"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Categoria</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($receitas as $receita)
                                        <tr>
                                            <td>{{ $receita->transaction_date->format('d/m/Y') }}</td>
                                            <td>{{ $receita->description }}</td>
                                            <td>
                                                <span class="text-primary">
                                                    R$ {{ number_format($receita->amount, 2, ',', '.') }}
                                                </span>
                                                @if($receita->is_paid)
                                                    <i class="bx bx-check-circle text-success ms-1"></i>
                                                @endif
                                            </td>
                                            <td>{{ $receita->category ? $receita->category->name : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação Receitas -->
                        <div class="mt-3">
                            {{ $receitas->appends(request()->except(['receitas_page']))->links() }}
                        </div>
                    @else
                        <div class="text-center py-3 text-muted">
                            <p class="mb-0">Nenhuma receita encontrada para o período selecionado.</p>
                        </div>
                    @endif
                </div>

                <!-- Seção Despesas -->
                <div class="mb-5">
                    <h4 class="text-danger mb-3">Despesas</h4>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div></div>
                        <div class="d-flex gap-2 align-items-center">
                            <form method="GET" action="{{ route('financial.reports.cash-flow.revenues-expenses') }}" id="filterFormDespesas" style="display: contents;">
                                @foreach(request()->except(['search_despesas', 'per_page_despesas', 'despesas_page', 'receitas_page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $v)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                        @endforeach
                                    @elseif($key !== 'search_receitas' && $key !== 'per_page_receitas' && $key !== 'sort_by_receitas' && $key !== 'sort_order_receitas')
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <select class="form-select form-select-sm" style="width: auto;" name="per_page_despesas" onchange="this.form.submit()">
                                    <option value="50" {{ request('per_page_despesas', 100) == 50 ? 'selected' : '' }}>50 resultados por página</option>
                                    <option value="100" {{ request('per_page_despesas', 100) == 100 ? 'selected' : '' }}>100 resultados por página</option>
                                    <option value="200" {{ request('per_page_despesas', 100) == 200 ? 'selected' : '' }}>200 resultados por página</option>
                                </select>
                                <input type="text" class="form-control form-control-sm" placeholder="Pesquisar" name="search_despesas" value="{{ request('search_despesas') }}" style="width: 200px;">
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

                    @if($despesas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.revenues-expenses', array_merge(request()->except(['sort_by_despesas', 'sort_order_despesas']), ['sort_by_despesas' => 'transaction_date', 'sort_order_despesas' => request('sort_order_despesas') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Data
                                                @if(request('sort_by_despesas') == 'transaction_date')
                                                    <i class="bx bx-chevron-{{ request('sort_order_despesas') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="bx bx-sort-alt-2"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.revenues-expenses', array_merge(request()->except(['sort_by_despesas', 'sort_order_despesas']), ['sort_by_despesas' => 'description', 'sort_order_despesas' => request('sort_order_despesas') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Descrição
                                                @if(request('sort_by_despesas') == 'description')
                                                    <i class="bx bx-chevron-{{ request('sort_order_despesas') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="bx bx-sort-alt-2"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ route('financial.reports.cash-flow.revenues-expenses', array_merge(request()->except(['sort_by_despesas', 'sort_order_despesas']), ['sort_by_despesas' => 'amount', 'sort_order_despesas' => request('sort_order_despesas') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                                Total
                                                @if(request('sort_by_despesas') == 'amount')
                                                    <i class="bx bx-chevron-{{ request('sort_order_despesas') == 'asc' ? 'up' : 'down' }}"></i>
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
                                                    -R$ {{ number_format($despesa->amount, 2, ',', '.') }}
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

                        <!-- Paginação Despesas -->
                        <div class="mt-3">
                            {{ $despesas->appends(request()->except(['despesas_page']))->links() }}
                        </div>
                    @else
                        <div class="text-center py-3 text-muted">
                            <p class="mb-0">Nenhuma despesa encontrada para o período selecionado.</p>
                        </div>
                    @endif
                </div>

                <!-- Resumo -->
                <div class="card" style="background-color: #f8f9fa; border: 2px solid #007bff; border-top: 4px solid #007bff;">
                    <div class="card-header bg-primary text-white">
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
@endsection

