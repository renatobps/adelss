@extends('layouts.porto')

@section('title', 'Notificações - Painel')
@section('page-title', 'Notificações')
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.painel.index') }}">Notificações</a></li>
    <li><span>Painel</span></li>
@endsection

@push('styles')
<style>
:root {
    --np-bg-dark: #2E353E;
    --np-card: #FFFFFF;
    --np-primary: #0088CC;
    --np-primary-hover: #0094DD;
    --np-border: #EEF0F2;
    --np-text: #2E353E;
    --np-text-secondary: #6C757D;
    --np-success: #1FA855;
    --np-error: #DC3545;
    --np-whatsapp: #25D366;
}

.np-stat-card { border-left-width: 4px !important; }
.np-stat-card .np-stat-value { font-size: 1.35rem; font-weight: 700; line-height: 1; }
.np-stat-card .np-stat-icon { font-size: 1.25rem; opacity: .9; }

.np-mobile-switch { display: none; }
@media (max-width: 991.98px) {
    .np-mobile-switch { display: flex; gap: .5rem; margin-bottom: 1rem; }
    .np-mobile-switch .btn { flex: 1; }
    .np-pane { display: none !important; }
    .np-pane.is-active { display: block !important; }
}

.np-dropzone {
    border: 2px dashed #cfd6de;
    border-radius: .75rem;
    background: #f8fafc;
    padding: 1.25rem;
    text-align: center;
    cursor: pointer;
    transition: .15s;
}
.np-dropzone.is-dragover {
    background: rgba(0,136,204,.06);
    border-color: var(--np-primary);
}
.np-dropzone__preview {
    display: none;
    align-items: center;
    gap: .75rem;
    text-align: left;
}
.np-dropzone.has-file .np-dropzone__idle { display: none; }
.np-dropzone.has-file .np-dropzone__preview { display: flex; }
.np-dropzone__thumb {
    width: 56px; height: 56px; border-radius: .5rem; object-fit: cover;
    background: #eef2f6; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.np-dropzone__thumb img { width: 100%; height: 100%; object-fit: cover; }
.np-dropzone__thumb i { font-size: 1.5rem; color: var(--np-text-secondary); }

.np-dest-tabs .nav-link {
    color: var(--np-text-secondary);
    border: 1px solid transparent;
    border-radius: .5rem;
    padding: .45rem .75rem;
    font-weight: 600;
    font-size: .875rem;
}
.np-dest-tabs .nav-link.active {
    color: var(--np-primary);
    background: rgba(0,136,204,.08);
    border-color: rgba(0,136,204,.25);
}

.np-multiselect {
    border: 1px solid var(--np-border);
    border-radius: .75rem;
    background: #fff;
    overflow: hidden;
}
.np-multiselect__chips {
    display: flex; flex-wrap: wrap; gap: .35rem;
    padding: .65rem .75rem 0;
    min-height: 0;
}
.np-multiselect__chips:empty { display: none; padding: 0; }
.np-chip {
    display: inline-flex; align-items: center; gap: .25rem;
    background: rgba(0,136,204,.1); color: var(--np-primary);
    border-radius: 999px; padding: .2rem .55rem; font-size: .8rem; font-weight: 600;
}
.np-chip button {
    border: 0; background: transparent; color: inherit; line-height: 1;
    padding: 0; margin-left: .1rem; font-size: 1rem;
}
.np-multiselect__meta {
    padding: .35rem .75rem 0;
    font-size: .8rem; color: var(--np-text-secondary);
}
.np-multiselect__search { border: 0; border-bottom: 1px solid var(--np-border); border-radius: 0; }
.np-multiselect__list {
    max-height: 220px; overflow-y: auto;
}
.np-multiselect__option {
    display: flex; align-items: flex-start; gap: .65rem;
    margin: 0; padding: .7rem .85rem; cursor: pointer;
    border-bottom: 1px solid #f3f5f7; font-size: .9rem;
}
.np-multiselect__option:last-child { border-bottom: 0; }
.np-multiselect__option:hover, .np-multiselect__option:active { background: #f8fbff; }
.np-multiselect__option input { margin-top: .2rem; flex-shrink: 0; width: 1.1rem; height: 1.1rem; }
.np-multiselect__option.is-hidden { display: none; }
.np-multiselect__empty {
    padding: 1rem; text-align: center; color: var(--np-text-secondary); font-size: .875rem;
}

.np-history-card {
    border: 1px solid var(--np-border);
    border-radius: .75rem;
    padding: .85rem 1rem;
    background: #fff;
}
.np-history-card + .np-history-card { margin-top: .65rem; }
.np-history-card__title { font-weight: 700; color: var(--np-text); }
.np-filters-active-badge { font-size: .7rem; }
.np-msg-btn {
    width: 2.25rem; height: 2.25rem; border-radius: .5rem;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid var(--np-border); background: #f8fafc; color: var(--np-primary);
}
.np-msg-btn:hover { background: rgba(0,136,204,.1); color: var(--np-primary-hover); }
.np-status-erro {
    cursor: pointer;
    transition: filter .15s ease, box-shadow .15s ease;
}
.np-status-erro:hover { filter: brightness(1.05); box-shadow: 0 0 0 2px rgba(220,53,69,.25); }
.np-history-footer {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem 1rem;
    align-items: center;
    justify-content: space-between;
}
.np-history-footer__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem .75rem;
}
.np-history-footer__pager {
    margin-left: auto;
}
.np-history-footer__pager .pagination {
    margin: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.np-history-footer__pager .page-link {
    padding: .35rem .65rem;
    font-size: .875rem;
    color: var(--np-primary);
}
.np-history-footer__pager .page-item.active .page-link {
    background-color: var(--np-primary);
    border-color: var(--np-primary);
}
.np-history-footer__pager .page-item.disabled .page-link {
    color: var(--np-text-secondary);
}

@media (max-width: 767.98px) {
    .np-history-table-wrap { display: none !important; }
    .np-history-cards { display: block !important; }
    .np-history-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .np-history-footer__pager {
        margin-left: 0;
    }
    .np-history-footer__pager .pagination {
        justify-content: center;
        width: 100%;
    }
}
@media (min-width: 768px) {
    .np-history-cards { display: none !important; }
}
</style>
@endpush

@section('content')
@php
    $filtroAtivo = request()->filled('status') || request()->filled('data_inicio') || request()->filled('data_fim');
    $canManageHistorico = $canManageHistorico ?? false;
    $perPage = $perPage ?? 10;
    $tipoMidiaLabel = function (?string $tipo): ?string {
        return match ($tipo) {
            'image' => 'Imagem',
            'video' => 'Vídeo',
            'audio' => 'Áudio',
            'document', 'pdf' => 'Documento',
            'custom', null, '' => null,
            default => ucfirst((string) $tipo),
        };
    };
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bx bx-error me-2"></i>{{ session('warning') }}
        @if(session('envio_erros') && count(session('envio_erros')))
            <ul class="mb-0 mt-2">
                @foreach(session('envio_erros') as $motivo)
                    <li>{{ $motivo }}</li>
                @endforeach
            </ul>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        @if(session('envio_erros') && count(session('envio_erros')))
            <ul class="mb-0 mt-2">
                @foreach(session('envio_erros') as $motivo)
                    <li>{{ $motivo }}</li>
                @endforeach
            </ul>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-3">
    <div class="col-4 col-md-3">
        <div class="card np-stat-card border-start border-success h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="small text-muted">Enviadas</div>
                        <div class="np-stat-value text-success" id="npStatEnviadas">{{ $stats['enviadas'] }}</div>
                        <div class="small text-muted">30 dias</div>
                    </div>
                    <i class="bx bx-check-circle np-stat-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4 col-md-3">
        <div class="card np-stat-card border-start border-danger h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="small text-muted">Erros</div>
                        <div class="np-stat-value text-danger" id="npStatErros">{{ $stats['erros'] }}</div>
                        <div class="small text-muted">30 dias</div>
                    </div>
                    <i class="bx bx-error np-stat-icon text-danger"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4 col-md-3">
        <div class="card np-stat-card border-start border-secondary h-100">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <div class="small text-muted">Total</div>
                        <div class="np-stat-value text-secondary" id="npStatTotal">{{ $stats['total'] }}</div>
                        <div class="small text-muted">30 dias</div>
                    </div>
                    <i class="bx bx-bar-chart-alt-2 np-stat-icon text-secondary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Mobile switch Enviar / Histórico --}}
<div class="np-mobile-switch" role="tablist">
    <button type="button" class="btn btn-primary" data-np-pane="enviar" aria-selected="true">
        <i class="bx bx-send me-1"></i>Enviar
    </button>
    <button type="button" class="btn btn-outline-primary" data-np-pane="historico" aria-selected="false">
        <i class="bx bx-history me-1"></i>Histórico
    </button>
</div>

<div class="row g-3 align-items-start">
    {{-- Enviar --}}
    <div class="col-lg-5 np-pane is-active" data-np-pane-panel="enviar">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title mb-0"><i class="bx bx-send me-2"></i>Enviar notificação</h2>
            </header>
            <div class="card-body">
                <form method="POST" action="{{ route('notificacoes.painel.enviar') }}" enctype="multipart/form-data" id="npSendForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Mensagem / Legenda</label>
                        <textarea name="mensagem" class="form-control @error('mensagem') is-invalid @enderror" rows="4" maxlength="4096" placeholder="Ex.: Olá {nome}!&#10;&#10;Hoje daremos continuidade...">{{ old('mensagem') }}</textarea>
                        <small class="text-muted">Use <code>{nome}</code> para o primeiro nome do destinatário.</small>
                        @error('mensagem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Upload --}}
                    <div class="mb-3">
                        <label class="form-label">Arquivo de mídia</label>
                        <div class="np-dropzone @error('arquivo') border-danger @enderror" id="npDropzone">
                            <input type="file" name="arquivo" id="npArquivo" class="d-none"
                                   accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                            <div class="np-dropzone__idle">
                                <i class="bx bx-cloud-upload fs-1 text-primary"></i>
                                <div class="fw-semibold">Arraste um arquivo ou clique para enviar</div>
                                <div class="small text-muted">Opcional · máx. 20MB · imagem, vídeo, áudio ou documento</div>
                            </div>
                            <div class="np-dropzone__preview">
                                <div class="np-dropzone__thumb" id="npFileThumb"><i class="bx bx-file"></i></div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate" id="npFileName">—</div>
                                    <div class="small text-muted" id="npFileMeta">—</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="npFileClear" title="Remover">
                                    <i class="bx bx-x"></i>
                                </button>
                            </div>
                        </div>
                        @error('arquivo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Destinatários em abas --}}
                    <div class="mb-2">
                        <label class="form-label mb-1">Destinatários</label>
                        <small class="text-muted d-block mb-2">Escolha uma ou combine formas — o envio usa todos os destinatários selecionados.</small>
                        <ul class="nav np-dest-tabs gap-1 mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-membros" data-bs-toggle="tab" data-bs-target="#pane-membros" type="button" role="tab">Por Membro</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-telefone" data-bs-toggle="tab" data-bs-target="#pane-telefone" type="button" role="tab">Por Telefone</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-depto" data-bs-toggle="tab" data-bs-target="#pane-depto" type="button" role="tab">Por Departamento</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="pane-membros" role="tabpanel">
                                <div class="np-multiselect" data-np-multiselect>
                                    <div class="np-multiselect__chips" data-chips></div>
                                    <div class="np-multiselect__meta" data-count>0 selecionados</div>
                                    <input type="search" class="form-control np-multiselect__search" placeholder="Buscar membro..." autocomplete="off" data-search>
                                    <div class="np-multiselect__list" data-list>
                                        @forelse($members as $m)
                                            <label class="np-multiselect__option" data-label="{{ Str::lower($m->name.' '.$m->phone) }}">
                                                <input type="checkbox" name="members[]" value="{{ $m->id }}" {{ in_array($m->id, old('members', [])) ? 'checked' : '' }}>
                                                <span>
                                                    <strong>{{ $m->name }}</strong>
                                                    <span class="d-block small text-muted">{{ $m->phone }}</span>
                                                </span>
                                            </label>
                                        @empty
                                            <div class="np-multiselect__empty">Nenhum membro com telefone.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pane-telefone" role="tabpanel">
                                <textarea name="telefones_manual" class="form-control @error('telefones_manual') is-invalid @enderror" rows="3" maxlength="5000" placeholder="Ex.: 61999999999&#10;Um por linha ou separados por vírgula">{{ old('telefones_manual') }}</textarea>
                                <small class="text-muted">DDD + número. O 55 é acrescentado se faltar.</small>
                                @error('telefones_manual')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="tab-pane fade" id="pane-depto" role="tabpanel">
                                <div class="np-multiselect" data-np-multiselect>
                                    <div class="np-multiselect__chips" data-chips></div>
                                    <div class="np-multiselect__meta" data-count>0 selecionados</div>
                                    <input type="search" class="form-control np-multiselect__search" placeholder="Buscar departamento..." autocomplete="off" data-search>
                                    <div class="np-multiselect__list" data-list>
                                        @forelse($departments as $d)
                                            <label class="np-multiselect__option" data-label="{{ Str::lower($d->name) }}">
                                                <input type="checkbox" name="departments[]" value="{{ $d->id }}" {{ in_array($d->id, old('departments', [])) ? 'checked' : '' }}>
                                                <span><strong>{{ $d->name }}</strong></span>
                                            </label>
                                        @empty
                                            <div class="np-multiselect__empty">Nenhum departamento.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">Envia para todos os membros do departamento com telefone.</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 w-md-auto mt-2">
                        <i class="bx bx-send me-1"></i>Enviar
                    </button>
                </form>
            </div>
        </section>
    </div>

    {{-- Histórico --}}
    <div class="col-lg-7 np-pane" data-np-pane-panel="historico">
        <section class="card">
            <header class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="card-title mb-0"><i class="bx bx-history me-2"></i>Histórico</h2>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if($canManageHistorico)
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#limparHistoricoModal">
                                <i class="bx bx-trash me-1"></i>Limpar histórico
                            </button>
                        @endif
                        <form method="GET" class="d-none d-md-flex gap-2 flex-wrap align-items-center">
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                            <select name="status" class="form-select form-select-sm" style="width:auto;">
                                <option value="">Todos os status</option>
                                @foreach(\App\Models\NotificacaoEnviada::STATUSES as $key => $label)
                                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="data_inicio" class="form-control form-control-sm" style="width:auto;" value="{{ request('data_inicio') }}">
                            <input type="date" name="data_fim" class="form-control form-control-sm" style="width:auto;" value="{{ request('data_fim') }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
                        </form>
                    </div>
                </div>

                <div class="accordion d-md-none mt-3" id="npFiltersAccordion">
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2 px-3 rounded" type="button" data-bs-toggle="collapse" data-bs-target="#npFiltersCollapse">
                                <i class="bx bx-filter-alt me-2"></i>Filtros
                                @if($filtroAtivo)
                                    <span class="badge bg-primary ms-2 np-filters-active-badge">ativo</span>
                                @endif
                            </button>
                        </h2>
                        <div id="npFiltersCollapse" class="accordion-collapse collapse" data-bs-parent="#npFiltersAccordion">
                            <div class="accordion-body px-0 pb-0 pt-2">
                                <form method="GET" class="row g-2">
                                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                                    <div class="col-12">
                                        <select name="status" class="form-select">
                                            <option value="">Todos os status</option>
                                            @foreach(\App\Models\NotificacaoEnviada::STATUSES as $key => $label)
                                                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="data_inicio" class="form-control" value="{{ request('data_inicio') }}" aria-label="Data inicial">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="data_fim" class="form-control" value="{{ request('data_fim') }}" aria-label="Data final">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-outline-primary w-100">Filtrar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="card-body p-0">
                <div class="table-responsive np-history-table-wrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Destinatário</th>
                                <th class="text-center" style="width:90px;">Mensagem</th>
                                <th style="width:140px;">Status</th>
                                <th style="width:140px;">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notificacoes as $n)
                                @php
                                    $statusBadge = match($n->status) {
                                        'enviada' => 'bg-primary',
                                        'entregue' => 'bg-info text-dark',
                                        'lida' => 'bg-success',
                                        'erro' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                    $destinatario = $n->member?->name ?? $n->telefone ?? '—';
                                    $midiaLabel = $tipoMidiaLabel($n->tipo_notificacao);
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $destinatario }}</td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="np-msg-btn notificacao-msg-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#mensagemModal"
                                                data-destinatario="{{ e($destinatario) }}"
                                                data-status="{{ e($n->status_label) }}"
                                                data-data="{{ $n->data_envio?->format('d/m/Y H:i') }}"
                                                data-recebido="{{ $n->recebido_em?->format('d/m/Y H:i') }}"
                                                data-lido="{{ $n->lido_em?->format('d/m/Y H:i') }}"
                                                data-mensagem="{{ e($n->mensagem) }}"
                                                data-midia="{{ e($midiaLabel ?? '') }}"
                                                title="Ver mensagem">
                                            <i class="bx bx-message-rounded-dots fs-5"></i>
                                        </button>
                                    </td>
                                    <td>
                                        @if($n->status === 'erro')
                                            <button type="button"
                                                    class="badge {{ $statusBadge }} border-0 np-status-erro"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#erroModal"
                                                    data-erro="{{ e($n->erro_detalhes ?: 'Erro no envio (sem detalhes).') }}">
                                                <i class="bx bx-x"></i> {{ $n->status_label }}
                                            </button>
                                        @else
                                            <span class="badge {{ $statusBadge }}">{{ $n->status_label }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $n->data_envio?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-3">Nenhum registro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="np-history-cards p-3">
                    @forelse($notificacoes as $n)
                        @php
                            $statusBadge = match($n->status) {
                                'enviada' => 'bg-primary',
                                'entregue' => 'bg-info text-dark',
                                'lida' => 'bg-success',
                                'erro' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                            $destinatario = $n->member?->name ?? $n->telefone ?? '—';
                            $midiaLabel = $tipoMidiaLabel($n->tipo_notificacao);
                        @endphp
                        <article class="np-history-card">
                            <div class="np-history-card__title mb-1">{{ $destinatario }}</div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <button type="button"
                                        class="np-msg-btn notificacao-msg-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#mensagemModal"
                                        data-destinatario="{{ e($destinatario) }}"
                                        data-status="{{ e($n->status_label) }}"
                                        data-data="{{ $n->data_envio?->format('d/m/Y H:i') }}"
                                        data-recebido="{{ $n->recebido_em?->format('d/m/Y H:i') }}"
                                        data-lido="{{ $n->lido_em?->format('d/m/Y H:i') }}"
                                        data-mensagem="{{ e($n->mensagem) }}"
                                        data-midia="{{ e($midiaLabel ?? '') }}"
                                        title="Ver mensagem">
                                    <i class="bx bx-message-rounded-dots fs-5"></i>
                                </button>
                                @if($n->status === 'erro')
                                    <button type="button"
                                            class="badge {{ $statusBadge }} border-0 np-status-erro"
                                            data-bs-toggle="modal"
                                            data-bs-target="#erroModal"
                                            data-erro="{{ e($n->erro_detalhes ?: 'Erro no envio (sem detalhes).') }}">
                                        <i class="bx bx-x"></i> {{ $n->status_label }}
                                    </button>
                                @else
                                    <span class="badge {{ $statusBadge }}">{{ $n->status_label }}</span>
                                @endif
                            </div>
                            <div class="small text-muted">{{ $n->data_envio?->format('d/m/Y H:i') }}</div>
                        </article>
                    @empty
                        <p class="text-center text-muted mb-0 py-3">Nenhum registro.</p>
                    @endforelse
                </div>
            </div>

            <div class="card-footer np-history-footer">
                <form method="GET" class="np-history-footer__meta">
                    @foreach(request()->except(['per_page', 'page']) as $key => $value)
                        @if(is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label class="small text-muted mb-0" for="npPerPage">Itens por página</label>
                    <select name="per_page" id="npPerPage" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        @foreach([10, 50, 100] as $opt)
                            <option value="{{ $opt }}" @selected((int) $perPage === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <span class="small text-muted">
                        Página {{ $notificacoes->currentPage() }} de {{ max(1, $notificacoes->lastPage()) }}
                        ({{ $notificacoes->total() }} no total)
                    </span>
                </form>
                <div class="np-history-footer__pager">
                    {{ $notificacoes->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </section>
    </div>
</div>

{{-- Modal mensagem --}}
<div class="modal fade" id="mensagemModal" tabindex="-1" aria-labelledby="mensagemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mensagemModalLabel">Mensagem enviada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 small text-muted">
                    <div><strong>Destinatário:</strong> <span id="msgModalDestinatario">—</span></div>
                    <div><strong>Status:</strong> <span id="msgModalStatus">—</span></div>
                    <div><strong>Enviada em:</strong> <span id="msgModalData">—</span></div>
                    <div id="msgModalRecebidoWrap" class="d-none"><strong>Recebido em:</strong> <span id="msgModalRecebido">—</span></div>
                    <div id="msgModalLidoWrap" class="d-none"><strong>Lida em:</strong> <span id="msgModalLido">—</span></div>
                    <div id="msgModalMidiaWrap" class="d-none"><strong>Mídia:</strong> <span id="msgModalMidia">—</span></div>
                </div>
                <div class="border rounded p-3 bg-light" style="white-space: pre-wrap; word-break: break-word;" id="msgModalTexto"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal erro --}}
<div class="modal fade" id="erroModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bx bx-error-circle me-1"></i> Detalhe do erro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="erroModalTexto" style="white-space: pre-wrap; word-break: break-word;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

@if($canManageHistorico)
<div class="modal fade" id="limparHistoricoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="limparHistoricoForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bx bx-trash me-1"></i> Limpar histórico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Esta ação é irreversível. Escolha o que deseja excluir:</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="modo" id="modoTudo" value="tudo" checked>
                    <label class="form-check-label" for="modoTudo">Excluir tudo</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="modo" id="modoAntesHoje" value="antes_hoje">
                    <label class="form-check-label" for="modoAntesHoje">Excluir anterior a hoje</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="modo" id="modoAntesData" value="antes_data">
                    <label class="form-check-label" for="modoAntesData">Excluir anterior a uma data específica</label>
                </div>
                <div class="mb-3 d-none" id="limparDataWrap">
                    <label class="form-label" for="data_limite">Manter a partir de</label>
                    <input type="date" class="form-control" name="data_limite" id="data_limite">
                    <small class="text-muted">Será excluído tudo com data anterior a esta.</small>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="confirmacaoExcluir">Digite <strong>EXCLUIR</strong> para confirmar</label>
                    <input type="text" class="form-control" name="confirmacao" id="confirmacaoExcluir" autocomplete="off" placeholder="EXCLUIR">
                </div>
                <div class="alert alert-danger d-none mt-3 mb-0 py-2" id="limparHistoricoErro"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger" id="limparHistoricoSubmit" disabled>Excluir</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const decodeHtml = (value) => {
        const el = document.createElement('textarea');
        el.innerHTML = value || '';
        return el.value;
    };

    // Modal mensagem
    document.getElementById('mensagemModal')?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        document.getElementById('msgModalDestinatario').textContent = decodeHtml(btn.getAttribute('data-destinatario')) || '—';
        document.getElementById('msgModalStatus').textContent = decodeHtml(btn.getAttribute('data-status')) || '—';
        document.getElementById('msgModalData').textContent = btn.getAttribute('data-data') || '—';
        document.getElementById('msgModalTexto').textContent = decodeHtml(btn.getAttribute('data-mensagem')) || '';
        const recebido = btn.getAttribute('data-recebido') || '';
        const lido = btn.getAttribute('data-lido') || '';
        const midia = decodeHtml(btn.getAttribute('data-midia') || '');
        document.getElementById('msgModalRecebidoWrap').classList.toggle('d-none', !recebido);
        document.getElementById('msgModalLidoWrap').classList.toggle('d-none', !lido);
        document.getElementById('msgModalMidiaWrap').classList.toggle('d-none', !midia);
        document.getElementById('msgModalRecebido').textContent = recebido || '—';
        document.getElementById('msgModalLido').textContent = lido || '—';
        document.getElementById('msgModalMidia').textContent = midia || '—';
    });

    // Modal erro
    document.getElementById('erroModal')?.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('erroModalTexto').textContent = decodeHtml(btn?.getAttribute('data-erro') || 'Erro no envio.');
    });

    // Limpar histórico
    const limparForm = document.getElementById('limparHistoricoForm');
    if (limparForm) {
        const confirmInput = document.getElementById('confirmacaoExcluir');
        const submitBtn = document.getElementById('limparHistoricoSubmit');
        const dataWrap = document.getElementById('limparDataWrap');
        const erroBox = document.getElementById('limparHistoricoErro');
        const syncConfirm = () => {
            submitBtn.disabled = (confirmInput.value || '').trim() !== 'EXCLUIR';
        };
        confirmInput?.addEventListener('input', syncConfirm);
        const syncModo = () => {
            dataWrap.classList.toggle('d-none', !document.getElementById('modoAntesData')?.checked);
        };
        limparForm.querySelectorAll('input[name="modo"]').forEach((radio) => {
            radio.addEventListener('change', syncModo);
        });
        syncModo();
        limparForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            erroBox.classList.add('d-none');
            submitBtn.disabled = true;
            const fd = new FormData(limparForm);
            try {
                const res = await fetch(@json(route('notificacoes.painel.historico.limpar')), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    const msg = data.message
                        || data.errors?.confirmacao?.[0]
                        || data.errors?.data_limite?.[0]
                        || data.errors?.modo?.[0]
                        || 'Não foi possível limpar o histórico.';
                    erroBox.textContent = msg;
                    erroBox.classList.remove('d-none');
                    syncConfirm();
                    return;
                }
                if (data.stats) {
                    document.getElementById('npStatEnviadas').textContent = data.stats.enviadas;
                    document.getElementById('npStatErros').textContent = data.stats.erros;
                    document.getElementById('npStatTotal').textContent = data.stats.total;
                }
                window.location.href = @json(route('notificacoes.painel.index'));
            } catch (err) {
                erroBox.textContent = 'Falha de comunicação ao limpar o histórico.';
                erroBox.classList.remove('d-none');
                syncConfirm();
            }
        });
    }

    // Mobile panes
    const paneButtons = document.querySelectorAll('[data-np-pane]');
    const panePanels = document.querySelectorAll('[data-np-pane-panel]');
    function setPane(name) {
        paneButtons.forEach((btn) => {
            const active = btn.getAttribute('data-np-pane') === name;
            btn.classList.toggle('btn-primary', active);
            btn.classList.toggle('btn-outline-primary', !active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panePanels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.getAttribute('data-np-pane-panel') === name);
        });
    }
    paneButtons.forEach((btn) => btn.addEventListener('click', () => setPane(btn.getAttribute('data-np-pane'))));
    // Em desktop ambos ficam visíveis via CSS; em mobile inicia em Enviar
    if (window.matchMedia('(max-width: 991.98px)').matches) {
        setPane('enviar');
    } else {
        panePanels.forEach((p) => p.classList.add('is-active'));
    }
    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 992px)').matches) {
            panePanels.forEach((p) => p.classList.add('is-active'));
        }
    });

    // Multiselect com busca + chips
    function initMultiselect(root) {
        const chipsEl = root.querySelector('[data-chips]');
        const countEl = root.querySelector('[data-count]');
        const searchEl = root.querySelector('[data-search]');
        const options = Array.from(root.querySelectorAll('.np-multiselect__option'));

        function labelOf(opt) {
            const strong = opt.querySelector('strong');
            return (strong ? strong.textContent : opt.textContent || '').trim();
        }

        function refresh() {
            const selected = options.filter((opt) => opt.querySelector('input')?.checked);
            chipsEl.innerHTML = '';
            selected.forEach((opt) => {
                const input = opt.querySelector('input');
                const chip = document.createElement('span');
                chip.className = 'np-chip';
                chip.innerHTML = '<span></span><button type="button" aria-label="Remover">&times;</button>';
                chip.querySelector('span').textContent = labelOf(opt);
                chip.querySelector('button').addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    input.checked = false;
                    refresh();
                });
                chipsEl.appendChild(chip);
            });
            const n = selected.length;
            countEl.textContent = n + (n === 1 ? ' selecionado' : ' selecionados');
        }

        options.forEach((opt) => {
            const input = opt.querySelector('input');
            input?.addEventListener('change', refresh);
        });

        searchEl?.addEventListener('input', () => {
            const q = (searchEl.value || '').trim().toLowerCase();
            options.forEach((opt) => {
                const hay = opt.getAttribute('data-label') || '';
                opt.classList.toggle('is-hidden', q !== '' && !hay.includes(q));
            });
        });

        refresh();
    }
    document.querySelectorAll('[data-np-multiselect]').forEach(initMultiselect);

    // Dropzone + preview
    const drop = document.getElementById('npDropzone');
    const fileInput = document.getElementById('npArquivo');
    const thumb = document.getElementById('npFileThumb');
    const nameEl = document.getElementById('npFileName');
    const metaEl = document.getElementById('npFileMeta');
    const clearBtn = document.getElementById('npFileClear');
    let objectUrl = null;

    function fileIcon(file) {
        if (file.type.startsWith('image/')) return null;
        if (file.type.startsWith('video/')) return 'bx-video';
        if (file.type.startsWith('audio/')) return 'bx-music';
        if (file.type.includes('pdf')) return 'bx-file-blank';
        return 'bx-file';
    }

    function showFile(file) {
        if (!file || !drop) return;
        drop.classList.add('has-file');
        nameEl.textContent = file.name;
        metaEl.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB · ' + (file.type || 'arquivo');
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        const icon = fileIcon(file);
        if (!icon) {
            objectUrl = URL.createObjectURL(file);
            thumb.innerHTML = '<img alt="Preview">';
            thumb.querySelector('img').src = objectUrl;
        } else {
            thumb.innerHTML = '<i class="bx ' + icon + '"></i>';
        }
    }

    function clearFile() {
        if (!drop || !fileInput) return;
        fileInput.value = '';
        drop.classList.remove('has-file');
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        thumb.innerHTML = '<i class="bx bx-file"></i>';
        nameEl.textContent = '—';
        metaEl.textContent = '—';
    }

    drop?.addEventListener('click', (e) => {
        if (e.target.closest('#npFileClear')) return;
        fileInput?.click();
    });
    ['dragenter', 'dragover'].forEach((ev) => drop?.addEventListener(ev, (e) => {
        e.preventDefault();
        drop.classList.add('is-dragover');
    }));
    ['dragleave', 'drop'].forEach((ev) => drop?.addEventListener(ev, (e) => {
        e.preventDefault();
        drop.classList.remove('is-dragover');
    }));
    drop?.addEventListener('drop', (e) => {
        const file = e.dataTransfer?.files?.[0];
        if (!file || !fileInput) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showFile(file);
    });
    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (file) showFile(file);
        else clearFile();
    });
    clearBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        clearFile();
    });
})();
</script>
@endpush
