@php
    $report = $report ?? null;
    $asModal = $asModal ?? false;
    $isEdit = (bool) $report;
    $isDetailed = $settings->isDetailed();
    $attendanceMap = $isEdit
        ? $report->attendances->pluck('present', 'member_id')
        : collect();
@endphp

        <div class="cultos-wizard-steps mb-3" id="wizardSteps">
            <button type="button" class="cultos-step is-active" data-step="1"><i class="bx bx-info-circle"></i> Informações</button>
            <button type="button" class="cultos-step" data-step="2"><i class="bx bx-group"></i> Chamada</button>
            <button type="button" class="cultos-step" data-step="3"><i class="bx bx-user-plus"></i> Visitantes</button>
            <button type="button" class="cultos-step" data-step="4"><i class="bx bx-dollar"></i> Financeiro</button>
            <button type="button" class="cultos-step" data-step="5"><i class="bx bx-camera"></i> Fotos</button>
        </div>
        <div class="progress mb-4" style="height: 4px;">
            <div class="progress-bar" id="wizardProgress" style="width: 20%"></div>
        </div>

        <form method="POST"
              action="{{ $isEdit ? route('cultos.update', $report) : route('cultos.store') }}"
              enctype="multipart/form-data"
              id="serviceReportForm">
            @csrf
            @if($isEdit) @method('PUT') @endif
            <input type="hidden" name="status" id="report_status" value="{{ old('status', $report->status ?? 'rascunho') }}">

            {{-- Step 1 --}}
            <div class="wizard-pane" data-pane="1">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Data do Culto <span class="text-danger">*</span></label>
                        <input type="date" name="report_date" id="report_date" class="form-control" required
                               value="{{ old('report_date', optional($report)->report_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Culto (Agenda)</label>
                        <select name="event_id" class="form-select">
                            <option value="">Digitar manualmente</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" @selected(old('event_id', $report->event_id ?? '') == $event->id)>
                                    {{ $event->title }} — {{ optional($event->start_date)->format('d/m/Y H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Culto <span class="text-danger">*</span></label>
                        <select name="service_type" id="service_type" class="form-select" required>
                            <option value="">Selecione o tipo</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected(old('service_type', $report->service_type ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6" id="customTypeWrap" style="display:none;">
                        <label class="form-label">Descreva o tipo</label>
                        <input type="text" name="custom_type_label" class="form-control"
                               value="{{ old('custom_type_label', $report->custom_type_label ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Horário</label>
                        <input type="time" name="start_time" class="form-control"
                               value="{{ old('start_time', $report && $report->start_time ? substr($report->start_time, 0, 5) : '') }}">
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Pregador</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="external_preacher" name="external_preacher" value="1"
                                       @checked(old('external_preacher', !empty($report?->external_preacher_name)))>
                                <label class="form-check-label" for="external_preacher">Pregador externo</label>
                            </div>
                        </div>
                        <select name="preacher_member_id" id="preacher_member_id" class="form-select">
                            <option value="">Selecione...</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}" @selected(old('preacher_member_id', $report->preacher_member_id ?? '') == $member->id)>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="text" name="external_preacher_name" id="external_preacher_name" class="form-control mt-2"
                               style="display:none;" placeholder="Nome do pregador externo"
                               value="{{ old('external_preacher_name', $report->external_preacher_name ?? '') }}">
                        <div class="form-text">Exibe líderes, membros e congregados ativos</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tema da Mensagem</label>
                        <input type="text" name="message_theme" class="form-control" placeholder="Ex: A fé que move montanhas"
                               value="{{ old('message_theme', $report->message_theme ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Série/Campanha</label>
                        <input type="text" name="campaign_series" class="form-control" placeholder="Ex: 21 Dias de Oração"
                               value="{{ old('campaign_series', $report->campaign_series ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição do Culto</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Breve descrição ou observações sobre o culto...">{{ old('description', $report->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="wizard-pane d-none" data-pane="2">
                @if($isDetailed)
                    <div class="row g-2 mb-3" id="attendanceKpis">
                        <div class="col-6 col-md-3"><div class="border rounded p-2 text-center"><div class="small text-muted">Membros</div><strong id="kpiMembers">{{ $members->count() }}</strong></div></div>
                        <div class="col-6 col-md-3"><div class="border rounded p-2 text-center"><div class="small text-muted">Presentes</div><strong id="kpiPresent" class="text-success">0</strong></div></div>
                        <div class="col-6 col-md-3"><div class="border rounded p-2 text-center"><div class="small text-muted">Ausentes</div><strong id="kpiAbsent" class="text-danger">0</strong></div></div>
                        <div class="col-6 col-md-3"><div class="border rounded p-2 text-center"><div class="small text-muted">Visitantes</div><strong id="kpiVisitorsStep">0</strong></div></div>
                    </div>
                    <input type="search" class="form-control mb-3" id="attendanceSearch" placeholder="Buscar por nome...">
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="markAllPresent">Todos presentes</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="markAllAbsent">Todos ausentes</button>
                    </div>
                    <div id="attendanceList" class="cultos-attendance-list">
                        @foreach($members as $index => $member)
                            @php $present = (bool) old("attendances.$index.present", $attendanceMap->get($member->id, false)); @endphp
                            <div class="cultos-attendance-item" data-name="{{ mb_strtolower($member->name) }}">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="cultos-avatar">{{ collect(explode(' ', $member->name))->map(fn($p)=>mb_substr($p,0,1))->take(2)->implode('') }}</div>
                                    <div>
                                        <strong>{{ $member->name }}</strong>
                                        <div><span class="badge bg-success-subtle text-success-emphasis">membro</span></div>
                                    </div>
                                </div>
                                <input type="hidden" name="attendances[{{ $index }}][member_id]" value="{{ $member->id }}">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input attendance-toggle" type="checkbox" role="switch"
                                           name="attendances[{{ $index }}][present]" value="1" @checked($present)>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantidade de membros presentes</label>
                            <input type="number" min="0" class="form-control" name="members_present_count"
                                   value="{{ old('members_present_count', $report->members_present_count ?? 0) }}">
                        </div>
                    </div>
                @endif

                @if($settings->enable_children_count)
                    <div class="mt-3">
                        <label class="form-label">Quantidade de crianças presentes</label>
                        <input type="number" min="0" class="form-control" name="children_count"
                               value="{{ old('children_count', $report->children_count ?? 0) }}">
                    </div>
                @endif
                @if($settings->enable_volunteers_count)
                    <div class="mt-3">
                        <label class="form-label">Pessoas servindo no culto</label>
                        <input type="number" min="0" class="form-control" name="volunteers_count"
                               value="{{ old('volunteers_count', $report->volunteers_count ?? 0) }}">
                    </div>
                @endif
            </div>

            {{-- Step 3 --}}
            <div class="wizard-pane d-none" data-pane="3">
                @if($isDetailed)
                    <div class="border rounded p-3 mb-3">
                        <h6><i class="bx bx-user-plus"></i> Adicionar Visitante</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <input type="text" class="form-control" id="visitor_name" placeholder="Nome completo do visitante">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="visitor_phone" placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" id="visitor_invited_by">
                                    <option value="">Convidado por...</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-primary" id="addVisitorBtn">
                                    <i class="bx bx-plus"></i> Adicionar Visitante
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="visitorsList"></div>
                    <div id="visitorsHidden"></div>
                @else
                    <label class="form-label">Quantidade de visitantes</label>
                    <input type="number" min="0" class="form-control" name="visitors_count"
                           value="{{ old('visitors_count', $report->visitors_count ?? 0) }}">
                @endif
            </div>

            {{-- Step 4 --}}
            <div class="wizard-pane d-none" data-pane="4">
                <div class="alert alert-primary d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-semibold">Total Arrecadado</div>
                        <div class="small opacity-75">Origem: módulo Financeiro (dízimo/oferta do dia)</div>
                    </div>
                    <strong id="offeringTotalLabel">R$ 0,00</strong>
                </div>
                <div id="financialBreakdown" class="mb-3"></div>
                <p class="small text-muted mb-4">
                    O valor é carregado automaticamente pelas receitas pagas cadastradas no Financeiro na
                    <strong>data do culto</strong>. Cadastre dízimos e ofertas no módulo Financeiro para aparecerem aqui e no relatório.
                    @if(Route::has('financial.transactions.index'))
                        <a href="{{ route('financial.transactions.index') }}" target="_blank" rel="noopener">Abrir Financeiro</a>
                    @endif
                </p>

                <div class="mt-2">
                    <label class="form-label">Destaques e Testemunhos</label>
                    <textarea name="highlights" class="form-control" rows="4"
                              placeholder="Registre os principais testemunhos, decisões, batismos ou momentos marcantes do culto...">{{ old('highlights', $report->highlights ?? '') }}</textarea>
                    <div class="form-text">Opcional: descreva momentos especiais, decisões por Cristo, testemunhos compartilhados, etc.</div>
                </div>

                @if($settings->enable_spiritual_decisions)
                    <div class="mt-4">
                        <h6>Manifestações Espirituais</h6>
                        <div id="spiritualList"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addSpiritualBtn">
                            <i class="bx bx-plus"></i> Adicionar registro
                        </button>
                        <div id="spiritualHidden"></div>
                    </div>
                @endif
            </div>

            {{-- Step 5 --}}
            <div class="wizard-pane d-none" data-pane="5">
                <div class="text-center mb-3">
                    <i class="bx bx-camera fs-1 text-primary"></i>
                    <h5>Fotos do Culto</h5>
                    <p class="text-muted">Adicione fotos para registrar os momentos do culto (opcional)</p>
                </div>
                <label class="cultos-dropzone d-block text-center p-4">
                    <i class="bx bx-image fs-2"></i>
                    <div>Clique para selecionar fotos</div>
                    <div class="small text-muted">JPG, PNG ou HEIC • Máx. 5MB cada</div>
                    <input type="file" name="photos[]" accept="image/*" multiple class="d-none" id="photosInput">
                </label>
                <p class="small text-muted text-center mt-2">As fotos serão enviadas ao salvar o relatório</p>
                @if($isEdit && $report->photos->isNotEmpty())
                    <div class="row g-2 mt-3">
                        @foreach($report->photos as $photo)
                            <div class="col-4 col-md-3">
                                <div class="border rounded p-1 position-relative">
                                    <img src="{{ $photo->url }}" class="img-fluid rounded" alt="Foto">
                                    <label class="form-check small mt-1">
                                        <input type="checkbox" name="remove_photo_ids[]" value="{{ $photo->id }}"> Remover
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary" id="wizardBack" disabled>
                    <i class="bx bx-chevron-left"></i> Voltar
                </button>
                <div class="d-flex gap-2">
                    @if($asModal)
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancelar</button>
                    @else
                        <a href="{{ route('cultos.index') }}" class="btn btn-link">Cancelar</a>
                    @endif
                    <button type="button" class="btn btn-primary" id="wizardNext">
                        Próximo <i class="bx bx-chevron-right"></i>
                    </button>
                    <button type="submit" class="btn btn-primary d-none" id="wizardSave">
                        <i class="bx bx-check"></i> Salvar Relatório
                    </button>
                </div>
            </div>
        </form>

@push('styles')
<style>
.cultos-wizard-steps { display:flex; flex-wrap:wrap; gap:.5rem; }
.cultos-step {
    border:1px solid #e5e7eb; background:#fff; color:#6b7280; border-radius:999px;
    padding:.4rem .85rem; font-size:.85rem; font-weight:600;
}
.cultos-step.is-active { background:#3b82f6; border-color:#3b82f6; color:#fff; }
.cultos-step.is-done { background:#eff6ff; border-color:#93c5fd; color:#1d4ed8; }
.cultos-attendance-item {
    display:flex; justify-content:space-between; align-items:center; gap:1rem;
    padding:.75rem; border:1px solid #e5e7eb; border-radius:.65rem; margin-bottom:.5rem;
}
.cultos-avatar {
    width:36px; height:36px; border-radius:50%; background:#dbeafe; color:#1d4ed8;
    display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700;
}
.cultos-dropzone {
    border:2px dashed #cbd5e1; border-radius:.85rem; cursor:pointer; background:#f8fafc;
}
.cultos-visitor-chip, .cultos-spiritual-chip {
    border:1px solid #e5e7eb; border-radius:.65rem; padding:.65rem .85rem; margin-bottom:.5rem;
    display:flex; justify-content:space-between; gap:.75rem; align-items:center;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    let step = 1;
    const maxStep = 5;
    const isDetailed = @json($isDetailed);
    const enableSpiritual = @json((bool) $settings->enable_spiritual_decisions);
    const existingVisitors = @json($isEdit ? $report->visitors->map(fn($v) => ['name'=>$v->name,'phone'=>$v->phone,'invited_by_member_id'=>$v->invited_by_member_id])->values() : []);
    const existingSpiritual = @json($isEdit ? $report->spiritualDecisions->map(fn($s) => ['type'=>$s->type,'person_name'=>$s->person_name,'notes'=>$s->notes])->values() : []);
    let visitors = existingVisitors || [];
    let spiritual = existingSpiritual || [];

    const panes = [...document.querySelectorAll('.wizard-pane')];
    const steps = [...document.querySelectorAll('.cultos-step')];
    const progress = document.getElementById('wizardProgress');
    const backBtn = document.getElementById('wizardBack');
    const nextBtn = document.getElementById('wizardNext');
    const saveBtn = document.getElementById('wizardSave');

    function showStep(n) {
        step = n;
        panes.forEach(p => p.classList.toggle('d-none', Number(p.dataset.pane) !== n));
        steps.forEach(s => {
            const sn = Number(s.dataset.step);
            s.classList.toggle('is-active', sn === n);
            s.classList.toggle('is-done', sn < n);
        });
        progress.style.width = ((n / maxStep) * 100) + '%';
        backBtn.disabled = n === 1;
        nextBtn.classList.toggle('d-none', n === maxStep);
        saveBtn.classList.toggle('d-none', n !== maxStep);
        updateAttendanceKpis();
        if (n === 4) updateOfferingTotal();
    }

    function validateStep(n) {
        if (n === 1) {
            const date = document.getElementById('report_date').value;
            const type = document.getElementById('service_type').value;
            if (!date || !type) {
                alert('Preencha Data do Culto e Tipo de Culto.');
                return false;
            }
        }
        return true;
    }

    nextBtn?.addEventListener('click', () => {
        if (!validateStep(step)) return;
        if (step < maxStep) showStep(step + 1);
    });
    backBtn?.addEventListener('click', () => { if (step > 1) showStep(step - 1); });
    steps.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = Number(btn.dataset.step);
            if (target <= step) { showStep(target); return; }
            for (let i = step; i < target; i++) {
                if (!validateStep(i)) return;
            }
            showStep(target);
        });
    });

    const typeEl = document.getElementById('service_type');
    const customWrap = document.getElementById('customTypeWrap');
    function toggleCustomType() {
        customWrap.style.display = typeEl.value === 'outro' ? '' : 'none';
    }
    typeEl?.addEventListener('change', toggleCustomType);
    toggleCustomType();

    const externalToggle = document.getElementById('external_preacher');
    const preacherSelect = document.getElementById('preacher_member_id');
    const preacherExternal = document.getElementById('external_preacher_name');
    function togglePreacher() {
        const external = externalToggle.checked;
        preacherSelect.style.display = external ? 'none' : '';
        preacherExternal.style.display = external ? '' : 'none';
    }
    externalToggle?.addEventListener('change', togglePreacher);
    togglePreacher();

    function updateAttendanceKpis() {
        if (!isDetailed) return;
        const toggles = [...document.querySelectorAll('.attendance-toggle')];
        const present = toggles.filter(t => t.checked).length;
        document.getElementById('kpiPresent').textContent = present;
        document.getElementById('kpiAbsent').textContent = toggles.length - present;
        document.getElementById('kpiVisitorsStep').textContent = visitors.length;
    }
    document.querySelectorAll('.attendance-toggle').forEach(t => t.addEventListener('change', updateAttendanceKpis));
    document.getElementById('markAllPresent')?.addEventListener('click', () => {
        document.querySelectorAll('.attendance-toggle').forEach(t => t.checked = true);
        updateAttendanceKpis();
    });
    document.getElementById('markAllAbsent')?.addEventListener('click', () => {
        document.querySelectorAll('.attendance-toggle').forEach(t => t.checked = false);
        updateAttendanceKpis();
    });
    document.getElementById('attendanceSearch')?.addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.cultos-attendance-item').forEach(item => {
            item.style.display = !q || item.dataset.name.includes(q) ? '' : 'none';
        });
    });

    function renderVisitors() {
        const list = document.getElementById('visitorsList');
        const hidden = document.getElementById('visitorsHidden');
        if (!list || !hidden) return;
        list.innerHTML = visitors.map((v, i) => `
            <div class="cultos-visitor-chip">
                <div><strong>${v.name}</strong><div class="small text-muted">${v.phone || 'Sem telefone'}</div></div>
                <button type="button" class="btn btn-sm btn-outline-danger" data-remove-visitor="${i}"><i class="bx bx-trash"></i></button>
            </div>
        `).join('');
        hidden.innerHTML = visitors.map((v, i) => `
            <input type="hidden" name="visitors[${i}][name]" value="${v.name.replaceAll('"','&quot;')}">
            <input type="hidden" name="visitors[${i}][phone]" value="${(v.phone || '').replaceAll('"','&quot;')}">
            <input type="hidden" name="visitors[${i}][invited_by_member_id]" value="${v.invited_by_member_id || ''}">
        `).join('');
        updateAttendanceKpis();
    }
    document.getElementById('addVisitorBtn')?.addEventListener('click', () => {
        const name = document.getElementById('visitor_name').value.trim();
        if (!name) { alert('Informe o nome do visitante.'); return; }
        visitors.push({
            name,
            phone: document.getElementById('visitor_phone').value.trim(),
            invited_by_member_id: document.getElementById('visitor_invited_by').value || null,
        });
        document.getElementById('visitor_name').value = '';
        document.getElementById('visitor_phone').value = '';
        document.getElementById('visitor_invited_by').value = '';
        renderVisitors();
    });
    document.getElementById('visitorsList')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-visitor]');
        if (!btn) return;
        visitors.splice(Number(btn.dataset.removeVisitor), 1);
        renderVisitors();
    });

    function updateOfferingTotal() {
        const dateInput = document.getElementById('report_date');
        const label = document.getElementById('offeringTotalLabel');
        const breakdown = document.getElementById('financialBreakdown');
        if (!dateInput || !label) return;

        const date = dateInput.value;
        if (!date) {
            label.textContent = 'R$ 0,00';
            if (breakdown) breakdown.innerHTML = '';
            return;
        }

        label.textContent = '…';
        if (breakdown) breakdown.innerHTML = '<div class="small text-muted">Carregando do Financeiro…</div>';

        const url = new URL(@json(route('cultos.financial-summary')), window.location.origin);
        url.searchParams.set('date', date);

        fetch(url.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(data => {
                const total = Number(data.total || 0);
                label.textContent = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                if (!breakdown) return;
                const rows = (data.by_category || []);
                if (!rows.length) {
                    breakdown.innerHTML = '<div class="small text-muted">Nenhuma receita (dízimo/oferta) encontrada no Financeiro para esta data.</div>';
                    return;
                }
                breakdown.innerHTML = rows.map(row => `
                    <div class="d-flex justify-content-between border rounded px-3 py-2 mb-1 bg-white">
                        <span>${row.name} <span class="text-muted small">(${row.count})</span></span>
                        <strong>${Number(row.total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</strong>
                    </div>
                `).join('');
            })
            .catch(() => {
                label.textContent = 'R$ 0,00';
                if (breakdown) breakdown.innerHTML = '<div class="small text-danger">Não foi possível carregar o financeiro.</div>';
            });
    }
    document.getElementById('report_date')?.addEventListener('change', updateOfferingTotal);

    function renderSpiritual() {
        const list = document.getElementById('spiritualList');
        const hidden = document.getElementById('spiritualHidden');
        if (!list || !hidden) return;
        const types = @json(\App\Models\ServiceReportSpiritualDecision::TYPES);
        list.innerHTML = spiritual.map((s, i) => `
            <div class="cultos-spiritual-chip">
                <div><strong>${types[s.type] || s.type}</strong><div class="small text-muted">${s.person_name || ''}</div></div>
                <button type="button" class="btn btn-sm btn-outline-danger" data-remove-spiritual="${i}"><i class="bx bx-trash"></i></button>
            </div>
        `).join('');
        hidden.innerHTML = spiritual.map((s, i) => `
            <input type="hidden" name="spiritual_decisions[${i}][type]" value="${s.type}">
            <input type="hidden" name="spiritual_decisions[${i}][person_name]" value="${(s.person_name || '').replaceAll('"','&quot;')}">
            <input type="hidden" name="spiritual_decisions[${i}][notes]" value="${(s.notes || '').replaceAll('"','&quot;')}">
        `).join('');
    }
    document.getElementById('addSpiritualBtn')?.addEventListener('click', () => {
        const type = prompt('Tipo: batismo_espirito | cura | decisao_por_cristo | outro', 'decisao_por_cristo');
        if (!type) return;
        const person = prompt('Nome da pessoa (opcional)', '') || '';
        spiritual.push({ type, person_name: person, notes: '' });
        renderSpiritual();
    });
    document.getElementById('spiritualList')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-spiritual]');
        if (!btn) return;
        spiritual.splice(Number(btn.dataset.removeSpiritual), 1);
        renderSpiritual();
    });

    renderVisitors();
    if (enableSpiritual) renderSpiritual();
    showStep(1);

    document.getElementById('newServiceReportModal')?.addEventListener('show.bs.modal', () => {
        showStep(1);
    });
})();
</script>
@endpush
