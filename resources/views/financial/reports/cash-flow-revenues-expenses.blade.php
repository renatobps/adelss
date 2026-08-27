@extends('layouts.porto')

@section('title', 'Relatório: Fluxo de Caixa - Receitas / Despesas')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Receitas / Despesas</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        @include('financial.reports.partials.full-filters', [
            'action' => route('financial.reports.cash-flow.revenues-expenses'),
            'defaultTypes' => ['receita'],
            'defaultStatus' => ['pago'],
        ])

        <div class="card fr-card mb-4">
            <div class="card-body">
                @include('financial.reports.partials.report-head', [
                    'title' => 'Fluxo de caixa — Receitas / Despesas',
                    'subtitle' => \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' a ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y'),
                ])

                <!-- Seção Receitas -->
                <div class="mb-5">
                    <h4 class="text-success mb-3">Receitas</h4>
                    
                    <div class="fr-table-toolbar">
                        <strong>Receitas: {{ $receitas->total() }}</strong>
                        <div class="fr-table-toolbar__actions">
                            <form method="GET" action="{{ route('financial.reports.cash-flow.revenues-expenses') }}" id="filterFormReceitas" class="fr-table-toolbar__actions">
                                @foreach(request()->except(['search_receitas', 'per_page_receitas', 'receitas_page', 'despesas_page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $v)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                        @endforeach
                                    @elseif($key !== 'search_despesas' && $key !== 'per_page_despesas' && $key !== 'sort_by_despesas' && $key !== 'sort_order_despesas')
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <select class="form-select" name="per_page_receitas" onchange="this.form.submit()">
                                    <option value="50" @selected(request('per_page_receitas', 100) == 50)>50 por página</option>
                                    <option value="100" @selected(request('per_page_receitas', 100) == 100)>100 por página</option>
                                    <option value="200" @selected(request('per_page_receitas', 100) == 200)>200 por página</option>
                                </select>
                                <input type="search" class="form-control" placeholder="Pesquisar" name="search_receitas" value="{{ request('search_receitas') }}">
                                <button type="submit" class="btn btn-outline-primary" aria-label="Pesquisar">
                                    <i class="bx bx-search"></i>
                                </button>
                            </form>
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
                    
                    <div class="fr-table-toolbar">
                        <strong>Despesas: {{ $despesas->total() }}</strong>
                        <div class="fr-table-toolbar__actions">
                            <form method="GET" action="{{ route('financial.reports.cash-flow.revenues-expenses') }}" id="filterFormDespesas" class="fr-table-toolbar__actions">
                                @foreach(request()->except(['search_despesas', 'per_page_despesas', 'despesas_page', 'receitas_page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $v)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                        @endforeach
                                    @elseif($key !== 'search_receitas' && $key !== 'per_page_receitas' && $key !== 'sort_by_receitas' && $key !== 'sort_order_receitas')
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <select class="form-select" name="per_page_despesas" onchange="this.form.submit()">
                                    <option value="50" @selected(request('per_page_despesas', 100) == 50)>50 por página</option>
                                    <option value="100" @selected(request('per_page_despesas', 100) == 100)>100 por página</option>
                                    <option value="200" @selected(request('per_page_despesas', 100) == 200)>200 por página</option>
                                </select>
                                <input type="search" class="form-control" placeholder="Pesquisar" name="search_despesas" value="{{ request('search_despesas') }}">
                                <button type="submit" class="btn btn-outline-primary" aria-label="Pesquisar">
                                    <i class="bx bx-search"></i>
                                </button>
                            </form>
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

