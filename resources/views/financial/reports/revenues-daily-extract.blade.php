@extends('layouts.porto')

@section('title', 'Relatório: Receitas - Extrato diário')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Receitas - Extrato diário</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        @include('financial.reports.partials.daily-filters', [
            'action' => route('financial.reports.revenues.daily-extract'),
            'categories' => $categoriesReceitas,
        ])

        <div class="card fr-card mb-4">
            <div class="card-body">
                @include('financial.reports.partials.report-head', [
                    'title' => 'Receitas — Extrato diário',
                    'subtitle' => \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' a ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y'),
                ])

                <!-- Gráfico de Área -->
                <div class="mb-4">
                    @include('financial.partials.daily-chart', [
                        'chartId' => 'revenuesChart',
                        'labels' => collect($chartData ?? [])->pluck('day')->all(),
                        'receitas' => collect($chartData ?? [])->pluck('receitas')->all(),
                        'despesas' => collect($chartData ?? [])->pluck('despesas')->all(),
                        'aReceber' => collect($chartData ?? [])->pluck('a_receber')->all(),
                        'aPagar' => collect($chartData ?? [])->pluck('a_pagar')->all(),
                        'area' => true,
                        'mobileNote' => 'Receitas e despesas agrupadas por período. Os previstos estão na tabela abaixo.',
                        'emptyMessage' => 'Sem receitas neste período',
                    ])
                </div>

                <div class="fr-table-toolbar">
                    <strong>Receitas: {{ $receitas->total() }}</strong>
                    <div class="fr-table-toolbar__actions">
                        <form method="GET" action="{{ route('financial.reports.revenues.daily-extract') }}" id="tableForm" class="fr-table-toolbar__actions">
                            @foreach(request()->except(['search', 'per_page', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <select class="form-select" name="per_page" onchange="this.form.submit()">
                                <option value="50" @selected(request('per_page', 100) == 50)>50 por página</option>
                                <option value="100" @selected(request('per_page', 100) == 100)>100 por página</option>
                                <option value="200" @selected(request('per_page', 100) == 200)>200 por página</option>
                            </select>
                            <input type="search" class="form-control" placeholder="Pesquisar" name="search" value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-primary" aria-label="Pesquisar">
                                <i class="bx bx-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tabela de Receitas -->
                @if($receitas->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <a href="{{ route('financial.reports.revenues.daily-extract', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'transaction_date', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                            Data
                                            @if(request('sort_by') == 'transaction_date')
                                                <i class="bx bx-chevron-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bx bx-sort-alt-2"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('financial.reports.revenues.daily-extract', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'description', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                                            Descrição
                                            @if(request('sort_by') == 'description')
                                                <i class="bx bx-chevron-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bx bx-sort-alt-2"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('financial.reports.revenues.daily-extract', array_merge(request()->except(['sort_by', 'sort_order']), ['sort_by' => 'amount', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
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

                    <!-- Paginação -->
                    <div class="mt-3">
                        {{ $receitas->appends(request()->except('page'))->links() }}
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2">Nenhuma receita encontrada para o período selecionado.</p>
                    </div>
                @endif

                <hr class="my-4">

                <!-- Resumo -->
                <div class="card" style="background-color: #f8f9fa; border: 2px solid #28a745; border-top: 4px solid #28a745;">
                    <div class="card-header bg-success text-white">
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

{{-- O gráfico é montado pelo partial financial.partials.daily-chart. --}}
@endsection

