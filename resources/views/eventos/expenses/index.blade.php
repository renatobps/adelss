@extends('layouts.porto')

@section('title', 'Transações dos eventos')

@section('page-title', 'Transações dos eventos')

@section('breadcrumbs')
    <li><a href="{{ route('eventos.campaigns.index') }}">Gestor de Eventos</a></li>
    <li><span>Transações</span></li>
@endsection

@section('content')
@php
    $money = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $hasFilters = $filters['q'] !== '' || $filters['kind'] !== '' || $filters['category'] !== '' || $filters['from'] || $filters['to'] || $selectedCampaign > 0;
@endphp

@include('eventos.campaigns.partials.alerts')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">Transações de todos os eventos</h4>
        <small class="text-muted">Visão consolidada. Para registrar uma transação, abra a campanha do evento correspondente.</small>
    </div>
    <div class="text-end">
        <div class="text-muted small">Todos os eventos — receitas extras / gastos</div>
        <strong class="fs-5 text-success">{{ $money($grandTotalIncome) }}</strong> / <strong class="fs-5 text-danger">{{ $money($grandTotalExpenses) }}</strong>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h5 class="mb-0"><i class="bx bx-party me-2"></i>Por evento</h5></header>
            <div class="card-body p-0">
                @if($campaigns->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">Nenhuma campanha cadastrada.</p>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($campaigns as $c)
                            @php($isSel = $selectedCampaign === $c->id)
                            <div class="list-group-item d-flex justify-content-between align-items-center {{ $isSel ? 'active' : '' }}">
                                <div class="min-w-0">
                                    <a href="{{ route('eventos.expenses.all', ['campaign' => $c->id]) }}"
                                       class="d-block text-truncate fw-semibold text-decoration-none {{ $isSel ? 'text-white' : '' }}" title="Filtrar lançamentos deste evento">
                                        {{ $c->name }}
                                    </a>
                                    <small class="{{ $isSel ? '' : 'text-muted' }}">{{ $c->expenses_count }} lançamento(s)</small>
                                </div>
                                <div class="text-end ms-2 text-nowrap">
                                    <div class="text-success">Receitas: {{ $money($c->income_total ?? 0) }}</div>
                                    <div>Gastos: {{ $money($c->expenses_total ?? 0) }}</div>
                                    <div>
                                        <a href="{{ route('eventos.campaigns.expenses.index', $c) }}" class="small {{ $isSel ? 'text-white' : '' }}" title="Abrir transações do evento">
                                            gerenciar <i class="bx bx-right-arrow-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div class="col-lg-8 mb-3">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Lançamentos</h5>
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    @if($selectedCampaign > 0)
                        <input type="hidden" name="campaign" value="{{ $selectedCampaign }}">
                    @endif
                    <input type="search" name="q" class="form-control form-control-sm" style="width: 170px" placeholder="Buscar..." value="{{ $filters['q'] }}">
                    <select name="kind" class="form-select form-select-sm" style="width: 140px" aria-label="Tipo de transação">
                        <option value="">Todos os tipos</option>
                        @foreach(\App\Models\CampaignExpense::KINDS as $value => $label)
                            <option value="{{ $value }}" @selected($filters['kind'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="category" class="form-select form-select-sm" style="width: 160px">
                        <option value="">Todas categorias</option>
                        @foreach($categories + $incomeCategories as $value => $label)
                            <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="from" class="form-control form-control-sm" style="width: 140px" value="{{ $filters['from'] }}" title="De">
                    <input type="date" name="to" class="form-control form-control-sm" style="width: 140px" value="{{ $filters['to'] }}" title="Até">
                    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bx bx-filter"></i></button>
                    @if($hasFilters)
                        <a href="{{ route('eventos.expenses.all') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
                    @endif
                </form>
            </header>

            @if($expenses->isEmpty())
                <div class="card-body">
                    <p class="text-muted text-center py-4 mb-0">Nenhuma transação encontrada.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Data</th>
                                <th>Tipo</th>
                                <th>Evento</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th class="text-center">Comprov.</th>
                                <th class="text-end pe-3">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $expense)
                                <tr>
                                    <td class="ps-3 text-nowrap">{{ $expense->expense_date->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-{{ $expense->isIncome() ? 'success' : 'danger' }}">{{ $expense->kindLabel() }}</span></td>
                                    <td><a href="{{ route('eventos.campaigns.expenses.index', $expense->campaign_id) }}" class="text-decoration-none">{{ $expense->campaign?->name ?? '—' }}</a></td>
                                    <td>
                                        {{ $expense->description }}
                                        @if($expense->supplier)
                                            <div><small class="text-muted">{{ $expense->supplier }}</small></div>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $expense->categoryLabel() }}</span></td>
                                    <td class="text-center">
                                        @foreach($expense->attachments as $attachment)
                                            <a href="{{ route('eventos.campaigns.expenses.attachment', $attachment) }}" target="_blank" rel="noopener"
                                               class="btn btn-sm btn-outline-secondary py-0 px-1" title="{{ $attachment->file_name }}">
                                                <i class="bx {{ $attachment->isImage() ? 'bx-image' : 'bxs-file-pdf' }}"></i>
                                            </a>
                                        @endforeach
                                    </td>
                                    <td class="text-end pe-3 text-nowrap"><strong class="text-{{ $expense->isIncome() ? 'success' : 'danger' }}">{{ $expense->isIncome() ? '+' : '−' }} {{ $money($expense->amount) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="6" class="ps-3"><strong>Saldo das transações nesta página ({{ $expenses->total() }} lançamento(s) no filtro)</strong></td>
                                <td class="text-end pe-3"><strong>{{ $money($expenses->getCollection()->sum(fn ($entry) => $entry->isIncome() ? (float) $entry->amount : -(float) $entry->amount)) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if($expenses->hasPages())
                    <div class="card-footer">{{ $expenses->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</div>
@endsection
