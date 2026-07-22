@php
    $canFiles = auth()->user()->is_admin || auth()->user()->hasPermission('midia.arquivos.view');
    $canIg = auth()->user()->is_admin || auth()->user()->hasPermission('midia.instagram.view');
    $canSettings = auth()->user()->is_admin
        || auth()->user()->hasPermission('midia.configuracoes.manage')
        || auth()->user()->hasPermission('midia.instagram.configuracoes.manage');
@endphp
<div class="d-flex flex-wrap gap-2 mb-3">
    @if($canFiles)
        <a href="{{ route('midia.index') }}" class="btn btn-sm {{ ($active ?? '') === 'arquivos' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bx bx-folder"></i> Arquivos
        </a>
    @endif
    @if($canIg)
        <a href="{{ route('midia.instagram.posts.index') }}" class="btn btn-sm {{ ($active ?? '') === 'instagram' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bx bxl-instagram"></i> Instagram
        </a>
    @endif
    @if($canSettings)
        <a href="{{ route('midia.settings') }}" class="btn btn-sm {{ ($active ?? '') === 'settings' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bx bx-cog"></i> Configurações
        </a>
    @endif
</div>
