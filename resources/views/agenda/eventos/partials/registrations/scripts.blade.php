<script>
document.addEventListener('DOMContentLoaded', function () {
    var RAFFLE_ENTRIES = @json($raffleEntries->values());

    (function initRaffle() {
        var openBtn = document.getElementById('erRaffleOpen');
        var startBtn = document.getElementById('erRaffleStart');
        var nameEl = document.getElementById('erRaffleName');
        var stage = document.getElementById('erRaffleStage');
        var sub = document.getElementById('erRaffleSub');
        var hint = document.getElementById('erRaffleHint');
        var modalEl = document.getElementById('erRaffleModal');
        var scopeAll = document.getElementById('erRaffleScopeAll');
        var scopePresent = document.getElementById('erRaffleScopePresent');
        if (!openBtn || !modalEl || !nameEl || !startBtn) return;

        var running = false;
        var timer = null;

        var namesOf = function (entries) {
            return entries.map(function (item) { return item.nome; });
        };

        var pool = function () {
            var presentOnly = scopePresent && scopePresent.checked;
            if (!presentOnly) return namesOf(RAFFLE_ENTRIES);
            return namesOf(RAFFLE_ENTRIES.filter(function (item) { return item.presente; }));
        };

        var pickIndex = function (max) {
            if (max <= 1) return 0;
            var buf = new Uint32Array(1);
            crypto.getRandomValues(buf);
            return buf[0] % max;
        };

        var setName = function (text) {
            nameEl.textContent = text;
        };

        var refreshHint = function () {
            var names = pool();
            var presentOnly = scopePresent && scopePresent.checked;
            if (hint) {
                hint.textContent = presentOnly
                    ? (names.length
                        ? 'Sorteio entre ' + names.length + ' presente(s) com check-in.'
                        : 'Ninguém fez check-in ainda. Use “Todos” ou registre a presença.')
                    : 'Sorteio entre ' + names.length + ' inscrito(s) ativos (pendentes e confirmados).';
            }
            startBtn.disabled = running || names.length === 0;
        };

        var stop = function () {
            running = false;
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
            refreshHint();
        };

        var openRaffle = function () {
            if (!RAFFLE_ENTRIES.length) return;
            stop();
            stage.classList.remove('is-spinning', 'is-winner');
            setName('Pronto para sortear');
            sub.textContent = 'O nome do ganhador aparece após a animação.';
            refreshHint();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        var run = function (event) {
            if (event) event.stopPropagation();
            var names = pool();
            if (running || !names.length) return;
            running = true;
            startBtn.disabled = true;
            stage.classList.remove('is-winner');
            stage.classList.add('is-spinning');
            sub.textContent = 'Sorteando...';

            var winner = names[pickIndex(names.length)];
            var started = Date.now();
            var duration = 2800;
            var delay = 50;

            var tick = function () {
                var elapsed = Date.now() - started;
                if (elapsed >= duration) {
                    stage.classList.remove('is-spinning');
                    stage.classList.add('is-winner');
                    setName(winner);
                    sub.textContent = 'Ganhador(a) do sorteio';
                    stop();
                    return;
                }
                setName(names[pickIndex(names.length)]);
                delay = Math.min(220, 50 + Math.floor(elapsed / 18));
                timer = setTimeout(tick, delay);
            };

            tick();
        };

        openBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openRaffle();
        });

        startBtn.addEventListener('click', run);
        scopeAll?.addEventListener('change', refreshHint);
        scopePresent?.addEventListener('change', refreshHint);

        modalEl.addEventListener('hidden.bs.modal', function () {
            stop();
            stage.classList.remove('is-spinning', 'is-winner');
        });

        if (window.location.hash === '#sorteio' && RAFFLE_ENTRIES.length) {
            history.replaceState(null, '', window.location.pathname + window.location.search);
            openRaffle();
        }
    })();

    var listing = document.getElementById('erListing');
    if (!listing) return;

    var openWhatsappModal = null;

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
            case 'whatsapp':
                if (openWhatsappModal) openWhatsappModal([data.id]);
                break;
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

                if (acao === 'whatsapp') {
                    if (openWhatsappModal) openWhatsappModal(selectedIds().map(Number));
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
            setTimeout(function () {             button.textContent = 'Copiar código PIX'; }, 1500);
        });
    });

    // ---- WhatsApp livre ------------------------------------------------
    var WHATSAPP_CONTACTS = @json($whatsappContacts ?? []);
    var WHATSAPP_OLD_IDS = @json(array_values(array_map('intval', (array) old('ids', []))));
    var AUTO_OPEN_WHATSAPP = {{ request()->boolean('whatsapp') || old('ids') ? 'true' : 'false' }};

    (function initWhatsappModal() {
        var form = document.getElementById('erWhatsappForm');
        var listEl = document.getElementById('erWhatsappList');
        var countEl = document.getElementById('erWhatsappCount');
        var idsEl = document.getElementById('erWhatsappIds');
        var searchEl = document.getElementById('erWhatsappSearch');
        var submitBtn = document.getElementById('erWhatsappSubmit');
        if (!form || !listEl) return;

        var renderList = function () {
            listEl.innerHTML = WHATSAPP_CONTACTS.map(function (c) {
                return '<label class="er-whats-item" data-label="' + esc((c.nome + ' ' + c.telefone).toLowerCase()) + '">'
                    + '<input type="checkbox" class="form-check-input mt-1 js-er-wa-check" value="' + c.id + '">'
                    + '<span><span class="er-name">' + esc(c.nome) + '</span>'
                    + '<span class="er-muted d-block">' + esc(c.numero) + ' · ' + esc(c.telefone)
                    + (c.cancelado ? ' · cancelado' : '') + '</span></span></label>';
            }).join('') || '<p class="text-muted small p-3 mb-0">Nenhum inscrito com telefone cadastrado.</p>';
        };

        var checkedWa = function () {
            return Array.prototype.filter.call(
                listEl.querySelectorAll('.js-er-wa-check'),
                function (input) { return input.checked; }
            );
        };

        var refreshCount = function () {
            var n = checkedWa().length;
            countEl.textContent = '(' + n + ')';
            submitBtn.disabled = n === 0;
        };

        var setChecked = function (ids) {
            var set = {};
            (ids || []).forEach(function (id) { set[String(id)] = true; });
            listEl.querySelectorAll('.js-er-wa-check').forEach(function (input) {
                input.checked = !!set[input.value];
            });
            refreshCount();
        };

        renderList();
        listEl.addEventListener('change', refreshCount);

        document.getElementById('erWhatsappSelectAll')?.addEventListener('click', function () {
            listEl.querySelectorAll('.js-er-wa-check').forEach(function (input) {
                var row = input.closest('.er-whats-item');
                if (row && row.classList.contains('is-hidden')) return;
                input.checked = true;
            });
            refreshCount();
        });

        document.getElementById('erWhatsappClear')?.addEventListener('click', function () {
            listEl.querySelectorAll('.js-er-wa-check').forEach(function (input) { input.checked = false; });
            refreshCount();
        });

        searchEl?.addEventListener('input', function () {
            var q = (searchEl.value || '').trim().toLowerCase();
            listEl.querySelectorAll('.er-whats-item').forEach(function (row) {
                var hay = row.getAttribute('data-label') || '';
                row.classList.toggle('is-hidden', q !== '' && hay.indexOf(q) === -1);
            });
        });

        openWhatsappModal = function (preselectedIds) {
            var ids = (preselectedIds || []).filter(function (id) {
                return WHATSAPP_CONTACTS.some(function (c) { return Number(c.id) === Number(id); });
            });
            setChecked(ids);
            if (searchEl) searchEl.value = '';
            listEl.querySelectorAll('.er-whats-item').forEach(function (row) {
                row.classList.remove('is-hidden');
            });
            modal('erWhatsappModal').show();
        };

        document.getElementById('erWhatsappOpen')?.addEventListener('click', function () {
            var selected = selectedIds().map(Number);
            openWhatsappModal(selected.length ? selected : []);
        });

        form.addEventListener('submit', function (event) {
            var ids = checkedWa().map(function (input) { return input.value; });
            if (!ids.length) {
                event.preventDefault();
                return;
            }
            var mensagem = (document.getElementById('erWhatsappMensagem')?.value || '').trim();
            var arquivo = document.getElementById('erWhatsappArquivo')?.files?.[0];
            if (!mensagem && !arquivo) {
                event.preventDefault();
                alert('Informe uma mensagem ou anexe um arquivo.');
                return;
            }
            if (ids.length > 1 && !confirm('Os envios serão feitos em segundo plano, com intervalo para proteger o número. Sucessos e erros aparecem em Notificações. Continuar?')) {
                event.preventDefault();
                return;
            }
            idsEl.innerHTML = '';
            ids.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                idsEl.appendChild(input);
            });
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Iniciando...';
        });

        var drop = document.getElementById('erDropzone');
        var fileInput = document.getElementById('erWhatsappArquivo');
        var thumb = document.getElementById('erFileThumb');
        var nameEl = document.getElementById('erFileName');
        var metaEl = document.getElementById('erFileMeta');
        var objectUrl = null;

        var fileIcon = function (file) {
            if (file.type.startsWith('image/')) return null;
            if (file.type.startsWith('video/')) return 'bx-video';
            if (file.type.startsWith('audio/')) return 'bx-music';
            if (file.type.indexOf('pdf') !== -1) return 'bx-file-blank';
            return 'bx-file';
        };

        var showFile = function (file) {
            if (!file || !drop) return;
            drop.classList.add('has-file');
            nameEl.textContent = file.name;
            metaEl.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB · ' + (file.type || 'arquivo');
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            var icon = fileIcon(file);
            if (!icon) {
                objectUrl = URL.createObjectURL(file);
                thumb.innerHTML = '<img alt="Preview">';
                thumb.querySelector('img').src = objectUrl;
            } else {
                thumb.innerHTML = '<i class="bx ' + icon + '"></i>';
            }
        };

        var clearFile = function () {
            if (!drop || !fileInput) return;
            fileInput.value = '';
            drop.classList.remove('has-file');
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            thumb.innerHTML = '<i class="bx bx-file"></i>';
            nameEl.textContent = '—';
            metaEl.textContent = '—';
        };

        drop?.addEventListener('click', function (e) {
            if (e.target.closest('#erFileClear')) return;
            fileInput?.click();
        });
        document.getElementById('erFileClear')?.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            clearFile();
        });
        fileInput?.addEventListener('change', function () {
            if (fileInput.files[0]) showFile(fileInput.files[0]);
            else clearFile();
        });
        ['dragenter', 'dragover'].forEach(function (ev) {
            drop?.addEventListener(ev, function (e) {
                e.preventDefault();
                drop.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            drop?.addEventListener(ev, function (e) {
                e.preventDefault();
                drop.classList.remove('is-dragover');
            });
        });
        drop?.addEventListener('drop', function (e) {
            var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
            if (!file || !fileInput) return;
            var dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showFile(file);
        });

        refreshCount();

        if (AUTO_OPEN_WHATSAPP) {
            openWhatsappModal(WHATSAPP_OLD_IDS.length ? WHATSAPP_OLD_IDS : selectedIds().map(Number));
        }
    })();
});
</script>
