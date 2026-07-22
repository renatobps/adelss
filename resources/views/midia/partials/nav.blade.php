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
        <a href="{{ route('midia.instagram.posts.index') }}"
           class="btn btn-sm d-inline-flex align-items-center gap-1 {{ ($active ?? '') === 'instagram' ? 'btn-primary' : 'btn-outline-secondary' }}">
            @if(($active ?? '') === 'instagram')
                <i class="bx bxl-instagram"></i>
            @else
                <span class="d-inline-flex align-items-center justify-content-center rounded"
                      style="width:18px;height:18px;background:linear-gradient(45deg,#F77737,#FD1D1D,#E4405F,#833AB4);color:#fff;">
                    <i class="bx bxl-instagram" style="font-size:12px;"></i>
                </span>
            @endif
            Instagram
        </a>
    @endif
    @if($canSettings)
        <a href="{{ route('midia.settings') }}" class="btn btn-sm {{ ($active ?? '') === 'settings' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bx bx-cog"></i> Configurações
        </a>
    @endif
</div>
