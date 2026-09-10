@php
    $isPrebenda = ! empty($recibo['is_prebenda']);
    $assinanteNome = $isPrebenda ? ($pastorNome ?? '') : ($tesoureiroNome ?? '');
    $assinanteImagem = $isPrebenda ? ($pastorAssinaturaSrc ?? null) : ($tesoureiroAssinaturaSrc ?? null);
@endphp
<div class="recibo-slot">
    @include('financial.transactions.partials.receipt-overlay', [
        'receipt' => $recibo['receipt'],
        'fundoSrc' => $reciboFundoSrc ?: false,
        'signerName' => $assinanteNome,
        'signerSignatureSrc' => $assinanteImagem,
        'signerCpfRg' => '',
        'signerAddress' => '',
    ])
</div>
