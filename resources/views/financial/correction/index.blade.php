@extends('layouts.porto')

@section('title', 'Correção')

@section('page-title', 'Correção')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Correção</span></li>
@endsection

@section('content')
<div class="fr-page">
@include('financial.reports.partials.styles')

<div class="alert alert-warning mb-4">
    <i class="bx bx-wrench me-2"></i>
    Tela temporária: altere nome, descrição e valor — a gravação é imediata, sem notificação.
</div>

@include('financial.reports.partials.full-filters', [
    'action' => route('financial.correction.index'),
    'categoriesReceitas' => $categoriesReceitas,
    'categoriesDespesas' => $categoriesDespesas,
])

<div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <strong>Resultados: {{ $transactions->total() }} transações</strong>
            <select class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()" form="filterForm" name="per_page">
                <option value="50" {{ request('per_page', 100) == 50 ? 'selected' : '' }}>50 por página</option>
                <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100 por página</option>
                <option value="200" {{ request('per_page', 100) == 200 ? 'selected' : '' }}>200 por página</option>
            </select>
        </div>

        @if($transactions->count() > 0)
        <div class="table-responsive financial-tx-table-wrap">
            <table class="table table-hover align-middle financial-tx-table mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th class="text-end">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                        @php
                            $isReceita = $transaction->type === 'receita';
                            $typeLabel = $isReceita ? 'Receita' : 'Despesa';
                            $selectedMemberId = $transaction->member_id ? (string) $transaction->member_id : '';
                            $fallbackName = $transaction->listingPersonName();
                        @endphp
                        <tr data-id="{{ $transaction->id }}">
                            <td class="text-nowrap">{{ optional($transaction->transaction_date)->format('d/m/Y') }}</td>
                            <td>
                                <select class="form-select form-select-sm js-correction-name"
                                        data-id="{{ $transaction->id }}"
                                        data-selected="{{ $selectedMemberId }}"
                                        data-fallback="{{ $fallbackName }}"
                                        aria-label="Nome">
                                    <option value="">—</option>
                                </select>
                            </td>
                            <td>
                                <input type="text"
                                       class="form-control form-control-sm js-correction-description"
                                       data-id="{{ $transaction->id }}"
                                       data-original="{{ $transaction->description }}"
                                       value="{{ $transaction->description }}"
                                       maxlength="255"
                                       aria-label="Descrição">
                            </td>
                            <td>{{ $typeLabel }}</td>
                            <td class="text-end">
                                <input type="text"
                                       inputmode="decimal"
                                       class="form-control form-control-sm text-end js-correction-amount {{ $isReceita ? 'is-receita' : 'is-despesa' }}"
                                       data-id="{{ $transaction->id }}"
                                       data-original="{{ number_format((float) $transaction->amount, 2, ',', '.') }}"
                                       value="{{ number_format((float) $transaction->amount, 2, ',', '.') }}"
                                       aria-label="Valor">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $transactions->appends(request()->query())->links() }}
        </div>
        @else
            <p class="text-muted mb-0">Nenhuma transação encontrada para o período.</p>
        @endif
    </div>
</div>
</div>

@push('styles')
<style>
    .financial-tx-table-wrap { border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: auto; }
    .financial-tx-table thead th { background: #f8fafc; font-size: 0.82rem; color: #4b5563; white-space: nowrap; }
    .js-correction-name { min-width: 14rem; }
    .js-correction-description { min-width: 12rem; }
    .js-correction-amount { max-width: 8.5rem; margin-left: auto; font-weight: 700; }
    .js-correction-amount.is-receita { color: #198754; }
    .js-correction-amount.is-despesa { color: #dc3545; }
    tr.is-saved td { background: #ecfdf5; }
    tr.is-error td { background: #fef2f2; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const members = @json($members->map(fn ($m) => ['id' => (string) $m->id, 'name' => $m->name])->values());
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const nameUrl = @json(route('financial.correction.update-name', ['transaction' => '__ID__']));
    const descriptionUrl = @json(route('financial.correction.update-description', ['transaction' => '__ID__']));
    const amountUrl = @json(route('financial.correction.update-amount', ['transaction' => '__ID__']));

    function urlFor(template, id) {
        return template.replace('__ID__', id);
    }

    function fillSelect(select) {
        if (select.dataset.filled === '1') {
            return;
        }
        const selected = select.dataset.selected || '';
        const fallback = select.dataset.fallback || '';
        const blank = select.querySelector('option[value=""]');
        if (blank && (selected || fallback)) {
            blank.selected = false;
        }
        members.forEach(function (member) {
            const option = document.createElement('option');
            option.value = member.id;
            option.textContent = member.name;
            if (member.id === selected) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        if (!selected && fallback) {
            const extra = document.createElement('option');
            extra.value = '__current__';
            extra.textContent = fallback;
            extra.disabled = true;
            extra.selected = true;
            select.appendChild(extra);
        }
        select.dataset.filled = '1';
    }

    document.querySelectorAll('.js-correction-name').forEach(fillSelect);

    function mark(row, ok) {
        row.classList.remove('is-saved', 'is-error');
        row.classList.add(ok ? 'is-saved' : 'is-error');
        setTimeout(function () { row.classList.remove('is-saved', 'is-error'); }, 900);
    }

    async function patch(url, body) {
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body)
        });
        const data = await response.json().catch(function () { return {}; });
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Não foi possível salvar.');
        }
        return data;
    }

    document.querySelectorAll('.js-correction-name').forEach(function (select) {
        select.addEventListener('change', async function () {
            const row = select.closest('tr');
            try {
                await patch(urlFor(nameUrl, select.dataset.id), { member_id: select.value });
                select.dataset.selected = select.value;
                mark(row, true);
            } catch (e) {
                mark(row, false);
            }
        });
    });

    document.querySelectorAll('.js-correction-description').forEach(function (input) {
        input.addEventListener('change', async function () {
            const row = input.closest('tr');
            try {
                const data = await patch(urlFor(descriptionUrl, input.dataset.id), { description: input.value });
                input.value = data.description;
                input.dataset.original = data.description;
                mark(row, true);
            } catch (e) {
                input.value = input.dataset.original;
                mark(row, false);
            }
        });
    });

    document.querySelectorAll('.js-correction-amount').forEach(function (input) {
        input.addEventListener('change', async function () {
            const row = input.closest('tr');
            try {
                const data = await patch(urlFor(amountUrl, input.dataset.id), { amount: input.value });
                input.value = data.amount;
                input.dataset.original = data.amount;
                mark(row, true);
            } catch (e) {
                input.value = input.dataset.original;
                mark(row, false);
            }
        });
    });
})();
</script>
@endpush
@endsection
