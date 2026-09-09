@extends('layouts.porto')

@section('title', 'Recibos')

@section('page-title', 'Recibos')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><span>Recibos</span></li>
@endsection

@section('content')
<div class="fr-page">
@include('financial.reports.partials.styles')

@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canViewReceitas = $isAdmin || $user?->hasPermission('financial.receitas.view') || $user?->hasPermission('financial.receitas.manage');
    $perPage = in_array((int) request('per_page'), [50, 100, 200], true) ? (int) request('per_page') : 50;
    $tabUrl = fn (string $target) => request()->fullUrlWithQuery(['tab' => $target, 'page' => null]);
    $transactionsFilter = fn ($transaction) => route('financial.transactions.index', [
        'start_date' => $transaction->transaction_date->format('Y-m-d'),
        'end_date' => $transaction->transaction_date->format('Y-m-d'),
        'search' => $transaction->description,
    ]);
    $fileIcon = function ($attachment) {
        $type = strtolower((string) $attachment->file_type);
        $extension = strtolower(pathinfo((string) $attachment->file_name, PATHINFO_EXTENSION));

        if (str_contains($type, 'pdf') || $extension === 'pdf') {
            return 'bx bxs-file-pdf text-danger';
        }

        if (str_contains($type, 'image') || in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return 'bx bx-image text-primary';
        }

        return 'bx bx-file text-muted';
    };
    $fileSize = function ($attachment) {
        $bytes = (int) $attachment->file_size;

        if ($bytes <= 0) {
            return null;
        }

        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1, ',', '.').' MB'
            : number_format(max($bytes / 1024, 1), 0, ',', '.').' KB';
    };
@endphp

<div class="alert alert-info mb-4" style="background-color: #e3f2fd; color: #1976d2; border: none;">
    <i class="bx bx-info-circle me-2"></i>
    Localize os recibos de dízimos, ofertas e despesas pagas — tanto os comprovantes enviados por upload
    quanto os recibos emitidos pelo próprio sistema.
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card fr-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Arquivos de recibo</small>
                <strong class="fs-4">{{ $summary['total'] }}</strong>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card fr-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Enviados por upload</small>
                <strong class="fs-4 text-primary">{{ $summary['uploaded'] }}</strong>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card fr-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Emitidos pelo sistema</small>
                <strong class="fs-4 text-success">{{ $summary['generated'] }}</strong>
            </div>
        </div>
    </div>
    @if($pendingAvailable)
    <div class="col-6 col-lg-3">
        <div class="card fr-card h-100">
            <div class="card-body">
                <small class="text-muted d-block">Despesas sem recibo</small>
                <strong class="fs-4 {{ $summary['pending'] > 0 ? 'text-danger' : '' }}">{{ $summary['pending'] }}</strong>
                <small class="text-muted d-block">R$ {{ number_format($summary['pending_amount'], 2, ',', '.') }}</small>
            </div>
        </div>
    </div>
    @endif
</div>

@include('financial.receipts.partials.filters')

<div class="card fr-card">
    <div class="card-body">
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'arquivos' ? 'active' : '' }}" href="{{ $tabUrl('arquivos') }}">
                    <i class="bx bx-receipt me-1"></i>Recibos arquivados
                </a>
            </li>
            @if($revenuesAvailable)
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'receitas' ? 'active' : '' }}" href="{{ $tabUrl('receitas') }}">
                    <i class="bx bx-donate-heart me-1"></i>Dízimos e ofertas
                    @if($summary['revenues'] > 0)
                        <span class="badge bg-secondary ms-1">{{ $summary['revenues'] }}</span>
                    @endif
                </a>
            </li>
            @endif
            @if($pendingAvailable)
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'pendentes' ? 'active' : '' }}" href="{{ $tabUrl('pendentes') }}">
                    <i class="bx bx-error-circle me-1"></i>Despesas sem recibo
                    @if($summary['pending'] > 0)
                        <span class="badge bg-danger ms-1">{{ $summary['pending'] }}</span>
                    @endif
                </a>
            </li>
            @endif
        </ul>

        <div class="fr-table-toolbar">
            @php
                $paginator = match ($tab) {
                    'receitas' => $revenues,
                    'pendentes' => $pending,
                    default => $receipts,
                };
                $resultLabel = match ($tab) {
                    'receitas' => 'lançamentos',
                    'pendentes' => 'despesas',
                    default => 'recibos',
                };
            @endphp
            <div>
                <strong>Resultados: {{ $paginator->total() }} {{ $resultLabel }}</strong>
            </div>
            <div class="fr-table-toolbar__actions">
                <select class="form-select form-select-sm" form="filterForm" name="per_page" onchange="this.form.submit()" style="width: auto;">
                    <option value="50" @selected($perPage === 50)>50 por página</option>
                    <option value="100" @selected($perPage === 100)>100 por página</option>
                    <option value="200" @selected($perPage === 200)>200 por página</option>
                </select>
                <a href="{{ route('financial.receipts.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary" title="Baixar CSV">
                    <i class="bx bx-download me-1"></i>Exportar
                </a>
            </div>
        </div>

        @if($tab === 'arquivos')
            @if($receipts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Recibo nº</th>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Lançamento</th>
                                <th>Recebido de / Pago à</th>
                                <th class="text-end">Valor</th>
                                <th>Origem</th>
                                <th>Arquivo</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipts as $attachment)
                                @php
                                    $transaction = $attachment->transaction;
                                    $generated = $attachment->isSystemGenerated();
                                @endphp
                                <tr>
                                    <td><span class="font-monospace">{{ \App\Support\FinancialReceiptPresenter::number($transaction) }}</span></td>
                                    <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge {{ $transaction->type === 'receita' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $transaction->type === 'receita' ? 'Receita' : 'Despesa' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>{{ $transaction->description }}</div>
                                        @if($transaction->document_number)
                                            <small class="text-muted">Doc nº {{ $transaction->document_number }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->listingPersonName() ?: '—' }}</td>
                                    <td class="text-end">R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                                    <td>
                                        <span class="badge {{ $generated ? 'bg-info' : 'bg-secondary' }}">
                                            {{ $generated ? 'Sistema' : 'Upload' }}
                                        </span>
                                        @if($transaction->category)
                                            <small class="text-muted d-block">{{ $transaction->category->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <i class="{{ $fileIcon($attachment) }} me-1"></i>
                                        <span title="{{ $attachment->file_name }}">{{ \Illuminate\Support\Str::limit($attachment->file_name, 28) }}</span>
                                        @if($size = $fileSize($attachment))
                                            <small class="text-muted d-block">{{ $size }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('financial.receipts.file', $attachment) }}" target="_blank"
                                           class="btn btn-sm btn-outline-primary" title="Abrir arquivo">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="{{ route('financial.receipts.download', $attachment) }}"
                                           class="btn btn-sm btn-outline-secondary" title="Baixar arquivo">
                                            <i class="bx bx-download"></i>
                                        </a>
                                        <a href="{{ route('financial.transactions.receipt', $transaction) }}" target="_blank"
                                           class="btn btn-sm btn-outline-secondary" title="Imprimir recibo do sistema">
                                            <i class="bx bx-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $receipts->links() }}</div>
            @else
                <div class="p-4 text-center text-muted">
                    <i class="bx bx-receipt" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">Nenhum arquivo de recibo encontrado com os filtros selecionados.</p>
                    @if($revenuesAvailable)
                        <p class="mb-0">
                            Recibos de dízimos e ofertas não geram arquivo: procure na aba
                            <a href="{{ $tabUrl('receitas') }}">Dízimos e ofertas</a>.
                        </p>
                    @endif
                </div>
            @endif
        @elseif($tab === 'receitas')
            @if($revenues->count() > 0)
                <p class="text-muted">
                    O recibo do dízimo ou oferta é emitido na hora: imprima o talão ou envie o comprovante
                    por WhatsApp nesta tela, sem abrir o lançamento.
                </p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Recibo nº</th>
                                <th>Data</th>
                                <th>Lançamento</th>
                                <th>Recebido de</th>
                                <th>Categoria</th>
                                <th class="text-end">Valor</th>
                                <th>Comprovante</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($revenues as $transaction)
                                @php
                                    $sentAt = $transaction->notificationLogs->first()?->created_at;
                                    $canSendWhatsapp = $canViewReceitas && $transaction->is_paid && $transaction->member_id;
                                    $hasPhone = filled($transaction->member?->phone);
                                @endphp
                                <tr>
                                    <td><span class="font-monospace">{{ \App\Support\FinancialReceiptPresenter::number($transaction) }}</span></td>
                                    <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div>{{ $transaction->description }}</div>
                                        @if($transaction->document_number)
                                            <small class="text-muted">Doc nº {{ $transaction->document_number }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->listingPersonName() ?: '—' }}</td>
                                    <td>{{ $transaction->category?->name ?? '—' }}</td>
                                    <td class="text-end">R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                                    <td data-receipt-status>
                                        @if($sentAt)
                                            <span class="badge bg-success">
                                                <i class="bx bxl-whatsapp me-1"></i>{{ $sentAt->format('d/m/Y H:i') }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted">Não enviado</span>
                                        @endif
                                        @if($transaction->attachments_count > 0)
                                            <small class="text-muted d-block">
                                                {{ $transaction->attachments_count }}
                                                {{ \Illuminate\Support\Str::plural('arquivo', $transaction->attachments_count) }} arquivado
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('financial.transactions.receipt', $transaction) }}" target="_blank"
                                           class="btn btn-sm btn-outline-primary" title="Imprimir recibo do sistema">
                                            <i class="bx bx-printer"></i>
                                        </a>
                                        @if($canSendWhatsapp)
                                            <button type="button"
                                                    class="btn btn-sm {{ $sentAt ? 'btn-outline-success' : 'btn-success' }} send-receipt-whatsapp"
                                                    data-transaction-id="{{ $transaction->id }}"
                                                    data-already-sent="{{ $sentAt ? '1' : '0' }}"
                                                    data-has-phone="{{ $hasPhone ? '1' : '0' }}"
                                                    title="{{ $hasPhone ? ($sentAt ? 'Reenviar comprovante por WhatsApp' : 'Enviar comprovante por WhatsApp') : 'Membro sem telefone cadastrado' }}"
                                                    @disabled(! $hasPhone)>
                                                <i class="bx bxl-whatsapp"></i>
                                            </button>
                                        @endif
                                        <a href="{{ $transactionsFilter($transaction) }}"
                                           class="btn btn-sm btn-outline-secondary" title="Abrir lançamento">
                                            <i class="bx bx-link-external"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $revenues->links() }}</div>
            @else
                <div class="p-4 text-center text-muted">
                    <i class="bx bx-donate-heart" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">Nenhum dízimo ou oferta recebido com os filtros selecionados.</p>
                </div>
            @endif
        @else
            @if($pending->count() > 0)
                <p class="text-muted">
                    Despesas já pagas que ainda não têm nenhum recibo arquivado. Emita o recibo do sistema
                    ou anexe o comprovante assinado ao lançamento.
                </p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Recibo nº</th>
                                <th>Data</th>
                                <th>Lançamento</th>
                                <th>Pago à</th>
                                <th>Categoria</th>
                                <th class="text-end">Valor</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pending as $transaction)
                                <tr>
                                    <td><span class="font-monospace">{{ \App\Support\FinancialReceiptPresenter::number($transaction) }}</span></td>
                                    <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                    <td>
                                        <div>{{ $transaction->description }}</div>
                                        @if($transaction->document_number)
                                            <small class="text-muted">Doc nº {{ $transaction->document_number }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->listingPersonName() ?: '—' }}</td>
                                    <td>{{ $transaction->category?->name ?? '—' }}</td>
                                    <td class="text-end">R$ {{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('financial.transactions.receipt', $transaction) }}" target="_blank"
                                           class="btn btn-sm btn-outline-primary" title="Imprimir recibo do sistema">
                                            <i class="bx bx-printer"></i>
                                        </a>
                                        <a href="{{ $transactionsFilter($transaction) }}"
                                           class="btn btn-sm btn-outline-secondary" title="Abrir lançamento para anexar">
                                            <i class="bx bx-paperclip"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $pending->links() }}</div>
            @else
                <div class="p-4 text-center text-muted">
                    <i class="bx bx-check-circle" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">Todas as despesas pagas do período têm recibo arquivado.</p>
                </div>
            @endif
        @endif
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.send-receipt-whatsapp').forEach(function (button) {
    button.addEventListener('click', function () {
        if (this.disabled || this.getAttribute('data-has-phone') !== '1') {
            return;
        }

        const alreadySent = this.getAttribute('data-already-sent') === '1';
        const confirmText = alreadySent
            ? 'Reenviar o comprovante por WhatsApp para o membro?'
            : 'Enviar comprovante por WhatsApp para o membro?';

        if (!confirm(confirmText)) {
            return;
        }

        const btn = this;
        const transactionId = this.dataset.transactionId;
        btn.disabled = true;

        fetch('{{ route("financial.transactions.send-receipt", ":id") }}'.replace(':id', transactionId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, data: data };
            });
        })
        .then(function (result) {
            if (result.ok && result.data.success) {
                const cell = btn.closest('tr')?.querySelector('[data-receipt-status]');
                if (cell) {
                    const now = new Date();
                    const pad = function (n) { return String(n).padStart(2, '0'); };
                    const stamp = pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear()
                        + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes());
                    const extra = cell.querySelector('small');
                    cell.innerHTML = '<span class="badge bg-success"><i class="bx bxl-whatsapp me-1"></i>' + stamp + '</span>';
                    if (extra) {
                        cell.appendChild(extra);
                    }
                }
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-success');
                btn.setAttribute('data-already-sent', '1');
                btn.setAttribute('title', 'Reenviar comprovante por WhatsApp');
                alert('Comprovante enviado por WhatsApp com sucesso!');
                return;
            }

            alert(result.data.error || result.data.message || 'Não foi possível enviar o comprovante.');
        })
        .catch(function () {
            alert('Erro ao enviar comprovante por WhatsApp. Atualize a página e tente de novo.');
        })
        .finally(function () {
            btn.disabled = btn.getAttribute('data-has-phone') !== '1';
        });
    });
});
</script>
@endpush
