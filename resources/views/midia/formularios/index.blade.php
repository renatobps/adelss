@extends('layouts.porto')

@section('title', 'Formulários — Mídia')
@section('page-title', 'Formulários')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><span>Formulários</span></li>
@endsection

@section('content')
@include('midia.formularios.partials.alerts')

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <form method="GET" class="d-flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm"
               placeholder="Buscar por título ou descrição" style="min-width: 220px;">
        <select name="situacao" class="form-select form-select-sm" style="width: auto;">
            <option value="">Todas as situações</option>
            <option value="abertos" @selected($filters['situacao'] === 'abertos')>Recebendo respostas</option>
            <option value="encerrados" @selected($filters['situacao'] === 'encerrados')>Encerrados ou inativos</option>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
        @if($filters['q'] !== '' || $filters['situacao'] !== '')
            <a href="{{ route('midia.formularios.index') }}" class="btn btn-sm btn-link">Limpar</a>
        @endif
    </form>

    @can('midia.formularios.manage')
        <a href="{{ route('midia.formularios.create') }}" class="btn btn-primary btn-sm">
            <i class="bx bx-plus"></i> Novo formulário
        </a>
    @endcan
</div>

@if($forms->total() === 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bx bx-list-check" style="font-size: 2.5rem; color: #6C757D;"></i>
            <p class="text-muted mt-3 mb-3">
                @if($filters['q'] !== '' || $filters['situacao'] !== '')
                    Nenhum formulário encontrado com esses filtros.
                @else
                    Nenhum formulário criado ainda. Crie um para começar a coletar dados por um link público.
                @endif
            </p>
            @can('midia.formularios.manage')
                <a href="{{ route('midia.formularios.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus"></i> Novo formulário
                </a>
            @endcan
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($forms as $form)
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100 form-card {{ $form->isOpen() ? 'form-card--open' : 'form-card--closed' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h5 class="mb-0">{{ $form->title }}</h5>
                            <span class="badge {{ $form->isOpen() ? 'bg-success' : 'bg-secondary' }}">{{ $form->statusLabel() }}</span>
                        </div>

                        @if(filled($form->description))
                            <p class="text-muted small mb-3">{{ Str::limit($form->description, 140) }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-3 mb-3 small text-muted">
                            <span><i class="bx bx-list-ul"></i> {{ $form->fields_count }} {{ Str::plural('campo', $form->fields_count) }}</span>
                            <span><i class="bx bx-message-square-detail"></i> {{ $form->submissions_count }} {{ Str::plural('resposta', $form->submissions_count) }}</span>
                            <span><i class="bx bx-calendar"></i> {{ $form->created_at->format('d/m/Y') }}</span>
                        </div>

                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text"><i class="bx bx-link"></i></span>
                            <input type="text" class="form-control" readonly value="{{ $form->publicUrl() }}"
                                   data-public-link aria-label="Link público do formulário">
                            <button type="button" class="btn btn-outline-secondary" data-copy-link
                                    title="Copiar link">Copiar</button>
                            <a href="{{ $form->publicUrl() }}" target="_blank" rel="noopener"
                               class="btn btn-outline-secondary" title="Abrir link público">
                                <i class="bx bx-external-link"></i>
                            </a>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('midia.formularios.responses.index', $form) }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-table"></i> Respostas
                            </a>
                            <a href="{{ route('midia.formularios.report', $form) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-bar-chart-alt-2"></i> Relatório
                            </a>
                            @can('midia.formularios.manage')
                                <a href="{{ route('midia.formularios.edit', $form) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-edit"></i> Editar
                                </a>
                                <form method="POST" action="{{ route('midia.formularios.destroy', $form) }}"
                                      onsubmit="return confirm('Remover o formulário {{ addslashes($form->title) }}? O link público deixa de funcionar. As {{ $form->submissions_count }} respostas já coletadas continuam guardadas.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3">
        {{ $forms->links() }}
    </div>
@endif
@endsection

@push('styles')
<style>
    .form-card { border-left: 3px solid transparent !important; }
    .form-card--open { border-left-color: #1FA855 !important; }
    .form-card--closed { border-left-color: #EEF0F2 !important; }
    .form-card--closed h5 { color: #6C757D; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    var botao = event.target.closest('[data-copy-link]');
    if (!botao) return;

    var campo = botao.closest('.input-group').querySelector('[data-public-link]');
    if (!campo) return;

    campo.select();

    var concluir = function () {
        var original = botao.textContent;
        botao.textContent = 'Copiado';
        setTimeout(function () { botao.textContent = original; }, 1500);
    };

    if (navigator.clipboard) {
        navigator.clipboard.writeText(campo.value).then(concluir);
    } else {
        document.execCommand('copy');
        concluir();
    }
});
</script>
@endpush
