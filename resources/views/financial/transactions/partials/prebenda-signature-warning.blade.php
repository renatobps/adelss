@if(!empty($pastorSignatureMissing))
    <div class="mt-2 text-danger">
        A imagem da assinatura do pastor não está cadastrada — o recibo sai com a linha em branco.
        <a href="{{ route('financial.reports.signatures') }}" target="_blank" rel="noopener">Cadastrar assinatura</a>.
    </div>
@endif
