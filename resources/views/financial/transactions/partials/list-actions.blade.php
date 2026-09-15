@php
    $menuId = 'tx-actions-' . $transaction->id . '-' . ($suffix ?? ($compact ?? false ? 'table' : 'card'));
@endphp
<div class="dropdown financial-tx-actions">
    <button type="button"
            class="financial-tx-actions__btn"
            id="{{ $menuId }}"
            data-bs-toggle="dropdown"
            data-bs-popper-config='{"strategy":"fixed"}'
            aria-expanded="false"
            aria-label="Ações"
            title="Ações">
        <i class="bx bx-dots-vertical-rounded"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="{{ $menuId }}">
        <li>
            <button type="button"
                    class="dropdown-item view-transaction-details"
                    data-transaction-id="{{ $transaction->id }}">
                <i class="bx bx-show me-2"></i>Detalhes
            </button>
        </li>
        @if($canEdit)
        <li>
            <button type="button"
                    class="dropdown-item edit-transaction"
                    data-transaction-id="{{ $transaction->id }}">
                <i class="bx bx-edit-alt me-2"></i>Editar
            </button>
        </li>
        @endif
        <li>
            <button type="button"
                    class="dropdown-item print-receipt"
                    data-transaction-id="{{ $transaction->id }}">
                <i class="bx bx-receipt me-2"></i>Recibo
            </button>
        </li>
        @if($transaction->is_paid && $transaction->member_id && (($isReceita && $canViewReceitas) || (!$isReceita && ($canViewDespesas ?? false))))
        <li>
            <button type="button"
                    class="dropdown-item send-receipt-whatsapp"
                    data-transaction-id="{{ $transaction->id }}">
                <i class="bx bxl-whatsapp me-2"></i>Enviar por WhatsApp
            </button>
        </li>
        @endif
        @if($canCreateReceitas || $canCreateDespesas)
        <li>
            <button type="button"
                    class="dropdown-item duplicate-transaction"
                    data-transaction-id="{{ $transaction->id }}">
                <i class="bx bx-copy me-2"></i>Duplicar
            </button>
        </li>
        @endif
        @if($isReceita && !$transaction->is_paid)
        <li>
            <button type="button"
                    class="dropdown-item mp-open-checkout"
                    data-transaction-id="{{ $transaction->id }}"
                    data-transaction-description="{{ $transaction->description }}"
                    data-transaction-amount="{{ number_format((float) $transaction->amount, 2, '.', '') }}">
                <i class="bx bx-credit-card me-2"></i>Receber com Mercado Pago
            </button>
        </li>
        @endif
        @if($canDelete)
        <li><hr class="dropdown-divider"></li>
        <li>
            <form action="{{ route('financial.transactions.destroy', $transaction) }}"
                  method="POST"
                  onsubmit="return confirm('Tem certeza que deseja remover esta transação?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item text-danger">
                    <i class="bx bx-trash me-2"></i>Excluir
                </button>
            </form>
        </li>
        @endif
    </ul>
</div>
