<div class="bloco">
    <div class="bloco-titulo">{{ $titulo }}</div>
    <div class="bloco-conteudo">
        @if(isset($itens) && is_array($itens) && count($itens) > 0)
            @foreach($itens as $item)
                @if($item && trim($item) !== '')
                    <div class="nome">{{ $item }}</div>
                @endif
            @endforeach
        @elseif(isset($itens) && $itens && trim($itens) !== '')
            <div class="nome">{{ $itens }}</div>
        @endif
    </div>
</div>
