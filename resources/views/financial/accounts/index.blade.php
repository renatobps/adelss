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
                Saldo (ativas): <strong>{{ $fmt($saldoAtivas) }}</strong>
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
                                <div class="financial-account-card__balance {{ $balance >= 0 ? 'is-positive' : 'is-negative' }}">
                                    {{ $fmt($balance) }}
                                </div>
                                <div class="financial-account-card__initial">
                                    Inicial: {{ $fmt($account->initial_balance) }}
                                </div>
                            </div>
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
        display: inline-flex;
        gap: 0.35rem;
        padding: 0.3rem;
        background: #eef1f5;
        border-radius: 999px;
    }
    .financial-accounts-tabs__item {
        display: inline-flex;
        align-items: center;
        padding: 0.4rem 0.9rem;
        border-radius: 999px;
        color: #4b5563;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 600;
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
@endsection
