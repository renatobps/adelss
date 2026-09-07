@php
    $r = $receipt ?? \App\Support\FinancialReceiptPresenter::from($transaction);
@endphp

<table class="id-bar">
    <tr>
        <td class="id-title">RECIBO</td>
        <td class="id-num">Nº <span class="box">{{ $r['reciboNumero'] }}</span></td>
        <td class="id-val">VALOR <span class="box">R$ {{ $r['amount'] }}</span></td>
    </tr>
</table>

<table class="fields">
    <tr>
        <td class="lbl">Recebi(emos) de:</td>
        <td class="val"><span class="fill">{{ $r['fromName'] }}</span></td>
    </tr>
    <tr>
        <td class="lbl">a quantia de:</td>
        <td class="val">
            <div class="amount-words">
                R$ {{ $r['amount'] }} ({{ $r['amountExtenso'] }})
            </div>
        </td>
    </tr>
    <tr>
        <td class="lbl">Correspondente a:</td>
        <td class="val"><span class="fill">{{ $r['categoryName'] }}</span></td>
    </tr>
</table>

<p class="clarity">
    e para clareza firmo(amos) o presente na cidade de <strong>{{ $r['city'] }}</strong>, {{ $r['dateLine'] }}.
</p>

<table class="fields">
    <tr>
        <td class="lbl">Assinatura:</td>
        <td class="val">
            @if(!empty($tesoureiroAssinaturaSrc) && (str_starts_with((string) $tesoureiroAssinaturaSrc, 'data:') || is_file($tesoureiroAssinaturaSrc)))
                <img src="{{ $tesoureiroAssinaturaSrc }}" class="signature-img" alt="">
            @endif
            <span class="fill fill--sign"></span>
        </td>
    </tr>
    <tr>
        <td class="lbl">Nome:</td>
        <td class="val">
            <table class="split">
                <tr>
                    <td class="split-name">
                        <span class="fill">{{ $tesoureiroNome ?? '' }}</span>
                    </td>
                    <td class="split-cpf">
                        <span class="lbl-inline">CPF/RG:</span>
                        <span class="fill">{{ $r['memberCpfRg'] }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="lbl">Endereço:</td>
        <td class="val"><span class="fill">{{ $r['memberAddress'] }}</span></td>
    </tr>
</table>

<p class="signer-role">1º Tesoureiro(a)</p>
