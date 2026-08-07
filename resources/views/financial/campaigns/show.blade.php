@extends('layouts.porto')

@section('title', $campaign->name)

@section('page-title', $campaign->name)

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.campaigns.index') }}">Campanhas</a></li>
    <li><span>{{ $campaign->name }}</span></li>
@endsection

@section('content')
@php
    use App\Models\CampaignInstallment;

    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canManage = fn (string $action) => $isAdmin
        || $user->hasPermission('financial.campanhas.manage')
        || $user->hasPermission("financial.campanhas.{$action}");
    $canCreate = $canManage('create');
    $canEdit = $canManage('edit');
    $canDelete = $canManage('delete');
    $canPay = $canManage('pagar');
    $canReverse = $canManage('estornar');

    $statusBadge = match($campaign->status) {
        'ativa' => 'bg-success',
        'encerrada' => 'bg-secondary',
        default => 'bg-danger',
    };
@endphp

@include('financial.campaigns.partials.alerts')

<!-- Cabeçalho: ações -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="badge {{ $statusBadge }}">{{ ucfirst($campaign->status) }}</span>
        @if($campaign->department)
            <span class="badge bg-light text-dark border"><i class="bx bx-group me-1"></i>{{ $campaign->department->name }}</span>
        @endif
        @if($campaign->start_date || $campaign->end_date)
            <small class="text-muted">
                {{ $campaign->start_date?->format('d/m/Y') ?? '—' }} a {{ $campaign->end_date?->format('d/m/Y') ?? '—' }}
            </small>
        @endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('financial.campaigns.report', $campaign) }}" class="btn btn-outline-primary btn-sm">
            <i class="bx bx-receipt me-1"></i>Prestação de Contas
        </a>
        @if($campaign->sponsors->isNotEmpty())
            <a href="{{ route('financial.campaigns.carnes', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-printer me-1"></i>Carnês de todos (PDF)
            </a>
        @endif
        @if($canEdit)
            <a href="{{ route('financial.campaigns.edit', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-edit me-1"></i>Editar
            </a>
        @endif
        @if($canDelete)
            <form method="POST" action="{{ route('financial.campaigns.destroy', $campaign) }}"
                  onsubmit="return confirm('Excluir esta campanha e TODOS os patrocinadores e parcelas? Esta ação não pode ser desfeita.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bx bx-trash me-1"></i>Excluir</button>
            </form>
        @endif
    </div>
</div>

<!-- Indicadores -->
<div class="row mb-1">
    @php
        $cards = [
            ['label' => 'Meta', 'value' => $metrics['goal'] > 0 ? 'R$ ' . number_format($metrics['goal'], 2, ',', '.') : 'Sem meta', 'icon' => 'bx bx-target-lock', 'color' => 'primary'],
            ['label' => 'Arrecadado', 'value' => 'R$ ' . number_format($metrics['raised'], 2, ',', '.'), 'icon' => 'bx bx-trending-up', 'color' => 'success'],
            ['label' => 'A receber', 'value' => 'R$ ' . number_format($metrics['pending'], 2, ',', '.'), 'icon' => 'bx bx-time-five', 'color' => 'warning'],
            ['label' => 'Em atraso', 'value' => 'R$ ' . number_format($metrics['overdue'], 2, ',', '.'), 'icon' => 'bx bx-error-circle', 'color' => 'danger'],
            ['label' => 'Patrocinadores', 'value' => $metrics['sponsors'], 'icon' => 'bx bx-user', 'color' => 'info'],
        ];
    @endphp
    @foreach($cards as $card)
        <div class="col-6 col-md-4 col-xl mb-3">
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

<!-- Progresso -->
<section class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between small mb-1">
            <span>Progresso da campanha</span>
            <strong>{{ number_format($metrics['progress'], 1, ',', '.') }}%</strong>
        </div>
        <div class="progress" style="height: 14px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $metrics['progress'] }}%"
                 aria-valuenow="{{ $metrics['progress'] }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</section>

<!-- Patrocinadores -->
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bx bx-user me-2"></i>Patrocinadores</h5>
        @if($canCreate)
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSponsorModal">
                <i class="bx bx-plus me-1"></i>Adicionar patrocinador
            </button>
        @endif
    </header>
    <div class="card-body p-0">
        @if($campaign->sponsors->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Nenhum patrocinador ainda. Adicione o primeiro para gerar as parcelas.</p>
        @else
            <div class="accordion accordion-flush" id="sponsorsAccordion">
                @foreach($campaign->sponsors as $sponsor)
                    @php
                        $paidCount = $sponsor->installments->where('status', CampaignInstallment::STATUS_PAGO)->count();
                        $totalCount = $sponsor->installments->count();
                        $totalPaid = (float) $sponsor->installments->where('status', CampaignInstallment::STATUS_PAGO)->sum('amount');
                    @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#sponsor-{{ $sponsor->id }}" aria-expanded="false">
                                <div class="d-flex flex-wrap align-items-center gap-2 w-100 me-2">
                                    <strong class="me-auto">{{ $sponsor->name }}</strong>
                                    <small class="text-muted"><i class="bx bx-phone me-1"></i>{{ $sponsor->phone ?: 'sem telefone' }}</small>
                                    <span class="badge {{ $paidCount === $totalCount && $totalCount > 0 ? 'bg-success' : 'bg-light text-dark border' }}">
                                        {{ $paidCount }}/{{ $totalCount }} pagas
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        R$ {{ number_format($totalPaid, 2, ',', '.') }}
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="sponsor-{{ $sponsor->id }}" class="accordion-collapse collapse" data-bs-parent="#sponsorsAccordion">
                            <div class="accordion-body">
                                <div class="d-flex justify-content-end gap-2 mb-2 flex-wrap">
                                    <a href="{{ route('financial.campaigns.sponsors.carne', $sponsor) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bx bx-printer me-1"></i>Gerar carnê (PDF)
                                    </a>
                                    @if($canEdit)
                                        <button type="button" class="btn btn-outline-secondary btn-sm js-edit-sponsor"
                                                data-action="{{ route('financial.campaigns.sponsors.update', $sponsor) }}"
                                                data-name="{{ $sponsor->name }}" data-phone="{{ $sponsor->phone }}" data-notes="{{ $sponsor->notes }}">
                                            <i class="bx bx-edit me-1"></i>Editar
                                        </button>
                                    @endif
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('financial.campaigns.sponsors.destroy', $sponsor) }}"
                                              onsubmit="return confirm('Remover o patrocinador {{ $sponsor->name }} e suas parcelas pendentes?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bx bx-trash me-1"></i>Remover</button>
                                        </form>
                                    @endif
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-2">
                                        <thead>
                                            <tr>
                                                @if($canPay)
                                                    <th style="width: 30px;"></th>
                                                @endif
                                                <th>Parcela</th>
                                                <th>Valor</th>
                                                <th>Vencimento</th>
                                                <th>Status</th>
                                                <th>Pagamento</th>
                                                <th>Recibo</th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sponsor->installments as $installment)
                                                <tr>
                                                    @if($canPay)
                                                        <td>
                                                            @if($installment->status === CampaignInstallment::STATUS_PENDENTE)
                                                                <input type="checkbox" class="form-check-input js-batch-check"
                                                                       data-sponsor="{{ $sponsor->id }}" value="{{ $installment->id }}">
                                                            @endif
                                                        </td>
                                                    @endif
                                                    <td>{{ $installment->installment_number }}/{{ $totalCount }}</td>
                                                    <td>R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}</td>
                                                    <td>{{ $installment->due_date?->format('d/m/Y') ?? '—' }}</td>
                                                    <td>
                                                        @if($installment->isPaid())
                                                            <span class="badge bg-success">Pago</span>
                                                        @elseif($installment->isOverdue())
                                                            <span class="badge bg-danger">Em atraso</span>
                                                        @elseif($installment->status === CampaignInstallment::STATUS_CANCELADO)
                                                            <span class="badge bg-secondary">Cancelado</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">Pendente</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($installment->isPaid())
                                                            <small>{{ $installment->paid_at?->format('d/m/Y H:i') }}<br>{{ $installment->paymentMethodLabel() }}</small>
                                                        @else
                                                            <small class="text-muted">—</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($installment->receipt_number)
                                                            <small>{{ $installment->receipt_number }}</small>
                                                            @if($installment->receipt_sent_at)
                                                                <i class="bx bx-check-double text-success" title="Comprovante enviado em {{ $installment->receipt_sent_at->format('d/m/Y H:i') }}"></i>
                                                            @endif
                                                        @else
                                                            <small class="text-muted">—</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                                            @if(!$installment->isPaid() && $installment->status !== CampaignInstallment::STATUS_CANCELADO && $canPay)
                                                                <button type="button" class="btn btn-success btn-sm js-pay-installment"
                                                                        data-action="{{ route('financial.campaigns.installments.pay', $installment) }}"
                                                                        data-label="Parcela {{ $installment->installment_number }}/{{ $totalCount }} — {{ $sponsor->name }} — R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}">
                                                                    <i class="bx bx-check"></i> Pagar
                                                                </button>
                                                            @endif
                                                            @if($installment->isPaid())
                                                                <a href="{{ route('financial.campaigns.installments.receipt', $installment) }}"
                                                                   class="btn btn-outline-secondary btn-sm" title="Baixar recibo (PDF)">
                                                                    <i class="bx bx-download"></i>
                                                                </a>
                                                                @if($canPay)
                                                                    <form method="POST" action="{{ route('financial.campaigns.installments.resend', $installment) }}">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-outline-primary btn-sm" title="Reenviar comprovante por WhatsApp">
                                                                            <i class="bx bxl-whatsapp"></i>
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                                @if($canReverse)
                                                                    <form method="POST" action="{{ route('financial.campaigns.installments.reverse', $installment) }}"
                                                                          onsubmit="return confirm('Estornar o pagamento da parcela {{ $installment->installment_number }}? Ela voltará para pendente e o recibo {{ $installment->receipt_number }} não será reutilizado.');">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Estornar pagamento">
                                                                            <i class="bx bx-undo"></i>
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if($canPay && $sponsor->installments->where('status', CampaignInstallment::STATUS_PENDENTE)->isNotEmpty())
                                    <button type="button" class="btn btn-outline-success btn-sm js-batch-pay" data-sponsor="{{ $sponsor->id }}" disabled>
                                        <i class="bx bx-check-double me-1"></i>Marcar selecionadas como pagas
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@if($canCreate)
<!-- Modal: adicionar patrocinador -->
<div class="modal fade" id="addSponsorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('financial.campaigns.sponsors.store', $campaign) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Adicionar patrocinador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" type="button" data-bs-toggle="pill" data-bs-target="#tabMembro" id="btnTabMembro">
                            <i class="bx bx-user-check me-1"></i>Membro
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#tabAvulso" id="btnTabAvulso">
                            <i class="bx bx-user-plus me-1"></i>Avulso
                        </button>
                    </li>
                </ul>
                <input type="hidden" name="type" id="sponsorType" value="membro">

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabMembro">
                        <label class="form-label">Buscar membros ativos *</label>
                        <input type="text" class="form-control" id="memberSearch" placeholder="Digite o nome e clique para adicionar..." autocomplete="off">
                        <div class="list-group mt-1 d-none" id="memberResults" style="max-height: 220px; overflow-y: auto;"></div>
                        <div id="memberChips" class="d-flex flex-wrap gap-2 mt-2"></div>
                        <div id="memberIdsInputs"></div>
                        <small class="text-muted">Você pode selecionar vários membros — cada um vira um patrocinador com suas próprias parcelas. Nome e telefone são copiados do cadastro.</small>
                    </div>
                    <div class="tab-pane fade" id="tabAvulso">
                        <div class="mb-2">
                            <label class="form-label">Nome *</label>
                            <input type="text" name="name" class="form-control" maxlength="255" placeholder="Nome do patrocinador">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Telefone / WhatsApp</label>
                            <input type="text" name="phone" class="form-control" maxlength="30" placeholder="(00) 00000-0000 — opcional">
                            <small class="text-muted">Sem telefone, o comprovante não será enviado por WhatsApp.</small>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Observações</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="1000"></textarea>
                </div>

                <div class="alert alert-info py-2 px-3 mt-3 mb-0">
                    <i class="bx bx-info-circle me-1"></i>
                    Para <strong>cada patrocinador</strong> serão geradas <strong>{{ $campaign->installments_count }} parcela(s)</strong> de
                    <strong>R$ {{ number_format((float) $campaign->installment_amount, 2, ',', '.') }}</strong>
                    @if($campaign->first_due_date)
                        a partir de <strong>{{ $campaign->first_due_date->format('d/m/Y') }}</strong>.
                    @else
                        (sem vencimento definido).
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Adicionar</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($canEdit)
<!-- Modal: editar patrocinador -->
<div class="modal fade" id="editSponsorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="editSponsorForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Editar patrocinador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" id="editSponsorName" class="form-control" required maxlength="255">
                </div>
                <div class="mb-2">
                    <label class="form-label">Telefone / WhatsApp</label>
                    <input type="text" name="phone" id="editSponsorPhone" class="form-control" maxlength="30">
                </div>
                <div class="mb-0">
                    <label class="form-label">Observações</label>
                    <textarea name="notes" id="editSponsorNotes" class="form-control" rows="2" maxlength="1000"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Salvar</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($canPay)
<!-- Modal: marcar como pago (individual e lote) -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="payForm" class="modal-content">
            @csrf
            <div id="payBatchInputs"></div>
            <div class="modal-header">
                <h5 class="modal-title">Registrar pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3" id="payModalLabel"></p>
                <div class="mb-3">
                    <label class="form-label">Forma de pagamento *</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">Selecione...</option>
                        @foreach(App\Models\CampaignInstallment::PAYMENT_METHODS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">Data do pagamento</label>
                    <input type="datetime-local" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                </div>
                <div class="alert alert-info py-2 px-3 mt-3 mb-0">
                    <i class="bx bxl-whatsapp me-1"></i>
                    O recibo em PDF será gerado e enviado por WhatsApp ao patrocinador (se houver telefone cadastrado).
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="bx bx-check me-1"></i>Confirmar pagamento</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ------- Abas do modal de patrocinador -------
    const sponsorType = document.getElementById('sponsorType');
    document.getElementById('btnTabMembro')?.addEventListener('click', () => sponsorType.value = 'membro');
    document.getElementById('btnTabAvulso')?.addEventListener('click', () => sponsorType.value = 'avulso');

    // ------- Autocomplete de membros (seleção múltipla) -------
    const memberSearch = document.getElementById('memberSearch');
    const memberResults = document.getElementById('memberResults');
    const memberChips = document.getElementById('memberChips');
    const memberIdsInputs = document.getElementById('memberIdsInputs');
    const selectedMembers = new Map(); // id -> {name, phone}
    let searchTimer = null;

    function renderSelectedMembers() {
        memberChips.innerHTML = '';
        memberIdsInputs.innerHTML = '';
        selectedMembers.forEach(function (m, id) {
            const chip = document.createElement('span');
            chip.className = 'badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 py-2 px-2';
            chip.style.fontSize = '0.8rem';

            const text = document.createElement('span');
            text.textContent = m.name + (m.phone ? ' (' + m.phone + ')' : ' (sem telefone)');
            chip.appendChild(text);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn-close';
            remove.style.fontSize = '0.55rem';
            remove.setAttribute('aria-label', 'Remover');
            remove.addEventListener('click', function () {
                selectedMembers.delete(id);
                renderSelectedMembers();
            });
            chip.appendChild(remove);

            memberChips.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'member_ids[]';
            input.value = id;
            memberIdsInputs.appendChild(input);
        });
    }

    if (memberSearch) {
        memberSearch.addEventListener('input', function () {
            const term = this.value.trim();
            clearTimeout(searchTimer);
            if (term.length < 2) {
                memberResults.classList.add('d-none');
                memberResults.innerHTML = '';
                return;
            }
            searchTimer = setTimeout(async function () {
                try {
                    const resp = await fetch('{{ route('financial.campaigns.members.search') }}?q=' + encodeURIComponent(term), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await resp.json();
                    memberResults.innerHTML = '';
                    (json.data || []).forEach(function (m) {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        const already = selectedMembers.has(String(m.id));
                        item.textContent = m.name + (m.phone ? ' — ' + m.phone : ' — sem telefone') + (already ? ' ✓' : '');
                        if (already) item.classList.add('disabled');
                        item.addEventListener('click', function () {
                            selectedMembers.set(String(m.id), { name: m.name, phone: m.phone });
                            renderSelectedMembers();
                            memberResults.classList.add('d-none');
                            memberResults.innerHTML = '';
                            memberSearch.value = '';
                            memberSearch.focus();
                        });
                        memberResults.appendChild(item);
                    });
                    memberResults.classList.toggle('d-none', memberResults.children.length === 0);
                } catch (e) { /* silencioso */ }
            }, 300);
        });
    }

    // ------- Modal de pagamento (individual) -------
    const payModalEl = document.getElementById('payModal');
    const payModal = payModalEl ? new bootstrap.Modal(payModalEl) : null;
    const payForm = document.getElementById('payForm');
    const payModalLabel = document.getElementById('payModalLabel');
    const payBatchInputs = document.getElementById('payBatchInputs');

    document.querySelectorAll('.js-pay-installment').forEach(function (btn) {
        btn.addEventListener('click', function () {
            payForm.action = this.dataset.action;
            payModalLabel.textContent = this.dataset.label;
            payBatchInputs.innerHTML = '';
            payModal.show();
        });
    });

    // ------- Seleção múltipla + pagamento em lote -------
    function refreshBatchButtons() {
        document.querySelectorAll('.js-batch-pay').forEach(function (btn) {
            const sponsor = btn.dataset.sponsor;
            const checked = document.querySelectorAll('.js-batch-check[data-sponsor="' + sponsor + '"]:checked').length;
            btn.disabled = checked === 0;
            btn.innerHTML = '<i class="bx bx-check-double me-1"></i>Marcar selecionadas como pagas' + (checked > 0 ? ' (' + checked + ')' : '');
        });
    }
    document.querySelectorAll('.js-batch-check').forEach(function (cb) {
        cb.addEventListener('change', refreshBatchButtons);
    });

    document.querySelectorAll('.js-batch-pay').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const sponsor = this.dataset.sponsor;
            const ids = Array.from(document.querySelectorAll('.js-batch-check[data-sponsor="' + sponsor + '"]:checked')).map(cb => cb.value);
            if (ids.length === 0) return;
            payForm.action = '{{ route('financial.campaigns.installments.pay-batch') }}';
            payModalLabel.textContent = ids.length + ' parcela(s) selecionada(s) serão marcadas como pagas.';
            payBatchInputs.innerHTML = ids.map(id => '<input type="hidden" name="installment_ids[]" value="' + id + '">').join('');
            payModal.show();
        });
    });

    // ------- Modal de edição de patrocinador -------
    const editModalEl = document.getElementById('editSponsorModal');
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    document.querySelectorAll('.js-edit-sponsor').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editSponsorForm').action = this.dataset.action;
            document.getElementById('editSponsorName').value = this.dataset.name || '';
            document.getElementById('editSponsorPhone').value = this.dataset.phone || '';
            document.getElementById('editSponsorNotes').value = this.dataset.notes || '';
            editModal.show();
        });
    });
});
</script>
@endpush
@endsection
