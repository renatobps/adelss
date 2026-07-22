@extends('layouts.porto')

@section('title', 'Despesas Fixas')

@section('page-title', 'Despesas Fixas')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Despesas Fixas</span></li>
@endsection

@section('content')
@php
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $monthLabel = $reference->translatedFormat('F Y');
    $monthShort = $reference->translatedFormat('M');
    $pendingCount = $pendingGeneration->count();
    $allGenerated = $activeCount > 0 && $pendingCount === 0;
@endphp

<div class="fixed-expenses">
    {{-- Header --}}
    <div class="fixed-expenses-card mb-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="d-flex align-items-start gap-2">
                <span class="fixed-expenses-card__icon"><i class="bx bx-refresh"></i></span>
                <div>
                    <h2 class="fixed-expenses-card__title mb-0">
                        Despesas Fixas
                        <i class="bx bx-info-circle text-muted fs-6" title="Despesas que se repetem todo mês"></i>
                    </h2>
                    <p class="fixed-expenses-card__subtitle mb-0">Gerencie despesas que se repetem mensalmente</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($pendingCount > 0)
                    <span class="badge fixed-expenses-badge-warn">{{ $pendingCount }} não gerada(s)</span>
                @endif
                @if($canManage)
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#fixedExpenseModal">
                        <i class="bx bx-plus"></i> Nova
                    </button>
                @endif
            </div>
        </div>

        @if($activeCount === 0)
            <div class="alert alert-light border mt-3 mb-0">
                Cadastre a primeira despesa fixa (água, energia, internet, etc.) para começar.
            </div>
        @elseif($pendingCount > 0)
            <form method="POST" action="{{ route('financial.fixed-expenses.generate') }}" class="mt-3 mb-0">
                @csrf
                <input type="hidden" name="month" value="{{ $reference->format('Y-m') }}">
                <input type="hidden" name="months_ahead" value="12">
                <button type="submit" class="btn btn-primary w-100 fixed-expenses-generate-btn">
                    <i class="bx bx-calendar-plus me-1"></i>
                    Gerar {{ $pendingCount }} Despesa(s) de {{ $monthLabel }}
                </button>
                <p class="text-muted small text-center mt-2 mb-2">
                    Ao gerar, as despesas serão criadas como "Pendentes" neste mês e nos meses subsequentes. Após pagar, marque como "Pago".
                </p>
                <div class="alert fixed-expenses-alert-warn mb-0">
                    <i class="bx bx-error-circle"></i>
                    {{ $pendingCount }} despesa(s) fixa(s) ainda não foram geradas este mês. Clique no botão acima para criar as transações.
                </div>
            </form>
        @else
            <div class="alert fixed-expenses-alert-ok mt-3 mb-0">
                <i class="bx bx-check-circle"></i>
                Todas as despesas fixas de {{ $monthLabel }} já foram geradas.
            </div>
        @endif
    </div>

    {{-- Mês atual --}}
    <div class="fixed-expenses-card mb-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <div>
                <h3 class="fixed-expenses-section-title mb-1">
                    <i class="bx bx-calendar text-primary"></i>
                    Despesas de {{ $monthLabel }}
                </h3>
                <div class="text-muted small">
                    {{ $paidCount }} de {{ $generatedCount }} paga(s) • Total: {{ $fmt($monthTotal) }}
                </div>
            </div>
            @if($monthPendingTotal > 0)
                <span class="badge fixed-expenses-badge-warn">{{ $fmt($monthPendingTotal) }} pendente</span>
            @endif
        </div>

        <form method="GET" action="{{ route('financial.fixed-expenses.index') }}" class="mb-3">
            <label class="form-label small mb-1">Mês de referência</label>
            <input type="month" name="month" class="form-control form-control-sm" style="max-width: 220px"
                   value="{{ $reference->format('Y-m') }}" onchange="this.form.submit()">
        </form>

        <p class="text-muted small mb-3">
            <i class="bx bx-info-circle"></i>
            Marque como "Pago" após efetuar o pagamento de cada despesa.
        </p>

        @if($monthItems->where('generated', true)->isEmpty())
            <p class="text-muted mb-0 small">Nenhuma despesa gerada para este mês ainda.</p>
        @else
            <div class="fixed-expenses-month-list">
                @foreach($monthItems->where('generated', true) as $item)
                    @php
                        $tx = $item['transaction'];
                        $due = $item['due_date'];
                        $isPaid = $item['paid'];
                        $isOverdue = !$isPaid && $due->lt(now()->startOfDay());
                    @endphp
                    <div class="fixed-expenses-month-item {{ $isPaid ? 'is-paid' : ($isOverdue ? 'is-overdue' : '') }}">
                        <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                            <span class="fixed-expenses-month-item__status">
                                @if($isPaid)
                                    <i class="bx bx-check-circle text-success"></i>
                                @else
                                    <i class="bx bx-error text-danger"></i>
                                @endif
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $item['fixed']->description }}</div>
                                <div class="small {{ $isPaid ? 'text-success' : ($isOverdue ? 'text-danger' : 'text-muted') }}">
                                    @if($isPaid)
                                        Paga em {{ $tx->transaction_date?->format('d/m/Y') }}
                                    @elseif($isOverdue)
                                        Venceu dia {{ $due->format('d/m') }}
                                    @else
                                        Vence dia {{ $due->format('d/m') }}
                                    @endif
                                    @if($item['fixed']->amount_variable)
                                        <span class="ms-1 badge bg-warning-subtle text-warning-emphasis">Valor variável</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <strong class="{{ $isPaid ? 'text-success' : 'text-danger' }}">{{ $fmt($item['amount']) }}</strong>
                            @if($canManage && !$isPaid)
                                <form method="POST" action="{{ route('financial.fixed-expenses.pay', $tx) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bx bx-check"></i> Pagar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Cadastradas --}}
    <div class="fixed-expenses-card">
        <h3 class="fixed-expenses-section-title mb-1">
            <i class="bx bx-list-ul text-primary"></i>
            Despesas Cadastradas
        </h3>
        <p class="text-muted small mb-3">
            {{ $fixedExpenses->count() }} despesa(s) fixa(s) configurada(s) • Dia de vencimento configurado para cada uma
        </p>

        @if($fixedExpenses->isEmpty())
            <p class="text-muted mb-0">Nenhuma despesa fixa cadastrada.</p>
        @else
            <div class="fixed-expenses-config-list">
                @foreach($fixedExpenses as $fixed)
                    @php
                        $monthRow = $monthItems->first(fn ($row) => $row['fixed']->id === $fixed->id);
                        $tx = $monthRow['transaction'] ?? null;
                        $monthStatus = !$fixed->is_active
                            ? 'Inativa'
                            : ($tx ? ($tx->is_paid ? 'Paga' : 'Pendente') : 'Não gerada');
                        $statusClass = match ($monthStatus) {
                            'Paga' => 'ok',
                            'Pendente' => 'warn',
                            'Inativa' => 'muted',
                            default => 'warn',
                        };
                    @endphp
                    <div class="fixed-expenses-config-item {{ $fixed->is_active ? '' : 'is-inactive' }}">
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <strong>{{ $fixed->description }}</strong>
                                <span class="badge fixed-expenses-badge-{{ $statusClass }}">{{ strtolower($monthShort) }}: {{ $monthStatus }}</span>
                            </div>
                            <div class="small {{ $tx && !$tx->is_paid ? 'text-danger' : 'text-muted' }}">
                                {{ $fmt($fixed->amount) }} • Vencimento: todo dia {{ $fixed->due_day }}
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @if($fixed->category)
                                    <span class="fixed-expenses-chip">{{ $fixed->category->name }}</span>
                                @endif
                                @if($fixed->account)
                                    <span class="text-muted small">{{ $fixed->account->name }}</span>
                                @endif
                                @if($fixed->amount_variable)
                                    <span class="fixed-expenses-chip is-warn">Valor variável</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            @if($canManage && $tx && !$tx->is_paid)
                                <form method="POST" action="{{ route('financial.fixed-expenses.pay', $tx) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bx bx-check"></i> Pagar
                                    </button>
                                </form>
                            @endif
                            @if($canManage)
                                <div class="form-check form-switch financial-automation-switch-wrap mb-0">
                                    <input class="form-check-input financial-automation-switch js-fixed-toggle"
                                           type="checkbox"
                                           role="switch"
                                           data-toggle-url="{{ route('financial.fixed-expenses.toggle', $fixed) }}"
                                           @checked($fixed->is_active)>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button type="button"
                                                    class="dropdown-item js-edit-fixed"
                                                    data-id="{{ $fixed->id }}"
                                                    data-description="{{ $fixed->description }}"
                                                    data-amount="{{ $fixed->amount }}"
                                                    data-due-day="{{ $fixed->due_day }}"
                                                    data-category-id="{{ $fixed->category_id }}"
                                                    data-account-id="{{ $fixed->account_id }}"
                                                    data-cost-center-id="{{ $fixed->cost_center_id }}"
                                                    data-contact-id="{{ $fixed->contact_id }}"
                                                    data-notes="{{ $fixed->notes }}"
                                                    data-is-active="{{ $fixed->is_active ? '1' : '0' }}"
                                                    data-amount-variable="{{ $fixed->amount_variable ? '1' : '0' }}">
                                                Editar
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('financial.fixed-expenses.destroy', $fixed) }}"
                                                  onsubmit="return confirm('Remover esta despesa fixa? As transações já geradas não serão apagadas.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">Excluir</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@if($canManage)
<div class="modal fade" id="fixedExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('financial.fixed-expenses.store') }}" id="fixedExpenseForm">
                @csrf
                <div id="fixedExpenseMethod"></div>
                <input type="hidden" name="month" value="{{ $reference->format('Y-m') }}">

                <div class="modal-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fixed-expenses-card__icon"><i class="bx bx-refresh"></i></span>
                        <div>
                            <h5 class="modal-title mb-0" id="fixedExpenseModalTitle">Nova Despesa Fixa</h5>
                            <div class="text-muted small" id="fixedExpenseModalSubtitle">Cadastre uma despesa que se repete todo mês</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="fe_description">Descrição <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fe_description" name="description" required maxlength="255" placeholder="Ex: Internet">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" value="Saída (Despesa)" disabled>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="fe_amount">Valor (R$) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="fe_amount" name="amount" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fe_due_day">Dia do Vencimento <span class="text-danger">*</span></label>
                            <select class="form-select" id="fe_due_day" name="due_day" required>
                                @for($d = 1; $d <= 28; $d++)
                                    <option value="{{ $d }}">Dia {{ $d }}</option>
                                @endfor
                            </select>
                            <div class="form-text" id="fe_due_day_help">Todo mês no dia selecionado</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fe_category_id">Categoria <span class="text-danger">*</span></label>
                            <select class="form-select" id="fe_category_id" name="category_id" required>
                                <option value="">Selecione...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fe_account_id">Conta <span class="text-danger">*</span></label>
                            <select class="form-select" id="fe_account_id" name="account_id" required>
                                <option value="">Selecione...</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fe_cost_center_id">Centro de Custo</label>
                            <select class="form-select" id="fe_cost_center_id" name="cost_center_id">
                                <option value="">Nenhum</option>
                                @foreach($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fe_contact_id">Fornecedor</label>
                            <select class="form-select" id="fe_contact_id" name="contact_id">
                                <option value="">Nenhum</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="fe_notes">Observações</label>
                            <textarea class="form-control" id="fe_notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="financial-automation-inline-toggle mt-3">
                        <div>
                            <div class="fw-semibold">Ativo</div>
                            <div class="text-muted small">Quando ativo, será incluído na geração mensal</div>
                        </div>
                        <div class="form-check form-switch financial-automation-switch-wrap mb-0">
                            <input class="form-check-input financial-automation-switch" type="checkbox" role="switch"
                                   id="fe_is_active" name="is_active" value="1" checked>
                        </div>
                    </div>

                    <div class="fixed-expenses-variable-box mt-3">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">
                                    <i class="bx bx-bolt text-warning"></i>
                                    Valor varia a cada mês
                                </div>
                                <div class="text-muted small">
                                    Ideal para luz, água, gás. O lançamento é gerado normalmente, mas você confirma o valor real antes de pagar.
                                </div>
                            </div>
                            <div class="form-check form-switch financial-automation-switch-wrap mb-0">
                                <input class="form-check-input financial-automation-switch" type="checkbox" role="switch"
                                       id="fe_amount_variable" name="amount_variable" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="fixedExpenseSubmitBtn">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
.fixed-expenses-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.85rem;
    padding: 1.15rem 1.2rem;
}
.fixed-expenses-card__icon {
    display: inline-flex;
    width: 2.25rem;
    height: 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.65rem;
    background: #dbeafe;
    color: #2563eb;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.fixed-expenses-card__title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1f2937;
}
.fixed-expenses-card__subtitle,
.fixed-expenses-section-title {
    color: #1f2937;
}
.fixed-expenses-card__subtitle { color: #6b7280; font-size: 0.9rem; }
.fixed-expenses-section-title {
    font-size: 1.05rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.fixed-expenses-generate-btn {
    padding: 0.85rem 1rem;
    font-weight: 600;
}
.fixed-expenses-alert-ok {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    border-radius: 0.65rem;
    padding: 0.75rem 0.9rem;
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
}
.fixed-expenses-alert-warn {
    background: #fff7ed;
    border: 1px solid #fdba74;
    color: #9a3412;
    border-radius: 0.65rem;
    padding: 0.75rem 0.9rem;
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
}
.fixed-expenses-badge-warn {
    background: #ffedd5;
    color: #c2410c;
    font-weight: 600;
}
.fixed-expenses-badge-ok {
    background: #dcfce7;
    color: #166534;
    font-weight: 600;
}
.fixed-expenses-badge-muted {
    background: #f3f4f6;
    color: #6b7280;
    font-weight: 600;
}
.fixed-expenses-month-item,
.fixed-expenses-config-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    padding: 0.85rem 0;
    border-bottom: 1px solid #f3f4f6;
    flex-wrap: wrap;
}
.fixed-expenses-month-item:last-child,
.fixed-expenses-config-item:last-child { border-bottom: 0; }
.fixed-expenses-config-item.is-inactive { opacity: 0.65; }
.fixed-expenses-chip {
    display: inline-flex;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    border: 1px solid #fca5a5;
    color: #b91c1c;
    font-size: 0.75rem;
    font-weight: 600;
}
.fixed-expenses-chip.is-warn {
    border-color: #fdba74;
    color: #c2410c;
    background: #fff7ed;
}
.fixed-expenses-variable-box {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 0.75rem;
    padding: 0.9rem 1rem;
}
.financial-automation-inline-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.9rem 1rem;
    border: 1px solid #eef2f7;
    border-radius: 0.75rem;
    background: #fafbfc;
}
.financial-automation-switch-wrap {
    display: flex;
    align-items: center;
    min-height: 1.85rem;
    padding-left: 3.25rem;
    margin-bottom: 0;
}
.financial-automation-switch-wrap .form-check-input.financial-automation-switch {
    width: 3rem !important;
    height: 1.65rem !important;
    margin-top: 0;
    margin-left: -3.25rem !important;
    cursor: pointer;
    flex-shrink: 0;
    border-radius: 2rem !important;
}
.financial-module-nav__item.is-active[href*="fixed-expenses"] {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}
.financial-module-nav__item.is-active[href*="fixed-expenses"] i {
    color: #fff;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const form = document.getElementById('fixedExpenseForm');
    const methodBox = document.getElementById('fixedExpenseMethod');
    const modalEl = document.getElementById('fixedExpenseModal');
    const titleEl = document.getElementById('fixedExpenseModalTitle');
    const subtitleEl = document.getElementById('fixedExpenseModalSubtitle');
    const submitBtn = document.getElementById('fixedExpenseSubmitBtn');
    const dueDayEl = document.getElementById('fe_due_day');
    const dueHelpEl = document.getElementById('fe_due_day_help');
    const storeUrl = @json(route('financial.fixed-expenses.store'));

    function resetForm() {
        if (!form) return;
        form.action = storeUrl;
        form.reset();
        methodBox.innerHTML = '';
        document.getElementById('fe_is_active').checked = true;
        document.getElementById('fe_amount_variable').checked = false;
        titleEl.textContent = 'Nova Despesa Fixa';
        subtitleEl.textContent = 'Cadastre uma despesa que se repete todo mês';
        submitBtn.textContent = 'Cadastrar';
        updateDueHelp();
    }

    function updateDueHelp() {
        if (!dueHelpEl || !dueDayEl) return;
        dueHelpEl.textContent = `Todo mês no dia ${dueDayEl.value}`;
    }

    dueDayEl?.addEventListener('change', updateDueHelp);

    modalEl?.addEventListener('hidden.bs.modal', resetForm);

    document.querySelectorAll('.js-edit-fixed').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!form) return;
            form.action = `{{ url('financial/fixed-expenses') }}/${btn.dataset.id}`;
            methodBox.innerHTML = '@method('PUT')';
            document.getElementById('fe_description').value = btn.dataset.description || '';
            document.getElementById('fe_amount').value = btn.dataset.amount || '';
            document.getElementById('fe_due_day').value = btn.dataset.dueDay || '1';
            document.getElementById('fe_category_id').value = btn.dataset.categoryId || '';
            document.getElementById('fe_account_id').value = btn.dataset.accountId || '';
            document.getElementById('fe_cost_center_id').value = btn.dataset.costCenterId || '';
            document.getElementById('fe_contact_id').value = btn.dataset.contactId || '';
            document.getElementById('fe_notes').value = btn.dataset.notes || '';
            document.getElementById('fe_is_active').checked = btn.dataset.isActive === '1';
            document.getElementById('fe_amount_variable').checked = btn.dataset.amountVariable === '1';
            titleEl.textContent = 'Editar Despesa Fixa';
            subtitleEl.textContent = 'Atualize os dados da despesa recorrente';
            submitBtn.textContent = 'Salvar';
            updateDueHelp();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });

    document.querySelectorAll('.js-fixed-toggle').forEach((switchEl) => {
        switchEl.addEventListener('change', async () => {
            const enabled = switchEl.checked;
            switchEl.disabled = true;
            try {
                const response = await fetch(switchEl.dataset.toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ is_active: enabled }),
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    switchEl.checked = !enabled;
                    alert(data.message || 'Não foi possível atualizar.');
                } else {
                    location.reload();
                }
            } catch (e) {
                switchEl.checked = !enabled;
                alert('Erro ao atualizar.');
            } finally {
                switchEl.disabled = false;
            }
        });
    });

    updateDueHelp();
})();
</script>
@endpush
