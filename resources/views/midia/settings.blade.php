@extends('layouts.porto')

@section('title', 'Configurações — Mídia')
@section('page-title', 'Configurações de Mídia')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><span>Configurações</span></li>
@endsection

@section('content')
@include('midia.partials.nav', ['active' => 'settings'])

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3">
    @can('midia.configuracoes.manage')
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="midia-brand-icon google">@include('midia.partials.icon-google', ['size' => 28])</span>
                    <h5 class="mb-0">Google Drive</h5>
                </div>
                @if($google->isConnected())
                    <div class="midia-connected-box mb-3">
                        <span class="midia-pulse mt-1" aria-hidden="true"></span>
                        <div>
                            <div>Conectado como <strong>{{ $google->connected_account_email ?: 'conta Google' }}</strong></div>
                            @if($google->connected_at)
                                <div class="small text-muted">desde {{ $google->connected_at->format('d/m/Y H:i') }}</div>
                            @endif
                        </div>
                    </div>
                    <p class="small text-muted">Pasta raiz: <code>{{ $google->root_folder_id }}</code></p>
                    <form method="POST" action="{{ route('midia.google.disconnect') }}" onsubmit="return confirm('Desconectar Google Drive?')">
                        @csrf
                        <button class="btn btn-outline-danger" type="submit">Desconectar</button>
                    </form>
                @else
                    <p class="text-muted">Conecte uma conta Google para armazenar fotos e documentos na pasta <strong>ADELSS</strong> do Drive.</p>
                    <ol class="small text-muted">
                        <li>Crie um projeto no Google Cloud Console e ative a Drive API.</li>
                        <li>Crie OAuth Web com redirect <code>{{ url('/midia/google/callback') }}</code>.</li>
                        <li>Configure <code>GOOGLE_DRIVE_CLIENT_ID</code> e <code>GOOGLE_DRIVE_CLIENT_SECRET</code> no .env.</li>
                    </ol>
                    <a href="{{ route('midia.google.redirect') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <span class="midia-brand-icon google" style="width:28px;height:28px;border-radius:.45rem;">@include('midia.partials.icon-google', ['size' => 18])</span>
                        Conectar Google Drive
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endcan

    @can('midia.instagram.configuracoes.manage')
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="midia-brand-icon instagram">@include('midia.partials.icon-instagram', ['size' => 22])</span>
                    <h5 class="mb-0">Instagram</h5>
                </div>
                @if($instagram->isConnected())
                    <div class="midia-connected-box mb-3">
                        <span class="midia-pulse mt-1" aria-hidden="true"></span>
                        <div>
                            <div>Conta Business/Creator conectada</div>
                            @if($instagram->connected_at)
                                <div class="small text-muted">desde {{ $instagram->connected_at->format('d/m/Y H:i') }}</div>
                            @endif
                        </div>
                    </div>
                    @if(filled($instagram->instagram_business_account_id))
                        <p class="small text-muted mb-1">IG User ID: <code>{{ $instagram->instagram_business_account_id }}</code></p>
                    @endif
                    @if(filled($instagram->facebook_page_id))
                        <p class="small text-muted">Page ID: <code>{{ $instagram->facebook_page_id }}</code></p>
                    @endif
                    @if($instagram->isTokenExpiringSoon())
                        <div class="alert alert-warning py-2">Token próximo da expiração ({{ optional($instagram->token_expires_at)->format('d/m/Y') }}). Reconecte para renovar.</div>
                    @endif
                    <form method="POST" action="{{ route('midia.instagram.disconnect') }}" onsubmit="return confirm('Desconectar Instagram?')">
                        @csrf
                        <button class="btn btn-outline-danger" type="submit">Desconectar</button>
                    </form>
                @else
                    <p class="text-muted">Conecte a conta comercial do Instagram para publicar automaticamente.</p>
                    <ol class="small text-muted">
                        <li>No Meta for Developers, use <strong>API Setup with Instagram Login</strong> (não Facebook Login).</li>
                        <li>Redirect OAuth: <code>{{ url('/midia/instagram/callback') }}</code>.</li>
                        <li>Configure <code>INSTAGRAM_APP_ID</code> e <code>INSTAGRAM_APP_SECRET</code> (Instagram App ID/Secret) no .env.</li>
                    </ol>
                    <a href="{{ route('midia.instagram.redirect') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <span class="midia-brand-icon instagram" style="width:28px;height:28px;border-radius:.45rem;">@include('midia.partials.icon-instagram', ['size' => 16])</span>
                        Conectar Instagram
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection

@push('styles')
@include('midia.partials.styles')
@endpush
