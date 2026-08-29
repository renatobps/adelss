<div class="signature-block">
    @if(!empty($tesoureiroAssinaturaSrc) && (str_starts_with((string) $tesoureiroAssinaturaSrc, 'data:') || is_file($tesoureiroAssinaturaSrc)))
        <img src="{{ $tesoureiroAssinaturaSrc }}" class="signature-img" alt="Assinatura do tesoureiro">
    @endif
    <div class="signature-rule"></div>
    <div class="signature-name">{{ !empty($tesoureiroNome) ? $tesoureiroNome : '1º Tesoureiro(a)' }}</div>
    <div class="signature-role">1º Tesoureiro(a)</div>
</div>
