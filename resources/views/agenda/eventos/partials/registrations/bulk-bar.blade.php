<form method="post" action="{{ route('agenda.eventos.registrations.bulk', $event) }}" id="erBulkForm" class="d-none">
    @csrf
    <input type="hidden" name="acao" id="erBulkAcao">
    <div id="erBulkIds"></div>
</form>

<div class="er-bulkbar" id="erBulkBar" role="region" aria-label="Ações em lote">
    <span class="er-bulkbar-count"><span id="erBulkCount">0</span> selecionada(s)</span>
    <button type="button" class="btn btn-light" data-er-bulk="confirmar">
        <i class="bx bx-check"></i> Confirmar
    </button>
    <button type="button" class="btn btn-light" data-er-bulk="comprovante">
        <i class="bx bxl-whatsapp"></i> Enviar comprovante
    </button>
    <button type="button" class="btn btn-light" data-er-bulk="exportar">
        <i class="bx bx-download"></i> Exportar
    </button>
    @if($canDeleteRegistrations)
        <button type="button" class="btn btn-danger" data-er-bulk="excluir">
            <i class="bx bx-trash"></i> Excluir
        </button>
    @endif
    <button type="button" class="btn btn-link text-white text-decoration-none" id="erBulkClear">Limpar</button>
</div>
