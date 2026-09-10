@extends('layouts.porto')

@section('title', 'Contas e Caixas')

@section('page-title', 'Contas e Caixas')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Contas e Caixas</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canCreate = $isAdmin || $user?->hasPermission('financial.accounts.create') || $user?->hasPermission('financial.accounts.manage');
    $canEdit = $isAdmin || $user?->hasPermission('financial.accounts.edit') || $user?->hasPermission('financial.accounts.manage');
    $canDelete = $isAdmin || $user?->hasPermission('financial.accounts.delete') || $user?->hasPermission('financial.accounts.manage');
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
@endphp

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

<div class="financial-accounts-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="financial-accounts-page__title mb-1">Contas e Caixas</h1>
            <p class="financial-accounts-page__subtitle mb-0">
                Saldo (ativas): <strong data-saldo-ativas>{{ $fmt($saldoAtivas) }}</strong>
            </p>
        </div>
        @if($canCreate)
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
            <i class="bx bx-plus me-1"></i>Nova
        </button>
        @endif
    </div>

    <div class="financial-accounts-tabs mb-4">
        <a href="{{ route('financial.accounts.index', ['status' => 'ativas']) }}"
           class="financial-accounts-tabs__item {{ $status === 'ativas' ? 'is-active' : '' }}">
            Ativas ({{ $counts['ativas'] }})
        </a>
        <a href="{{ route('financial.accounts.index', ['status' => 'inativas']) }}"
           class="financial-accounts-tabs__item {{ $status === 'inativas' ? 'is-active' : '' }}">
            Inativas ({{ $counts['inativas'] }})
        </a>
        <a href="{{ route('financial.accounts.index', ['status' => 'todas']) }}"
           class="financial-accounts-tabs__item {{ $status === 'todas' ? 'is-active' : '' }}">
            Todas ({{ $counts['todas'] }})
        </a>
    </div>

    @if($accounts->count() > 0)
        <div class="row g-3">
            @foreach($accounts as $account)
                @php
                    $balance = (float) ($account->current_balance ?? 0);
                    $color = $account->color ?: '#3b82f6';
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="financial-account-card h-100">
                        <div class="financial-account-card__header">
                            <div class="financial-account-card__name">
                                <span class="financial-account-card__dot" style="background: {{ $color }};"></span>
                                <strong>{{ $account->name }}</strong>
                                @unless($account->is_active)
                                    <span class="badge bg-secondary ms-1">Inativa</span>
                                @endunless
                                @if($account->isMercadoPago())
                                    <span class="badge bg-info ms-1">Mercado Pago</span>
                                @endif
                            </div>
                            <span class="financial-account-card__type">{{ $account->typeLabel() }}</span>
                        </div>

                        <div class="financial-account-card__body">
                            <div class="mb-3">
                                <div class="financial-account-card__label">Banco</div>
                                <div class="financial-account-card__value">{{ $account->bankDisplay() }}</div>
                            </div>
                            <hr class="financial-account-card__divider">
                            <div>
                                <div class="financial-account-card__label">Saldo Atual</div>
                                <div class="financial-account-card__balance {{ $balance >= 0 ? 'is-positive' : 'is-negative' }}"
                                     @if($account->isMercadoPago()) data-mp-balance @endif>
                                    {{ $fmt($balance) }}
                                </div>
                                @if(($account->balance_source ?? '') !== 'mp_flow')
                                    <div class="financial-account-card__initial">
                                        Inicial: {{ $fmt($account->initial_balance) }}
                                    </div>
                                @endif
                            </div>
                            @if($account->isMercadoPago() && is_array($mpMovements ?? null) && empty($mpMovements['error']))
                                <hr class="financial-account-card__divider">
                                <div class="financial-account-card__flow">
                                    <div>
                                        <div class="financial-account-card__label">Entradas ({{ $mpMovements['days'] }} dias)</div>
                                        <div class="financial-account-card__flow-in" data-mp-in-total>{{ $fmt($mpMovements['in_total'] ?? 0) }}</div>
                                    </div>
                                    <div>
                                        <div class="financial-account-card__label">Saídas ({{ $mpMovements['days'] }} dias)</div>
                                        <div class="financial-account-card__flow-out" data-mp-out-total>{{ $fmt($mpMovements['out_total'] ?? 0) }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="financial-account-card__actions">
                            @if($canEdit)
                            <button type="button"
                                    class="btn btn-light financial-account-card__btn-edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editAccountModal{{ $account->id }}">
                                <i class="bx bx-edit-alt me-1"></i>Editar
                            </button>
                            <form action="{{ route('financial.accounts.toggle-active', $account) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="{{ $status }}">
                                <button type="submit"
                                        class="btn btn-light financial-account-card__btn-icon"
                                        title="{{ $account->is_active ? 'Desativar conta' : 'Reativar conta' }}">
                                    <i class="bx {{ $account->is_active ? 'bx-hide' : 'bx-show' }}"></i>
                                </button>
                            </form>
                            @endif
                            @if($canDelete)
                            <form action="{{ route('financial.accounts.destroy', $account) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Tem certeza que deseja remover esta conta?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="status" value="{{ $status }}">
                                <button type="submit" class="btn btn-danger financial-account-card__btn-icon" title="Excluir">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        @if(is_array($mpMovements ?? null))
            <section class="financial-mp-movements"
                     data-mp-refresh-url="{{ route('financial.accounts.mp-movements') }}"
                     data-mp-export-pdf="{{ route('financial.accounts.mp-extract.pdf') }}"
                     data-mp-export-excel="{{ route('financial.accounts.mp-extract.excel') }}"
                     data-mp-page-size="10">
                <div class="financial-mp-movements__head">
                    <div>
                        <h2 class="financial-mp-movements__title">Entradas e saídas — Mercado Pago</h2>
                        <p class="financial-mp-movements__hint mb-0">
                            Últimos {{ $mpMovements['days'] }} dias · a tela atualiza sozinha a cada 30 segundos.
                            PIX recebido entra na hora. PIX enviado só aparece quando o Mercado Pago fecha o relatório.
                        </p>
                        <p class="text-warning small mb-0 mt-2 js-mp-outflows-pending {{ empty($mpMovements['outflows_pending']) ? 'd-none' : '' }}">
                            O relatório de saídas ainda está sendo gerado. A saída do PIX enviado entra automaticamente em alguns minutos.
                        </p>
                    </div>
                    <div class="financial-mp-movements__totals">
                        <span class="text-success">Entradas <span data-mp-in-total>{{ $fmt($mpMovements['in_total'] ?? 0) }}</span></span>
                        <span class="text-danger">Saídas <span data-mp-out-total>{{ $fmt($mpMovements['out_total'] ?? 0) }}</span></span>
                    </div>
                </div>
                <div class="financial-mp-toolbar">
                    <label class="visually-hidden" for="mpExtractSearch">Buscar no extrato</label>
                    <input type="search"
                           id="mpExtractSearch"
                           class="form-control js-mp-search"
                           placeholder="Buscar data, tipo, descrição, meio..."
                           autocomplete="off"
                           inputmode="search">
                    <div class="financial-mp-toolbar__controls">
                        <label class="financial-mp-pagesize" for="mpExtractPageSize">
                            <span>Por página</span>
                            <select id="mpExtractPageSize" class="form-select js-mp-page-size" aria-label="Itens por página">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </label>
                        <div class="financial-mp-toolbar__export">
                            <a href="{{ route('financial.accounts.mp-extract.pdf') }}"
                               class="btn btn-outline-secondary js-mp-export-pdf">
                                <i class="bx bxs-file-pdf me-1"></i>PDF
                            </a>
                            <a href="{{ route('financial.accounts.mp-extract.excel') }}"
                               class="btn btn-outline-secondary js-mp-export-excel">
                                <i class="bx bx-spreadsheet me-1"></i>Excel
                            </a>
                        </div>
                    </div>
                    <p class="financial-mp-toolbar__count js-mp-count mb-0"></p>
                </div>
                @if(!empty($mpMovements['error']))
                    <p class="text-warning small mb-0 js-mp-error">{{ $mpMovements['error'] }}</p>
                @else
                    <p class="text-warning small mb-0 js-mp-error d-none"></p>
                @endif
                <p class="text-muted small mb-0 js-mp-loading {{ !empty($mpMovements['loading']) && empty($mpMovements['items']) ? '' : 'd-none' }}">Carregando extrato…</p>
                <p class="text-muted small mb-0 js-mp-empty {{ empty($mpMovements['error']) && empty($mpMovements['loading']) && empty($mpMovements['items']) ? '' : 'd-none' }}">Nenhum pagamento neste período.</p>
                <p class="text-muted small mb-0 js-mp-no-results d-none">Nenhum movimento encontrado para essa busca.</p>
                <div class="js-mp-table-wrap {{ empty($mpMovements['error']) && !empty($mpMovements['items']) ? '' : 'd-none' }}">
                    <p class="text-muted small js-mp-truncated {{ empty($mpMovements['truncated']) ? 'd-none' : '' }}">Mostrando os movimentos mais recentes.</p>
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-sm financial-mp-movements__table mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Meio</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="js-mp-movements-body"></tbody>
                        </table>
                    </div>
                    <div class="financial-mp-cards d-md-none js-mp-cards"></div>
                    <nav class="financial-mp-pagination js-mp-pagination" aria-label="Paginação do extrato"></nav>
                </div>
                <script type="application/json" id="mp-movements-data">@json($mpMovements['items'] ?? [])</script>
            </section>
        @endif

        @if($canEdit)
            @foreach($accounts as $account)
                <div class="modal fade" id="editAccountModal{{ $account->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content financial-account-modal">
                            <div class="modal-header border-0 pb-0">
                                <div>
                                    <h5 class="modal-title mb-1">Editar Conta</h5>
                                    <p class="text-muted small mb-0">Atualize os dados da conta ou caixa</p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <form action="{{ route('financial.accounts.update', $account) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="redirect_status" value="{{ $status }}">
                                <div class="modal-body">
                                    @include('financial.accounts._form', [
                                        'prefix' => 'edit_' . $account->id . '_',
                                        'account' => $account,
                                        'types' => $types,
                                        'colors' => $colors,
                                    ])
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @else
        <div class="text-center py-5 text-muted">
            <i class="bx bx-wallet" style="font-size: 3rem;"></i>
            <p class="mt-2 mb-1 fw-semibold">Nenhuma conta nesta lista</p>
            <p class="mb-0 small">
                @if($status === 'ativas')
                    Crie uma nova conta ou reative uma conta inativa.
                @elseif($status === 'inativas')
                    Não há contas desativadas.
                @else
                    Cadastre a primeira conta para começar.
                @endif
            </p>
        </div>
    @endif
</div>

@if($canCreate)
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content financial-account-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title mb-1" id="createAccountModalLabel">Nova Conta</h5>
                    <p class="text-muted small mb-0">Adicione uma nova conta bancária ou caixa</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="{{ route('financial.accounts.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('financial.accounts._form', [
                        'prefix' => 'create_',
                        'account' => null,
                        'types' => $types,
                        'colors' => $colors,
                    ])
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Conta</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    .financial-accounts-page__title {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        color: #1f2937;
    }
    .financial-accounts-page__subtitle {
        color: #6b7280;
        font-size: 0.95rem;
    }
    .financial-accounts-tabs {
        display: flex;
        gap: 0.35rem;
        padding: 0.3rem;
        background: #eef1f5;
        border-radius: 999px;
        overflow-x: auto;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .financial-accounts-tabs::-webkit-scrollbar { display: none; }
    .financial-accounts-tabs__item {
        display: inline-flex;
        align-items: center;
        padding: 0.4rem 0.9rem;
        border-radius: 999px;
        color: #4b5563;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .financial-accounts-tabs__item.is-active {
        background: #fff;
        color: #111827;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }
    .financial-account-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        padding: 1.1rem 1.15rem;
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }
    .financial-account-card__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
    }
    .financial-account-card__name {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        min-width: 0;
        color: #111827;
    }
    .financial-account-card__dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .financial-account-card__type {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.65rem;
        border: 1px solid #d1d5db;
        border-radius: 999px;
        font-size: 0.75rem;
        color: #4b5563;
        white-space: nowrap;
    }
    .financial-account-card__label {
        font-size: 0.8rem;
        color: #9ca3af;
        margin-bottom: 0.15rem;
    }
    .financial-account-card__value {
        font-size: 0.98rem;
        font-weight: 600;
        color: #111827;
    }
    .financial-account-card__divider {
        margin: 0.85rem 0;
        border-color: #eef2f7;
        opacity: 1;
    }
    .financial-account-card__balance {
        font-size: 1.55rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .financial-account-card__balance.is-positive { color: #16a34a; }
    .financial-account-card__balance.is-negative { color: #dc2626; }
    .financial-account-card__initial {
        margin-top: 0.2rem;
        font-size: 0.82rem;
        color: #9ca3af;
    }
    .financial-account-card__flow {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .financial-account-card__flow-in,
    .financial-account-card__flow-out {
        font-size: 1.05rem;
        font-weight: 700;
    }
    .financial-account-card__flow-in { color: #16a34a; }
    .financial-account-card__flow-out { color: #dc2626; }
    .financial-mp-movements {
        margin-top: 1.5rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        padding: 1.1rem 1.15rem;
    }
    .financial-mp-movements__head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.9rem;
    }
    .financial-mp-movements__title {
        margin: 0 0 0.2rem;
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
    }
    .financial-mp-movements__hint {
        color: #6b7280;
        font-size: 0.85rem;
    }
    .financial-mp-movements__totals {
        display: flex;
        gap: 1rem;
        font-weight: 700;
        font-size: 0.92rem;
        align-items: flex-start;
    }
    .financial-mp-movements__table th {
        color: #6b7280;
        font-weight: 600;
        font-size: 0.78rem;
        border-bottom-color: #eef2f7;
    }
    .financial-mp-movements__table td {
        vertical-align: top;
        border-bottom-color: #f3f4f6;
    }
    .financial-mp-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
        margin-bottom: 0.85rem;
    }
    .financial-mp-toolbar .js-mp-search {
        flex: 1 1 220px;
        min-width: 0;
        border-radius: 0.75rem;
    }
    .financial-mp-toolbar__controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.55rem;
    }
    .financial-mp-pagesize {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0;
        font-size: 0.82rem;
        color: #4b5563;
        font-weight: 600;
        white-space: nowrap;
    }
    .financial-mp-pagesize .form-select {
        width: auto;
        min-width: 4.5rem;
        min-height: 40px;
        border-radius: 0.75rem;
    }
    .financial-mp-toolbar__export {
        display: flex;
        gap: 0.4rem;
    }
    .financial-mp-toolbar__export .btn {
        min-height: 40px;
        white-space: nowrap;
    }
    .financial-mp-toolbar__export .btn.is-disabled {
        pointer-events: none;
        opacity: 0.55;
    }
    .financial-mp-toolbar__count {
        font-size: 0.8rem;
        color: #6b7280;
        white-space: nowrap;
    }
    .financial-mp-cards {
        display: grid;
        gap: 0.65rem;
    }
    .financial-mp-card {
        border: 1px solid #eef2f7;
        border-radius: 0.75rem;
        padding: 0.8rem 0.9rem;
        background: #fafbfc;
    }
    .financial-mp-card__top {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.75rem;
        margin-bottom: 0.35rem;
    }
    .financial-mp-card__tipo { font-size: 0.78rem; font-weight: 700; }
    .financial-mp-card__valor { font-size: 1rem; font-weight: 700; white-space: nowrap; }
    .financial-mp-card__desc { font-size: 0.92rem; color: #111827; font-weight: 600; }
    .financial-mp-card__payer,
    .financial-mp-card__meta {
        font-size: 0.78rem;
        color: #6b7280;
        margin-top: 0.15rem;
    }
    .financial-mp-pagination {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.85rem;
    }
    .financial-mp-pagination__btns {
        display: flex;
        gap: 0.4rem;
    }
    .financial-mp-pagination .btn {
        min-height: 40px;
        min-width: 44px;
    }
    .financial-mp-pagination__info {
        font-size: 0.8rem;
        color: #6b7280;
    }
    @media (max-width: 767.98px) {
        .financial-accounts-page__title { font-size: 1.3rem; }
        .financial-account-card__header {
            flex-direction: column;
            align-items: flex-start;
        }
        .financial-account-card__actions {
            flex-wrap: wrap;
        }
        .financial-account-card__btn-edit {
            flex: 1 1 100%;
        }
        .financial-mp-movements {
            padding: 0.9rem 0.85rem;
        }
        .financial-mp-movements__totals {
            width: 100%;
            justify-content: space-between;
        }
        .financial-mp-toolbar .js-mp-search {
            flex-basis: 100%;
            min-height: 44px;
        }
        .financial-mp-toolbar__controls {
            width: 100%;
        }
        .financial-mp-pagesize {
            flex: 1 1 auto;
        }
        .financial-mp-pagesize .form-select {
            min-height: 44px;
            flex: 1;
        }
        .financial-mp-toolbar__export {
            width: 100%;
        }
        .financial-mp-toolbar__export .btn {
            flex: 1;
            min-height: 44px;
        }
        .financial-mp-pagination {
            flex-direction: column;
            align-items: stretch;
        }
        .financial-mp-pagination__btns .btn {
            flex: 1;
        }
        .financial-account-modal .modal-dialog {
            margin: 0.5rem;
        }
    }
    .financial-account-card__actions {
        display: flex;
        gap: 0.45rem;
        margin-top: auto;
    }
    .financial-account-card__btn-edit {
        flex: 1;
        border: 1px solid #e5e7eb;
    }
    .financial-account-card__btn-icon {
        width: 40px;
        min-width: 40px;
        padding-left: 0;
        padding-right: 0;
        border: 1px solid #e5e7eb;
    }
    .financial-account-modal .modal-content {
        border: 0;
        border-radius: 1rem;
    }
    .financial-account-color-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
    }
    .financial-account-color-option {
        position: relative;
        width: 28px;
        height: 28px;
    }
    .financial-account-color-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .financial-account-color-option span {
        display: block;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.08);
    }
    .financial-account-color-option input:checked + span {
        outline: 2px solid #93c5fd;
        outline-offset: 2px;
    }
    .financial-account-active-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: #fafafa;
    }
    .financial-account-active-box strong {
        display: block;
        margin-bottom: 0.2rem;
    }
    .financial-account-active-box p {
        margin: 0;
        font-size: 0.82rem;
        color: #6b7280;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    function syncAccountType(select) {
        if (!select) return;
        const wrap = select.closest('.modal, form') || document;
        const hint = wrap.querySelector('.js-mp-balance-hint');
        if (hint) {
            hint.classList.toggle('d-none', select.value !== 'mercado_pago');
        }
    }
    document.querySelectorAll('.js-account-type').forEach(function (select) {
        select.addEventListener('change', function () { syncAccountType(select); });
        syncAccountType(select);
    });

    const refreshRoot = document.querySelector('[data-mp-refresh-url]');
    if (refreshRoot) {
        const url = refreshRoot.getAttribute('data-mp-refresh-url');
        const pdfExportBase = refreshRoot.getAttribute('data-mp-export-pdf') || '';
        const excelExportBase = refreshRoot.getAttribute('data-mp-export-excel') || '';
        const searchInput = refreshRoot.querySelector('.js-mp-search');
        const pageSizeSelect = refreshRoot.querySelector('.js-mp-page-size');
        const body = refreshRoot.querySelector('.js-mp-movements-body');
        const cards = refreshRoot.querySelector('.js-mp-cards');
        const pagination = refreshRoot.querySelector('.js-mp-pagination');
        const countEl = refreshRoot.querySelector('.js-mp-count');
        let allItems = [];
        let page = 1;
        let pageSize = 10;
        const allowedSizes = { 10: true, 25: true, 50: true };
        try {
            const stored = parseInt(localStorage.getItem('adelss.mp.extract.pageSize') || '', 10);
            if (allowedSizes[stored]) pageSize = stored;
        } catch (e) {}
        if (pageSizeSelect) {
            pageSizeSelect.value = String(pageSize);
        }

        const dataNode = document.getElementById('mp-movements-data');
        if (dataNode) {
            try {
                const parsed = JSON.parse(dataNode.textContent || '[]');
                if (Array.isArray(parsed)) {
                    allItems = parsed;
                }
            } catch (e) {}
        }

        const fmt = function (value) {
            const number = Number(value || 0);
            return 'R$ ' + number.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };
        const escapeHtml = function (text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        };
        const setText = function (selector, value) {
            document.querySelectorAll(selector).forEach(function (el) {
                el.textContent = value;
            });
        };
        const toggle = function (selector, show) {
            document.querySelectorAll(selector).forEach(function (el) {
                el.classList.toggle('d-none', !show);
            });
        };
        const itemMeta = function (item) {
            const dir = item.direction || '';
            return {
                dir: dir,
                tipo: dir === 'in' ? 'Entrada' : (dir === 'out' ? 'Saída' : 'Pendente'),
                tipoClass: dir === 'in' ? 'text-success' : (dir === 'out' ? 'text-danger' : 'text-muted'),
                sign: dir === 'out' ? '-' : ''
            };
        };
        const filteredItems = function () {
            const term = ((searchInput && searchInput.value) || '').trim().toLowerCase();
            if (!term) return allItems.slice();
            return allItems.filter(function (item) {
                const meta = itemMeta(item);
                const haystack = [
                    item.occurred_at_label,
                    meta.tipo,
                    item.description,
                    item.payer,
                    item.method,
                    String(item.amount || '')
                ].join(' ').toLowerCase();
                return haystack.indexOf(term) !== -1;
            });
        };
        const renderPage = function () {
            const filtered = filteredItems();
            const total = filtered.length;
            const pages = Math.max(1, Math.ceil(total / pageSize));
            if (page > pages) page = pages;
            const start = (page - 1) * pageSize;
            const slice = filtered.slice(start, start + pageSize);
            const hasError = !!refreshRoot.querySelector('.js-mp-error:not(.d-none)');

            toggle('.js-mp-empty', !hasError && allItems.length === 0 && !refreshRoot.querySelector('.js-mp-loading:not(.d-none)'));
            toggle('.js-mp-no-results', !hasError && allItems.length > 0 && total === 0);
            toggle('.js-mp-table-wrap', !hasError && total > 0);

            if (countEl) {
                countEl.textContent = total === 0
                    ? ''
                    : (total + ' movimento' + (total === 1 ? '' : 's'));
            }

            const query = encodeURIComponent((searchInput && searchInput.value) || '');
            const suffix = query ? ('?q=' + query) : '';
            refreshRoot.querySelectorAll('.js-mp-export-pdf').forEach(function (link) {
                link.href = pdfExportBase + suffix;
                link.classList.toggle('is-disabled', total === 0);
            });
            refreshRoot.querySelectorAll('.js-mp-export-excel').forEach(function (link) {
                link.href = excelExportBase + suffix;
                link.classList.toggle('is-disabled', total === 0);
            });

            if (body) {
                body.innerHTML = slice.map(function (item) {
                    const meta = itemMeta(item);
                    const payer = item.payer
                        ? '<div class="text-muted small">' + escapeHtml(item.payer) + '</div>'
                        : '';
                    return '<tr>' +
                        '<td class="text-nowrap">' + escapeHtml(item.occurred_at_label || '—') + '</td>' +
                        '<td class="' + meta.tipoClass + ' fw-semibold">' + meta.tipo + '</td>' +
                        '<td>' + escapeHtml(item.description || 'Pagamento') + payer + '</td>' +
                        '<td>' + escapeHtml(item.method || '—') + '</td>' +
                        '<td class="text-end ' + meta.tipoClass + '">' + meta.sign + fmt(item.amount) + '</td>' +
                        '</tr>';
                }).join('');
            }

            if (cards) {
                cards.innerHTML = slice.map(function (item) {
                    const meta = itemMeta(item);
                    const payer = item.payer
                        ? '<div class="financial-mp-card__payer">' + escapeHtml(item.payer) + '</div>'
                        : '';
                    return '<article class="financial-mp-card">' +
                        '<div class="financial-mp-card__top">' +
                            '<span class="financial-mp-card__tipo ' + meta.tipoClass + '">' + meta.tipo + '</span>' +
                            '<span class="financial-mp-card__valor ' + meta.tipoClass + '">' + meta.sign + fmt(item.amount) + '</span>' +
                        '</div>' +
                        '<div class="financial-mp-card__desc">' + escapeHtml(item.description || 'Pagamento') + '</div>' +
                        payer +
                        '<div class="financial-mp-card__meta">' +
                            escapeHtml(item.method || '—') + ' · ' + escapeHtml(item.occurred_at_label || '—') +
                        '</div>' +
                    '</article>';
                }).join('');
            }

            if (pagination) {
                if (pages <= 1) {
                    pagination.innerHTML = '';
                    return;
                }
                const from = start + 1;
                const to = start + slice.length;
                pagination.innerHTML =
                    '<span class="financial-mp-pagination__info">' + from + '–' + to + ' de ' + total + '</span>' +
                    '<div class="financial-mp-pagination__btns">' +
                        '<button type="button" class="btn btn-outline-secondary btn-sm js-mp-prev"' + (page <= 1 ? ' disabled' : '') + '>Anterior</button>' +
                        '<button type="button" class="btn btn-outline-secondary btn-sm js-mp-next"' + (page >= pages ? ' disabled' : '') + '>Próxima</button>' +
                    '</div>';
            }
        };

        const applyPayload = function (payload) {
            const movements = payload.movements || {};
            allItems = Array.isArray(movements.items) ? movements.items : [];
            page = 1;
            setText('[data-mp-in-total]', fmt(movements.in_total));
            setText('[data-mp-out-total]', fmt(movements.out_total));
            setText('[data-mp-balance]', fmt(payload.mp_balance));
            setText('[data-saldo-ativas]', fmt(payload.saldo_ativas));
            document.querySelectorAll('[data-mp-balance]').forEach(function (el) {
                const balance = Number(payload.mp_balance || 0);
                el.classList.toggle('is-positive', balance >= 0);
                el.classList.toggle('is-negative', balance < 0);
            });
            toggle('.js-mp-outflows-pending', !!movements.outflows_pending);
            toggle('.js-mp-error', !!movements.error);
            toggle('.js-mp-loading', false);
            const errorEl = document.querySelector('.js-mp-error');
            if (errorEl && movements.error) {
                errorEl.textContent = movements.error;
            }
            toggle('.js-mp-truncated', !!movements.truncated);
            renderPage();
        };

        const tick = function () {
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (payload) { if (payload && payload.movements) applyPayload(payload); })
                .catch(function () {
                    toggle('.js-mp-loading', false);
                    renderPage();
                });
        };

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                page = 1;
                renderPage();
            });
        }
        if (pageSizeSelect) {
            pageSizeSelect.addEventListener('change', function () {
                const next = parseInt(pageSizeSelect.value, 10);
                pageSize = allowedSizes[next] ? next : 10;
                try { localStorage.setItem('adelss.mp.extract.pageSize', String(pageSize)); } catch (e) {}
                page = 1;
                renderPage();
            });
        }
        if (pagination) {
            pagination.addEventListener('click', function (event) {
                const btn = event.target.closest('button');
                if (!btn || btn.disabled) return;
                if (btn.classList.contains('js-mp-prev')) page -= 1;
                if (btn.classList.contains('js-mp-next')) page += 1;
                renderPage();
            });
        }

        renderPage();
        if (allItems.length === 0) {
            tick();
        }
        setInterval(tick, 30000);
    }
})();
</script>
@endpush
@endsection
