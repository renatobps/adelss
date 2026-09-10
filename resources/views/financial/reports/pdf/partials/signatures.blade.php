<style>
    .assinaturas { width: 100%; margin-top: 40px; border-collapse: collapse; }
    .assinaturas td { width: 50%; text-align: center; padding: 0 24px; vertical-align: bottom; }
    .assinaturas .nome { font-weight: bold; font-size: 11px; padding-top: 4px; color: #1e3a5f; }
    .assinaturas .cargo { font-size: 9px; color: #6b7c93; }
    .assinaturas .linha { border-top: 1px solid #1f2937; margin: 48px auto 6px; width: 220px; }
    .assinaturas .assinatura-img { max-height: 56px; max-width: 200px; display: block; margin: 0 auto 4px; }
    .assinatura-bloco { width: 100%; border-collapse: collapse; }
</style>
<table class="assinaturas">
    <tr>
        <td>
            <table class="assinatura-bloco">
                <tr>
                    <td>
                        @if(!empty($pastorAssinaturaSrc))
                            <img src="{{ $pastorAssinaturaSrc }}" class="assinatura-img" alt="Assinatura do pastor dirigente">
                        @else
                            <div class="linha"></div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="nome">{{ !empty($pastorNome) ? $pastorNome : 'Nome e assinatura' }}</td>
                </tr>
                <tr>
                    <td class="cargo">Pastor Dirigente</td>
                </tr>
            </table>
        </td>
        <td>
            <table class="assinatura-bloco">
                <tr>
                    <td>
                        @if(!empty($tesoureiroAssinaturaSrc))
                            <img src="{{ $tesoureiroAssinaturaSrc }}" class="assinatura-img" alt="Assinatura do tesoureiro">
                        @else
                            <div class="linha"></div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="nome">{{ !empty($tesoureiroNome) ? $tesoureiroNome : 'Nome e assinatura' }}</td>
                </tr>
                <tr>
                    <td class="cargo">1º Tesoureiro(a)</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
