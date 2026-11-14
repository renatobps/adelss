@extends('layouts.porto')
@section('title', 'Configuração de rifas')
@section('page-title', 'Configuração de rifas')
@section('breadcrumbs')
    <li><a href="{{ route('rifas.index') }}">Rifas</a></li><li><span>Configuração</span></li>
@endsection
@section('content')
<section class="card">
    <header class="card-header"><h2 class="card-title">Mensagem de envio das cartelas</h2></header>
    <div class="card-body">
        <p>Cadastre uma mensagem para usar no envio das cartelas por WhatsApp. Esta configuração vale para todas as rifas. Antes de enviar, você escolherá entre a mensagem padrão do sistema e a mensagem cadastrada.</p>
        <form method="POST" action="{{ route('rifas.configuracao.update') }}">
            @csrf @method('PUT')
            <label for="rifa-message-template" class="form-label">Mensagem cadastrada</label>
            <textarea id="rifa-message-template" name="template" class="form-control mb-2" rows="9" maxlength="2000" required placeholder="Olá, {nome}! Seguem suas cartelas da rifa {rifa}. Boa campanha!">{{ old('template', $mensagem) }}</textarea>
            <p class="small text-muted">Até 2.000 caracteres. As variáveis serão preenchidas com os dados de cada vendedor e da rifa. A mensagem acompanha o PDF; a chave PIX e o recebedor permanecem no arquivo.</p>
            <div class="border rounded p-3 mb-3">
                <strong>Variáveis disponíveis</strong>
                <ul class="mt-2 mb-0">@foreach($variaveis as $variavel => $descricao)<li><code>{{ $variavel }}</code> — {{ $descricao }}</li>@endforeach</ul>
            </div>
            <button type="submit" class="btn btn-primary">Salvar mensagem</button>
            <a href="{{ route('rifas.index') }}" class="btn btn-default">Voltar para rifas</a>
        </form>
    </div>
</section>
@endsection
