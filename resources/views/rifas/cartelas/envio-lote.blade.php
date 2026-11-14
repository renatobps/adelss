@can('update', $rifa)
<section id="envio-cartelas" class="card mb-3">
    <header class="card-header"><h2 class="card-title">Enviar cartelas aos vendedores</h2></header>
    <div class="card-body">
        <p>Cada vendedor receberá um PDF somente com as suas cartelas, incluindo a chave PIX e o nome do recebedor cadastrados. O envio considera toda a rifa, inclusive cartelas de outras páginas.</p>
        <button type="button" id="rifa-send-all" class="btn btn-success" data-url="{{ route('rifas.cartelas.destinatarios', $rifa) }}" data-csrf="{{ csrf_token() }}" @disabled($vendedoresDistribuidos->isEmpty() || !$rifa->pix_chave || !$rifa->pix_recebedor)>Enviar cartelas para todos por WhatsApp</button>
        <p class="small text-muted mt-2">Mantenha esta página aberta até concluir. Vendedores sem telefone serão informados na lista. Um novo envio também incluirá as cartelas enviadas anteriormente.</p>
        @if(!$rifa->pix_chave || !$rifa->pix_recebedor)<p class="text-warning">Cadastre a chave PIX e o nome do recebedor em “Editar” para incluí-los nos arquivos.</p>@endif
        <div id="rifa-send-progress" hidden>
            <p id="rifa-send-status" role="status" aria-live="polite"></p>
            <progress id="rifa-send-meter" value="0" max="1" style="width:100%;"></progress>
            <ul id="rifa-send-results" class="mt-2" style="max-height:260px;overflow-y:auto;"></ul>
        </div>
    </div>
</section>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var button = document.getElementById('rifa-send-all');
    if (!button) return;
    var busy = false;
    var panel = document.getElementById('rifa-send-progress');
    var status = document.getElementById('rifa-send-status');
    var meter = document.getElementById('rifa-send-meter');
    var results = document.getElementById('rifa-send-results');
    window.addEventListener('beforeunload', function (event) {
        if (busy) { event.preventDefault(); event.returnValue = ''; }
    });
    button.addEventListener('click', async function () {
        if (busy) return;
        busy = true; button.disabled = true;
        panel.hidden = false; results.textContent = ''; status.textContent = 'Preparando a lista de vendedores…'; meter.value = 0;
        try {
            var response = await fetch(button.dataset.url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin', redirect: 'error'});
            var data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Não foi possível carregar os vendedores. Confira sua sessão e tente novamente.');
            var recipients = data.destinatarios;
            var eligible = recipients.filter(function (person) { return person.pode_enviar; });
            if (!eligible.length) throw new Error('Não há vendedores com cartelas atribuídas e telefone cadastrado para enviar.');
            if (!confirm('Enviar as cartelas para ' + eligible.length + ' vendedor(es)? Cartelas enviadas anteriormente também serão reenviadas.')) {
                status.textContent = 'Envio cancelado antes de iniciar.'; return;
            }
            var sent = 0, failed = 0, skipped = 0;
            meter.max = recipients.length;
            for (var i = 0; i < recipients.length; i++) {
                var person = recipients[i];
                var item = document.createElement('li');
                if (!person.pode_enviar) {
                    skipped++; item.textContent = person.nome + ': não enviado — vendedor indisponível ou sem telefone.';
                } else {
                    status.textContent = 'Enviando para ' + person.nome + ' (' + (i + 1) + '/' + recipients.length + ')…';
                    try {
                        var sendResponse = await fetch(person.url, {
                            method: 'POST', credentials: 'same-origin', redirect: 'error',
                            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': button.dataset.csrf}
                        });
                        var result = await sendResponse.json();
                        if (!sendResponse.ok || !result.success) throw new Error(result.message || 'Envio não confirmado.');
                        sent++; item.textContent = person.nome + ': enviado (' + person.cartelas + ' cartela(s)).';
                    } catch (error) {
                        failed++; item.textContent = person.nome + ': envio não confirmado. ' + error.message;
                    }
                }
                results.appendChild(item); meter.value = i + 1;
            }
            status.textContent = 'Concluído: ' + sent + ' enviado(s), ' + failed + ' não confirmado(s) e ' + skipped + ' sem destinatário válido. Confira os resultados antes de reenviar.';
        } catch (error) {
            status.textContent = error.message;
        } finally {
            busy = false; button.disabled = false;
        }
    });
});
</script>
@endpush
@endcan
