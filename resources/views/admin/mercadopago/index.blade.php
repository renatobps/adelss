@extends('layouts.porto')
@section('title', 'Contas Mercado Pago')
@section('page-title', 'Pagamentos — Mercado Pago')
@section('content')
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())
<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="alert alert-info">
    Cadastre as contas aqui e selecione a conta de recebimento no cadastro de cada evento.
    O Financeiro e os eventos sem conta específica continuam usando a configuração padrão do servidor.
</div>
<div class="row">
    <div class="col-xl-5 mb-3">
        <section class="card">
            <header class="card-header"><h2 class="card-title">Contas disponíveis</h2></header>
            <div class="card-body">
            @forelse($accounts as $account)
                <article class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between gap-2"><strong>{{ $account->name }}</strong><span class="badge {{ $account->sandbox ? 'bg-warning text-dark' : 'bg-success' }}">{{ $account->sandbox ? 'Teste' : 'Produção' }}</span></div>
                    <p class="mt-2 mb-1">Conta validada: {{ $account->merchant_name }} ({{ $account->merchant_id }})</p>
                    <p class="small text-muted">Credenciais protegidas. Os segredos salvos não são exibidos.</p>
                    <label class="form-label small">URL para configurar no painel de Webhooks desta aplicação Mercado Pago</label>
                    <input class="form-control form-control-sm mb-2" value="{{ $account->notification_url }}?account={{ $account->uuid }}" readonly aria-label="URL de webhook de {{ $account->name }}">
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.mercadopago.edit', $account) }}">Alterar configuração</a>
                </article>
            @empty
                <p class="text-muted">Nenhuma conta cadastrada. A configuração padrão do servidor continua em uso.</p>
            @endforelse
            </div>
        </section>
    </div>
    <div class="col-xl-7">
        <section class="card">
            <header class="card-header"><h2 class="card-title">{{ $editing ? 'Alterar conta: '.$editing->name : 'Adicionar conta' }}</h2></header>
            <div class="card-body">
                @if($editing)
                <div class="alert alert-warning">Ao salvar, as novas cobranças dos eventos que usam este perfil passarão a usar a nova configuração. As credenciais anteriores serão preservadas para concluir e acompanhar as cobranças já criadas.</div>
                @endif
                <form method="post" action="{{ $editing ? route('admin.mercadopago.update', $editing) : route('admin.mercadopago.store') }}" autocomplete="off">
                    @csrf
                    @if($editing) @method('PUT') @endif
                    <div class="mb-3"><label for="mp-name" class="form-label">Nome para identificar a conta *</label><input id="mp-name" name="name" class="form-control" value="{{ old('name', $editing?->name) }}" placeholder="Ex.: Juventude — Imersão" maxlength="100" required></div>
                    <div class="mb-3"><label for="mp-public" class="form-label">MP_PUBLIC_KEY *</label><input id="mp-public" name="public_key" class="form-control font-monospace" value="{{ old('public_key', $editing?->public_key) }}" required maxlength="255" spellcheck="false"></div>
                    <div class="mb-3"><label for="mp-token" class="form-label">MP_ACCESS_TOKEN {{ $editing ? '(preencha somente para substituir)' : '*' }}</label><input id="mp-token" name="access_token" type="password" class="form-control" autocomplete="new-password" maxlength="2000" @if(!$editing) required @endif placeholder="{{ $editing ? 'Já configurado. Deixe vazio para manter.' : '' }}"></div>
                    <div class="mb-3"><label for="mp-secret" class="form-label">MP_WEBHOOK_SECRET {{ $editing ? '(preencha para substituir)' : '*' }}</label><input id="mp-secret" name="webhook_secret" type="password" class="form-control" autocomplete="new-password" maxlength="2000" @if(!$editing) required @endif placeholder="{{ $editing ? 'Já configurado. Deixe vazio para manter.' : '' }}"><small class="text-muted">Use a assinatura secreta da mesma aplicação das credenciais. Ao trocar o Access Token, informe também este segredo.</small></div>
                    <div class="row">
                        <div class="col-md-8 mb-3"><label for="mp-descriptor" class="form-label">MP_STATEMENT_DESCRIPTOR *</label><input id="mp-descriptor" name="statement_descriptor" class="form-control" value="{{ old('statement_descriptor', $editing?->statement_descriptor ?? 'ADELSS') }}" maxlength="13" required><small class="text-muted">Até 13 letras, números ou espaços.</small></div>
                        <div class="col-md-4 mb-3"><label for="mp-currency" class="form-label">MP_CURRENCY *</label><select id="mp-currency" name="currency" class="form-select"><option value="BRL">BRL — Real</option></select></div>
                    </div>
                    <div class="mb-3"><label for="mp-url" class="form-label">MP_NOTIFICATION_URL *</label><input id="mp-url" name="notification_url" type="url" class="form-control" value="{{ old('notification_url', $editing?->notification_url ?? $expectedWebhookUrl) }}" maxlength="1000" required><small class="text-muted">URL base esperada: {{ $expectedWebhookUrl }}. Neste campo, não inclua ?account=. Após salvar, copie a URL com a identificação da conta para o painel do Mercado Pago.</small></div>
                    <div class="mb-3"><label for="mp-mode" class="form-label">MP_SANDBOX *</label><select id="mp-mode" name="sandbox" class="form-select"><option value="0" @selected((string) old('sandbox', (int) ($editing?->sandbox ?? false)) === '0')>false — Produção</option><option value="1" @selected((string) old('sandbox', (int) ($editing?->sandbox ?? false)) === '1')>true — Teste</option></select><small class="text-muted">Escolha o ambiente correspondente às credenciais. Marcar teste não transforma credenciais de produção em credenciais de teste.</small></div>
                    <div class="mb-3"><label for="mp-password" class="form-label">Sua senha de administrador *</label><input id="mp-password" name="current_password" type="password" class="form-control" autocomplete="current-password" required><small class="text-muted">Necessária para autorizar esta alteração de recebimento.</small></div>
                    <button class="btn btn-primary" type="submit">Validar conta e salvar</button>
                    @if($editing)<a class="btn btn-outline-secondary ms-2" href="{{ route('admin.mercadopago.index') }}">Cancelar</a>@endif
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
