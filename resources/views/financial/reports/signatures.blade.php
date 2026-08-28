@extends('layouts.porto')

@section('title', 'Assinaturas dos relatórios')

@section('page-title', 'Relatórios')

@section('breadcrumbs')
    <li><a href="{{ route('financial.summary') }}">Financeiro</a></li>
    <li><a href="{{ route('financial.reports.index') }}">Relatórios</a></li>
    <li><span>Assinaturas</span></li>
@endsection

@section('content')
<div class="row fr-page">
    @include('financial.reports.partials.sidebar')

    <div class="col-12 col-lg-9 fr-main">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card fr-card mb-4">
            <div class="card-body">
                <h5 class="mb-2">Assinaturas automáticas nos PDFs</h5>
                <p class="text-muted mb-0">
                    A assinatura do 1º tesoureiro entra nos PDFs (extrato, dízimos/ofertas e fechamento semanal).
                    O nome vem do cargo no cadastro de membros. Fundo transparente (PNG) funciona melhor.
                </p>
            </div>
        </div>

        <div class="card fr-card">
            <div class="card-body">
                <h6 class="mb-1">1º Tesoureiro(a)</h6>
                <p class="small text-muted">{{ $tesoureiroNome ?: 'Nenhum membro com este cargo encontrado.' }}</p>

                @if($tesoureiroAssinaturaSrc)
                    <div class="border rounded p-3 mb-3 text-center bg-light">
                        <img src="{{ $tesoureiroAssinaturaSrc }}" alt="Assinatura" style="max-height: 80px; max-width: 100%;">
                    </div>
                @else
                    <div class="border rounded p-3 mb-3 text-muted small">Nenhuma imagem carregada — o PDF mostra linha para assinar.</div>
                @endif

                <form method="POST" action="{{ route('financial.reports.signatures.update') }}" enctype="multipart/form-data" class="mb-2">
                    @csrf
                    <input type="hidden" name="role" value="tesoureiro">
                    <input type="file" class="form-control mb-2" name="assinatura" accept="image/png,image/jpeg,image/webp" required>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bx bx-upload me-1"></i>Enviar assinatura
                    </button>
                </form>
                @if($tesoureiroAssinaturaSrc)
                    <form method="POST" action="{{ route('financial.reports.signatures.destroy') }}" onsubmit="return confirm('Remover esta assinatura?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="role" value="tesoureiro">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Remover imagem</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
