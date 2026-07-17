@extends('layouts.porto')

@section('title', 'Formulários do estudo')

@section('page-title', 'Ensino')

@section('breadcrumbs')
    <li><a href="{{ route('ensino.estudos.index') }}">Ensino</a></li>
    <li><a href="{{ route('ensino.estudos.show', $estudo) }}">{{ $estudo->name }}</a></li>
    <li><span>Formulários</span></li>
@endsection

@section('content')
@php
    $user = Auth::user();
    $isAdmin = $user?->is_admin ?? false;
    $canManage = $isAdmin || ($user && ($user->hasPermission('ensino.estudos.edit') || $user->hasPermission('ensino.estudos.manage')));
@endphp

<div class="row">
    <div class="col-12">
        <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Formulários de perguntas</h5>
                    <small class="text-muted">Estudo: {{ $estudo->name }}</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('ensino.estudos.show', $estudo) }}" class="btn btn-secondary btn-sm">
                        <i class="bx bx-arrow-back me-1"></i>Voltar
                    </a>
                    @if($canManage)
                    <a href="{{ route('ensino.estudos.formularios.create', $estudo) }}" class="btn btn-success btn-sm">
                        <i class="bx bx-plus me-1"></i>Novo formulário
                    </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Perguntas</th>
                                <th>Respostas</th>
                                <th>Status</th>
                                <th>Link público</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($forms as $form)
                                <tr>
                                    <td>
                                        <a href="{{ route('ensino.estudos.formularios.show', [$estudo, $form]) }}" class="fw-bold text-decoration-none" style="color:#2c3e50;">
                                            {{ $form->title }}
                                        </a>
                                    </td>
                                    <td>{{ $form->questions_count }}</td>
                                    <td>{{ $form->submissions_count }}</td>
                                    <td>
                                        <span class="badge {{ $form->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $form->is_active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="{{ $form->publicUrl() }}" target="_blank" class="small">Abrir</a>
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1"
                                                    data-copy="{{ $form->publicUrl() }}" title="Copiar link">
                                                <i class="bx bx-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('ensino.estudos.formularios.show', [$estudo, $form]) }}" class="btn btn-info" title="Detalhes">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <a href="{{ route('ensino.estudos.formularios.pdf', [$estudo, $form]) }}" class="btn btn-outline-danger" title="Exportar PDF" target="_blank">
                                                <i class="bx bx-file"></i>
                                            </a>
                                            <a href="{{ route('ensino.estudos.formularios.submissions', [$estudo, $form]) }}" class="btn btn-outline-primary" title="Respostas">
                                                <i class="bx bx-list-ul"></i>
                                            </a>
                                            @if($canManage)
                                            <a href="{{ route('ensino.estudos.formularios.edit', [$estudo, $form]) }}" class="btn btn-primary" title="Editar">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            <form action="{{ route('ensino.estudos.formularios.destroy', [$estudo, $form]) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Excluir este formulário e todas as respostas?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="Excluir">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Nenhum formulário criado ainda.
                                        @if($canManage)
                                            <a href="{{ route('ensino.estudos.formularios.create', $estudo) }}">Criar o primeiro</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(btn.dataset.copy);
            btn.classList.add('btn-success');
            setTimeout(() => btn.classList.remove('btn-success'), 1200);
        } catch (e) {
            prompt('Copie o link:', btn.dataset.copy);
        }
    });
});
</script>
@endpush
