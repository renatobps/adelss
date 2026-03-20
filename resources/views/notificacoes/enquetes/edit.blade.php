@extends('layouts.porto')

@section('title', 'Editar Enquete - Notificações')
@section('page-title', 'Editar Enquete')
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.enquetes.index') }}">Notificações</a></li>
    <li><a href="{{ route('notificacoes.enquetes.index') }}">Enquetes</a></li>
    <li><a href="{{ route('notificacoes.enquetes.show', $enquete) }}">{{ $enquete->titulo }}</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bx bx-edit me-2"></i>Editar Enquete</h1>
    <div>
        <a href="{{ route('notificacoes.enquetes.show', $enquete) }}" class="btn btn-outline-primary me-1">
            <i class="bx bx-show me-1"></i> Visualizar
        </a>
        <a href="{{ route('notificacoes.enquetes.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Voltar
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="bx bx-info-circle me-2"></i>
    <strong>Enquetes via Z-API:</strong>
    As enquetes são enviadas como polls interativos usando a API Z-API. Os membros recebem uma enquete com botões para escolher a resposta.
</div>

<section class="card">
    <header class="card-header">
        <h5 class="mb-0"><i class="bx bx-cog me-2"></i>Configuração da Enquete</h5>
    </header>
    <div class="card-body">
        <form action="{{ route('notificacoes.enquetes.update', $enquete) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Título da Enquete *</label>
                    <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                           value="{{ old('titulo', $enquete->titulo) }}" required>
                    @error('titulo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label">Descrição (Opcional)</label>
                    <textarea name="descricao" class="form-control @error('descricao') is-invalid @enderror"
                              rows="3" placeholder="Descreva o objetivo da enquete...">{{ old('descricao', $enquete->descricao) }}</textarea>
                    @error('descricao')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Data de Início (Opcional)</label>
                    <input type="datetime-local" name="inicio_em" class="form-control @error('inicio_em') is-invalid @enderror"
                           value="{{ old('inicio_em', $enquete->inicio_em?->format('Y-m-d\TH:i')) }}">
                    @error('inicio_em')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Data de Fim (Opcional)</label>
                    <input type="datetime-local" name="fim_em" class="form-control @error('fim_em') is-invalid @enderror"
                           value="{{ old('fim_em', $enquete->fim_em?->format('Y-m-d\TH:i')) }}">
                    @error('fim_em')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ativa" value="1"
                               {{ old('ativa', $enquete->ativa) ? 'checked' : '' }}>
                        <label class="form-check-label">Enquete ativa</label>
                    </div>
                </div>
            </div>

            <hr>

            <div class="mb-4">
                <label class="form-label">Opções da Enquete *</label>
                <div id="opcoes-container">
                    @foreach(old('opcoes', $enquete->opcoes ?: ['', '']) as $index => $opcao)
                        <div class="row g-2 mb-2">
                            <div class="col-md-10">
                                <input type="text" name="opcoes[]" class="form-control"
                                       placeholder="Opção {{ $index + 1 }}" value="{{ $opcao }}" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removerOpcao(this)"
                                        {{ count(old('opcoes', $enquete->opcoes ?: ['', ''])) <= 2 ? 'disabled' : '' }}>
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="adicionarOpcao()" id="btn-adicionar-opcao">
                    <i class="bx bx-plus me-1"></i> Adicionar Opção
                </button>

                @error('opcoes')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-check-circle me-1"></i> Salvar Alterações
                </button>
                <a href="{{ route('notificacoes.enquetes.show', $enquete) }}" class="btn btn-outline-secondary">
                    <i class="bx bx-x-circle me-1"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
let opcaoCount = {{ count(old('opcoes', $enquete->opcoes ?: ['', ''])) }};

function adicionarOpcao() {
    opcaoCount++;
    const container = document.getElementById('opcoes-container');
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2';
    div.innerHTML = `
        <div class="col-md-10">
            <input type="text" name="opcoes[]" class="form-control" placeholder="Opção ${opcaoCount}" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removerOpcao(this)">
                <i class="bx bx-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);

    const opcoes = container.querySelectorAll('button[onclick="removerOpcao(this)"]');
    opcoes.forEach(btn => {
        if (container.children.length > 2) btn.disabled = false;
    });
}

function removerOpcao(button) {
    const container = document.getElementById('opcoes-container');
    if (container.children.length > 2) {
        button.closest('.row').remove();
        const opcoes = container.querySelectorAll('button[onclick="removerOpcao(this)"]');
        if (container.children.length === 2) {
            opcoes.forEach(btn => btn.disabled = true);
        }
    }
}
</script>
@endpush
@endsection
