@extends('layouts.porto')

@section('title', $campaign->name)

@section('page-title', $campaign->name)

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.campaigns.index') }}">Campanhas</a></li>
    <li><span>{{ $campaign->name }}</span></li>
@endsection

@push('styles')
    @include('financial.campaigns.partials.sponsors-styles')
@endpush

@section('content')
@php
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

    // Monta URLs da listagem preservando busca, filtro, ordenação, densidade e modo de visualização.
    $sponsorUrl = function (array $overrides = []) use ($campaign, $filters) {
        $params = array_merge([
            'q' => $filters['q'],
            'situacao' => $filters['situacao'],
            'sort' => $filters['sort'],
            'per_page' => $filters['per_page'],
            'view' => $filters['view'],
            'page' => request()->query('page'),
        ], $overrides);

        $defaults = ['q' => '', 'situacao' => 'todos', 'sort' => 'atraso', 'per_page' => 20, 'view' => 'lista', 'page' => 1];

        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || (isset($defaults[$key]) && $value == $defaults[$key])) {
                unset($params[$key]);
            }
        }

        return route('financial.campaigns.show', array_merge(['campaign' => $campaign->id], $params));
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
        @if($metrics['sponsors'] > 0)
            <a href="{{ route('financial.campaigns.carnes', $campaign) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-printer me-1"></i>Carnês de todos (PDF)
            </a>
        @endif
        @if($canEdit)
            <a href="{{ route('financial.campaigns.reminders.edit', $campaign) }}"
               class="btn btn-outline-{{ $reminderSettings->isActiveFor($campaign) ? 'success' : 'secondary' }} btn-sm">
                <i class="bx bx-bell me-1"></i>Lembretes
                @if($reminderSettings->isActiveFor($campaign))
                    <span class="badge bg-success ms-1">ativo</span>
                @elseif($reminderSettings->paused)
                    <span class="badge bg-warning text-dark ms-1">pausado</span>
                @endif
            </a>
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

<!-- Indicadores: atalhos de filtro da listagem -->
<div class="row mb-1 campaign-kpis">
    @php
        $cards = [
            ['label' => 'Meta', 'value' => $metrics['goal'] > 0 ? 'R$ ' . number_format($metrics['goal'], 2, ',', '.') : 'Sem meta', 'icon' => 'bx bx-target-lock', 'color' => 'primary', 'hex' => '#0088CC', 'filter' => null],
            ['label' => 'Previsão', 'value' => 'R$ ' . number_format($metrics['expected'], 2, ',', '.'), 'icon' => 'bx bx-line-chart', 'color' => 'primary', 'hex' => '#0088CC', 'filter' => null, 'hint' => 'Soma de todas as parcelas dos patrocinadores (pagas + a receber)'],
            ['label' => 'Arrecadado', 'value' => 'R$ ' . number_format($metrics['raised'], 2, ',', '.'), 'icon' => 'bx bx-trending-up', 'color' => 'success', 'hex' => '#1FA855', 'filter' => 'quitado'],
            ['label' => 'A receber', 'value' => 'R$ ' . number_format($metrics['pending'], 2, ',', '.'), 'icon' => 'bx bx-time-five', 'color' => 'warning', 'hex' => '#F5A623', 'filter' => 'em_dia'],
            ['label' => 'Em atraso', 'value' => 'R$ ' . number_format($metrics['overdue'], 2, ',', '.'), 'icon' => 'bx bx-error-circle', 'color' => 'danger', 'hex' => '#DC3545', 'filter' => 'em_atraso'],
            ['label' => 'Patrocinadores', 'value' => $metrics['sponsors'], 'icon' => 'bx bx-user', 'color' => 'info', 'hex' => '#0088CC', 'filter' => 'todos'],
        ];
    @endphp
    @foreach($cards as $card)
        @php
            $isActive = $card['filter'] !== null && $filters['situacao'] === $card['filter'];
            $classes = 'card kpi-card h-100' . ($isActive ? ' is-active' : '');
        @endphp
        <div class="col-6 col-md-4 col-xl mb-3">
            @if($card['filter'])
                <a href="{{ $sponsorUrl(['situacao' => $card['filter'] === 'todos' ? null : $card['filter'], 'page' => null]) }}"
                   class="{{ $classes }} d-block" style="--kpi-color: {{ $card['hex'] }}"
                   title="Filtrar a lista de patrocinadores">
            @else
                <section class="{{ $classes }}" @if($card['hint'] ?? false) title="{{ $card['hint'] }}" @endif>
            @endif
                <div class="card-body py-3 d-flex align-items-center gap-2">
                    <i class="{{ $card['icon'] }} fs-3 text-{{ $card['color'] }} flex-shrink-0"></i>
                    <div class="min-w-0">
                        <div class="text-muted small">{{ $card['label'] }}</div>
                        <strong>{{ $card['value'] }}</strong>
                    </div>
                </div>
            @if($card['filter'])
                </a>
            @else
                </section>
            @endif
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
<section class="card campaign-sponsors">
    <header class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bx bx-user me-2"></i>Patrocinadores</h5>
        @if($canCreate)
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSponsorModal">
                <i class="bx bx-plus me-1"></i>Adicionar patrocinador
            </button>
        @endif
    </header>

    @if($metrics['sponsors'] === 0)
        <div class="card-body">
            <p class="text-muted text-center py-4 mb-0">Nenhum patrocinador ainda. Adicione o primeiro para gerar as parcelas.</p>
        </div>
    @else
        {{-- Busca, paginação e lista no mesmo container: é o que permite a busca
             ficar fixa (sticky) enquanto a lista rola no celular. --}}
        <div class="card-body pb-0">
            @include('financial.campaigns.partials.sponsors-toolbar')
            @include('financial.campaigns.partials.sponsors-pagination')

            @if($sponsors->isEmpty())
                <p class="text-muted text-center py-4 mb-0">Nenhum patrocinador encontrado com os filtros aplicados.</p>
            @else
                <div class="cs-listing {{ $filters['view'] === 'tabela' ? 'table-responsive' : '' }}">
                    @include('financial.campaigns.partials.sponsors-' . ($filters['view'] === 'tabela' ? 'table' : 'list'))
                </div>
            @endif
        </div>

        <div class="card-footer">
            @include('financial.campaigns.partials.sponsors-pagination', ['withPerPage' => true])
        </div>
    @endif
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

<!-- Modal: cobrança em massa dos patrocinadores em atraso -->
<div class="modal fade" id="chargeOverdueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <form method="POST" action="{{ route('financial.campaigns.sponsors.charge-overdue', $campaign) }}" class="modal-content">
            @csrf
            <input type="hidden" name="q" value="{{ $filters['q'] }}">
            <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
            <div class="modal-header">
                <h5 class="modal-title">Cobrar todos em atraso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    A mensagem será enviada por WhatsApp para
                    <strong>{{ $chargeableCount }} patrocinador(es)</strong> em atraso
                    @if($filters['q'] !== '')
                        que correspondem à busca <strong>"{{ $filters['q'] }}"</strong>
                    @endif
                    .
                </p>
                @if($counts['em_atraso'] > $chargeableCount)
                    <p class="small text-muted">
                        {{ $counts['em_atraso'] - $chargeableCount }} patrocinador(es) em atraso não têm telefone cadastrado e não receberão a mensagem.
                    </p>
                @endif

                <label class="form-label small text-muted mb-1">Prévia da mensagem</label>
                <pre class="bg-light border rounded p-2 small mb-3" style="white-space: pre-wrap;">🔔 *Lembrete de contribuição — {{ $campaign->name }}*

Olá, [nome do patrocinador]!
Consta em nosso controle *[N] parcela(s) em atraso*, somando *R$ [valor]*.
A mais antiga venceu em *[data]*.

Se você já efetuou o pagamento, por favor desconsidere esta mensagem.
Deus abençoe sua generosidade! 🙏</pre>

                <div class="alert alert-warning py-2 px-3 mb-0">
                    <i class="bx bx-error-circle me-1"></i>
                    Este é um <strong>envio em massa</strong> para membros da igreja e <strong>não pode ser desfeito</strong>.
                    Confirme os valores antes de prosseguir.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger" @disabled($chargeableCount === 0)>
                    <i class="bx bxl-whatsapp me-1"></i>Enviar para {{ $chargeableCount }} patrocinador(es)
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Painel lateral: parcelas do patrocinador (visão em tabela) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="sponsorOffcanvas" aria-labelledby="sponsorOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="sponsorOffcanvasLabel">Parcelas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body" id="sponsorOffcanvasBody"></div>
</div>

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

    // ------- Carregamento das parcelas sob demanda -------
    // As parcelas não vêm no HTML da página: são buscadas ao abrir o accordion
    // ou o painel lateral, para que 100 patrocinadores não tragam 400 parcelas.
    async function loadInstallments(container, url) {
        if (container.dataset.loaded === '1') return;
        try {
            const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            container.innerHTML = await resp.text();
            container.dataset.loaded = '1';
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-0">Não foi possível carregar as parcelas. Recarregue a página e tente novamente.</div>';
        }
    }

    document.getElementById('sponsorsAccordion')?.addEventListener('show.bs.collapse', function (event) {
        const container = event.target.querySelector('.js-installments');
        if (container) loadInstallments(container, container.dataset.url);
    });

    const offcanvasEl = document.getElementById('sponsorOffcanvas');
    const offcanvas = offcanvasEl ? new bootstrap.Offcanvas(offcanvasEl) : null;
    const offcanvasBody = document.getElementById('sponsorOffcanvasBody');
    const offcanvasTitle = document.getElementById('sponsorOffcanvasLabel');

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('.js-open-sponsor');
        if (!trigger || !offcanvas) return;
        // Cliques no menu "⋮" da linha não devem abrir o painel.
        if (trigger.tagName === 'TR' && event.target.closest('.dropdown')) return;

        event.preventDefault();
        offcanvasTitle.textContent = trigger.dataset.name || 'Parcelas';
        offcanvasBody.dataset.loaded = '';
        offcanvasBody.innerHTML = '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>Carregando parcelas...</div>';
        offcanvas.show();
        loadInstallments(offcanvasBody, trigger.dataset.url);
    });

    // ------- Modal de pagamento (individual e lote) -------
    const payModalEl = document.getElementById('payModal');
    const payModal = payModalEl ? new bootstrap.Modal(payModalEl) : null;
    const payForm = document.getElementById('payForm');
    const payModalLabel = document.getElementById('payModalLabel');
    const payBatchInputs = document.getElementById('payBatchInputs');

    function refreshBatchButtons() {
        document.querySelectorAll('.js-batch-pay').forEach(function (btn) {
            const sponsor = btn.dataset.sponsor;
            const checked = document.querySelectorAll('.js-batch-check[data-sponsor="' + sponsor + '"]:checked').length;
            btn.disabled = checked === 0;
            btn.innerHTML = '<i class="bx bx-check-double me-1"></i>Marcar selecionadas como pagas' + (checked > 0 ? ' (' + checked + ')' : '');
        });
    }

    // Delegação: o conteúdo das parcelas é injetado depois do carregamento da página.
    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('js-batch-check')) refreshBatchButtons();
    });

    document.addEventListener('click', function (event) {
        const payBtn = event.target.closest('.js-pay-installment');
        if (payBtn && payModal) {
            payForm.action = payBtn.dataset.action;
            payModalLabel.textContent = payBtn.dataset.label;
            payBatchInputs.innerHTML = '';
            payModal.show();
            return;
        }

        const batchBtn = event.target.closest('.js-batch-pay');
        if (batchBtn && payModal) {
            const sponsor = batchBtn.dataset.sponsor;
            const ids = Array.from(document.querySelectorAll('.js-batch-check[data-sponsor="' + sponsor + '"]:checked')).map(cb => cb.value);
            if (ids.length === 0) return;
            payForm.action = '{{ route('financial.campaigns.installments.pay-batch') }}';
            payModalLabel.textContent = ids.length + ' parcela(s) selecionada(s) serão marcadas como pagas.';
            payBatchInputs.innerHTML = ids.map(id => '<input type="hidden" name="installment_ids[]" value="' + id + '">').join('');
            payModal.show();
            return;
        }

        const editBtn = event.target.closest('.js-edit-sponsor');
        if (editBtn) {
            const editModalEl = document.getElementById('editSponsorModal');
            if (!editModalEl) return;
            document.getElementById('editSponsorForm').action = editBtn.dataset.action;
            document.getElementById('editSponsorName').value = editBtn.dataset.name || '';
            document.getElementById('editSponsorPhone').value = editBtn.dataset.phone || '';
            document.getElementById('editSponsorNotes').value = editBtn.dataset.notes || '';
            bootstrap.Modal.getOrCreateInstance(editModalEl).show();
        }
    });
});
</script>
@endpush
@endsection
