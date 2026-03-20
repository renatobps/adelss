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
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
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
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Enviadas (30 dias)</span>
                    <strong>{{ $stats['enviadas'] }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Erros (30 dias)</span>
                    <strong>{{ $stats['erros'] }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Total (30 dias)</span>
                    <strong>{{ $stats['total'] }}</strong>
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
                <form method="POST" action="{{ route('notificacoes.painel.enviar') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Mensagem <span class="text-danger">*</span></label>
                        <textarea name="mensagem" class="form-control @error('mensagem') is-invalid @enderror" rows="4" required maxlength="4096" placeholder="Digite a mensagem...">{{ old('mensagem') }}</textarea>
                        @error('mensagem')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <label class="form-label">Departamentos</label>
                        <select name="departments[]" class="form-select" multiple size="3">
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ in_array($d->id, old('departments', [])) ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Envia para todos os membros do departamento (com telefone).</small>
                    </div>
                    <p class="text-muted small">Selecione pelo menos um membro ou um departamento.</p>
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
                        <option value="enviada" {{ request('status') === 'enviada' ? 'selected' : '' }}>Enviada</option>
                        <option value="erro" {{ request('status') === 'erro' ? 'selected' : '' }}>Erro</option>
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
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notificacoes as $n)
                                <tr>
                                    <td>
                                        {{ $n->member?->name ?? $n->telefone ?? '—' }}
                                    </td>
                                    <td><small>{{ Str::limit($n->mensagem, 40) }}</small></td>
                                    <td>
                                        @if($n->status === 'enviada')
                                            <span class="badge bg-success">Enviada</span>
                                        @elseif($n->status === 'erro')
                                            <span class="badge bg-danger">Erro</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $n->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $n->data_envio?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-3">Nenhum registro.</td></tr>
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
@endsection
