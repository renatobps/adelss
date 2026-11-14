@if($rifa->pix_chave && $rifa->pix_recebedor)
    <div style="border:1px solid #b9c5d2;border-radius:6px;padding:12px;margin:12px 0;word-wrap:break-word;">
        <strong>Pagamento por PIX</strong><br>
        Recebedor: {{ $rifa->pix_recebedor }}<br>
        Chave PIX: <span style="word-break:break-all;">{{ $rifa->pix_chave }}</span>
    </div>
@endif
