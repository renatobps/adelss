@extends('layouts.porto')

@section('title', 'Transações')

@section('page-title', 'Transações')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Transações</span></li>
@endsection

@section('content')
<div class="fr-page">
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canCreateReceitas = $isAdmin || $user->hasPermission('financial.receitas.create') || $user->hasPermission('financial.receitas.manage');
    $canCreateDespesas = $isAdmin || $user->hasPermission('financial.despesas.create') || $user->hasPermission('financial.despesas.manage');
    $canEditReceitas = $isAdmin || $user->hasPermission('financial.receitas.edit') || $user->hasPermission('financial.receitas.manage');
    $canEditDespesas = $isAdmin || $user->hasPermission('financial.despesas.edit') || $user->hasPermission('financial.despesas.manage');
    $canDeleteReceitas = $isAdmin || $user->hasPermission('financial.receitas.delete') || $user->hasPermission('financial.receitas.manage');
    $canDeleteDespesas = $isAdmin || $user->hasPermission('financial.despesas.delete') || $user->hasPermission('financial.despesas.manage');
    $canViewReceitas = $isAdmin || $user->hasPermission('financial.receitas.view') || $user->hasPermission('financial.receitas.manage');
    $whatsappReceiptEnabled = config('financial.whatsapp.dizimo_receipt_enabled', true);
@endphp

@include('financial.reports.partials.styles')

<!-- Header -->
<div class="alert alert-info mb-4" style="background-color: #e3f2fd; color: #1976d2; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Gerencie suas transações financeiras.
</div>

@if(session('success') && !session('continue_adding'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Gráfico e Resumo -->
<div class="row mb-4">
    <!-- Gráfico Mensal -->
    <div class="col-lg-8 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar-check me-2"></i>Resumo mensal
                </h5>
            </header>
            <div class="card-body">
                @include('financial.partials.daily-chart', [
                    'chartId' => 'monthlyTransactionChart',
                    'labels' => collect($chartData ?? [])->pluck('day')->all(),
                    'receitas' => collect($chartData ?? [])->pluck('receitas')->all(),
                    'despesas' => collect($chartData ?? [])->pluck('despesas')->all(),
                    'aReceber' => collect($chartData ?? [])->pluck('a_receber')->all(),
                    'aPagar' => collect($chartData ?? [])->pluck('a_pagar')->all(),
                    'area' => true,
                    'mobileNote' => 'Valores agrupados por período. Os previstos estão no box Previsão, abaixo.',
                    'emptyMessage' => 'Sem movimentações no período selecionado',
                ])
            </div>
        </div>
    </div>

    <!-- Box de Previsão -->
    <div class="col-lg-4 mb-4">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <header class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-chart me-2"></i>Previsão
                </h5>
                <small class="text-muted">de acordo com as datas selecionadas</small>
            </header>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Total recebido:</span>
                        <strong class="text-primary">R$ {{ number_format($summary['total_received'] ?? 0, 2, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Total pago:</span>
                        <strong class="text-warning">- R$ {{ number_format($summary['total_paid'] ?? 0, 2, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">A receber:</span>
                        <strong class="text-success">R$ {{ number_format($summary['to_receive'] ?? 0, 2, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">A pagar:</span>
                        <strong class="text-danger">- R$ {{ number_format($summary['to_pay'] ?? 0, 2, ',', '.') }}</strong>
                    </div>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="{{ route('financial.reports.index') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-line-chart me-1"></i>Mais relatórios
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros e Ações -->
@include('financial.reports.partials.full-filters', [
    'action' => route('financial.transactions.index'),
    'categoriesReceitas' => $categoriesReceitas,
    'categoriesDespesas' => $categoriesDespesas,
])
<div class="d-flex flex-wrap gap-2 justify-content-md-end mb-4">
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="bx bx-upload me-1"></i>Importar
    </button>
    @if($canCreateReceitas)
    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createReceitaModal">
        <i class="bx bx-plus me-1"></i> Adicionar receita
    </button>
    @endif
    @if($canCreateDespesas)
    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#createDespesaModal">
        <i class="bx bx-plus me-1"></i> Adicionar despesa
    </button>
    @endif
</div>

<!-- Tabela de Transações -->
<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <strong>Resultados: {{ $transactions->total() }} transações</strong>
            </div>
            @php
                $txView = request('view') === 'cards' ? 'cards' : 'table';
                $sortKey = in_array(request('sort'), ['date', 'description', 'amount'], true) ? request('sort') : 'date';
                $sortDir = request('sort_dir') === 'asc' ? 'asc' : 'desc';
                $sortIcon = function (string $column) use ($sortKey, $sortDir) {
                    if ($sortKey !== $column) {
                        return 'bx-sort';
                    }
                    return $sortDir === 'asc' ? 'bx-sort-up' : 'bx-sort-down';
                };
                $sortUrl = function (string $column) use ($sortKey, $sortDir) {
                    $nextDir = ($sortKey === $column && $sortDir === 'desc') ? 'asc' : 'desc';
                    return request()->fullUrlWithQuery(['sort' => $column, 'sort_dir' => $nextDir]);
                };
            @endphp
            <input type="hidden" name="sort" form="filterForm" value="{{ $sortKey }}">
            <input type="hidden" name="sort_dir" form="filterForm" value="{{ $sortDir }}">
            <input type="hidden" name="view" form="filterForm" value="{{ $txView }}">
            <div class="financial-tx-toolbar">
                <div class="btn-group btn-group-sm" role="group" aria-label="Modo de exibição">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}"
                       class="btn btn-outline-secondary {{ $txView === 'table' ? 'active' : '' }}"
                       title="Tabela">
                        <i class="bx bx-table"></i>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'cards']) }}"
                       class="btn btn-outline-secondary {{ $txView === 'cards' ? 'active' : '' }}"
                       title="Cartões">
                        <i class="bx bx-list-ul"></i>
                    </a>
                </div>
                <select class="form-select form-select-sm financial-tx-toolbar__perpage" onchange="this.form.submit()" form="filterForm" name="per_page">
                    <option value="50" {{ request('per_page', 100) == 50 ? 'selected' : '' }}>50 resultados por página</option>
                    <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100 resultados por página</option>
                    <option value="200" {{ request('per_page', 100) == 200 ? 'selected' : '' }}>200 resultados por página</option>
                </select>
                <input type="text" class="form-control form-control-sm financial-tx-toolbar__search" placeholder="Pesquisar" name="search" form="filterForm" value="{{ request('search') }}">
                <button type="submit" form="filterForm" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-search"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printTransactions()" title="Imprimir">
                    <i class="bx bx-printer"></i>
                </button>
                <a href="{{ route('financial.transactions.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary" title="Download CSV">
                    <i class="bx bx-download"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal" title="Importar">
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

        @if($transactions->count() > 0)
            @if($txView === 'table')
            <div class="table-responsive financial-tx-table-wrap">
                <table class="table table-hover align-middle financial-tx-table mb-0">
                    <thead>
                        <tr>
                            <th>
                                <a href="{{ $sortUrl('date') }}" class="financial-tx-sort {{ $sortKey === 'date' ? 'is-active' : '' }}">
                                    Data <i class="bx {{ $sortIcon('date') }}"></i>
                                </a>
                            </th>
                            <th>Nome</th>
                            <th>
                                <a href="{{ $sortUrl('description') }}" class="financial-tx-sort {{ $sortKey === 'description' ? 'is-active' : '' }}">
                                    Descrição <i class="bx {{ $sortIcon('description') }}"></i>
                                </a>
                            </th>
                            <th>Categoria</th>
                            <th class="text-end">
                                <a href="{{ $sortUrl('amount') }}" class="financial-tx-sort {{ $sortKey === 'amount' ? 'is-active' : '' }}">
                                    Valor <i class="bx {{ $sortIcon('amount') }}"></i>
                                </a>
                            </th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            @php
                                $isReceita = $transaction->type === 'receita';
                                $canEdit = ($isReceita && $canEditReceitas) || (!$isReceita && $canEditDespesas);
                                $canDelete = ($isReceita && $canDeleteReceitas) || (!$isReceita && $canDeleteDespesas);
                                $dateLabel = optional($transaction->transaction_date)->format('d/m/Y') ?: '—';
                                $typeLabel = $isReceita ? 'Receita' : 'Despesa';
                                $personName = $transaction->listingPersonName();
                                $amountPrefix = $isReceita ? '+' : '-';
                                $amountLabel = $amountPrefix.'R$ '.number_format((float) $transaction->amount, 2, ',', '.');
                            @endphp
                            <tr class="{{ $isReceita ? 'is-receita' : 'is-despesa' }}"
                                data-description="{{ $transaction->description }}"
                                data-status="{{ $typeLabel }}"
                                data-meta="{{ $dateLabel }} | {{ $personName }} | {{ $typeLabel }}"
                                data-amount="{{ $amountLabel }}">
                                <td class="text-nowrap">{{ $dateLabel }}</td>
                                <td>{{ $personName }}</td>
                                <td>
                                    <a href="#"
                                       class="edit-description financial-tx-table__title"
                                       data-transaction-id="{{ $transaction->id }}"
                                       data-description="{{ $transaction->description }}">
                                        {{ $transaction->description }}
                                    </a>
                                </td>
                                <td>{{ $typeLabel }}</td>
                                <td class="text-end text-nowrap fw-semibold {{ $isReceita ? 'text-success' : 'text-danger' }}">{{ $amountLabel }}</td>
                                <td class="text-end">
                                    @include('financial.transactions.partials.list-actions', ['compact' => true])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="financial-tx-list">
                @foreach($transactions as $transaction)
                    @php
                        $isReceita = $transaction->type === 'receita';
                        $canEdit = ($isReceita && $canEditReceitas) || (!$isReceita && $canEditDespesas);
                        $canDelete = ($isReceita && $canDeleteReceitas) || (!$isReceita && $canDeleteDespesas);
                        $statusLabel = $transaction->is_paid
                            ? 'Pago'
                            : ($isReceita ? 'A receber' : 'A pagar');
                        $statusClass = $transaction->is_paid ? 'is-paid' : 'is-pending';
                        $dateTime = optional($transaction->transaction_date)->format('d/m/Y') ?: '—';
                        $accountName = $transaction->account?->name ?: 'Sem conta';
                        $typeLabel = $isReceita ? 'Receita' : 'Despesa';
                        $categoryName = $transaction->category?->name;
                        $personName = $transaction->listingPersonName();
                        $amountPrefix = $isReceita ? '+' : '-';
                        $latestPayment = $transaction->latestPaymentTransaction;
                    @endphp

                    <article class="financial-tx-card {{ $isReceita ? 'is-receita' : 'is-despesa' }}">
                        <div class="financial-tx-card__main">
                            <div class="financial-tx-card__icon" aria-hidden="true">
                                <i class="bx {{ $isReceita ? 'bx-trending-up' : 'bx-trending-down' }}"></i>
                            </div>

                            <div class="financial-tx-card__body">
                                <div class="financial-tx-card__title-row">
                                    <h3 class="financial-tx-card__title">
                                        <a href="#"
                                           class="edit-description"
                                           data-transaction-id="{{ $transaction->id }}"
                                           data-description="{{ $transaction->description }}">
                                            {{ $transaction->description }}
                                        </a>
                                    </h3>
                                    <span class="financial-tx-card__badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    @if($latestPayment)
                                        @php
                                            $paymentStatus = strtolower((string) $latestPayment->status);
                                            $paymentBadge = $paymentStatus === 'approved'
                                                ? 'bg-success'
                                                : (in_array($paymentStatus, ['rejected', 'cancelled', 'refunded', 'charged_back'], true) ? 'bg-danger' : 'bg-warning text-dark');
                                        @endphp
                                        <span class="badge {{ $paymentBadge }} ms-1">MP: {{ $paymentStatus }}</span>
                                    @endif
                                </div>

                                <ul class="financial-tx-card__meta">
                                    <li>
                                        <i class="bx bx-calendar"></i>
                                        <span>{{ $dateTime }}</span>
                                    </li>
                                    <li>
                                        <i class="bx bx-wallet"></i>
                                        <span>{{ $accountName }}</span>
                                    </li>
                                    @if($personName !== '')
                                    <li>
                                        <i class="bx bx-user"></i>
                                        <span>{{ $personName }}</span>
                                    </li>
                                    @endif
                                    <li>
                                        <i class="bx bx-purchase-tag"></i>
                                        <span>{{ $typeLabel }}{{ $categoryName ? ' · ' . $categoryName : '' }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="financial-tx-card__side">
                            <div class="financial-tx-card__amount {{ $isReceita ? 'text-success' : 'text-danger' }}">
                                {{ $amountPrefix }}R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}
                            </div>
                            @include('financial.transactions.partials.list-actions', ['compact' => false])
                        </div>
                    </article>
                @endforeach
            </div>
            @endif

            <div class="mt-3">
                {{ $transactions->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bx bx-filter-alt" style="font-size: 3rem;"></i>
                <p class="mt-2 mb-1 fw-semibold">Nenhuma transação encontrada</p>
                <p class="mb-0 small">Ajuste os filtros ou adicione uma nova transação</p>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .financial-tx-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .financial-tx-card {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
    }

    .financial-tx-card__main {
        display: flex;
        gap: 0.9rem;
        min-width: 0;
        flex: 1 1 280px;
    }

    .financial-tx-card__icon {
        width: 42px;
        height: 42px;
        border-radius: 0.55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.25rem;
    }

    .financial-tx-card.is-receita .financial-tx-card__icon {
        background: #e8f8ef;
        color: #198754;
    }

    .financial-tx-card.is-despesa .financial-tx-card__icon {
        background: #fdecee;
        color: #dc3545;
    }

    .financial-tx-card__body {
        min-width: 0;
    }

    .financial-tx-card__title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.45rem;
    }

    .financial-tx-card__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.25;
    }

    .financial-tx-card__title a {
        color: inherit;
        text-decoration: none;
    }

    .financial-tx-card__title a:hover {
        color: #2563eb;
    }

    .financial-tx-card__badge {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .financial-tx-card__badge.is-paid {
        background: #5bc0de;
        color: #fff;
    }

    .financial-tx-card__badge.is-pending {
        background: #f3f4f6;
        color: #6b7280;
    }

    .financial-tx-card__meta {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .financial-tx-card__meta li {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        color: #6b7280;
        font-size: 0.86rem;
        line-height: 1.35;
    }

    .financial-tx-card__meta i {
        font-size: 0.95rem;
        color: #9ca3af;
    }

    .financial-tx-card__side {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
        gap: 0.75rem;
        margin-left: auto;
        min-width: 160px;
    }

    .financial-tx-card__amount {
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }

    .financial-tx-actions__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        border: 0;
        border-radius: 0.45rem;
        background: transparent;
        color: #6b7280;
        font-size: 1.35rem;
        line-height: 1;
        cursor: pointer;
    }

    .financial-tx-actions__btn:hover,
    .financial-tx-actions__btn[aria-expanded="true"] {
        background: #f3f4f6;
        color: #111827;
    }

    .financial-tx-actions .dropdown-item {
        display: flex;
        align-items: center;
        width: 100%;
        border: 0;
        background: transparent;
        cursor: pointer;
    }

    .financial-tx-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .financial-tx-toolbar__perpage {
        width: auto;
    }

    .financial-tx-toolbar__search {
        width: 200px;
    }

    .financial-tx-table-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        overflow: auto;
    }

    .financial-tx-table thead th {
        white-space: nowrap;
        background: #f8fafc;
        font-size: 0.82rem;
        color: #4b5563;
        border-bottom-width: 1px;
    }

    .financial-tx-sort {
        color: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .financial-tx-sort:hover,
    .financial-tx-sort.is-active {
        color: #2563eb;
    }

    .financial-tx-table__title {
        color: #111827;
        font-weight: 600;
        text-decoration: none;
    }

    .financial-tx-table__title:hover {
        color: #2563eb;
    }

    .financial-tx-table th:last-child,
    .financial-tx-table td:last-child {
        width: 48px;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .financial-tx-card__side {
            width: 100%;
            align-items: flex-start;
            min-width: 0;
        }

        /* Larguras fixas aqui somavam mais que a tela e faziam a página
           inteira rolar na horizontal. */
        .financial-tx-toolbar {
            width: 100%;
        }

        .financial-tx-toolbar__perpage {
            width: 100%;
        }

        .financial-tx-toolbar__search {
            width: auto;
            flex: 1 1 8rem;
            min-width: 0;
        }
    }
</style>
@endpush

<!-- Modal: Importar Transações -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="bx bx-upload me-2"></i>Importar Transações
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('financial.transactions.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Formato do arquivo CSV:</strong><br>
                        O arquivo deve conter as colunas: Data, Tipo, Descrição, Valor, Status (opcional), separadas por ponto e vírgula (;).<br>
                        <small>Data no formato: dd/mm/aaaa | Tipo: receita ou despesa | Valor: formato brasileiro (R$ 1.234,56)</small>
                    </div>
                    <div class="mb-3">
                        <label for="import_file" class="form-label">Selecionar arquivo CSV <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('import_file') is-invalid @enderror" 
                               id="import_file" name="import_file" accept=".csv,.txt" required>
                        @error('import_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Tamanho máximo: 10MB. Formato: CSV</small>
                    </div>
                    @if(session('import_errors') && count(session('import_errors')) > 0)
                        <div class="alert alert-warning">
                            <strong>Erros durante a importação:</strong>
                            <ul class="mb-0">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-upload me-1"></i>Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Criar Receita -->
@if($canCreateReceitas)
<div class="modal fade" id="createReceitaModal" tabindex="-1" aria-labelledby="createReceitaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #007bff; color: white;">
                <h5 class="modal-title" id="createReceitaModalLabel">
                    <i class="bx bx-plus me-2"></i>Criar receita
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('financial.transactions.store.receita') }}" method="POST" enctype="multipart/form-data" id="receitaForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="receita_date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" 
                                   id="receita_date" name="transaction_date" 
                                   value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                            @error('transaction_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="receita_description" class="form-label">Descrição <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                   id="receita_description" name="description" 
                                   value="{{ old('description') }}" 
                                   placeholder="Digite a descrição" required>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="receita_amount" class="form-label">Valor <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                   id="receita_amount" name="amount" 
                                   value="{{ old('amount', '0.00') }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label d-block">Pago?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="receita_is_paid" name="is_paid" value="1" {{ old('is_paid', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="receita_is_paid">Sim</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="receita_received_from" class="form-label">
                                Recebido de <span class="text-danger receita-donor-required d-none" id="receita_donor_required">*</span>
                            </label>
                            <select class="form-select @error('member_id') is-invalid @enderror" 
                                    id="receita_received_from" name="member_id">
                                <option value="">Selecione</option>
                                <option value="other">Outros</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}" {{ old('member_id') == $member->id ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control mt-2 d-none" 
                                   id="receita_other_name" name="received_from_other" 
                                   placeholder="Digite o nome (quando selecionar 'Outros')"
                                   value="{{ old('received_from_other') }}">
                            @error('member_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('received_from_other')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="receita_category" class="form-label">Categoria</label>
                            <select class="form-select" id="receita_category" name="category_id">
                                <option value="">Selecione</option>
                                @foreach($categories->where('type', 'receita') as $category)
                                    <option value="{{ $category->id }}"
                                            data-slug="{{ $category->slug }}"
                                            data-dizimo="{{ $category->isDizimo() ? '1' : '0' }}"
                                            data-dizimo-oferta="{{ $category->isDizimoOuOferta() ? '1' : '0' }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="receita_account" class="form-label">Conta</label>
                            <select class="form-select" id="receita_account" name="account_id">
                                <option value="">Selecione</option>
                                @foreach(($accountsActive ?? $accounts) as $account)
                                    <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="receita_cost_center" class="form-label">Centro de custo</label>
                            <select class="form-select" id="receita_cost_center" name="cost_center_id">
                                <option value="">Selecione</option>
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}" {{ old('cost_center_id') == $costCenter->id ? 'selected' : '' }}>
                                        {{ $costCenter->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="receita_payment_type" class="form-label">Tipo de pagamento</label>
                            <select class="form-select" id="receita_payment_type" name="payment_type">
                                <option value="unico" {{ old('payment_type', 'unico') == 'unico' ? 'selected' : '' }}>Único</option>
                                <option value="parcelado" {{ old('payment_type') == 'parcelado' ? 'selected' : '' }}>Parcelado</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="receita_document" class="form-label">Doc nº</label>
                            <input type="text" class="form-control" id="receita_document" name="document_number" 
                                   value="{{ old('document_number') }}" placeholder="Número do documento">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="receita_competence" class="form-label">Competência</label>
                            <input type="date" class="form-control" id="receita_competence" name="competence_date" 
                                   value="{{ old('competence_date') }}">
                        </div>
                        <div class="col-md-4 mb-3 receita-due-date-field d-none">
                            <label for="receita_due_date" class="form-label">Vencimento</label>
                            <input type="date" class="form-control" id="receita_due_date" name="due_date"
                                   value="{{ old('due_date') }}">
                        </div>
                    </div>

                    @if($whatsappReceiptEnabled)
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="receita_send_whatsapp" name="send_whatsapp_receipt" value="1" checked>
                            <label class="form-check-label" for="receita_send_whatsapp">
                                Enviar comprovante por WhatsApp ao membro (dízimo/oferta)
                            </label>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="receita_notes" class="form-label">Anotações</label>
                        <textarea class="form-control" id="receita_notes" name="notes" rows="3" 
                                  placeholder="Digite anotações...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivos <span id="receita_file_count">0</span>/5</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('receita_attachments_upload').click()">
                                <i class="bx bx-upload me-1"></i>Anexar arquivo
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('receita_attachments_camera').click()">
                                <i class="bx bx-camera me-1"></i>Tirar foto
                            </button>
                        </div>
                        <small class="text-muted d-block mb-2">Imagens ou PDF. Máx. 10MB por arquivo. No celular, use &quot;Tirar foto&quot; para abrir a câmera.</small>
                        <input type="file" class="d-none" id="receita_attachments_upload" multiple accept="image/*,application/pdf">
                        <input type="file" class="d-none" id="receita_attachments_camera" accept="image/*" capture="environment">
                        <input type="file" class="d-none" id="receita_attachments" name="attachments[]" multiple>
                        <div id="receita_files_preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="save_action" value="new" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Salvar e novo
                    </button>
                    <button type="submit" name="save_action" value="close" class="btn" style="background-color: #20c997; color: white;">
                        <i class="bx bx-check me-1"></i>Salvar e fechar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal: Detalhes da Transação -->
<div class="modal fade" id="viewTransactionModal" tabindex="-1" aria-labelledby="viewTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" id="viewTransactionModalHeader" style="background-color: #0d6efd; color: white;">
                <h5 class="modal-title" id="viewTransactionModalLabel">
                    <i class="bx bx-show me-2"></i>Detalhes da Transação
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="viewTransactionLoading" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Carregando detalhes...
                </div>
                <div id="viewTransactionContent" class="d-none">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="text-muted small">Descrição</div>
                            <h4 class="mb-1" id="view_description">—</h4>
                            <span class="badge" id="view_status_badge">—</span>
                            <span class="badge bg-secondary ms-1" id="view_type_badge">—</span>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Valor</div>
                            <div class="fs-3 fw-bold" id="view_amount">—</div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Data</div>
                            <div id="view_transaction_date">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Registrado em</div>
                            <div id="view_created_at">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Contato</div>
                            <div id="view_contato">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Categoria</div>
                            <div id="view_category">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Conta</div>
                            <div id="view_account">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Centro de custo</div>
                            <div id="view_cost_center">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Tipo de pagamento</div>
                            <div id="view_payment_type">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Doc nº</div>
                            <div id="view_document_number">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Competência</div>
                            <div id="view_competence_date">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Vencimento</div>
                            <div id="view_due_date">—</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Criado por</div>
                            <div id="view_created_by">—</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Anotações</div>
                            <div id="view_notes">—</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small mb-1">Anexos</div>
                            <div id="view_attachments">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-outline-primary" id="viewTransactionReceiptBtn">
                    <i class="bx bx-receipt me-1"></i>Exibir recibo
                </button>
                <button type="button" class="btn btn-primary d-none" id="viewTransactionEditBtn">
                    <i class="bx bx-edit me-1"></i>Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Transação -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #007bff; color: white;">
                <h5 class="modal-title" id="editTransactionModalLabel">
                    <i class="bx bx-edit me-2"></i>Editar Transação
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" id="editTransactionForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="edit_transaction_date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" 
                                   id="edit_transaction_date" name="transaction_date" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="edit_description" class="form-label">Descrição <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" 
                                   id="edit_description" name="description" 
                                   placeholder="Digite a descrição" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="edit_amount" class="form-label">Valor <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" 
                                   id="edit_amount" name="amount" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label d-block">Pago?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="edit_is_paid" name="is_paid" value="1">
                                <label class="form-check-label" for="edit_is_paid">Sim</label>
                            </div>
                        </div>
                    </div>

                    <!-- Campos para Receita -->
                    <div id="edit_receita_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_member_id" class="form-label">
                                    Recebido de <span class="text-danger receita-donor-required d-none" id="edit_donor_required">*</span>
                                </label>
                                <select class="form-select" id="edit_member_id" name="member_id">
                                    <option value="">Selecione</option>
                                    <option value="other">Outros</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" class="form-control mt-2 d-none" 
                                       id="edit_other_name" name="received_from_other" 
                                       placeholder="Digite o nome (quando selecionar 'Outros')">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_receita_category_id" class="form-label">Categoria</label>
                                <select class="form-select" id="edit_receita_category_id" name="category_id">
                                    <option value="">Selecione</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" data-type="{{ $category->type }}"
                                                data-slug="{{ $category->slug }}"
                                                data-dizimo="{{ $category->isDizimo() ? '1' : '0' }}"
                                                data-dizimo-oferta="{{ $category->isDizimoOuOferta() ? '1' : '0' }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Campos para Despesa -->
                    <div id="edit_despesa_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_payee_id" class="form-label">Pago à</label>
                                <select class="form-select" id="edit_payee_id" name="member_id">
                                    <option value="">Selecione</option>
                                    <option value="other">Outros</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" class="form-control mt-2 d-none"
                                       id="edit_payee_other_name" name="received_from_other"
                                       placeholder="Nome de quem recebeu (quando não for membro)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_despesa_category_id" class="form-label">Categoria</label>
                                <select class="form-select" id="edit_despesa_category_id" name="category_id">
                                    <option value="">Selecione</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" data-type="{{ $category->type }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_account_id" class="form-label">Conta</label>
                            <select class="form-select" id="edit_account_id" name="account_id">
                                <option value="">Selecione</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_cost_center_id" class="form-label">Centro de custo</label>
                            <select class="form-select" id="edit_cost_center_id" name="cost_center_id">
                                <option value="">Selecione</option>
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_payment_type" class="form-label">Tipo de pagamento</label>
                            <select class="form-select" id="edit_payment_type" name="payment_type">
                                <option value="unico">Único</option>
                                <option value="parcelado">Parcelado</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_document_number" class="form-label">Doc nº</label>
                            <input type="text" class="form-control" id="edit_document_number" name="document_number" 
                                   placeholder="Número do documento">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_competence_date" class="form-label">Competência</label>
                            <input type="date" class="form-control" id="edit_competence_date" name="competence_date">
                        </div>
                        <div class="col-md-4 mb-3" id="edit_due_date_wrapper">
                            <label for="edit_due_date" class="form-label">Vencimento</label>
                            <input type="date" class="form-control" id="edit_due_date" name="due_date">
                        </div>
                    </div>

                    @if($whatsappReceiptEnabled)
                    <div class="mb-3" id="edit_whatsapp_receipt_wrapper" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_send_whatsapp" name="send_whatsapp_receipt" value="1" checked>
                            <label class="form-check-label" for="edit_send_whatsapp">
                                Enviar comprovante por WhatsApp ao marcar como recebido
                            </label>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Anotações</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3" 
                                  placeholder="Digite anotações..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Arquivos <span id="edit_file_count">0</span>/5
                            <span class="text-danger d-none" id="edit_receipt_required">* Recibo assinado obrigatório</span>
                        </label>
                        <div class="alert alert-warning py-2 small d-none" id="edit_receipt_hint">
                            Pagamento a membro exige o recibo de pagamento assinado por quem recebeu (foto ou PDF).
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('edit_attachments_upload').click()">
                                <i class="bx bx-upload me-1"></i>Anexar arquivo
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('edit_attachments_camera').click()">
                                <i class="bx bx-camera me-1"></i>Tirar foto
                            </button>
                        </div>
                        <small class="text-muted d-block mb-2">Imagens ou PDF. Máx. 10MB por arquivo. No celular, use &quot;Tirar foto&quot; para abrir a câmera.</small>
                        <input type="file" class="d-none" id="edit_attachments_upload" multiple accept="image/*,application/pdf">
                        <input type="file" class="d-none" id="edit_attachments_camera" accept="image/*" capture="environment">
                        <input type="file" class="d-none" id="edit_attachments" name="attachments[]" multiple>
                        <div id="edit_files_preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" style="background-color: #20c997; color: white;">
                        <i class="bx bx-check me-1"></i>Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Criar Despesa -->
@if($canCreateDespesas)
<div class="modal fade" id="createDespesaModal" tabindex="-1" aria-labelledby="createDespesaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc3545; color: white;">
                <h5 class="modal-title" id="createDespesaModalLabel">
                    <i class="bx bx-plus me-2"></i>Criar despesa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('financial.transactions.store.despesa') }}" method="POST" enctype="multipart/form-data" id="despesaForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="despesa_date" class="form-label">Data <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" 
                                   id="despesa_date" name="transaction_date" 
                                   value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                            @error('transaction_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="despesa_description" class="form-label">Descrição <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                   id="despesa_description" name="description" 
                                   value="{{ old('description') }}" 
                                   placeholder="Digite a descrição" required>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="despesa_amount" class="form-label">Valor <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                   id="despesa_amount" name="amount" 
                                   value="{{ old('amount', '0.00') }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label d-block">Pago?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="despesa_is_paid" name="is_paid" value="1" {{ old('is_paid', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="despesa_is_paid">Sim</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="despesa_payee" class="form-label">Pago à</label>
                            <select class="form-select @error('member_id') is-invalid @enderror" id="despesa_payee" name="member_id">
                                <option value="">Selecione</option>
                                <option value="other" {{ old('member_id') === 'other' ? 'selected' : '' }}>Outros</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}" {{ (string) old('member_id') === (string) $member->id ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control mt-2 {{ old('member_id') === 'other' ? '' : 'd-none' }}"
                                   id="despesa_other_name" name="received_from_other"
                                   placeholder="Nome de quem recebeu (quando não for membro)"
                                   value="{{ old('received_from_other') }}">
                            @error('member_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('received_from_other')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="despesa_category" class="form-label">Categoria</label>
                            <select class="form-select" id="despesa_category" name="category_id">
                                <option value="">Selecione</option>
                                @foreach($categories->where('type', 'despesa') as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="despesa_account" class="form-label">Conta</label>
                            <select class="form-select" id="despesa_account" name="account_id">
                                <option value="">Selecione</option>
                                @foreach(($accountsActive ?? $accounts) as $account)
                                    <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="despesa_cost_center" class="form-label">Centro de custo</label>
                            <select class="form-select" id="despesa_cost_center" name="cost_center_id">
                                <option value="">Selecione</option>
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}" {{ old('cost_center_id') == $costCenter->id ? 'selected' : '' }}>
                                        {{ $costCenter->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="despesa_payment_type" class="form-label">Tipo de pagamento</label>
                            <select class="form-select" id="despesa_payment_type" name="payment_type">
                                <option value="unico" {{ old('payment_type', 'unico') == 'unico' ? 'selected' : '' }}>Único</option>
                                <option value="parcelado" {{ old('payment_type') == 'parcelado' ? 'selected' : '' }}>Parcelado</option>
                            </select>
                        </div>
                    </div>

                    <div class="row d-none" id="despesa_installments_wrapper">
                        <div class="col-md-4 mb-3">
                            <label for="despesa_installments_count" class="form-label">Em quantas vezes? <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('installments_count') is-invalid @enderror"
                                   id="despesa_installments_count" name="installments_count"
                                   min="2" max="60" value="{{ old('installments_count', 2) }}"
                                   placeholder="Ex: 3">
                            @error('installments_count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">O valor total será dividido igualmente. As parcelas serão lançadas nos meses seguintes, no mesmo dia.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="despesa_document" class="form-label">Doc nº</label>
                            <input type="text" class="form-control" id="despesa_document" name="document_number" 
                                   value="{{ old('document_number') }}" placeholder="Número do documento">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="despesa_competence" class="form-label">Competência</label>
                            <input type="date" class="form-control" id="despesa_competence" name="competence_date" 
                                   value="{{ old('competence_date') }}">
                        </div>
                        <div class="col-md-4 mb-3 despesa-due-date-field d-none">
                            <label for="despesa_due_date" class="form-label">Vencimento</label>
                            <input type="date" class="form-control" id="despesa_due_date" name="due_date"
                                   value="{{ old('due_date') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="despesa_notes" class="form-label">Anotações</label>
                        <textarea class="form-control" id="despesa_notes" name="notes" rows="3" 
                                  placeholder="Digite anotações...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Arquivos <span id="despesa_file_count">0</span>/5
                            <span class="text-danger d-none" id="despesa_receipt_required">* Recibo assinado obrigatório</span>
                        </label>
                        <div class="alert alert-warning py-2 small d-none" id="despesa_receipt_hint">
                            Pagamento a membro exige o recibo de pagamento assinado por quem recebeu (foto ou PDF).
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('despesa_attachments_upload').click()">
                                <i class="bx bx-upload me-1"></i>Anexar arquivo
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('despesa_attachments_camera').click()">
                                <i class="bx bx-camera me-1"></i>Tirar foto
                            </button>
                        </div>
                        <small class="text-muted d-block mb-2">Imagens ou PDF. Máx. 10MB por arquivo. No celular, use &quot;Tirar foto&quot; para abrir a câmera.</small>
                        <input type="file" class="d-none" id="despesa_attachments_upload" multiple accept="image/*,application/pdf">
                        <input type="file" class="d-none" id="despesa_attachments_camera" accept="image/*" capture="environment">
                        <input type="file" class="d-none" id="despesa_attachments" name="attachments[]" multiple>
                        <div id="despesa_files_preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="save_action" value="new" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Salvar e novo
                    </button>
                    <button type="submit" name="save_action" value="close" class="btn" style="background-color: #20c997; color: white;">
                        <i class="bx bx-check me-1"></i>Salvar e fechar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal: Checkout Mercado Pago -->
<div class="modal fade" id="mercadoPagoModal" tabindex="-1" aria-labelledby="mercadoPagoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="mercadoPagoModalLabel">
                    <i class="bx bx-credit-card me-2"></i>Checkout transparente Mercado Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <div><strong>Transação:</strong> <span id="mp_transaction_description">-</span></div>
                    <div><strong>Valor:</strong> R$ <span id="mp_transaction_amount">0,00</span></div>
                </div>

                <div id="mp_checkout_feedback"></div>

                <ul class="nav nav-tabs mb-3" id="mpCheckoutTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mp-pix-tab" data-bs-toggle="tab" data-bs-target="#mp-pix-pane" type="button" role="tab">
                            PIX
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mp-card-tab" data-bs-toggle="tab" data-bs-target="#mp-card-pane" type="button" role="tab">
                            Cartão de crédito
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="mp-pix-pane" role="tabpanel" aria-labelledby="mp-pix-tab">
                        <form id="mpPixForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="mp_pix_email" class="form-label">E-mail do pagador</label>
                                    <input type="email" class="form-control" id="mp_pix_email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mp_pix_document" class="form-label">CPF do pagador</label>
                                    <input type="text" class="form-control" id="mp_pix_document" placeholder="Somente números" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success" id="mp_pix_submit_btn">
                                <i class="bx bx-qr me-1"></i>Gerar PIX
                            </button>
                        </form>

                        <div id="mp_pix_result" class="mt-3 d-none">
                            <div class="card border-success">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="bx bx-qr me-1"></i>PIX gerado</h6>
                                    <div class="text-center mb-3">
                                        <img id="mp_pix_qr_image" alt="QR Code PIX" style="max-width: 260px; width: 100%;">
                                    </div>
                                    <label class="form-label">Código copia e cola</label>
                                    <textarea id="mp_pix_qr_text" class="form-control" rows="4" readonly></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="mp-card-pane" role="tabpanel" aria-labelledby="mp-card-tab">
                        <form id="mp-card-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="form-checkout__cardholderEmail" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="form-checkout__cardholderEmail" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="form-checkout__identificationNumber" class="form-label">CPF</label>
                                    <input type="text" class="form-control" id="form-checkout__identificationNumber" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="form-checkout__cardholderName" class="form-label">Titular do cartão</label>
                                    <input type="text" class="form-control" id="form-checkout__cardholderName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="form-checkout__cardNumber" class="form-label">Número do cartão</label>
                                    <input type="text" class="form-control" id="form-checkout__cardNumber" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="form-checkout__expirationDate" class="form-label">Validade (MM/AA)</label>
                                    <input type="text" class="form-control" id="form-checkout__expirationDate" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="form-checkout__securityCode" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="form-checkout__securityCode" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="form-checkout__installments" class="form-label">Parcelas</label>
                                    <select class="form-select" id="form-checkout__installments" required></select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="form-checkout__paymentMethod" class="form-label">Bandeira</label>
                                    <select class="form-select" id="form-checkout__paymentMethod" required></select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="form-checkout__issuer" class="form-label">Emissor</label>
                                    <select class="form-select" id="form-checkout__issuer" required></select>
                                </div>
                            </div>

                            <select id="form-checkout__identificationType" class="d-none"></select>
                            <button type="submit" class="btn btn-primary" id="mp_card_submit_btn">
                                <i class="bx bx-credit-card me-1"></i>Pagar com cartão
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('continue_adding'))
<div class="modal fade" id="continueAddingModal" tabindex="-1" aria-labelledby="continueAddingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="continueAddingModalLabel">
                    <i class="bx bx-check-circle me-2"></i>Salvo com sucesso
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">{{ session('success') }}</p>
                <p class="mb-0 fw-semibold">Continuar adicionando {{ session('continue_adding') === 'despesa' ? 'despesas' : 'receitas' }}?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
                <button type="button" class="btn btn-primary" id="continueAddingYesBtn">Sim</button>
            </div>
        </div>
    </div>
</div>
@endif

</div>

@push('scripts')
@if(!empty($mercadoPagoPublicKey))
<script src="https://sdk.mercadopago.com/js/v2"></script>
@endif
<script>

    const continueAddingType = @json(session('continue_adding'));
    const continueAddingModalEl = document.getElementById('continueAddingModal');
    if (continueAddingModalEl && continueAddingType) {
        const continueAddingModal = new bootstrap.Modal(continueAddingModalEl);
        continueAddingModal.show();
        document.getElementById('continueAddingYesBtn')?.addEventListener('click', function () {
            continueAddingModal.hide();
            const targetId = continueAddingType === 'despesa' ? 'createDespesaModal' : 'createReceitaModal';
            const nextModalEl = document.getElementById(targetId);
            if (!nextModalEl) return;
            continueAddingModalEl.addEventListener('hidden.bs.modal', function () {
                bootstrap.Modal.getOrCreateInstance(nextModalEl).show();
            }, { once: true });
        });
    }

    const mercadoPagoPublicKey = @json($mercadoPagoPublicKey ?? '');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const mercadoPagoModalEl = document.getElementById('mercadoPagoModal');
    const mercadoPagoModal = mercadoPagoModalEl ? new bootstrap.Modal(mercadoPagoModalEl) : null;
    const checkoutState = {
        transactionId: null,
        amount: 0,
        description: '',
    };
    let cardFormInstance = null;

    function formatMoney(value) {
        return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function showCheckoutFeedback(message, type = 'info') {
        const el = document.getElementById('mp_checkout_feedback');
        if (!el) return;
        el.innerHTML = `<div class="alert alert-${type} mb-3">${message}</div>`;
    }

    function clearCheckoutFeedback() {
        const el = document.getElementById('mp_checkout_feedback');
        if (!el) return;
        el.innerHTML = '';
    }

    function resetCheckoutModal() {
        clearCheckoutFeedback();
        const pixResult = document.getElementById('mp_pix_result');
        if (pixResult) {
            pixResult.classList.add('d-none');
        }
        const pixText = document.getElementById('mp_pix_qr_text');
        if (pixText) {
            pixText.value = '';
        }
        const pixImg = document.getElementById('mp_pix_qr_image');
        if (pixImg) {
            pixImg.removeAttribute('src');
        }
    }

    function updateCheckoutHeader() {
        const descriptionEl = document.getElementById('mp_transaction_description');
        const amountEl = document.getElementById('mp_transaction_amount');
        if (descriptionEl) {
            descriptionEl.textContent = checkoutState.description || '-';
        }
        if (amountEl) {
            amountEl.textContent = formatMoney(checkoutState.amount);
        }
    }

    function ensureCardForm() {
        if (!mercadoPagoPublicKey || !window.MercadoPago || cardFormInstance) {
            return;
        }

        const mp = new window.MercadoPago(mercadoPagoPublicKey, {
            locale: 'pt-BR',
        });

        cardFormInstance = mp.cardForm({
            amount: String(checkoutState.amount || 0),
            iframe: false,
            form: {
                id: 'mp-card-form',
                cardNumber: {
                    id: 'form-checkout__cardNumber',
                    placeholder: 'Número do cartão',
                },
                expirationDate: {
                    id: 'form-checkout__expirationDate',
                    placeholder: 'MM/AA',
                },
                securityCode: {
                    id: 'form-checkout__securityCode',
                    placeholder: 'CVV',
                },
                cardholderName: {
                    id: 'form-checkout__cardholderName',
                    placeholder: 'Titular do cartão',
                },
                issuer: {
                    id: 'form-checkout__issuer',
                    placeholder: 'Banco emissor',
                },
                installments: {
                    id: 'form-checkout__installments',
                    placeholder: 'Parcelas',
                },
                identificationType: {
                    id: 'form-checkout__identificationType',
                    placeholder: 'Tipo',
                },
                identificationNumber: {
                    id: 'form-checkout__identificationNumber',
                    placeholder: 'CPF',
                },
                cardholderEmail: {
                    id: 'form-checkout__cardholderEmail',
                    placeholder: 'E-mail',
                },
                paymentMethod: {
                    id: 'form-checkout__paymentMethod',
                    placeholder: 'Bandeira',
                },
            },
            callbacks: {
                onSubmit: function(event) {
                    event.preventDefault();
                    submitCardPayment();
                },
                onError: function(error) {
                    if (!error) return;
                    showCheckoutFeedback('Erro no formulário do cartão: ' + (error.message || 'verifique os dados informados.'), 'danger');
                },
            },
        });
    }

    function submitCardPayment() {
        if (!checkoutState.transactionId) {
            showCheckoutFeedback('Selecione uma transação antes de pagar.', 'danger');
            return;
        }
        if (!cardFormInstance) {
            showCheckoutFeedback('Formulário de cartão não inicializado.', 'danger');
            return;
        }

        const btn = document.getElementById('mp_card_submit_btn');
        if (btn) btn.disabled = true;
        clearCheckoutFeedback();

        const data = cardFormInstance.getCardFormData();
        fetch('{{ route("financial.checkout.card", ":id") }}'.replace(':id', checkoutState.transactionId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                token: data.token,
                payer_email: data.cardholderEmail,
                payer_document: data.identificationNumber,
                payment_method_id: data.paymentMethodId,
                issuer_id: data.issuerId,
                installments: Number(data.installments || 1),
            }),
        })
            .then(response => response.json().catch(() => ({})))
            .then(res => {
                if (res.success) {
                    showCheckoutFeedback('Pagamento com cartão enviado para processamento. Status atual: ' + (res.data.status || 'pending') + '.', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                    return;
                }
                showCheckoutFeedback(res.error || 'Não foi possível processar o cartão.', 'danger');
            })
            .catch(() => {
                showCheckoutFeedback('Erro de conexão ao enviar pagamento de cartão.', 'danger');
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
    }

    document.querySelectorAll('.mp-open-checkout').forEach(function(button) {
        button.addEventListener('click', function() {
            checkoutState.transactionId = this.dataset.transactionId;
            checkoutState.description = this.dataset.transactionDescription || '';
            checkoutState.amount = Number(this.dataset.transactionAmount || 0);
            updateCheckoutHeader();
            resetCheckoutModal();
            ensureCardForm();
            if (mercadoPagoModal) {
                mercadoPagoModal.show();
            }
        });
    });

    document.getElementById('mpPixForm')?.addEventListener('submit', function(event) {
        event.preventDefault();
        if (!checkoutState.transactionId) {
            showCheckoutFeedback('Selecione uma transação antes de gerar o PIX.', 'danger');
            return;
        }

        const email = document.getElementById('mp_pix_email')?.value?.trim();
        const documentValue = document.getElementById('mp_pix_document')?.value?.trim();
        if (!email || !documentValue) {
            showCheckoutFeedback('Informe e-mail e CPF para gerar o PIX.', 'warning');
            return;
        }

        const btn = document.getElementById('mp_pix_submit_btn');
        if (btn) btn.disabled = true;
        clearCheckoutFeedback();

        fetch('{{ route("financial.checkout.pix", ":id") }}'.replace(':id', checkoutState.transactionId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                payer_email: email,
                payer_document: documentValue,
            }),
        })
            .then(response => response.json().catch(() => ({})))
            .then(res => {
                if (res.success) {
                    const pixResult = document.getElementById('mp_pix_result');
                    const qrImage = document.getElementById('mp_pix_qr_image');
                    const qrText = document.getElementById('mp_pix_qr_text');
                    if (pixResult) pixResult.classList.remove('d-none');
                    if (qrImage && res.data.qr_code_base64) {
                        qrImage.src = 'data:image/png;base64,' + res.data.qr_code_base64;
                    }
                    if (qrText) {
                        qrText.value = res.data.qr_code_text || '';
                    }
                    showCheckoutFeedback('PIX criado com sucesso. A baixa ocorrerá automaticamente via webhook quando o pagamento for aprovado.', 'success');
                    return;
                }
                showCheckoutFeedback(res.error || 'Não foi possível gerar o PIX.', 'danger');
            })
            .catch(() => {
                showCheckoutFeedback('Erro de conexão ao gerar PIX.', 'danger');
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
    });

    if (!mercadoPagoPublicKey) {
        document.querySelectorAll('.mp-open-checkout').forEach(function(button) {
            button.setAttribute('disabled', 'disabled');
            button.setAttribute('title', 'Configure MP_PUBLIC_KEY no .env para habilitar checkout.');
        });
    }

    // Toggle campo "Outros" no modal de edição (receita)
    document.getElementById('edit_member_id')?.addEventListener('change', function() {
        const otherField = document.getElementById('edit_other_name');
        
        if (this.value === 'other') {
            otherField?.classList.remove('d-none');
            otherField?.setAttribute('required', 'required');
        } else if (this.value === '' || !this.value) {
            otherField?.classList.add('d-none');
            otherField?.removeAttribute('required');
            otherField.value = '';
        } else {
            otherField?.classList.add('d-none');
            otherField?.removeAttribute('required');
            otherField.value = '';
        }
    });

    function escapeAttachmentName(name) {
        const div = document.createElement('div');
        div.textContent = name || 'arquivo';
        return div.innerHTML;
    }

    function normalizeCameraFileName(file) {
        const genericNames = ['image.jpg', 'image.jpeg', 'image.png', 'blob', ''];
        if (!genericNames.includes((file.name || '').toLowerCase())) {
            return file;
        }

        const ext = file.type === 'image/png' ? 'png' : 'jpg';
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        return new File([file], `foto-${timestamp}.${ext}`, { type: file.type || 'image/jpeg' });
    }

    function createAttachmentManager(config) {
        const mainInput = document.getElementById(config.mainInputId);
        const uploadInput = document.getElementById(config.uploadInputId);
        const cameraInput = document.getElementById(config.cameraInputId);
        const preview = document.getElementById(config.previewId);
        const countEl = document.getElementById(config.countId);
        const maxFiles = config.maxFiles || 5;
        let newFiles = [];

        function getExistingCount() {
            return preview ? preview.querySelectorAll('[data-existing-attachment]').length : 0;
        }

        function getTotalCount() {
            return getExistingCount() + newFiles.length;
        }

        function updateCount() {
            if (countEl) {
                countEl.textContent = getTotalCount();
            }
        }

        function syncMainInput() {
            if (!mainInput) return;
            const dt = new DataTransfer();
            newFiles.forEach(file => dt.items.add(file));
            mainInput.files = dt.files;
        }

        function renderNewFiles() {
            if (!preview) return;
            preview.querySelectorAll('[data-new-attachment]').forEach(el => el.remove());

            newFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded';
                div.dataset.newAttachment = '1';
                div.innerHTML = `
                    <span class="small"><i class="bx bx-image me-1"></i>${escapeAttachmentName(file.name)}</span>
                    <button type="button" class="btn btn-sm btn-danger" title="Remover">
                        <i class="bx bx-trash"></i>
                    </button>
                `;
                div.querySelector('button').addEventListener('click', () => removeNewFile(index));
                preview.appendChild(div);
            });

            updateCount();
        }

        function addFiles(fileList) {
            for (const file of Array.from(fileList || [])) {
                if (getTotalCount() >= maxFiles) {
                    alert(`Máximo de ${maxFiles} arquivos permitidos`);
                    break;
                }
                newFiles.push(normalizeCameraFileName(file));
            }

            syncMainInput();
            renderNewFiles();
        }

        function removeNewFile(index) {
            newFiles.splice(index, 1);
            syncMainInput();
            renderNewFiles();
        }

        function resetNewFiles() {
            newFiles = [];
            syncMainInput();
            if (preview) {
                preview.querySelectorAll('[data-new-attachment]').forEach(el => el.remove());
            }
            updateCount();
        }

        uploadInput?.addEventListener('change', function(e) {
            if (e.target.files?.length) {
                addFiles(e.target.files);
            }
            e.target.value = '';
        });

        cameraInput?.addEventListener('change', function(e) {
            if (e.target.files?.length) {
                addFiles(e.target.files);
            }
            e.target.value = '';
        });

        return { addFiles, resetNewFiles, updateCount, getTotalCount };
    }

    const receitaAttachmentManager = createAttachmentManager({
        mainInputId: 'receita_attachments',
        uploadInputId: 'receita_attachments_upload',
        cameraInputId: 'receita_attachments_camera',
        previewId: 'receita_files_preview',
        countId: 'receita_file_count',
    });

    const despesaAttachmentManager = createAttachmentManager({
        mainInputId: 'despesa_attachments',
        uploadInputId: 'despesa_attachments_upload',
        cameraInputId: 'despesa_attachments_camera',
        previewId: 'despesa_files_preview',
        countId: 'despesa_file_count',
    });

    const editAttachmentManager = createAttachmentManager({
        mainInputId: 'edit_attachments',
        uploadInputId: 'edit_attachments_upload',
        cameraInputId: 'edit_attachments_camera',
        previewId: 'edit_files_preview',
        countId: 'edit_file_count',
    });

    function categoryIsDizimo(select) {
        const option = select?.selectedOptions?.[0];
        if (!option || !option.value) return false;
        if (option.dataset.dizimo === '1') return true;
        const slug = (option.dataset.slug || '').toLowerCase();
        if (slug === 'dizimo') return true;
        const name = (option.textContent || '').toLowerCase();
        return name.includes('dízimo') || name.includes('dizimo');
    }

    function setDisabledFormControls(container, disabled) {
        container?.querySelectorAll('select, input, textarea').forEach(function(el) {
            el.disabled = disabled;
        });
    }

    function syncDespesaPayeeUi(select, otherField, requiredId, hintId) {
        const isMember = select && select.value && select.value !== 'other';
        const isOther = select?.value === 'other';
        document.getElementById(requiredId)?.classList.toggle('d-none', !isMember);
        document.getElementById(hintId)?.classList.toggle('d-none', !isMember);
        if (!otherField) return;
        if (isOther) {
            otherField.classList.remove('d-none');
            otherField.setAttribute('required', 'required');
        } else {
            otherField.classList.add('d-none');
            otherField.removeAttribute('required');
            otherField.value = '';
        }
    }

    function syncReceitaDonorRequired(categorySelect, memberSelect, requiredMark, otherField) {
        const required = categoryIsDizimo(categorySelect);
        requiredMark?.classList.toggle('d-none', !required);
        if (!memberSelect) return;
        if (required) {
            memberSelect.setAttribute('required', 'required');
        } else {
            memberSelect.removeAttribute('required');
        }
        if (otherField && memberSelect.value !== 'other') {
            otherField.removeAttribute('required');
        }
    }

    document.getElementById('receita_received_from')?.addEventListener('change', function() {
        const otherField = document.getElementById('receita_other_name');
        
        if (this.value === 'other') {
            otherField?.classList.remove('d-none');
            otherField?.setAttribute('required', 'required');
            // Não remover required do select para manter validação HTML5
        } else if (this.value === '' || !this.value) {
            otherField?.classList.add('d-none');
            otherField?.removeAttribute('required');
            otherField.value = '';
        } else {
            otherField?.classList.add('d-none');
            otherField?.removeAttribute('required');
            otherField.value = '';
        }
    });

    document.getElementById('despesa_payee')?.addEventListener('change', function() {
        syncDespesaPayeeUi(
            this,
            document.getElementById('despesa_other_name'),
            'despesa_receipt_required',
            'despesa_receipt_hint'
        );
    });
    syncDespesaPayeeUi(
        document.getElementById('despesa_payee'),
        document.getElementById('despesa_other_name'),
        'despesa_receipt_required',
        'despesa_receipt_hint'
    );

    document.getElementById('edit_payee_id')?.addEventListener('change', function() {
        syncDespesaPayeeUi(
            this,
            document.getElementById('edit_payee_other_name'),
            'edit_receipt_required',
            'edit_receipt_hint'
        );
    });

    // Validação antes de enviar o formulário de receita
    document.getElementById('receitaForm')?.addEventListener('submit', function(e) {
        const receivedFrom = document.getElementById('receita_received_from');
        const otherField = document.getElementById('receita_other_name');
        const categorySelect = document.getElementById('receita_category');
        const requiresDonor = categoryIsDizimo(categorySelect);
        
        if (requiresDonor && (!receivedFrom.value || receivedFrom.value === '')) {
            e.preventDefault();
            alert('Para dízimo, selecione de quem foi recebido ou escolha "Outros".');
            receivedFrom.focus();
            return false;
        }
        
        if (receivedFrom.value === 'other') {
            if (!otherField.value || !otherField.value.trim()) {
                e.preventDefault();
                alert('Informe de quem foi recebido quando selecionar "Outros".');
                otherField.focus();
                return false;
            }
            receivedFrom.removeAttribute('name');
            receivedFrom.setAttribute('name', 'member_id_hidden');
        } else if (otherField) {
            otherField.removeAttribute('name');
        }
    });

    document.getElementById('despesaForm')?.addEventListener('submit', function(e) {
        const payee = document.getElementById('despesa_payee');
        const otherField = document.getElementById('despesa_other_name');

        if (payee?.value === 'other') {
            if (!otherField?.value?.trim()) {
                e.preventDefault();
                alert('Informe a quem foi pago quando selecionar "Outros".');
                otherField?.focus();
                return false;
            }
            payee.removeAttribute('name');
            payee.setAttribute('name', 'member_id_hidden');
        } else if (otherField && payee?.value !== 'other') {
            otherField.removeAttribute('name');
        }

        if (payee?.value && payee.value !== 'other' && despesaAttachmentManager.getTotalCount() < 1) {
            e.preventDefault();
            alert('Anexe o recibo de pagamento assinado pela pessoa que recebeu.');
            return false;
        }
    });

    document.getElementById('editTransactionForm')?.addEventListener('submit', function(e) {
        const payee = document.getElementById('edit_payee_id');
        if (payee?.disabled) {
            return;
        }
        const otherField = document.getElementById('edit_payee_other_name');
        if (payee?.value === 'other') {
            if (!otherField?.value?.trim()) {
                e.preventDefault();
                alert('Informe a quem foi pago quando selecionar "Outros".');
                otherField?.focus();
                return false;
            }
            payee.removeAttribute('name');
            payee.setAttribute('name', 'member_id_hidden');
        }

        if (payee?.value && payee.value !== 'other' && editAttachmentManager.getTotalCount() < 1) {
            e.preventDefault();
            alert('Anexe o recibo de pagamento assinado pela pessoa que recebeu.');
            return false;
        }
    });

    document.getElementById('receita_category')?.addEventListener('change', function() {
        syncReceitaDonorRequired(
            this,
            document.getElementById('receita_received_from'),
            document.getElementById('receita_donor_required'),
            document.getElementById('receita_other_name')
        );
    });
    syncReceitaDonorRequired(
        document.getElementById('receita_category'),
        document.getElementById('receita_received_from'),
        document.getElementById('receita_donor_required'),
        document.getElementById('receita_other_name')
    );
    document.getElementById('edit_receita_category_id')?.addEventListener('change', function() {
        syncReceitaDonorRequired(
            this,
            document.getElementById('edit_member_id'),
            document.getElementById('edit_donor_required'),
            document.getElementById('edit_other_name')
        );
    });

    // Selecionar todos os checkboxes
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.transaction-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    // Editar descrição ao clicar
    document.querySelectorAll('.edit-description').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            const transactionId = this.dataset.transactionId;
            const currentDescription = this.dataset.description;
            
            const newDescription = prompt('Editar descrição:', currentDescription);
            if (newDescription !== null && newDescription.trim() !== '' && newDescription !== currentDescription) {
                fetch('{{ route("financial.transactions.update-description", ":id") }}'.replace(':id', transactionId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        description: newDescription.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.textContent = newDescription.trim();
                        this.dataset.description = newDescription.trim();
                        alert('Descrição atualizada com sucesso!');
                    } else {
                        alert('Erro ao atualizar descrição.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao atualizar descrição.');
                });
            }
        });
    });

    // Detalhes da transação
    let currentViewTransactionId = null;
    document.querySelectorAll('.view-transaction-details').forEach(function(button) {
        button.addEventListener('click', function() {
            loadTransactionDetails(this.dataset.transactionId);
        });
    });

    function loadTransactionDetails(transactionId) {
        currentViewTransactionId = transactionId;
        const loading = document.getElementById('viewTransactionLoading');
        const content = document.getElementById('viewTransactionContent');
        loading.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Carregando detalhes...';
        loading.classList.remove('d-none', 'text-danger');
        loading.classList.add('text-muted');
        content.classList.add('d-none');

        const modal = new bootstrap.Modal(document.getElementById('viewTransactionModal'));
        modal.show();

        fetch('{{ route("financial.transactions.show", ":id") }}'.replace(':id', transactionId), {
            headers: { 'Accept': 'application/json' }
        })
            .then(response => {
                if (!response.ok) throw new Error('Falha ao carregar');
                return response.json();
            })
            .then(data => {
                populateViewModal(data);
                loading.classList.add('d-none');
                content.classList.remove('d-none');
            })
            .catch(() => {
                loading.classList.remove('text-muted');
                loading.classList.add('text-danger');
                loading.innerHTML = 'Erro ao carregar detalhes da transação.';
            });
    }

    function populateViewModal(tx) {
        const isReceita = tx.type === 'receita';
        document.getElementById('viewTransactionModalLabel').innerHTML =
            '<i class="bx bx-show me-2"></i>Detalhes da ' + (isReceita ? 'Receita' : 'Despesa');
        document.getElementById('viewTransactionModalHeader').style.backgroundColor = isReceita ? '#198754' : '#dc3545';

        document.getElementById('view_description').textContent = tx.description || '—';
        document.getElementById('view_amount').textContent = (isReceita ? '+' : '-') + (tx.amount_formatted || 'R$ 0,00');
        document.getElementById('view_amount').className = 'fs-3 fw-bold ' + (isReceita ? 'text-success' : 'text-danger');

        const statusBadge = document.getElementById('view_status_badge');
        statusBadge.textContent = tx.status_label || '—';
        statusBadge.className = 'badge ' + (tx.is_paid ? 'bg-info' : 'bg-secondary');

        document.getElementById('view_type_badge').textContent = tx.type_label || '—';
        document.getElementById('view_transaction_date').textContent = tx.transaction_date || '—';
        document.getElementById('view_created_at').textContent = tx.created_at || '—';
        document.getElementById('view_contato').textContent = tx.contato || '—';
        document.getElementById('view_category').textContent = tx.category || '—';
        document.getElementById('view_account').textContent = tx.account || '—';
        document.getElementById('view_cost_center').textContent = tx.cost_center || '—';
        document.getElementById('view_payment_type').textContent = tx.payment_type || '—';
        document.getElementById('view_document_number').textContent = tx.document_number || '—';
        document.getElementById('view_competence_date').textContent = tx.competence_date || '—';
        document.getElementById('view_due_date').textContent = tx.due_date || '—';
        document.getElementById('view_created_by').textContent = tx.created_by || '—';
        document.getElementById('view_notes').textContent = tx.notes || '—';

        const attachmentsEl = document.getElementById('view_attachments');
        if (tx.attachments && tx.attachments.length) {
            attachmentsEl.innerHTML = tx.attachments.map(function(a) {
                return '<span class="badge bg-light text-dark border me-1 mb-1"><i class="bx bx-paperclip me-1"></i>' +
                    (a.file_name || 'arquivo') + '</span>';
            }).join('');
        } else {
            attachmentsEl.textContent = 'Nenhum anexo';
        }

        const editBtn = document.getElementById('viewTransactionEditBtn');
        @if($canEditReceitas || $canEditDespesas)
        const canEditThis = (isReceita && @json($canEditReceitas)) || (!isReceita && @json($canEditDespesas));
        editBtn.classList.toggle('d-none', !canEditThis);
        @else
        editBtn.classList.add('d-none');
        @endif
    }

    document.getElementById('viewTransactionReceiptBtn')?.addEventListener('click', function() {
        if (!currentViewTransactionId) return;
        const url = '{{ route("financial.transactions.receipt", ":id") }}'.replace(':id', currentViewTransactionId);
        window.open(url, '_blank', 'width=800,height=600');
    });

    document.getElementById('viewTransactionEditBtn')?.addEventListener('click', function() {
        if (!currentViewTransactionId) return;
        const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewTransactionModal'));
        viewModal?.hide();
        loadTransactionForEdit(currentViewTransactionId);
    });

    // Editar transação
    document.querySelectorAll('.edit-transaction').forEach(function(button) {
        button.addEventListener('click', function() {
            const transactionId = this.dataset.transactionId;
            loadTransactionForEdit(transactionId);
        });
    });

    // Função para carregar transação para edição
    function loadTransactionForEdit(transactionId) {
        fetch('{{ route("financial.transactions.edit", ":id") }}'.replace(':id', transactionId))
            .then(response => response.json())
            .then(data => {
                populateEditModal(data);
                const modal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao carregar transação para edição.');
            });
    }

    // Função para preencher modal de edição
    function populateEditModal(transaction) {
        // Limpar formulário
        const form = document.getElementById('editTransactionForm');
        form.reset();
        
        // Definir action do formulário
        form.action = '{{ route("financial.transactions.update", ":id") }}'.replace(':id', transaction.id);
        
        // Preencher campos básicos
        document.getElementById('edit_transaction_date').value = transaction.transaction_date || '';
        document.getElementById('edit_description').value = transaction.description || '';
        document.getElementById('edit_amount').value = transaction.amount || '';
        document.getElementById('edit_is_paid').checked = transaction.is_paid || false;
        document.getElementById('edit_account_id').value = transaction.account_id || '';
        document.getElementById('edit_cost_center_id').value = transaction.cost_center_id || '';
        document.getElementById('edit_payment_type').value = transaction.payment_type || 'unico';
        document.getElementById('edit_document_number').value = transaction.document_number || '';
        document.getElementById('edit_competence_date').value = transaction.competence_date || '';
        document.getElementById('edit_due_date').value = transaction.due_date || '';
        document.getElementById('edit_notes').value = transaction.notes || '';
        toggleDueDateField('edit_is_paid', 'edit_due_date_wrapper');
        toggleWhatsappReceiptField(transaction);
        
        // Atualizar título do modal
        const modalTitle = document.getElementById('editTransactionModalLabel');
        modalTitle.innerHTML = '<i class="bx bx-edit me-2"></i>Editar ' + (transaction.type === 'receita' ? 'Receita' : 'Despesa');
        
        // Atualizar cor do header
        const modalHeader = document.querySelector('#editTransactionModal .modal-header');
        if (transaction.type === 'receita') {
            modalHeader.style.backgroundColor = '#007bff';
        } else {
            modalHeader.style.backgroundColor = '#dc3545';
        }
        
        // Mostrar/esconder campos baseado no tipo
        const receitaFields = document.getElementById('edit_receita_fields');
        const despesaFields = document.getElementById('edit_despesa_fields');
        
        if (transaction.type === 'receita') {
            receitaFields.style.display = 'block';
            despesaFields.style.display = 'none';
            
            // Preencher campo "Recebido de"
            const memberSelect = document.getElementById('edit_member_id');
            const otherNameInput = document.getElementById('edit_other_name');
            
            if (transaction.member_id) {
                memberSelect.value = transaction.member_id;
                otherNameInput.classList.add('d-none');
                otherNameInput.removeAttribute('required');
            } else if (transaction.received_from_other) {
                memberSelect.value = 'other';
                otherNameInput.classList.remove('d-none');
                otherNameInput.value = transaction.received_from_other;
                otherNameInput.setAttribute('required', 'required');
            } else {
                memberSelect.value = '';
                otherNameInput.classList.add('d-none');
                otherNameInput.removeAttribute('required');
            }
            
            // Filtrar categorias de receita
            filterCategories('edit_receita_category_id', 'receita');
            document.getElementById('edit_receita_category_id').value = transaction.category_id || '';
            syncReceitaDonorRequired(
                document.getElementById('edit_receita_category_id'),
                document.getElementById('edit_member_id'),
                document.getElementById('edit_donor_required'),
                document.getElementById('edit_other_name')
            );
        } else {
            receitaFields.style.display = 'none';
            despesaFields.style.display = 'block';
            
            const payeeSelect = document.getElementById('edit_payee_id');
            const payeeOther = document.getElementById('edit_payee_other_name');

            if (transaction.member_id) {
                payeeSelect.value = transaction.member_id;
                payeeOther.classList.add('d-none');
                payeeOther.value = '';
            } else if (transaction.received_from_other) {
                payeeSelect.value = 'other';
                payeeOther.classList.remove('d-none');
                payeeOther.value = transaction.received_from_other;
            } else {
                payeeSelect.value = '';
                payeeOther.classList.add('d-none');
                payeeOther.value = '';
            }

            filterCategories('edit_despesa_category_id', 'despesa');
            document.getElementById('edit_despesa_category_id').value = transaction.category_id || '';
            syncDespesaPayeeUi(payeeSelect, payeeOther, 'edit_receipt_required', 'edit_receipt_hint');
        }

        setDisabledFormControls(receitaFields, transaction.type !== 'receita');
        setDisabledFormControls(despesaFields, transaction.type !== 'despesa');
        
        // Limpar preview de arquivos
        editAttachmentManager.resetNewFiles();
        const editPreview = document.getElementById('edit_files_preview');
        editPreview.innerHTML = '';
        
        // Mostrar anexos existentes
        if (transaction.attachments && transaction.attachments.length > 0) {
            transaction.attachments.forEach((attachment) => {
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded';
                div.dataset.existingAttachment = '1';
                div.innerHTML = `
                    <span class="small"><i class="bx bx-paperclip me-1"></i>${escapeAttachmentName(attachment.file_name)}</span>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeExistingFile(${attachment.id}, this)">
                        <i class="bx bx-trash"></i>
                    </button>
                `;
                editPreview.appendChild(div);
            });
        }

        editAttachmentManager.updateCount();
    }

    // Função para filtrar categorias
    function filterCategories(selectId, type) {
        const select = document.getElementById(selectId);
        const options = select.querySelectorAll('option');
        options.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
            } else {
                const optionType = option.dataset.type;
                option.style.display = (optionType === type) ? 'block' : 'none';
            }
        });
    }

    // Função para remover arquivo existente
    function removeExistingFile(attachmentId, button) {
        if (!confirm('Deseja remover este anexo?')) {
            return;
        }
        
        // Adicionar input hidden para marcar arquivo para exclusão
        const form = document.getElementById('editTransactionForm');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_attachments[]';
        input.value = attachmentId;
        form.appendChild(input);
        
        // Remover do preview
        button.closest('div').remove();
        editAttachmentManager.updateCount();
    }

    // Imprimir recibo
    document.querySelectorAll('.print-receipt').forEach(function(button) {
        button.addEventListener('click', function() {
            const transactionId = this.dataset.transactionId;
            const url = '{{ route("financial.transactions.receipt", ":id") }}'.replace(':id', transactionId);
            window.open(url, '_blank', 'width=800,height=600');
        });
    });

    // Enviar recibo por WhatsApp
    document.querySelectorAll('.send-receipt-whatsapp').forEach(function(button) {
        button.addEventListener('click', function() {
            const transactionId = this.dataset.transactionId;
            const btn = this;
            if (!confirm('Enviar comprovante por WhatsApp para o membro?')) {
                return;
            }

            btn.disabled = true;
            fetch('{{ route("financial.transactions.send-receipt", ":id") }}'.replace(':id', transactionId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    alert('Comprovante enviado por WhatsApp com sucesso!');
                } else {
                    alert(data.error || data.message || 'Não foi possível enviar o comprovante.');
                }
            })
            .catch(() => alert('Erro ao enviar comprovante por WhatsApp. Atualize a página e tente de novo.'))
            .finally(() => { btn.disabled = false; });
        });
    });

    function toggleDueDateField(paidCheckboxId, wrapperId) {
        const paidCheckbox = document.getElementById(paidCheckboxId);
        const wrapper = document.getElementById(wrapperId);
        if (!paidCheckbox || !wrapper) return;
        wrapper.classList.toggle('d-none', paidCheckbox.checked);
    }

    function toggleWhatsappReceiptField(transaction) {
        const wrapper = document.getElementById('edit_whatsapp_receipt_wrapper');
        if (!wrapper) return;
        const show = transaction.type === 'receita' && transaction.member_id;
        wrapper.style.display = show ? 'block' : 'none';
    }

    ['receita_is_paid', 'despesa_is_paid', 'edit_is_paid'].forEach(function(id) {
        const checkbox = document.getElementById(id);
        if (!checkbox) return;
        checkbox.addEventListener('change', function() {
            if (id === 'receita_is_paid') {
                document.querySelectorAll('.receita-due-date-field').forEach(el => el.classList.toggle('d-none', this.checked));
            } else if (id === 'despesa_is_paid') {
                document.querySelectorAll('.despesa-due-date-field').forEach(el => el.classList.toggle('d-none', this.checked));
            } else {
                toggleDueDateField('edit_is_paid', 'edit_due_date_wrapper');
            }
        });
    });

    document.getElementById('receita_is_paid')?.dispatchEvent(new Event('change'));
    document.getElementById('despesa_is_paid')?.dispatchEvent(new Event('change'));

    function toggleDespesaInstallmentsField() {
        const paymentType = document.getElementById('despesa_payment_type');
        const wrapper = document.getElementById('despesa_installments_wrapper');
        const input = document.getElementById('despesa_installments_count');
        if (!paymentType || !wrapper || !input) return;

        const isParcelado = paymentType.value === 'parcelado';
        wrapper.classList.toggle('d-none', !isParcelado);

        if (isParcelado) {
            input.setAttribute('required', 'required');
        } else {
            input.removeAttribute('required');
        }
    }

    document.getElementById('despesa_payment_type')?.addEventListener('change', toggleDespesaInstallmentsField);
    toggleDespesaInstallmentsField();

    document.getElementById('despesaForm')?.addEventListener('submit', function(e) {
        const paymentType = document.getElementById('despesa_payment_type');
        const installmentsInput = document.getElementById('despesa_installments_count');

        if (paymentType?.value === 'parcelado') {
            const installments = parseInt(installmentsInput?.value || '0', 10);
            if (!installments || installments < 2) {
                e.preventDefault();
                alert('Informe em quantas vezes a despesa será parcelada (mínimo 2).');
                installmentsInput?.focus();
                return false;
            }
        }
    });

    // Duplicar transação
    document.querySelectorAll('.duplicate-transaction').forEach(function(button) {
        button.addEventListener('click', function() {
            if (!confirm('Deseja duplicar esta transação?')) {
                return;
            }
            
            const transactionId = this.dataset.transactionId;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("financial.transactions.duplicate", ":id") }}'.replace(':id', transactionId);
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            document.body.appendChild(form);
            form.submit();
        });
    });

    // Variáveis para impressão (renderizadas pelo Blade)
    const printData = {
        startDate: @json($startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : ''),
        endDate: @json($endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : ''),
        total: @json($transactions->total() ?? 0)
    };

    // Função para imprimir transações
    function printTransactions() {
        const printWindow = window.open('', '_blank');
        const list = document.querySelector('.financial-tx-list');
        const tableRows = document.querySelectorAll('.financial-tx-table tbody tr');

        if (!list && !tableRows.length) {
            alert('Nenhuma transação para imprimir.');
            return;
        }

        const nowFormatted = new Date().toLocaleString('pt-BR');
        let rows = '';
        if (tableRows.length) {
            rows = Array.from(tableRows).map(function(tr) {
                return '<tr><td>' + (tr.dataset.description || '') + '</td><td>' + (tr.dataset.status || '') + '</td><td>' + (tr.dataset.meta || '') + '</td><td>' + (tr.dataset.amount || '') + '</td></tr>';
            }).join('');
        } else {
            rows = Array.from(list.querySelectorAll('.financial-tx-card')).map(function(card) {
                const title = (card.querySelector('.financial-tx-card__title')?.textContent || '').trim();
                const amount = (card.querySelector('.financial-tx-card__amount')?.textContent || '').trim();
                const meta = Array.from(card.querySelectorAll('.financial-tx-card__meta li')).map(function(li) {
                    return (li.textContent || '').trim();
                }).join(' | ');
                const status = (card.querySelector('.financial-tx-card__badge')?.textContent || '').trim();
                return '<tr><td>' + title + '</td><td>' + status + '</td><td>' + meta + '</td><td>' + amount + '</td></tr>';
            }).join('');
        }

        printWindow.document.write('<!DOCTYPE html><html><head><title>Transações - ' + nowFormatted + '</title><style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }');
        printWindow.document.write('h1 { text-align: center; margin-bottom: 20px; }');
        printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
        printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
        printWindow.document.write('th { background-color: #f2f2f2; font-weight: bold; }');
        printWindow.document.write('tr:nth-child(even) { background-color: #f9f9f9; }');
        printWindow.document.write('@media print { @page { margin: 1cm; } body { margin: 0; } }');
        printWindow.document.write('</style></head><body>');
        printWindow.document.write('<h1>Relatório de Transações</h1>');
        printWindow.document.write('<p><strong>Período:</strong> ' + printData.startDate + ' até ' + printData.endDate + '</p>');
        printWindow.document.write('<p><strong>Total de transações:</strong> ' + printData.total + '</p>');
        printWindow.document.write('<table><thead><tr><th>Descrição</th><th>Status</th><th>Detalhes</th><th>Valor</th></tr></thead><tbody>' + rows + '</tbody></table>');
        printWindow.document.write('<p style="margin-top: 20px; text-align: right; font-size: 10px;">Impresso em: ' + nowFormatted + '</p>');
        printWindow.document.write('</body></html>');

        printWindow.document.close();
        printWindow.focus();

        setTimeout(() => {
            printWindow.print();
        }, 250);
    }
</script>
@endpush
@endsection
