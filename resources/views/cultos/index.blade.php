@extends('layouts.porto')

@section('title', 'Relatórios de Culto')
@section('page-title', 'Relatórios de Culto')

@section('breadcrumbs')
    <li><span>Relatórios de Culto</span></li>
@endsection

@section('content')
@php $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp

@include('cultos.partials.module-nav', ['active' => 'index'])

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="text-muted small">Relatórios (mês)</div>
                    <i class="bx bx-file text-primary"></i>
                </div>
                <div class="fs-3 fw-bold">{{ $kpi['reports'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="text-muted small">Presentes (mês)</div>
                    <i class="bx bx-group text-success"></i>
                </div>
                <div class="fs-3 fw-bold">{{ $kpi['present'] }}</div>
                <div class="small text-muted">{{ $kpi['members'] }} membros · {{ $kpi['visitors'] }} visit.</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="text-muted small">Visitantes (mês)</div>
                    <i class="bx bx-user-plus text-info"></i>
                </div>
                <div class="fs-3 fw-bold">{{ $kpi['visitors'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="text-muted small">Arrecadação (mês)</div>
                    <i class="bx bx-dollar-circle text-warning"></i>
                </div>
                <div class="fs-4 fw-bold">{{ $fmt($kpi['offering']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}"
                           placeholder="Buscar por tipo, pregador ou tema...">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos os status</option>
                    <option value="rascunho" @selected($filters['status'] === 'rascunho')>Rascunho</option>
                    <option value="finalizado" @selected($filters['status'] === 'finalizado')>Finalizado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Período</label>
                <select name="period" class="form-select">
                    <option value="">Todo período</option>
                    <option value="mes" @selected($filters['period'] === 'mes')>Mês atual</option>
                    <option value="30d" @selected($filters['period'] === '30d')>Últimos 30 dias</option>
                    <option value="ano" @selected($filters['period'] === 'ano')>Ano atual</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-outline-primary flex-grow-1" type="submit">Filtrar</button>
                @can('viewAny', \App\Models\ServiceReport::class)
                    <a href="{{ route('cultos.pdf.consolidated', request()->query()) }}"
                       class="btn btn-outline-danger" title="Exportar PDF consolidado dos filtros atuais">
                        <i class="bx bx-download"></i> PDF Consolidado
                    </a>
                @endcan
            </div>
        </form>
    </div>
</div>

@if($reports->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bx bx-file fs-1 text-muted"></i>
            <h5 class="mt-3">Nenhum relatório encontrado</h5>
            <p class="text-muted">Crie seu primeiro relatório de culto</p>
            @can('create', \App\Models\ServiceReport::class)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newServiceReportModal">
                    <i class="bx bx-plus"></i> Novo Relatório
                </button>
            @endcan
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            @foreach($reports as $report)
                @php
                    $date = $report->report_date;
                    $presentMembers = $report->presentMembersCount();
                    $presentVisitors = $report->resolvedVisitorsCount();
                @endphp
                <div class="list-group-item py-3">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="cultos-date-block text-center">
                            <div class="fw-bold">{{ $date->format('d') }}</div>
                            <div class="small text-uppercase">{{ $date->locale('pt_BR')->translatedFormat('M') }}</div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <strong>Culto de {{ $report->service_type_label }}</strong>
                                <span class="badge {{ $report->status === 'finalizado' ? 'bg-success' : 'bg-info-subtle text-info-emphasis' }}">
                                    {{ $report->status === 'finalizado' ? 'Finalizado' : 'Rascunho' }}
                                </span>
                            </div>
                            <div class="text-muted small">
                                {{ $date->locale('pt_BR')->translatedFormat('l, d \\d\\e F') }}
                                · {{ $report->preacher_name }}
                            </div>
                            <div class="small mt-1">
                                {{ $presentMembers + $presentVisitors }} presentes ({{ $presentMembers }} mb · {{ $presentVisitors }} vis)
                                · <i class="bx bx-dollar"></i> {{ $fmt($report->offering_total) }}
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            @can('view', $report)
                                <button type="button"
                                        class="btn btn-sm btn-light btn-view-report"
                                        title="Ver relatório"
                                        data-url="{{ route('cultos.show', $report) }}">
                                    <i class="bx bx-show"></i>
                                </button>
                                <a href="{{ route('cultos.pdf', $report) }}" class="btn btn-sm btn-light" title="Exportar PDF">
                                    <i class="bx bx-download"></i>
                                </a>
                            @endcan
                            @can('update', $report)
                                <a href="{{ route('cultos.edit', $report) }}" class="btn btn-sm btn-light" title="Editar">
                                    <i class="bx bx-edit"></i>
                                </a>
                                @if($report->status === 'rascunho')
                                    <form method="POST" action="{{ route('cultos.finalize', $report) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-light text-success" title="Finalizar">
                                            <i class="bx bx-check-circle"></i>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                            @can('delete', $report)
                                <form method="POST" action="{{ route('cultos.destroy', $report) }}"
                                      onsubmit="return confirm('Excluir este relatório?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger" title="Excluir">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($reports->hasPages())
            <div class="card-footer bg-white">{{ $reports->links() }}</div>
        @endif
    </div>
@endif

{{-- Modal detalhes --}}
<div class="modal fade" id="viewServiceReportModal" tabindex="-1" aria-labelledby="viewServiceReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewServiceReportModalLabel">
                    <i class="bx bx-file me-1"></i> Detalhes do Relatório
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" id="viewReportEditBtn" class="btn btn-sm btn-outline-primary d-none">
                        <i class="bx bx-edit"></i> Editar
                    </a>
                    <a href="#" id="viewReportPdfBtn" class="btn btn-sm btn-outline-danger" target="_blank">
                        <i class="bx bx-download"></i> PDF
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
            </div>
            <div class="modal-body" id="viewReportBody">
                <div class="text-center text-muted py-5">Carregando...</div>
            </div>
        </div>
    </div>
</div>

@can('create', \App\Models\ServiceReport::class)
<div class="modal fade" id="newServiceReportModal" tabindex="-1" aria-labelledby="newServiceReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newServiceReportModalLabel">Novo Relatório de Culto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                @include('cultos.partials.wizard', [
                    'report' => null,
                    'settings' => $settings,
                    'members' => $members,
                    'events' => $events,
                    'types' => $types,
                    'asModal' => true,
                ])
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('styles')
<style>
.cultos-date-block {
    width: 56px; height: 56px; border-radius: 0.65rem;
    background: #eff6ff; color: #1d4ed8;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    line-height: 1.1; flex-shrink: 0;
}
#newServiceReportModal .modal-dialog,
#viewServiceReportModal .modal-dialog {
    max-width: min(1100px, 96vw);
}
#newServiceReportModal .modal-body,
#viewServiceReportModal .modal-body {
    max-height: calc(100vh - 140px);
}
.cultos-view-avatar {
    width: 36px; height: 36px; border-radius: 50%; background: #dbeafe; color: #1d4ed8;
    display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700;
}
.cultos-view-kpi {
    min-width: 90px; text-align: center; padding: .5rem .75rem; border-radius: .65rem; background: #f8fafc;
}
.cultos-view-kpi .value { font-size: 1.25rem; font-weight: 700; line-height: 1.1; }
.cultos-view-kpi .label { font-size: .75rem; color: #64748b; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const money = (v) => Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    function renderView(data) {
        const presentList = (data.present_members || []).map((m) => `
            <div class="d-flex align-items-center gap-2 border rounded px-3 py-2 mb-2">
                <span class="cultos-view-avatar">${esc(m.initials)}</span>
                <span>${esc(m.name)}</span>
            </div>
        `).join('') || '<div class="text-muted small">Nenhum membro listado na chamada.</div>';

        const visitorsList = (data.visitors || []).map((v) => `
            <div class="border rounded px-3 py-2 mb-2">
                <strong>${esc(v.name)}</strong>
                <div class="small text-muted">${esc(v.phone || 'Sem telefone')}${v.invited_by ? ' · Convidado por ' + esc(v.invited_by) : ''}</div>
            </div>
        `).join('') || '<div class="text-muted small">Nenhum visitante registrado.</div>';

        const financialRows = (data.financial?.by_category || []).map((row) => `
            <div class="d-flex justify-content-between border rounded px-3 py-2 mb-1">
                <span>${esc(row.name)} <span class="text-muted small">(${row.count})</span></span>
                <strong>${money(row.total)}</strong>
            </div>
        `).join('') || '<div class="text-muted small">Nenhuma receita encontrada no Financeiro para esta data.</div>';

        const spiritual = (data.spiritual_decisions || []).map((s) => `
            <div class="border rounded px-3 py-2 mb-2">
                <strong>${esc(s.type_label)}</strong>
                <div class="small text-muted">${esc(s.person_name || '')}</div>
            </div>
        `).join('');

        const photos = (data.photos || []).map((p) => `
            <div class="col-4 col-md-3">
                <img src="${esc(p.url)}" class="img-fluid rounded border" alt="Foto do culto">
            </div>
        `).join('') || '<div class="text-muted small">Nenhuma foto anexada.</div>';

        return `
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h4 class="mb-0">Culto de ${esc(data.service_type_label)}</h4>
                        <span class="badge ${data.status === 'finalizado' ? 'bg-success' : 'bg-info-subtle text-info-emphasis'}">${esc(data.status_label)}</span>
                    </div>
                    <div class="text-muted small">
                        <i class="bx bx-calendar"></i> ${esc(data.report_date_label)}
                        · <i class="bx bx-user"></i> ${esc(data.preacher_name)}
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="cultos-view-kpi"><div class="value text-primary">${data.total_present}</div><div class="label">Total Presentes</div></div>
                    <div class="cultos-view-kpi"><div class="value text-success">${data.members_count}</div><div class="label">Membros</div></div>
                    <div class="cultos-view-kpi"><div class="value text-info">${data.visitors_count}</div><div class="label">Visitantes</div></div>
                    <div class="cultos-view-kpi"><div class="value text-warning">${money(data.offering_total)}</div><div class="label">Arrecadação</div></div>
                </div>
            </div>

            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#viewTabPresencas" type="button">Presenças</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#viewTabVisitantes" type="button">Visitantes</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#viewTabFinanceiro" type="button">Financeiro</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#viewTabConteudo" type="button">Conteúdo</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#viewTabFotos" type="button">Fotos</button></li>
            </ul>
            <div class="tab-content border border-top-0 rounded-bottom p-3">
                <div class="tab-pane fade show active" id="viewTabPresencas">
                    <h6 class="text-success"><i class="bx bx-check-circle"></i> Presentes (${data.members_count})</h6>
                    ${presentList}
                </div>
                <div class="tab-pane fade" id="viewTabVisitantes">${visitorsList}</div>
                <div class="tab-pane fade" id="viewTabFinanceiro">
                    <div class="alert alert-primary d-flex justify-content-between align-items-center">
                        <span>Total do Financeiro</span>
                        <strong>${money(data.financial?.total)}</strong>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><div class="border rounded p-3"><div class="small text-muted">Ofertas</div><strong>${money(data.financial?.ofertas)}</strong></div></div>
                        <div class="col-md-6"><div class="border rounded p-3"><div class="small text-muted">Dízimos</div><strong>${money(data.financial?.dizimos)}</strong></div></div>
                    </div>
                    ${financialRows}
                </div>
                <div class="tab-pane fade" id="viewTabConteudo">
                    <div class="mb-2"><span class="text-muted small">Tema</span><div>${esc(data.message_theme || '—')}</div></div>
                    <div class="mb-2"><span class="text-muted small">Série/Campanha</span><div>${esc(data.campaign_series || '—')}</div></div>
                    <div class="mb-2"><span class="text-muted small">Descrição</span><div>${esc(data.description || '—')}</div></div>
                    <div class="mb-2"><span class="text-muted small">Destaques</span><div>${esc(data.highlights || '—')}</div></div>
                    ${spiritual ? '<hr><h6>Manifestações Espirituais</h6>' + spiritual : ''}
                </div>
                <div class="tab-pane fade" id="viewTabFotos">
                    <div class="row g-2">${photos}</div>
                </div>
            </div>
        `;
    }

    document.querySelectorAll('.btn-view-report').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const modalEl = document.getElementById('viewServiceReportModal');
            const body = document.getElementById('viewReportBody');
            const editBtn = document.getElementById('viewReportEditBtn');
            const pdfBtn = document.getElementById('viewReportPdfBtn');
            body.innerHTML = '<div class="text-center text-muted py-5">Carregando...</div>';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();

            try {
                const res = await fetch(btn.dataset.url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('Falha ao carregar');
                const data = await res.json();
                body.innerHTML = renderView(data);
                if (data.edit_url) {
                    editBtn.href = data.edit_url;
                    editBtn.classList.remove('d-none');
                } else {
                    editBtn.classList.add('d-none');
                }
                pdfBtn.href = data.pdf_url || '#';
            } catch (e) {
                body.innerHTML = '<div class="alert alert-danger mb-0">Não foi possível carregar o relatório.</div>';
            }
        });
    });
})();
</script>

@if(!empty($openNewReportModal))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('newServiceReportModal');
    if (modalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
});
</script>
@endif
@endpush
