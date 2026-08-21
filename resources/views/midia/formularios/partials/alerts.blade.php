@foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $chave => $classe)
    @if(session($chave))
        <div class="alert alert-{{ $classe }} alert-dismissible fade show" role="alert">
            {{ session($chave) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
@endforeach

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif
