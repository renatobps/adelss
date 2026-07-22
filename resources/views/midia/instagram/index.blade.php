@extends('layouts.porto')

@section('title', 'Publicações Instagram')
@section('page-title', 'Publicações Instagram')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><span>Instagram</span></li>
@endsection

@section('content')
@include('midia.partials.nav', ['active' => 'instagram'])

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@unless($instagramConnected)
    <div class="alert alert-warning">
        Instagram não conectado.
        <a href="{{ route('midia.settings') }}">Configurações</a>
    </div>
@endunless

@if($instagram->isTokenExpiringSoon())
    <div class="alert alert-warning">O token do Instagram expira em breve ({{ optional($instagram->token_expires_at)->format('d/m/Y') }}). Reconecte nas configurações.</div>
@endif

<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            @foreach(\App\Models\ScheduledPost::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
    @can('midia.instagram.schedule')
        <a href="{{ route('midia.instagram.posts.create') }}" class="btn btn-primary btn-sm">
            <i class="bx bx-plus"></i> Agendar publicação
        </a>
    @endcan
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Quando</th>
                    <th>Legenda</th>
                    <th>Status</th>
                    <th>Publicado em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                    <tr>
                        <td>{{ $post->scheduled_for->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="text-truncate" style="max-width:320px;">{{ Str::limit($post->caption, 80) }}</div>
                            @if($post->error_message)
                                <div class="small text-danger">{{ Str::limit($post->error_message, 120) }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $badge = match($post->status) {
                                    'publicado' => 'bg-success',
                                    'erro' => 'bg-danger',
                                    'publicando' => 'bg-warning text-dark',
                                    default => 'bg-info-subtle text-info-emphasis',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ $post->status_label }}</span>
                        </td>
                        <td>{{ optional($post->published_at)->format('d/m/Y H:i') ?: '—' }}</td>
                        <td class="text-end">
                            @if($post->canRetry())
                                @can('midia.instagram.schedule')
                                    <form method="POST" action="{{ route('midia.instagram.posts.retry', $post) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning" type="submit">Tentar novamente</button>
                                    </form>
                                @endcan
                            @endif
                            @if($post->canCancel())
                                @can('midia.instagram.schedule')
                                    <form method="POST" action="{{ route('midia.instagram.posts.destroy', $post) }}" class="d-inline" onsubmit="return confirm('Cancelar esta publicação?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Cancelar</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma publicação agendada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($posts->hasPages())
        <div class="card-footer bg-white">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
