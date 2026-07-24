@extends('layouts.porto')

@section('title', 'Notificações - Painel')
@section('page-title', 'Notificações')
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.painel.index') }}">Notificações</a></li>
    <li><span>Painel</span></li>
@endsection

@section('content')
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

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-4 border-success">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-dark">Enviadas (30 dias)</span>
                    <strong class="text-success">{{ $stats['enviadas'] }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-danger">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-dark">Erros (30 dias)</span>
                    <strong class="text-danger">{{ $stats['erros'] }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-4 border-secondary">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-dark">Total (30 dias)</span>
                    <strong class="text-secondary">{{ $stats['total'] }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title"><i class="bx bx-send me-2"></i>Enviar notificação</h2>
            </header>
            <div class="card-body">
                <form method="POST" action="{{ route('notificacoes.painel.enviar') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Mensagem / Legenda</label>
                        <textarea name="mensagem" class="form-control @error('mensagem') is-invalid @enderror" rows="4" maxlength="4096" placeholder="Ex.: Olá {nome}!&#10;&#10;Hoje daremos continuidade...">{{ old('mensagem') }}</textarea>
                        <small class="text-muted">Use <code>{nome}</code> para inserir o primeiro nome do destinatário (ex.: Renato).</small>
                        @error('mensagem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Arquivo de mídia</label>
                            <input type="file" name="arquivo" class="form-control @error('arquivo') is-invalid @enderror" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                            <small class="text-muted">Opcional. Máximo de 20MB. O tipo é detectado automaticamente (image/document/video/audio).</small>
                            @error('arquivo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Membros</label>
                        <select name="members[]" class="form-select" multiple size="5">
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" {{ in_array($m->id, old('members', [])) ? 'selected' : '' }}>{{ $m->name }} ({{ $m->phone }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Ctrl para múltipla seleção.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone(s) manual(is)</label>
                        <textarea name="telefones_manual" class="form-control @error('telefones_manual') is-invalid @enderror" rows="2" maxlength="5000" placeholder="Ex.: 61999999999">{{ old('telefones_manual') }}</textarea>
                        <small class="text-muted">Opcional. DDD + número; vários separados por vírgula ou um por linha. O 55 é acrescentado se faltar.</small>
                        @error('telefones_manual')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Departamentos</label>
                        <select name="departments[]" class="form-select" multiple size="3">
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ in_array($d->id, old('departments', [])) ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Envia para todos os membros do departamento (com telefone).</small>
                    </div>
                    <p class="text-muted small">Selecione destinatários e envie texto, mídia (arquivo + tipo) ou ambos.</p>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-send me-1"></i>Enviar</button>
                </form>
            </div>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0"><i class="bx bx-history me-2"></i>Histórico</h2>
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="status" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Todos os status</option>
                        @foreach(\App\Models\NotificacaoEnviada::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="data_inicio" class="form-control form-control-sm" style="width:auto;" value="{{ request('data_inicio') }}" placeholder="Início">
                    <input type="date" name="data_fim" class="form-control form-control-sm" style="width:auto;" value="{{ request('data_fim') }}" placeholder="Fim">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
                </form>
            </header>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Destinatário</th>
                                <th>Mensagem</th>
                                <th>Status</th>
                                <th>Motivo</th>
                                <th>Data</th>
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
                                @endphp
                                <tr>
                                    <td>{{ $destinatario }}</td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-link btn-sm text-start p-0 text-decoration-none notificacao-msg-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#mensagemModal"
                                                data-destinatario="{{ e($destinatario) }}"
                                                data-status="{{ e($n->status_label) }}"
                                                data-data="{{ $n->data_envio?->format('d/m/Y H:i') }}"
                                                data-recebido="{{ $n->recebido_em?->format('d/m/Y H:i') }}"
                                                data-lido="{{ $n->lido_em?->format('d/m/Y H:i') }}"
                                                data-mensagem="{{ e($n->mensagem) }}"
                                                title="Clique para ver a mensagem completa">
                                            <small>{{ Str::limit($n->mensagem, 40) }}</small>
                                        </button>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusBadge }}">{{ $n->status_label }}</span>
                                        @if($n->status === 'entregue' && $n->recebido_em)
                                            <div class="small text-muted">{{ $n->recebido_em->format('d/m H:i') }}</div>
                                        @elseif($n->status === 'lida' && $n->lido_em)
                                            <div class="small text-muted">{{ $n->lido_em->format('d/m H:i') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($n->status === 'erro' && $n->erro_detalhes)
                                            <small class="text-danger" title="{{ $n->erro_detalhes }}">{{ Str::limit($n->erro_detalhes, 80) }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $n->data_envio?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-3">Nenhum registro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($notificacoes->hasPages())
                <div class="card-footer">{{ $notificacoes->links() }}</div>
            @endif
        </section>
    </div>
</div>

{{-- Modal mensagem completa --}}
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
                </div>
                <div class="border rounded p-3 bg-light" style="white-space: pre-wrap; word-break: break-word;" id="msgModalTexto"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('mensagemModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    if (!btn) return;

    const decode = (value) => {
        const el = document.createElement('textarea');
        el.innerHTML = value || '';
        return el.value;
    };

    document.getElementById('msgModalDestinatario').textContent = decode(btn.getAttribute('data-destinatario')) || '—';
    document.getElementById('msgModalStatus').textContent = decode(btn.getAttribute('data-status')) || '—';
    document.getElementById('msgModalData').textContent = btn.getAttribute('data-data') || '—';
    document.getElementById('msgModalTexto').textContent = decode(btn.getAttribute('data-mensagem')) || '';

    const recebido = btn.getAttribute('data-recebido') || '';
    const lido = btn.getAttribute('data-lido') || '';
    const wrapR = document.getElementById('msgModalRecebidoWrap');
    const wrapL = document.getElementById('msgModalLidoWrap');
    wrapR.classList.toggle('d-none', !recebido);
    wrapL.classList.toggle('d-none', !lido);
    document.getElementById('msgModalRecebido').textContent = recebido || '—';
    document.getElementById('msgModalLido').textContent = lido || '—';
});
</script>
@endpush
