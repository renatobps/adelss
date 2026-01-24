@extends('layouts.porto')

@section('title', 'Transações')

@section('page-title', 'Transações')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Transações</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canCreateReceitas = $isAdmin || $user->hasPermission('financial.receitas.create') || $user->hasPermission('financial.receitas.manage');
    $canCreateDespesas = $isAdmin || $user->hasPermission('financial.despesas.create') || $user->hasPermission('financial.despesas.manage');
    $canEditReceitas = $isAdmin || $user->hasPermission('financial.receitas.edit') || $user->hasPermission('financial.receitas.manage');
    $canEditDespesas = $isAdmin || $user->hasPermission('financial.despesas.edit') || $user->hasPermission('financial.despesas.manage');
    $canDeleteReceitas = $isAdmin || $user->hasPermission('financial.receitas.delete') || $user->hasPermission('financial.receitas.manage');
    $canDeleteDespesas = $isAdmin || $user->hasPermission('financial.despesas.delete') || $user->hasPermission('financial.despesas.manage');
@endphp

<!-- Header -->
<div class="alert alert-info mb-4" style="background-color: #e3f2fd; color: #1976d2; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Gerencie suas transações financeiras.
</div>

@if(session('success'))
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
                <canvas id="monthlyTransactionChart" height="80"></canvas>
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
<div class="card mb-4" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        <form method="GET" action="{{ route('financial.transactions.index') }}" id="filterForm">
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
                    <select class="form-select form-select-sm" name="type">
                        <option value="">Todos</option>
                        <option value="receita" {{ request('type') == 'receita' ? 'selected' : '' }}>Receitas</option>
                        <option value="despesa" {{ request('type') == 'despesa' ? 'selected' : '' }}>Despesas</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label small">Status:</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">Todos</option>
                        <option value="recebido" {{ request('status') == 'recebido' ? 'selected' : '' }}>Recebido</option>
                        <option value="pago" {{ request('status') == 'pago' ? 'selected' : '' }}>Pago</option>
                        <option value="a_receber" {{ request('status') == 'a_receber' ? 'selected' : '' }}>A receber</option>
                        <option value="a_pagar" {{ request('status') == 'a_pagar' ? 'selected' : '' }}>A pagar</option>
                    </select>
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
                    <label class="form-label small">Categorias:</label>
                    <select class="form-select form-select-sm" name="category_id">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
        <div class="row mt-3">
            <div class="col-md-6">
                <button type="submit" form="filterForm" class="btn btn-primary btn-sm">
                    <i class="bx bx-filter me-1"></i>Aplicar Filtros
                </button>
                @if(request()->hasAny(['type', 'status', 'category_id', 'account_id', 'cost_center_id', 'start_date', 'end_date']))
                    <a href="{{ route('financial.transactions.index') }}" class="btn btn-default btn-sm">
                        <i class="bx bx-x me-1"></i>Limpar
                    </a>
                @endif
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="bx bx-upload me-1"></i>Importar
                </button>
                @if($canCreateReceitas)
                <button type="button" class="btn btn-success btn-sm me-1" data-bs-toggle="modal" data-bs-target="#createReceitaModal">
                    <i class="bx bx-plus me-1"></i>+ Adicionar receita
                </button>
                @endif
                @if($canCreateDespesas)
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#createDespesaModal">
                    <i class="bx bx-plus me-1"></i>+ Adicionar despesa
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Transações -->
<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <strong>Resultados: {{ $transactions->total() }} transações</strong>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()" form="filterForm" name="per_page">
                    <option value="50" {{ request('per_page', 100) == 50 ? 'selected' : '' }}>50 resultados por página</option>
                    <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100 resultados por página</option>
                    <option value="200" {{ request('per_page', 100) == 200 ? 'selected' : '' }}>200 resultados por página</option>
                </select>
                <input type="text" class="form-control form-control-sm" placeholder="Pesquisar" name="search" form="filterForm" value="{{ request('search') }}" style="width: 200px;">
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
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Total</th>
                            <th>Contato</th>
                            <th>Categoria</th>
                            <th>Conta</th>
                            <th class="text-end" style="width: 100px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input transaction-checkbox" value="{{ $transaction->id }}">
                                </td>
                                <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td>
                                    <a href="#" class="text-primary edit-description" 
                                       data-transaction-id="{{ $transaction->id }}"
                                       data-description="{{ $transaction->description }}">
                                        {{ $transaction->description }}
                                    </a>
                                </td>
                                <td>
                                    <span class="{{ $transaction->type === 'despesa' ? 'text-danger' : 'text-success' }}">
                                        {{ $transaction->type === 'despesa' ? '-' : '' }}R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                    </span>
                                    @if($transaction->is_paid)
                                        <i class="bx bx-check-circle text-success ms-1"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->type === 'receita')
                                        {{ $transaction->member ? $transaction->member->name : ($transaction->received_from_other ?? 'Outros') }}
                                    @else
                                        {{ $transaction->contact ? $transaction->contact->name : '-' }}
                                    @endif
                                </td>
                                <td>{{ $transaction->category ? $transaction->category->name : '-' }}</td>
                                <td>{{ $transaction->account ? $transaction->account->name : '-' }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @php
                                            $canEdit = ($transaction->type === 'receita' && $canEditReceitas) || ($transaction->type === 'despesa' && $canEditDespesas);
                                            $canDelete = ($transaction->type === 'receita' && $canDeleteReceitas) || ($transaction->type === 'despesa' && $canDeleteDespesas);
                                        @endphp
                                        @if($canEdit)
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-transaction" 
                                                data-transaction-id="{{ $transaction->id }}" 
                                                title="Editar">
                                            <i class="bx bx-edit"></i>
                                        </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-info print-receipt" 
                                                data-transaction-id="{{ $transaction->id }}" 
                                                title="Imprimir">
                                            <i class="bx bx-printer"></i>
                                        </button>
                                        @if($canCreateReceitas || $canCreateDespesas)
                                        <button type="button" class="btn btn-sm btn-outline-secondary duplicate-transaction" 
                                                data-transaction-id="{{ $transaction->id }}" 
                                                title="Duplicar">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                        @endif
                                        @if($canDelete)
                                        <form action="{{ route('financial.transactions.destroy', $transaction) }}" 
                                              method="POST" 
                                              class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja remover esta transação?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="mt-3">
                {{ $transactions->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-5 text-muted">
                <i class="bx bx-inbox" style="font-size: 3rem;"></i>
                <p class="mt-2">Nenhuma transação encontrada.</p>
            </div>
        @endif
    </div>
</div>

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
    <div class="modal-dialog modal-lg">
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
                            <label for="receita_received_from" class="form-label">Recebido de <span class="text-danger">*</span></label>
                            <select class="form-select @error('member_id') is-invalid @enderror" 
                                    id="receita_received_from" name="member_id" required>
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
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                                @foreach($accounts as $account)
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
                    </div>

                    <div class="mb-3">
                        <label for="receita_notes" class="form-label">Anotações</label>
                        <textarea class="form-control" id="receita_notes" name="notes" rows="3" 
                                  placeholder="Digite anotações...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivos <span id="receita_file_count">0</span>/5</label>
                        <button type="button" class="btn btn-primary btn-sm mb-2" onclick="document.getElementById('receita_attachments').click()">
                            <i class="bx bx-paperclip me-1"></i>Anexar arquivo (Máx. 10MB/arquivo)
                        </button>
                        <input type="file" class="d-none" id="receita_attachments" name="attachments[]" 
                               multiple accept="image/*,application/pdf" capture="environment">
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

<!-- Modal: Editar Transação -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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
                                <label for="edit_member_id" class="form-label">Recebido de <span class="text-danger">*</span></label>
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
                                <label for="edit_category_id" class="form-label">Categoria</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
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

                    <!-- Campos para Despesa -->
                    <div id="edit_despesa_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_contact_id" class="form-label">Pago à</label>
                                <select class="form-select" id="edit_contact_id" name="contact_id">
                                    <option value="">Selecione</option>
                                    @foreach($contacts as $contact)
                                        <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_category_id" class="form-label">Categoria</label>
                                <select class="form-select" id="edit_category_id" name="category_id">
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
                    </div>

                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Anotações</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3" 
                                  placeholder="Digite anotações..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivos <span id="edit_file_count">0</span>/5</label>
                        <button type="button" class="btn btn-primary btn-sm mb-2" onclick="document.getElementById('edit_attachments').click()">
                            <i class="bx bx-paperclip me-1"></i>Anexar arquivo (Máx. 10MB/arquivo)
                        </button>
                        <input type="file" class="d-none" id="edit_attachments" name="attachments[]" 
                               multiple accept="image/*,application/pdf">
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
    <div class="modal-dialog modal-lg">
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
                            <label for="despesa_contact" class="form-label">Pago à</label>
                            <select class="form-select" id="despesa_contact" name="contact_id">
                                <option value="">Selecione</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->name }}
                                    </option>
                                @endforeach
                            </select>
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
                                @foreach($accounts as $account)
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
                    </div>

                    <div class="mb-3">
                        <label for="despesa_notes" class="form-label">Anotações</label>
                        <textarea class="form-control" id="despesa_notes" name="notes" rows="3" 
                                  placeholder="Digite anotações...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivos <span id="despesa_file_count">0</span>/5</label>
                        <button type="button" class="btn btn-primary btn-sm mb-2" onclick="document.getElementById('despesa_attachments').click()">
                            <i class="bx bx-paperclip me-1"></i>Anexar arquivo (Máx. 10MB/arquivo)
                        </button>
                        <input type="file" class="d-none" id="despesa_attachments" name="attachments[]" 
                               multiple accept="image/*,application/pdf" capture="environment">
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico Mensal
    const monthlyCtx = document.getElementById('monthlyTransactionChart');
    if (monthlyCtx) {
        const chartData = @json($chartData ?? []);
        
        console.log('Dados do gráfico:', chartData); // Debug
        
        // Extrair labels (dias) e dados
        const labels = chartData.map(item => item.day);
        const receitas = chartData.map(item => {
            const value = parseFloat(item.receitas) || 0;
            return value;
        });
        const despesas = chartData.map(item => {
            const value = parseFloat(item.despesas) || 0;
            return value;
        });
        const aReceber = chartData.map(item => {
            const value = parseFloat(item.a_receber) || 0;
            return value;
        });
        const aPagar = chartData.map(item => {
            const value = parseFloat(item.a_pagar) || 0;
            return value;
        });
        
        console.log('Receitas:', receitas); // Debug
        console.log('Despesas:', despesas); // Debug
        
        // Calcular máximo para o eixo Y
        const allValues = [...receitas, ...despesas, ...aReceber, ...aPagar];
        const maxValue = allValues.length > 0 ? Math.max(...allValues, 100) : 1000;
        const yMax = Math.ceil(maxValue / 200) * 200; // Arredondar para múltiplo de 200
        
        new Chart(monthlyCtx, {
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
                        max: yMax || 1000,
                        ticks: {
                            stepSize: Math.ceil((yMax || 1000) / 5)
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

    // Gerenciar anexos de arquivos - Edição
    document.getElementById('edit_attachments')?.addEventListener('change', function(e) {
        const files = e.target.files;
        const preview = document.getElementById('edit_files_preview');
        const count = document.getElementById('edit_file_count');
        const existingCount = preview.querySelectorAll('.border.rounded').length;
        
        if (files.length + existingCount > 5) {
            alert('Máximo de 5 arquivos permitidos');
            this.value = '';
            return;
        }
        
        const currentCount = parseInt(count.textContent) || 0;
        count.textContent = currentCount + files.length;
        
        Array.from(files).forEach((file, index) => {
            const div = document.createElement('div');
            div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded';
            div.innerHTML = `
                <span class="small">${file.name}</span>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeEditFile(${index}, this)">
                    <i class="bx bx-trash"></i>
                </button>
            `;
            preview.appendChild(div);
        });
    });

    function removeEditFile(index, button) {
        const input = document.getElementById('edit_attachments');
        const dt = new DataTransfer();
        const files = Array.from(input.files);
        files.splice(index, 1);
        files.forEach(file => dt.items.add(file));
        input.files = dt.files;
        
        button.closest('div').remove();
        
        const count = document.getElementById('edit_file_count');
        const currentCount = parseInt(count.textContent) || 0;
        count.textContent = Math.max(0, currentCount - 1);
        
        input.dispatchEvent(new Event('change'));
    }

    // Toggle campo "Outros" no modal de receita
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

    // Validação antes de enviar o formulário de receita
    document.getElementById('receitaForm')?.addEventListener('submit', function(e) {
        const receivedFrom = document.getElementById('receita_received_from');
        const otherField = document.getElementById('receita_other_name');
        const memberIdOriginal = document.getElementById('receita_member_id_original');
        
        if (!receivedFrom.value || receivedFrom.value === '') {
            e.preventDefault();
            alert('Selecione um membro ou escolha "Outros".');
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
            // Quando for "outros", enviar member_id vazio
            receivedFrom.removeAttribute('name');
            receivedFrom.setAttribute('name', 'member_id_hidden');
        } else {
            // Quando for membro específico, garantir que received_from_other esteja vazio
            if (otherField) {
                otherField.removeAttribute('name');
            }
        }
    });

    // Gerenciar anexos de arquivos - Receita
    document.getElementById('receita_attachments')?.addEventListener('change', function(e) {
        const files = e.target.files;
        const preview = document.getElementById('receita_files_preview');
        const count = document.getElementById('receita_file_count');
        
        if (files.length > 5) {
            alert('Máximo de 5 arquivos permitidos');
            this.value = '';
            return;
        }
        
        count.textContent = files.length;
        preview.innerHTML = '';
        
        Array.from(files).forEach((file, index) => {
            const div = document.createElement('div');
            div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded';
            div.innerHTML = `
                <span class="small">${file.name}</span>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFile(${index}, 'receita')">
                    <i class="bx bx-trash"></i>
                </button>
            `;
            preview.appendChild(div);
        });
    });

    // Gerenciar anexos de arquivos - Despesa
    document.getElementById('despesa_attachments')?.addEventListener('change', function(e) {
        const files = e.target.files;
        const preview = document.getElementById('despesa_files_preview');
        const count = document.getElementById('despesa_file_count');
        
        if (files.length > 5) {
            alert('Máximo de 5 arquivos permitidos');
            this.value = '';
            return;
        }
        
        count.textContent = files.length;
        preview.innerHTML = '';
        
        Array.from(files).forEach((file, index) => {
            const div = document.createElement('div');
            div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded';
            div.innerHTML = `
                <span class="small">${file.name}</span>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFile(${index}, 'despesa')">
                    <i class="bx bx-trash"></i>
                </button>
            `;
            preview.appendChild(div);
        });
    });

    function removeFile(index, type) {
        const input = document.getElementById(`${type}_attachments`);
        const dt = new DataTransfer();
        const files = Array.from(input.files);
        files.splice(index, 1);
        files.forEach(file => dt.items.add(file));
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    }

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
        document.getElementById('edit_category_id').value = transaction.category_id || '';
        document.getElementById('edit_account_id').value = transaction.account_id || '';
        document.getElementById('edit_cost_center_id').value = transaction.cost_center_id || '';
        document.getElementById('edit_payment_type').value = transaction.payment_type || 'unico';
        document.getElementById('edit_document_number').value = transaction.document_number || '';
        document.getElementById('edit_competence_date').value = transaction.competence_date || '';
        document.getElementById('edit_notes').value = transaction.notes || '';
        
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
            filterCategories('edit_category_id', 'receita');
        } else {
            receitaFields.style.display = 'none';
            despesaFields.style.display = 'block';
            
            // Preencher campo "Pago à"
            document.getElementById('edit_contact_id').value = transaction.contact_id || '';
            
            // Filtrar categorias de despesa
            filterCategories('edit_category_id', 'despesa');
        }
        
        // Limpar preview de arquivos
        document.getElementById('edit_files_preview').innerHTML = '';
        document.getElementById('edit_file_count').textContent = '0';
        
        // Mostrar anexos existentes
        if (transaction.attachments && transaction.attachments.length > 0) {
            const preview = document.getElementById('edit_files_preview');
            transaction.attachments.forEach((attachment, index) => {
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center mb-2 p-2 border rounded';
                div.innerHTML = `
                    <span class="small">${attachment.file_name}</span>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeExistingFile(${attachment.id}, this)">
                        <i class="bx bx-trash"></i>
                    </button>
                `;
                preview.appendChild(div);
            });
            document.getElementById('edit_file_count').textContent = transaction.attachments.length;
        }
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
        
        // Atualizar contador
        const count = document.getElementById('edit_file_count');
        const currentCount = parseInt(count.textContent) || 0;
        count.textContent = Math.max(0, currentCount - 1);
    }

    // Imprimir recibo
    document.querySelectorAll('.print-receipt').forEach(function(button) {
        button.addEventListener('click', function() {
            const transactionId = this.dataset.transactionId;
            const url = '{{ route("financial.transactions.receipt", ":id") }}'.replace(':id', transactionId);
            window.open(url, '_blank', 'width=800,height=600');
        });
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
        const table = document.querySelector('.table-responsive');
        
        if (!table) {
            alert('Nenhuma transação para imprimir.');
            return;
        }

        const nowFormatted = new Date().toLocaleString('pt-BR');

        printWindow.document.write('<!DOCTYPE html><html><head><title>Transações - ' + nowFormatted + '</title><style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }');
        printWindow.document.write('h1 { text-align: center; margin-bottom: 20px; }');
        printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
        printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
        printWindow.document.write('th { background-color: #f2f2f2; font-weight: bold; }');
        printWindow.document.write('tr:nth-child(even) { background-color: #f9f9f9; }');
        printWindow.document.write('.text-success { color: #28a745; }');
        printWindow.document.write('.text-danger { color: #dc3545; }');
        printWindow.document.write('@media print { @page { margin: 1cm; } body { margin: 0; } }');
        printWindow.document.write('</style></head><body>');
        printWindow.document.write('<h1>Relatório de Transações</h1>');
        printWindow.document.write('<p><strong>Período:</strong> ' + printData.startDate + ' até ' + printData.endDate + '</p>');
        printWindow.document.write('<p><strong>Total de transações:</strong> ' + printData.total + '</p>');
        printWindow.document.write(table.innerHTML);
        printWindow.document.write('<p style="margin-top: 20px; text-align: right; font-size: 10px;">Impresso em: ' + nowFormatted + '</p>');
        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.focus();
        
        // Aguardar carregamento antes de imprimir
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }
</script>
@endpush
@endsection
