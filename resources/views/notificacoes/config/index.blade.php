@extends('layouts.porto')

@section('title', 'Configuração WPP - Notificações')
@section('page-title')
    <i class="bx bxl-whatsapp me-2"></i> Configuração do WhatsApp
@endsection
@section('breadcrumbs')
    <li><a href="{{ route('notificacoes.config.index') }}">Notificações</a></li>
    <li><span>Configuração WPP</span></li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Status da Conexão -->
    <div class="col-md-6 mb-4">
        <section class="card">
            <header class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bx bx-signal me-2"></i> Status da Conexão</h5>
            </header>
            <div class="card-body">
                <div id="connection-status">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Verificando conexão...</p>
                    </div>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="verificarStatus()">
                        <i class="bx bx-refresh me-1"></i> Verificar Status
                    </button>
                </div>
            </div>
        </section>
    </div>

    <!-- Instâncias -->
    <div class="col-md-6 mb-4">
        <section class="card">
            <header class="card-header">
                <h5 class="mb-0"><i class="bx bx-hdd me-2"></i> Instâncias</h5>
            </header>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Instância</th>
                                <th>Uso</th>
                                <th>Dono</th>
                                <th>Status</th>
                                <th class="text-end" width="230">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="instances-tbody">
                            <tr><td colspan="5" class="text-center text-muted py-3">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Configurações da API -->
<div class="row">
    <div class="col-12 mb-4">
        <section class="card">
            <header class="card-header">
                <h5 class="mb-0"><i class="bx bx-cog me-2"></i> Configurações da API</h5>
            </header>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>URL da API:</strong><br><code>{{ config('whatsapp.api_url') ?: '—' }}</code></p>
                    </div>
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>Tipo de Autenticação:</strong><br><span class="badge bg-success">Evolution GO (apikey + instanceId)</span></p>
                    </div>
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>Endpoint de Envio:</strong><br><code>POST /send/text</code></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>API Docs:</strong><br>
                            <a href="{{ rtrim(config('whatsapp.api_url'), '/') }}/swagger/index.html" target="_blank" rel="noopener"><i class="bx bx-link-external me-1"></i> Swagger Evolution GO</a>
                        </p>
                    </div>
                    <div class="col-md-4 mb-2">
                        <p class="mb-0">
                            <strong>Instância em uso:</strong><br>
                            <code id="instancia-em-uso">{{ $instanciaSelecionada ?: 'Não selecionada' }}</code>
                        </p>
                    </div>
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>API Key:</strong><br><code>{{ config('whatsapp.api_key') ? (substr(config('whatsapp.api_key'), 0, 10) . '...') : 'Não configurado' }}</code></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>Instância padrão (.env):</strong><br><code>{{ $instanciaPadrao ?: 'Não configurada' }}</code></p>
                    </div>
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>Instance ID:</strong><br><code id="instancia-id">{{ $instanciaId ?: '—' }}</code></p>
                    </div>
                </div>
                <div class="alert alert-success mt-3 mb-0">
                    <strong><i class="bx bx-info-circle me-1"></i> Configuração Evolution GO:</strong><br>
                    <small>
                        A Evolution GO usa <strong>apikey</strong> e <strong>instanceId</strong> (UUID) nos headers — sem nome da instância no path.
                        Selecione a instância ativa na tabela acima; o sistema resolve o UUID via <code>GET /instance/all</code>.
                        @if(config('whatsapp.api_key'))
                            <strong>API Key:</strong> {{ substr(config('whatsapp.api_key'), 0, 10) }}...
                        @endif
                    </small>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Configuração de Webhooks -->
<div class="row">
    <div class="col-12 mb-4">
        <section class="card">
            <header class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bx bx-link me-2"></i> Configuração de Webhooks (Evolution GO)</h5>
            </header>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong><i class="bx bx-info-circle me-1"></i> Sobre os Webhooks:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Mensagens recebidas:</strong> respostas de enquetes (clique em botão) e textos — evento <code>Message</code></li>
                        <li><strong>Confirmações de envio:</strong> opcional — evento <code>SendMessage</code> (<code>SEND_MESSAGE</code>)</li>
                        <li><strong>Local (Ultrahook):</strong> <code>ultrahook webhook http://127.0.0.1:8000/webhook</code> e use a URL do Ultrahook no campo abaixo</li>
                        <li><strong>Produção:</strong> use a URL pública do sistema, ex.: <code>{{ url('/webhook') }}</code> (sem Ultrahook)</li>
                        <li>O Laravel recebe em <code>POST /webhook</code></li>
                    </ul>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-inbox me-1"></i> Webhook — Mensagens Recebidas</label>
                        <div class="input-group">
                            <input type="url" class="form-control" id="webhook_received_url"
                                   placeholder="{{ config('app.env') === 'local' ? 'https://seu-alias-webhook.ultrahook.com' : url('/webhook') }}"
                                   value="{{ config('whatsapp.webhook_url') ?: url('/webhook') }}">
                            <button type="button" class="btn btn-primary" onclick="configurarWebhookReceived()"><i class="bx bx-save me-1"></i> Configurar</button>
                        </div>
                        <small class="text-muted">Evolution GO → <code>POST /instance/connect</code> (header <code>instanceId</code>)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-check-circle me-1"></i> Webhook — Confirmações (opcional)</label>
                        <div class="input-group">
                            <input type="url" class="form-control" id="webhook_delivery_url"
                                   placeholder="https://seudominio.com/webhook/delivery"
                                   value="{{ url('/webhook/delivery') }}">
                            <button type="button" class="btn btn-outline-primary" onclick="configurarWebhookDelivery()"><i class="bx bx-save me-1"></i> Configurar</button>
                        </div>
                        <small class="text-muted">Opcional — só se quiser rastrear status de envio</small>
                    </div>
                </div>
                <div id="webhook-result" class="mt-3"></div>
            </div>
        </section>
    </div>
</div>

<!-- Enviar Mensagem de Teste -->
<div class="row">
    <div class="col-12 mb-4">
        <section class="card">
            <header class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bx bx-send me-2"></i> Enviar Mensagem de Teste</h5>
            </header>
            <div class="card-body">
                <form id="teste-form" onsubmit="enviarTeste(event)">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Número (com DDD)</label>
                            <input type="text" class="form-control" id="teste_numero" placeholder="11999999999" required>
                            <small class="text-muted">Apenas números</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mensagem</label>
                            <input type="text" class="form-control" id="teste_mensagem" value="Teste do Sistema ADELSS! 👋" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-warning w-100"><i class="bx bx-send me-1"></i> Enviar</button>
                        </div>
                    </div>
                </form>
                <hr>
                <h6 class="mb-3"><i class="bx bx-building me-1"></i> Enviar para Departamento</h6>
                <form id="teste-departamento-form" onsubmit="enviarTesteDepartamento(event)">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Departamento</label>
                            <select id="teste_departamento" class="form-select" required>
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mensagem</label>
                            <input type="text" id="teste_mensagem_grupo" class="form-control" value="Teste em grupo! 👋" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100"><i class="bx bx-send me-1"></i> Enviar</button>
                        </div>
                    </div>
                </form>
                <div id="teste-result" class="mt-3"></div>
            </div>
        </section>
    </div>
</div>

<!-- Últimas Atividades -->
<div class="row">
    <div class="col-12">
        <section class="card">
            <header class="card-header">
                <h5 class="mb-0"><i class="bx bx-file me-2"></i> Últimas Atividades</h5>
            </header>
            <div class="card-body">
                <div id="activity-log">
                    <p class="text-muted text-center mb-0">Nenhuma atividade registrada ainda</p>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var base = '{{ url("notificacoes") }}';
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var instanciaAtiva = @json($instanciaSelecionada ?? '');

    function addLog(message, type) {
        var now = new Date().toLocaleTimeString('pt-BR');
        var badgeClass = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-warning');
        var log = '<div class="d-flex justify-content-between align-items-center border-bottom py-2"><span><span class="badge ' + badgeClass + '">' + now + '</span> ' + message + '</span></div>';
        var el = document.getElementById('activity-log');
        if (el.querySelector('.text-muted')) el.innerHTML = '';
        el.insertAdjacentHTML('afterbegin', log);
        var divs = el.querySelectorAll('div');
        for (var i = 10; i < divs.length; i++) divs[i].remove();
    }

    window.verificarStatus = function() {
        document.getElementById('connection-status').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Verificando...</p></div>';
        fetch(base + '/config/status')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var data = res.data || {};
                var state = (data.state || '').toLowerCase();
                var open = state === 'open' || state === 'connected' || state === 'conectado';
                document.getElementById('connection-status').innerHTML =
                    open ? '<div class="alert alert-success mb-0"><i class="bx bx-check-circle me-2"></i><strong>Conectado!</strong><br>WhatsApp está online e pronto para enviar mensagens.</div>'
                        : '<div class="alert alert-warning mb-0"><i class="bx bx-error-circle me-2"></i><strong>Desconectado</strong><br>Verifique a conexão da instância na Evolution GO.</div>';
                addLog('Status verificado', 'success');
            })
            .catch(function() {
                document.getElementById('connection-status').innerHTML = '<div class="alert alert-warning mb-0"><i class="bx bx-error-circle me-2"></i><strong>Não foi possível verificar status</strong></div>';
                addLog('Erro ao verificar status', 'warning');
            });
    };

    window.enviarTeste = function(e) {
        e.preventDefault();
        var numero = document.getElementById('teste_numero').value.trim();
        var mensagem = document.getElementById('teste_mensagem').value.trim();
        if (!numero || !mensagem) { alert('Preencha o número e a mensagem'); return; }
        var resultEl = document.getElementById('teste-result');
        resultEl.innerHTML = '<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Enviando...</div>';
        var formData = new FormData();
        formData.append('_token', csrf);
        formData.append('numero', numero);
        formData.append('mensagem', mensagem);
        fetch(base + '/config/teste', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(res) {
                if (res.success) {
                    resultEl.innerHTML = '<div class="alert alert-success"><i class="bx bx-check-circle me-2"></i>Mensagem enviada com sucesso!</div>';
                    addLog('Mensagem teste enviada para ' + numero, 'success');
                } else {
                    resultEl.innerHTML = '<div class="alert alert-danger"><i class="bx bx-error-circle me-2"></i>' + (res.error || 'Erro ao enviar') + '</div>';
                    addLog('Erro: ' + (res.error || 'Erro ao enviar'), 'danger');
                }
            })
            .catch(function() {
                resultEl.innerHTML = '<div class="alert alert-danger">Erro de conexão.</div>';
                addLog('Erro ao enviar mensagem teste', 'danger');
            });
    };

    window.enviarTesteDepartamento = function(e) {
        e.preventDefault();
        var deptId = document.getElementById('teste_departamento').value;
        var mensagem = document.getElementById('teste_mensagem_grupo').value.trim();
        if (!deptId || !mensagem) { alert('Selecione um departamento e preencha a mensagem'); return; }
        var resultEl = document.getElementById('teste-result');
        resultEl.innerHTML = '<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Enviando para o departamento...</div>';
        var formData = new FormData();
        formData.append('_token', csrf);
        formData.append('department_id', deptId);
        formData.append('mensagem', mensagem);
        fetch(base + '/config/teste', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(res) {
                if (res.success) {
                    var d = res.data || {};
                    resultEl.innerHTML = '<div class="alert alert-success"><i class="bx bx-check-circle me-2"></i>Enviadas: ' + (d.enviadas || 0) + ', Erros: ' + (d.erros || 0) + '</div>';
                    addLog('Mensagens enviadas para departamento (sucesso: ' + (d.enviadas || 0) + ')', 'success');
                } else {
                    resultEl.innerHTML = '<div class="alert alert-danger"><i class="bx bx-error-circle me-2"></i>' + (res.error || 'Erro') + '</div>';
                    addLog('Erro ao enviar para departamento: ' + (res.error || ''), 'danger');
                }
            })
            .catch(function() {
                resultEl.innerHTML = '<div class="alert alert-danger">Erro de conexão.</div>';
                addLog('Erro ao enviar para departamento', 'danger');
            });
    };

    window.carregarDepartamentos = function() {
        fetch(base + '/departamentos-lista-json')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var sel = document.getElementById('teste_departamento');
                if (!sel) return;
                while (sel.options.length > 1) sel.remove(1);
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(function(d) {
                        var opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        sel.appendChild(opt);
                    });
                } else {
                    var opt = document.createElement('option');
                    opt.disabled = true;
                    opt.textContent = 'Nenhum departamento cadastrado';
                    sel.appendChild(opt);
                }
            });
    };

    window.carregarInstancias = function() {
        var tbody = document.getElementById('instances-tbody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Carregando...</td></tr>';
        fetch(base + '/config/instances')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">' + (res.error || 'Erro') + '</td></tr>';
                    return;
                }
                if (res.selected) {
                    instanciaAtiva = res.selected;
                }
                var instanciaEl = document.getElementById('instancia-em-uso');
                if (instanciaEl) {
                    instanciaEl.textContent = instanciaAtiva || 'Não selecionada';
                }
                var data = Array.isArray(res.data) ? res.data : (res.data && res.data.instances) || [];
                if (!data.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Nenhuma instância disponível.</td></tr>';
                    return;
                }
                var html = '';
                data.forEach(function(it) {
                    var inst = it.instance || it;
                    var name = inst.instanceName || '—';
                    var safeName = String(name).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                    var owner = inst.owner || '—';
                    var status = (inst.status || 'unknown').toLowerCase();
                    var selected = !!inst.selected || (instanciaAtiva && instanciaAtiva === name);
                    var badgeClass = (status === 'open' || status === 'connected' || status === 'conectado' || status === 'active') ? 'bg-success' : 'bg-warning';
                    var usoHtml = selected
                        ? '<span class="badge bg-primary">Ativa</span>'
                        : '<span class="badge bg-light text-dark">Disponível</span>';
                    var selectBtn = selected
                        ? '<button class="btn btn-sm btn-primary me-1" disabled><i class="bx bx-check me-1"></i> Em uso</button>'
                        : '<button class="btn btn-sm btn-outline-success me-1" onclick="selecionarInstancia(\'' + safeName + '\')"><i class="bx bx-target-lock me-1"></i> Usar esta</button>';
                    html += '<tr><td>' + name + '</td><td>' + usoHtml + '</td><td>' + owner + '</td><td><span class="badge ' + badgeClass + '">' + status + '</span></td><td class="text-end">' + selectBtn + '<button class="btn btn-sm btn-outline-primary" onclick="reiniciarInstancia(\'' + safeName + '\')"><i class="bx bx-reset me-1"></i> Reiniciar</button></td></tr>';
                });
                tbody.innerHTML = html;
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Não foi possível carregar.</td></tr>';
            });
    };

    window.selecionarInstancia = function(instanceName) {
        if (!instanceName) { return; }
        var resultEl = document.getElementById('teste-result');
        if (resultEl) {
            resultEl.innerHTML = '<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Alterando instância ativa...</div>';
        }
        fetch(base + '/config/instances/' + encodeURIComponent(instanceName) + '/select', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(res) {
                if (res.success) {
                    instanciaAtiva = (res.data && res.data.instanceName) || instanceName;
                    var instanciaEl = document.getElementById('instancia-em-uso');
                    if (instanciaEl) {
                        instanciaEl.textContent = instanciaAtiva;
                    }
                    var instanciaIdEl = document.getElementById('instancia-id');
                    if (instanciaIdEl && res.data && res.data.instanceId) {
                        instanciaIdEl.textContent = res.data.instanceId;
                    }
                    if (resultEl) {
                        resultEl.innerHTML = '<div class="alert alert-success"><i class="bx bx-check-circle me-2"></i>' + (res.message || 'Instância ativa alterada.') + '</div>';
                    }
                    addLog('Instância ativa alterada para ' + instanciaAtiva, 'success');
                    verificarStatus();
                    carregarInstancias();
                    return;
                }
                if (resultEl) {
                    resultEl.innerHTML = '<div class="alert alert-danger"><i class="bx bx-error-circle me-2"></i>' + (res.error || 'Erro ao selecionar instância') + '</div>';
                }
                addLog('Erro ao selecionar instância: ' + (res.error || ''), 'danger');
            })
            .catch(function() {
                if (resultEl) {
                    resultEl.innerHTML = '<div class="alert alert-danger">Erro de conexão ao selecionar instância.</div>';
                }
                addLog('Erro de conexão ao selecionar instância', 'danger');
            });
    };

    window.reiniciarInstancia = function(instanceName) {
        if (!instanceName) { return; }
        var resultEl = document.getElementById('teste-result');
        if (resultEl) {
            resultEl.innerHTML = '<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Reiniciando instância...</div>';
        }
        fetch(base + '/config/instances/' + encodeURIComponent(instanceName) + '/restart', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(res) {
                if (res.success) {
                    if (resultEl) {
                        resultEl.innerHTML = '<div class="alert alert-success"><i class="bx bx-check-circle me-2"></i>Instância reiniciada com sucesso.</div>';
                    }
                    addLog('Instância ' + instanceName + ' reiniciada', 'success');
                    verificarStatus();
                    carregarInstancias();
                } else {
                    if (resultEl) {
                        resultEl.innerHTML = '<div class="alert alert-danger"><i class="bx bx-error-circle me-2"></i>' + (res.error || 'Erro ao reiniciar instância') + '</div>';
                    }
                    addLog('Erro ao reiniciar instância: ' + (res.error || ''), 'danger');
                }
            })
            .catch(function() {
                if (resultEl) {
                    resultEl.innerHTML = '<div class="alert alert-danger">Erro de conexão ao reiniciar instância.</div>';
                }
                addLog('Erro de conexão ao reiniciar instância', 'danger');
            });
    };

    window.configurarWebhookReceived = function() {
        var url = document.getElementById('webhook_received_url').value.trim();
        if (!url) { alert('Informe a URL do webhook'); return; }
        var resultEl = document.getElementById('webhook-result');
        resultEl.innerHTML = '<div class="alert alert-info">Configurando...</div>';
        fetch(base + '/config/webhook-received', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ value: url })
        })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    resultEl.innerHTML = '<div class="alert alert-success"><i class="bx bx-check-circle me-2"></i>' + (res.message || 'Webhook configurado.') + '</div>';
                    addLog('Webhook received: ' + url, 'success');
                } else {
                    resultEl.innerHTML = '<div class="alert alert-danger">' + (res.error || 'Erro') + '</div>';
                    addLog('Erro webhook received: ' + (res.error || ''), 'danger');
                }
            })
            .catch(function() {
                resultEl.innerHTML = '<div class="alert alert-danger">Erro de conexão.</div>';
                addLog('Erro ao configurar webhook', 'danger');
            });
    };

    window.configurarWebhookDelivery = function() {
        var url = document.getElementById('webhook_delivery_url').value.trim();
        if (!url) { alert('Informe a URL do webhook'); return; }
        var resultEl = document.getElementById('webhook-result');
        resultEl.innerHTML = '<div class="alert alert-info">Configurando...</div>';
        fetch(base + '/config/webhook-delivery', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ value: url })
        })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    resultEl.innerHTML = '<div class="alert alert-success"><i class="bx bx-check-circle me-2"></i>' + (res.message || 'Webhook configurado.') + '</div>';
                    addLog('Webhook delivery: ' + url, 'success');
                } else {
                    resultEl.innerHTML = '<div class="alert alert-danger">' + (res.error || 'Erro') + '</div>';
                    addLog('Erro webhook delivery: ' + (res.error || ''), 'danger');
                }
            })
            .catch(function() {
                resultEl.innerHTML = '<div class="alert alert-danger">Erro de conexão.</div>';
                addLog('Erro ao configurar webhook', 'danger');
            });
    };

    document.addEventListener('DOMContentLoaded', function() {
        verificarStatus();
        carregarInstancias();
        carregarDepartamentos();
    });
})();
</script>
@endpush
