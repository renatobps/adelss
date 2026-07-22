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
                <h5 class="mb-3"><i class="bx bxl-google"></i> Google Drive</h5>
                @if($google->isConnected())
                    <div class="alert alert-success py-2">
                        Conectado como <strong>{{ $google->connected_account_email ?: 'conta Google' }}</strong>
                        @if($google->connected_at)
                            <div class="small">desde {{ $google->connected_at->format('d/m/Y H:i') }}</div>
                        @endif
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
                    <a href="{{ route('midia.google.redirect') }}" class="btn btn-primary">
                        <i class="bx bxl-google"></i> Conectar Google Drive
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
                <h5 class="mb-3"><i class="bx bxl-instagram"></i> Instagram</h5>
                @if($instagram->isConnected())
                    <div class="alert alert-success py-2">
                        Conta Business/Creator conectada
                        @if($instagram->connected_at)
                            <div class="small">desde {{ $instagram->connected_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                    <p class="small text-muted mb-1">IG User ID: <code>{{ $instagram->instagram_business_account_id }}</code></p>
                    <p class="small text-muted">Page ID: <code>{{ $instagram->facebook_page_id }}</code></p>
                    @if($instagram->isTokenExpiringSoon())
                        <div class="alert alert-warning py-2">Token próximo da expiração ({{ optional($instagram->token_expires_at)->format('d/m/Y') }}). Reconecte para renovar.</div>
                    @endif
                    <form method="POST" action="{{ route('midia.instagram.disconnect') }}" onsubmit="return confirm('Desconectar Instagram?')">
                        @csrf
                        <button class="btn btn-outline-danger" type="submit">Desconectar</button>
                    </form>
                @else
                    <p class="text-muted">Conecte a conta comercial do Instagram (vinculada a uma Página do Facebook) para publicar automaticamente.</p>
                    <ol class="small text-muted">
                        <li>No Meta for Developers, adicione Instagram Graph API ao app.</li>
                        <li>Redirect: <code>{{ url('/midia/instagram/callback') }}</code>.</li>
                        <li>Configure <code>INSTAGRAM_APP_ID</code> e <code>INSTAGRAM_APP_SECRET</code> (ou META_*) no .env.</li>
                    </ol>
                    <a href="{{ route('midia.instagram.redirect') }}" class="btn btn-primary">
                        <i class="bx bxl-instagram"></i> Conectar Instagram
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection
