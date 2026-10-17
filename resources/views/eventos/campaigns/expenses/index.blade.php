@extends('layouts.porto')

@section('title', 'Gastos — ' . $campaign->name)

@section('page-title', 'Gastos do evento')

@section('breadcrumbs')
    <li><a href="{{ route('eventos.campaigns.index') }}">Gestor de Eventos</a></li>
    <li><a href="{{ route('eventos.campaigns.index') }}">Campanhas</a></li>
    <li><a href="{{ route('eventos.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></li>
    <li><span>Gastos</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canManage = fn (string $action) => $isAdmin
        || $user->hasPermission('eventos.campanhas.manage')
        || $user->hasPermission("eventos.campanhas.{$action}");
    $canCreate = $canManage('create');
    $canEdit = $canManage('edit');
    $canDelete = $canManage('delete');
    $money = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['from'] || $filters['to'];
@endphp

@include('eventos.campaigns.partials.alerts')
@include('eventos.campaigns.partials.campaign-tabs', ['active' => 'gastos'])

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0">{{ $campaign->name }}</h4>
        <small class="text-muted">Registre cada saída do evento (comida, aluguel, decoração...) com o comprovante da despesa.</small>
    </div>
    @if($canCreate)
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal" data-mode="create">
            <i class="bx bx-plus me-1"></i>Registrar gasto
        </button>
    @endif
</div>

<!-- Indicadores -->
<div class="row mb-1">
    @php
        $cards = [
            ['label' => 'Arrecadado', 'value' => $money($totals['raised']), 'icon' => 'bx bx-trending-up', 'color' => 'success'],
            ['label' => 'Total gasto', 'value' => $money($totals['spent']), 'icon' => 'bx bx-trending-down', 'color' => 'danger'],
            ['label' => 'Saldo', 'value' => $money($totals['balance']), 'icon' => 'bx bx-wallet', 'color' => $totals['balance'] >= 0 ? 'primary' : 'danger'],
            ['label' => 'Lançamentos', 'value' => $totals['count'], 'icon' => 'bx bx-receipt', 'color' => 'info'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-6 col-xl-3 mb-3">
            <section class="card h-100">
                <div class="card-body py-3 d-flex align-items-center gap-2">
                    <i class="{{ $card['icon'] }} fs-3 text-{{ $card['color'] }} flex-shrink-0"></i>
                    <div class="min-w-0">
                        <div class="text-muted small">{{ $card['label'] }}</div>
                        <strong>{{ $card['value'] }}</strong>
                    </div>
                </div>
            </section>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-4 mb-3">
        <section class="card h-100">
            <header class="card-header"><h5 class="mb-0"><i class="bx bx-pie-chart-alt-2 me-2"></i>Por categoria</h5></header>
            <div class="card-body p-0">
                @if(empty($byCategory))
                    <p class="text-muted text-center py-4 mb-0">Nenhum gasto registrado.</p>
                @else
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            @foreach($byCategory as $cat => $total)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('eventos.campaigns.expenses.index', ['campaign' => $campaign->id, 'category' => $cat]) }}" class="text-decoration-none">
                                            {{ $categories[$cat] ?? ucfirst($cat) }}
                                        </a>
                                    </td>
                                    <td class="text-end pe-3"><strong>{{ $money($total) }}</strong></td>
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <td class="ps-3"><strong>Total</strong></td>
                                <td class="text-end pe-3"><strong>{{ $money($totals['spent']) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>
        </section>
    </div>

    <div class="col-lg-8 mb-3">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Lançamentos</h5>
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="search" name="q" class="form-control form-control-sm" style="width: 170px" placeholder="Buscar..." value="{{ $filters['q'] }}">
                    <select name="category" class="form-select form-select-sm" style="width: 160px">
                        <option value="">Todas categorias</option>
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="from" class="form-control form-control-sm" style="width: 140px" value="{{ $filters['from'] }}" title="De">
                    <input type="date" name="to" class="form-control form-control-sm" style="width: 140px" value="{{ $filters['to'] }}" title="Até">
                    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bx bx-filter"></i></button>
                    @if($hasFilters)
                        <a href="{{ route('eventos.campaigns.expenses.index', $campaign) }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
                    @endif
                </form>
            </header>

            @if($expenses->isEmpty())
                <div class="card-body">
                    <p class="text-muted text-center py-4 mb-0">
                        @if($hasFilters)
                            Nenhum gasto encontrado com os filtros aplicados.
                        @else
                            Nenhum gasto registrado ainda. Clique em <strong>Registrar gasto</strong> para lançar a primeira despesa com o comprovante.
                        @endif
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Data</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Fornecedor</th>
                                <th class="text-center">Comprovantes</th>
                                <th class="text-end">Valor</th>
                                <th class="text-end pe-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenses as $expense)
                                @php
                                    $attachmentsJson = $expense->attachments->map(function ($a) {
                                        return ['id' => $a->id, 'name' => $a->file_name, 'url' => route('eventos.campaigns.expenses.attachment', $a)];
                                    })->values()->toJson();
                                @endphp
                                <tr>
                                    <td class="ps-3 text-nowrap">{{ $expense->expense_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div>{{ $expense->description }}</div>
                                        @if($expense->notes)
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($expense->notes, 80) }}</small>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $expense->categoryLabel() }}</span></td>
                                    <td>
                                        {{ $expense->supplier ?: '—' }}
                                        @if($expense->payment_method)
                                            <div><small class="text-muted">{{ $expense->paymentMethodLabel() }}</small></div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex flex-wrap gap-1 justify-content-center">
                                            @foreach($expense->attachments as $attachment)
                                                <a href="{{ route('eventos.campaigns.expenses.attachment', $attachment) }}" target="_blank" rel="noopener"
                                                   class="btn btn-sm btn-outline-secondary py-0 px-1" title="{{ $attachment->file_name }} ({{ $attachment->humanSize() }})">
                                                    <i class="bx {{ $attachment->isImage() ? 'bx-image' : 'bxs-file-pdf' }}"></i>
                                                </a>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end text-nowrap"><strong class="text-danger">{{ $money($expense->amount) }}</strong></td>
                                    <td class="text-end pe-3 text-nowrap">
                                        @if($canEdit)
                                            <button type="button" class="btn btn-sm btn-outline-secondary js-edit-expense"
                                                    data-action="{{ route('eventos.campaigns.expenses.update', $expense) }}"
                                                    data-description="{{ $expense->description }}"
                                                    data-category="{{ $expense->category }}"
                                                    data-amount="{{ number_format((float) $expense->amount, 2, '.', '') }}"
                                                    data-date="{{ $expense->expense_date->format('Y-m-d') }}"
                                                    data-supplier="{{ $expense->supplier }}"
                                                    data-payment="{{ $expense->payment_method }}"
                                                    data-notes="{{ $expense->notes }}"
                                                    data-attachments="{{ $attachmentsJson }}"
                                                    title="Editar"><i class="bx bx-edit"></i></button>
                                        @endif
                                        @if($canDelete)
                                            <form method="POST" action="{{ route('eventos.campaigns.expenses.destroy', $expense) }}" class="d-inline"
                                                  onsubmit="return confirm('Excluir este gasto e seus comprovantes?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bx bx-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="5" class="ps-3"><strong>Total {{ $hasFilters ? 'filtrado' : '' }} ({{ $expenses->total() }} lançamento(s))</strong></td>
                                <td class="text-end"><strong>{{ $money($expenses->getCollection()->sum('amount')) }}</strong></td>
                                <td></td>
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

@if($canCreate || $canEdit)
<!-- Modal: registrar / editar gasto -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="expenseForm" action="{{ route('eventos.campaigns.expenses.store', $campaign) }}" class="modal-content" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="expenseMethod" value="POST">
            <input type="hidden" name="form_action" id="expFormAction" value="{{ route('eventos.campaigns.expenses.store', $campaign) }}">
            <div class="modal-header">
                <h5 class="modal-title" id="expenseModalTitle">Registrar gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Descrição *</label>
                        <input type="text" name="description" id="expDescription" class="form-control" required maxlength="255"
                               placeholder="Ex: Aluguel da chácara, lanche das crianças..." value="{{ old('description') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Categoria *</label>
                        <select name="category" id="expCategory" class="form-select" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', 'outros') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Valor (R$) *</label>
                        <input type="number" name="amount" id="expAmount" class="form-control" step="0.01" min="0.01" required
                               placeholder="0,00" value="{{ old('amount') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Data do gasto *</label>
                        <input type="date" name="expense_date" id="expDate" class="form-control" required value="{{ old('expense_date', now()->toDateString()) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Forma de pagamento</label>
                        <select name="payment_method" id="expPayment" class="form-select">
                            <option value="">— Não informado —</option>
                            @foreach($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Fornecedor / Pago a</label>
                        <input type="text" name="supplier" id="expSupplier" class="form-control" maxlength="255" placeholder="Opcional" value="{{ old('supplier') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea name="notes" id="expNotes" class="form-control" rows="2" maxlength="2000" placeholder="Opcional">{{ old('notes') }}</textarea>
                </div>

                <div class="mb-0">
                    <label class="form-label">Comprovante de despesa * <small class="text-muted fw-normal">(nota fiscal, recibo ou foto — JPG, PNG, WEBP, HEIC ou PDF, até 10MB cada, máx. 5)</small></label>
                    <div id="expExistingAttachments" class="d-flex flex-wrap gap-2 mb-2"></div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="expPickFiles">
                            <i class="bx bx-paperclip me-1"></i>Selecionar arquivo
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-md-none" id="expTakePhoto">
                            <i class="bx bx-camera me-1"></i>Tirar foto
                        </button>
                    </div>
                    <input type="file" class="d-none" id="expFilesUpload" multiple accept="image/*,application/pdf">
                    <input type="file" class="d-none" id="expFilesCamera" accept="image/*" capture="environment">
                    <input type="file" class="d-none" id="expFiles" name="attachments[]" multiple>
                    <ul class="list-unstyled small mb-0" id="expFilesList"></ul>
                    <div class="form-text text-danger d-none" id="expFilesRequired">
                        <i class="bx bx-error-circle me-1"></i>É obrigatório anexar ao menos um comprovante.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="expSubmit"><i class="bx bx-save me-1"></i>Salvar gasto</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('expenseModal');
    if (!modalEl) return;

    const form = document.getElementById('expenseForm');
    const storeAction = form.getAttribute('action');
    const title = document.getElementById('expenseModalTitle');
    const method = document.getElementById('expenseMethod');
    const formAction = document.getElementById('expFormAction');
    const existingBox = document.getElementById('expExistingAttachments');
    const filesInput = document.getElementById('expFiles');
    const uploadInput = document.getElementById('expFilesUpload');
    const cameraInput = document.getElementById('expFilesCamera');
    const filesList = document.getElementById('expFilesList');
    const requiredMsg = document.getElementById('expFilesRequired');

    // Acumula arquivos de várias seleções (galeria + câmera) no input real.
    let dt = new DataTransfer();

    function renderFiles() {
        filesList.innerHTML = '';
        Array.from(dt.files).forEach(function (file, index) {
            const li = document.createElement('li');
            li.className = 'd-flex align-items-center gap-2 py-1';
            li.innerHTML = '<i class="bx ' + (file.type.startsWith('image/') ? 'bx-image' : 'bxs-file-pdf') + '"></i>'
                + '<span class="text-truncate" style="max-width: 320px">' + file.name + '</span>'
                + '<span class="text-muted">(' + (file.size / 1024).toFixed(0) + ' KB)</span>';
            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'btn btn-link btn-sm text-danger p-0 ms-1';
            rm.innerHTML = '<i class="bx bx-x"></i>';
            rm.addEventListener('click', function () {
                const next = new DataTransfer();
                Array.from(dt.files).forEach((f, i) => { if (i !== index) next.items.add(f); });
                dt = next;
                filesInput.files = dt.files;
                renderFiles();
            });
            li.appendChild(rm);
            filesList.appendChild(li);
        });
        requiredMsg.classList.add('d-none');
    }

    function addFiles(list) {
        Array.from(list).forEach(f => { if (dt.files.length < 5) dt.items.add(f); });
        filesInput.files = dt.files;
        renderFiles();
    }

    document.getElementById('expPickFiles').addEventListener('click', () => uploadInput.click());
    document.getElementById('expTakePhoto')?.addEventListener('click', () => cameraInput.click());
    uploadInput.addEventListener('change', function () { addFiles(this.files); this.value = ''; });
    cameraInput.addEventListener('change', function () { addFiles(this.files); this.value = ''; });

    function remainingExisting() {
        return existingBox.querySelectorAll('input[name="remove_attachments[]"]:not(:checked)').length;
    }

    form.addEventListener('submit', function (event) {
        if (dt.files.length === 0 && remainingExisting() === 0) {
            event.preventDefault();
            requiredMsg.classList.remove('d-none');
        }
    });

    function resetForm() {
        form.reset();
        dt = new DataTransfer();
        filesInput.files = dt.files;
        renderFiles();
        existingBox.innerHTML = '';
        document.getElementById('expDate').value = '{{ now()->toDateString() }}';
    }

    function openCreate() {
        resetForm();
        form.action = storeAction;
        formAction.value = storeAction;
        method.value = 'POST';
        title.textContent = 'Registrar gasto';
    }

    function openEdit(btn) {
        resetForm();
        form.action = btn.dataset.action;
        formAction.value = btn.dataset.action;
        method.value = 'PUT';
        title.textContent = 'Editar gasto';
        document.getElementById('expDescription').value = btn.dataset.description || '';
        document.getElementById('expCategory').value = btn.dataset.category || 'outros';
        document.getElementById('expAmount').value = btn.dataset.amount || '';
        document.getElementById('expDate').value = btn.dataset.date || '';
        document.getElementById('expSupplier').value = btn.dataset.supplier || '';
        document.getElementById('expPayment').value = btn.dataset.payment || '';
        document.getElementById('expNotes').value = btn.dataset.notes || '';

        let attachments = [];
        try { attachments = JSON.parse(btn.dataset.attachments || '[]'); } catch (e) {}
        attachments.forEach(function (a) {
            const wrap = document.createElement('div');
            wrap.className = 'border rounded px-2 py-1 d-flex align-items-center gap-2 small';
            wrap.innerHTML = '<a href="' + a.url + '" target="_blank" rel="noopener" class="text-truncate" style="max-width: 220px">'
                + '<i class="bx bx-paperclip me-1"></i>' + a.name + '</a>'
                + '<label class="form-check form-check-inline mb-0 ms-1 text-danger" title="Remover este comprovante">'
                + '<input type="checkbox" class="form-check-input" name="remove_attachments[]" value="' + a.id + '"> remover</label>';
            existingBox.appendChild(wrap);
        });
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (trigger && trigger.dataset.mode === 'create') openCreate();
    });

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.js-edit-expense');
        if (!btn) return;
        openEdit(btn);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    // Reabre o modal quando a validação do servidor falhou (os campos de texto
    // voltam preenchidos; os arquivos precisam ser selecionados de novo).
    @if($errors->any() && old('description') !== null)
        form.action = @json(old('form_action')) || storeAction;
        method.value = @json(old('_method', 'POST'));
        formAction.value = form.action;
        title.textContent = method.value === 'PUT' ? 'Editar gasto' : 'Registrar gasto';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        requiredMsg.classList.toggle('d-none', !@json($errors->has('attachments') || $errors->has('attachments.*')));
    @endif
});
</script>
@endpush
@endsection
