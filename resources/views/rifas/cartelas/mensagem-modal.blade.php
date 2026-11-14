@can('update', $rifa)
<div class="modal fade" id="rifa-message-modal" tabindex="-1" aria-labelledby="rifa-message-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 id="rifa-message-modal-title" class="modal-title">Qual mensagem deseja enviar?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancelar envio"></button></div>
        <div class="modal-body">
            <p id="rifa-message-send-summary"></p>
            <div class="border rounded p-3 mb-3">
                <div class="form-check"><input type="radio" name="rifa_message_choice" id="rifa-message-default" value="padrao" class="form-check-input" checked><label for="rifa-message-default" class="form-check-label"><strong>Mensagem padrão do sistema</strong></label></div>
                <p class="small text-muted mb-0 mt-2">Saudação com o nome do vendedor, identificação da rifa e das cartelas, valor por número e dados PIX cadastrados.</p>
            </div>
            <div class="border rounded p-3">
                <div class="form-check"><input type="radio" name="rifa_message_choice" id="rifa-message-saved" value="cadastrada" class="form-check-input" @disabled(!$mensagemCartelasCadastrada)><label for="rifa-message-saved" class="form-check-label"><strong>Mensagem cadastrada</strong></label></div>
                @if($mensagemCartelasCadastrada)
                    <div style="white-space:pre-wrap;overflow-wrap:anywhere;" class="mt-2">{{ $mensagemCartelasCadastrada }}</div>
                    <p class="small text-muted mt-2 mb-0">As variáveis serão preenchidas para cada vendedor.</p>
                @else
                    <p class="small text-muted mt-2 mb-0">Você ainda não cadastrou uma mensagem. Acesse <a href="{{ route('rifas.configuracao.edit') }}">Configuração</a> para cadastrá-la.</p>
                @endif
            </div>
            <p class="small text-muted mt-3 mb-0">O PDF das cartelas será anexado em qualquer uma das opções.</p>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancelar</button><button type="button" id="rifa-message-confirm" class="btn btn-success">Enviar cartelas</button></div>
    </div></div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var element = document.getElementById('rifa-message-modal');
    var modal = new bootstrap.Modal(element);
    var pending = null;
    var choice = null;
    window.rifaMensagemCartelas = {
        escolher: function (summary) {
            if (pending) return Promise.resolve(null);
            document.getElementById('rifa-message-default').checked = true;
            document.getElementById('rifa-message-send-summary').textContent = summary;
            choice = null;
            return new Promise(function (resolve) { pending = resolve; modal.show(); });
        }
    };
    document.getElementById('rifa-message-confirm').addEventListener('click', function () {
        var selected = element.querySelector('input[name="rifa_message_choice"]:checked');
        if (!selected || selected.disabled) return;
        choice = selected.value;
        modal.hide();
    });
    element.addEventListener('hidden.bs.modal', function () {
        var resolve = pending;
        pending = null;
        if (resolve) resolve(choice);
    });
    document.querySelectorAll('.js-rifa-cartela-whatsapp').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (form.dataset.enviando) return;
            var type = await window.rifaMensagemCartelas.escolher('Enviar esta cartela para o WhatsApp do vendedor responsável?');
            if (!type) return;
            form.querySelector('input[name="mensagem_tipo"]').value = type;
            form.dataset.enviando = '1';
            form.querySelector('button').disabled = true;
            form.submit();
        });
    });
});
</script>
@endpush
@endcan
