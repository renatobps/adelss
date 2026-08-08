<script>
document.addEventListener('DOMContentLoaded', function () {
    var listing = document.getElementById('erListing');
    if (!listing) return;

    var esc = function (value) {
        var div = document.createElement('div');
        div.textContent = value === null || value === undefined || value === '' ? '—' : value;
        return div.innerHTML;
    };

    // json_encode escapa acentos (\u00e9), então o base64 é sempre ASCII e o atob basta.
    var payloadOf = function (el) {
        var holder = el.closest('[data-registration]');
        if (!holder) return null;
        try {
            return JSON.parse(atob(holder.getAttribute('data-registration')));
        } catch (e) {
            return null;
        }
    };

    var modal = function (id) {
        var el = document.getElementById(id);
        return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
    };

    // ---- Detalhes ------------------------------------------------------
    var openDetails = function (data) {
        var linhas = [
            ['Inscrição', data.numero],
            ['Nome', data.nome],
            ['Status', data.status_label],
            ['E-mail', data.email],
            ['Telefone', data.telefone],
            ['Endereço', data.endereco],
            ['Inscrito em', data.criada_em],
            ['Check-in', data.checkin ? data.checkin + (data.checkin_por ? ' (por ' + data.checkin_por + ')' : '') : 'Sem check-in'],
            ['Comprovante', data.comprovante ? 'Enviado em ' + data.comprovante : 'Não enviado']
        ];
        if (data.pagamento) {
            linhas.push(['Pagamento', data.pagamento]);
            linhas.push(['Valor', data.valor]);
        }
        if (data.excluida_em) {
            linhas.push(['Excluída em', data.excluida_em]);
        }

        var html = '<dl class="row mb-0 small">';
        linhas.forEach(function (linha) {
            html += '<dt class="col-5 text-muted fw-normal">' + esc(linha[0]) + '</dt>'
                + '<dd class="col-7">' + esc(linha[1]) + '</dd>';
        });
        html += '</dl>';

        if (data.respostas && data.respostas.length) {
            html += '<hr><h6 class="small text-uppercase text-muted">Respostas do formulário</h6><dl class="row mb-0 small">';
            data.respostas.forEach(function (item) {
                html += '<dt class="col-5 text-muted fw-normal">' + esc(item.campo) + '</dt>'
                    + '<dd class="col-7">' + esc(item.resposta) + '</dd>';
            });
            html += '</dl>';
        }

        document.getElementById('erDetailsBody').innerHTML = html;
        document.getElementById('erDetailsPdf').href = data.urls.pdf;
        modal('erDetailsModal').show();
    };

    // ---- Edição --------------------------------------------------------
    var openEdit = function (data) {
        var form = document.getElementById('erEditForm');
        if (!form) return;
        form.action = data.urls.atualizar;
        document.getElementById('erEditNumber').textContent = data.numero;
        document.getElementById('erEditName').value = data.nome || '';
        document.getElementById('erEditEmail').value = data.email || '';
        document.getElementById('erEditPhone').value = data.telefone || '';
        document.getElementById('erEditAddress').value = data.endereco || '';
        modal('erEditModal').show();
    };

    // ---- Status --------------------------------------------------------
    var STATUS_LABELS = { pendente: 'Pendente', confirmado: 'Confirmado', cancelado: 'Cancelado' };

    var openStatus = function (data, novoStatus) {
        var form = document.getElementById('erStatusForm');
        if (!form) return;
        form.action = data.urls.status;
        document.getElementById('erStatusValue').value = novoStatus;
        document.getElementById('erStatusText').textContent =
            'Alterar ' + data.nome + ' (' + data.numero + ') para "' + (STATUS_LABELS[novoStatus] || novoStatus) + '"?';
        document.getElementById('erStatusHint').textContent =
            novoStatus === 'confirmado'
                ? 'Ao confirmar, o comprovante é enviado automaticamente por WhatsApp.'
                : (novoStatus === 'cancelado'
                    ? 'A inscrição continua no histórico com status cancelado.'
                    : '');
        modal('erStatusModal').show();
    };

    // ---- Exclusão ------------------------------------------------------
    var openDelete = function (data) {
        var form = document.getElementById('erDeleteForm');
        if (!form) return;
        form.action = data.urls.excluir;

        document.getElementById('erDeleteSummary').innerHTML =
            '<div class="fw-semibold">' + esc(data.nome) + '</div>'
            + '<div class="small text-muted">' + esc(data.email) + '</div>'
            + '<div class="small text-muted">Inscrição ' + esc(data.numero) + '</div>';

        var aviso = document.getElementById('erDeleteWarning');
        var botao = document.getElementById('erDeleteConfirm');
        if (data.pagamento_confirmado) {
            aviso.className = 'alert alert-danger mb-0 small';
            aviso.innerHTML = 'Esta inscrição tem <strong>pagamento confirmado</strong> e não pode ser excluída. '
                + 'Cancele a inscrição (com o devido estorno) para preservar a conciliação financeira do evento.';
            botao.disabled = true;
        } else {
            aviso.className = 'alert alert-warning mb-0 small';
            aviso.innerHTML = 'A inscrição sai da lista mas continua recuperável pelo filtro <strong>Excluídas</strong>. '
                + 'Se a pessoa se inscreveu e desistiu, prefira <strong>cancelar</strong> — o histórico é preservado.';
            botao.disabled = false;
        }

        modal('erDeleteModal').show();
    };

    listing.addEventListener('click', function (event) {
        var trigger = event.target.closest('.js-er-action');
        if (!trigger) return;

        var data = payloadOf(trigger);
        if (!data) return;

        switch (trigger.getAttribute('data-er-action')) {
            case 'detalhes': openDetails(data); break;
            case 'editar': openEdit(data); break;
            case 'status': openStatus(data, trigger.getAttribute('data-er-status')); break;
            case 'excluir': openDelete(data); break;
        }
    });

    // ---- Seleção múltipla ---------------------------------------------
    var bulkBar = document.getElementById('erBulkBar');
    var bulkForm = document.getElementById('erBulkForm');

    var selectedRows = function () {
        return Array.prototype.filter.call(
            listing.querySelectorAll('.js-er-check'),
            function (input) { return input.checked; }
        );
    };

    // Tabela e cards renderizam a mesma inscrição: sem deduplicar, o lote receberia ids repetidos.
    var selectedIds = function () {
        var ids = [];
        selectedRows().forEach(function (input) {
            if (ids.indexOf(input.value) === -1) ids.push(input.value);
        });
        return ids;
    };

    var refreshBulkBar = function () {
        if (!bulkBar) return;
        var total = selectedIds().length;
        document.getElementById('erBulkCount').textContent = total;
        bulkBar.classList.toggle('is-visible', total > 0);
    };

    listing.addEventListener('change', function (event) {
        var input = event.target;

        if (input.classList.contains('js-er-check-all')) {
            listing.querySelectorAll('.js-er-check').forEach(function (check) {
                check.checked = input.checked;
            });
            refreshBulkBar();
            return;
        }

        if (input.classList.contains('js-er-check')) {
            // A mesma inscrição aparece na tabela e no card: os dois espelhos andam juntos.
            listing.querySelectorAll('.js-er-check[value="' + input.value + '"]').forEach(function (check) {
                check.checked = input.checked;
            });
            refreshBulkBar();
        }
    });

    var submitBulk = function (acao) {
        var ids = selectedIds();
        if (!ids.length || !bulkForm) return;

        document.getElementById('erBulkAcao').value = acao;
        var container = document.getElementById('erBulkIds');
        container.innerHTML = '';
        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            container.appendChild(input);
        });
        bulkForm.submit();
    };

    if (bulkBar) {
        bulkBar.addEventListener('click', function (event) {
            var button = event.target.closest('[data-er-bulk]');
            if (button) {
                var acao = button.getAttribute('data-er-bulk');

                if (acao === 'excluir') {
                    var nomes = [];
                    selectedRows().forEach(function (input) {
                        var data = payloadOf(input);
                        if (data && nomes.indexOf(data.nome) === -1) nomes.push(data.nome);
                    });

                    document.getElementById('erBulkDeleteCount').textContent =
                        selectedIds().length + ' inscrição(ões) serão excluídas. Inscrições com pagamento confirmado são ignoradas.';
                    document.getElementById('erBulkDeleteList').innerHTML =
                        nomes.map(function (nome) { return '<li>' + esc(nome) + '</li>'; }).join('');
                    modal('erBulkDeleteModal').show();
                    return;
                }

                if (acao === 'comprovante' && !confirm('Os envios são espaçados para proteger o número contra banimento — o lote pode levar alguns minutos. Continuar?')) {
                    return;
                }

                submitBulk(acao);
                return;
            }

            if (event.target.closest('#erBulkClear')) {
                listing.querySelectorAll('.js-er-check, .js-er-check-all').forEach(function (check) {
                    check.checked = false;
                });
                refreshBulkBar();
            }
        });
    }

    var bulkDeleteConfirm = document.getElementById('erBulkDeleteConfirm');
    if (bulkDeleteConfirm) {
        bulkDeleteConfirm.addEventListener('click', function () {
            submitBulk('excluir');
        });
    }

    // ---- Máscara de telefone na edição ---------------------------------
    document.querySelectorAll('.js-er-phone-mask').forEach(function (input) {
        var format = function (value) {
            var digits = (value || '').replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 2) return digits.length ? '(' + digits : '';
            if (digits.length <= 7) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
            return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7);
        };
        input.addEventListener('input', function () {
            input.value = format(input.value);
        });
    });

    // ---- Copiar código PIX ---------------------------------------------
    document.querySelectorAll('.js-copy-pix-code').forEach(function (button) {
        button.addEventListener('click', async function () {
            var field = document.querySelector(button.getAttribute('data-target'));
            if (!field) return;
            try {
                await navigator.clipboard.writeText(field.value || '');
            } catch (e) {
                field.select();
                document.execCommand('copy');
            }
            button.textContent = 'Código copiado!';
            setTimeout(function () { button.textContent = 'Copiar código PIX'; }, 1500);
        });
    });
});
</script>
