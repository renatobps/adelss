@php
    $r = $receipt ?? \App\Support\FinancialReceiptPresenter::from($transaction);
    $fundo = $fundoSrc ?? $fundoPath ?? \App\Support\FinancialReceiptLogo::backgroundHtmlSrc();
@endphp
<div class="recibo-page">
    @if(!empty($fundo))
        <img src="{{ $fundo }}" class="recibo-bg" alt="">
    @endif

    <div class="f f-numero">{{ $r['reciboNumero'] }}</div>
    <div class="f f-valor">R$ {{ $r['amount'] }}</div>
    <div class="f f-de">{{ $r['fromName'] }}</div>
    <div class="f f-extenso">{{ $r['amountLine'] }}</div>
    <div class="f f-categoria">{{ $r['categoryName'] }}</div>
    <div class="f f-cidade">{{ $r['city'] }}</div>
    <div class="f f-dia">{{ $r['day'] }}</div>
    <div class="f f-mes">{{ $r['month'] }}</div>
    <div class="f f-ano">{{ $r['year'] }}</div>
    <div class="f f-assinatura">
        @if(!empty($tesoureiroAssinaturaSrc) && (str_starts_with((string) $tesoureiroAssinaturaSrc, 'data:') || is_file((string) $tesoureiroAssinaturaSrc)))
            <img src="{{ $tesoureiroAssinaturaSrc }}" class="f-assinatura-img" alt="">
        @endif
    </div>
    <div class="f f-nome">{{ $tesoureiroNome ?? '' }}</div>
</div>
