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
                    <button type="button" class="btn btn-primary" onclick="conectar()">
                        <i class="bx bxl-whatsapp me-1"></i> Obter QR Code
                    </button>
                </div>
            </div>
        </section>
    </div>

    <!-- QR Code -->
    <div class="col-md-6 mb-4">
        <section class="card">
            <header class="card-header">
                <h5 class="mb-0"><i class="bx bx-qr-scan me-2"></i> QR Code de Conexão</h5>
            </header>
            <div class="card-body">
                <div id="qrcode-container" class="text-center">
                    <i class="bx bx-qr-scan" style="font-size: 5rem; color: #ddd;"></i>
                    <p class="text-muted mt-3">Clique em "Obter QR Code" para conectar</p>
                </div>
                <div id="qrcode-instructions" class="mt-3 d-none">
                    <div class="alert alert-info">
                        <strong><i class="bx bx-info-circle me-1"></i> Como conectar:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Abra o WhatsApp no seu celular</li>
                            <li>Toque em <strong>Configurações</strong> → <strong>Dispositivos Conectados</strong></li>
                            <li>Toque em <strong>Conectar um Dispositivo</strong></li>
                            <li>Aponte o celular para este QR Code</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Instâncias -->
<div class="row">
    <div class="col-12 mb-4">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0"><i class="bx bx-hdd me-2"></i> Instâncias</h5>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <input type="text" id="newInstanceName" class="form-control form-control-sm" placeholder="nome da instância" style="width:220px">
                    <button class="btn btn-sm btn-primary" onclick="criarInstancia()"><i class="bx bx-plus-circle me-1"></i> Criar</button>
                </div>
            </header>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Instância</th>
                                <th>Dono</th>
                                <th>Status</th>
                                <th class="text-end" width="180">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="instances-tbody">
                            <tr><td colspan="4" class="text-center text-muted py-3">Carregando...</td></tr>
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
                        <p class="mb-0"><strong>Tipo de Autenticação:</strong><br><span class="badge bg-success">Z-API (Client-Token)</span></p>
                    </div>
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>Endpoint de Envio:</strong><br><code>/instances/{id}/token/{token}/send-text</code></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>API Docs:</strong><br>
                            <a href="https://developer.z-api.io" target="_blank" rel="noopener"><i class="bx bx-link-external me-1"></i> Ver Documentação Z-API</a>
                        </p>
                    </div>
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>Instance ID:</strong><br><code>{{ config('whatsapp.instance_id') ?: 'Não configurado' }}</code></p>
                    </div>
                    <div class="col-md-4 mb-2">
                        <p class="mb-0"><strong>Instance Token:</strong><br><code>{{ config('whatsapp.instance_token') ? (substr(config('whatsapp.instance_token'), 0, 10) . '...') : 'Não configurado' }}</code></p>
                    </div>
                </div>
                <div class="alert alert-success mt-3 mb-0">
                    <strong><i class="bx bx-info-circle me-1"></i> Configuração Z-API:</strong><br>
                    <small>
                        A API Z-API usa <strong>Client-Token</strong> no header e <strong>Instance ID + Token</strong> no path da URL.
                        @if(config('whatsapp.client_token'))
                            <strong>Client-Token:</strong> {{ substr(config('whatsapp.client_token'), 0, 10) }}...
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
                <h5 class="mb-0"><i class="bx bx-link me-2"></i> Configuração de Webhooks</h5>
            </header>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong><i class="bx bx-info-circle me-1"></i> Sobre os Webhooks:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Webhook Received:</strong> URL para receber mensagens recebidas (respostas de enquetes, mensagens de texto, etc.)</li>
                        <li><strong>Webhook Delivery:</strong> URL para receber confirmações de envio de mensagens</li>
                        <li>Ambos devem ser URLs públicas acessíveis pela internet</li>
                    </ul>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-inbox me-1"></i> Webhook para Receber Mensagens</label>
                        <div class="input-group">
                            <input type="url" class="form-control" id="webhook_received_url" placeholder="https://seu-dominio.com/webhook/whatsapp" value="{{ config('whatsapp.webhook_url') ?: url('/notificacoes/webhook/whatsapp') }}">
                            <button type="button" class="btn btn-primary" onclick="configurarWebhookReceived()"><i class="bx bx-save me-1"></i> Configurar</button>
                        </div>
                        <small class="text-muted">URL onde a Z-API enviará mensagens recebidas</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bx bx-check-circle me-1"></i> Webhook para Confirmações de Envio</label>
                        <div class="input-group">
                            <input type="url" class="form-control" id="webhook_delivery_url" placeholder="https://seu-dominio.com/webhook/whatsapp/delivery" value="{{ url('/notificacoes/webhook/whatsapp/delivery') }}">
                            <button type="button" class="btn btn-primary" onclick="configurarWebhookDelivery()"><i class="bx bx-save me-1"></i> Configurar</button>
                        </div>
                        <small class="text-muted">URL onde a Z-API enviará confirmações de envio</small>
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
                        : '<div class="alert alert-warning mb-0"><i class="bx bx-error-circle me-2"></i><strong>Desconectado</strong><br>Clique em "Obter QR Code" para conectar.</div>';
                addLog('Status verificado', 'success');
            })
            .catch(function() {
                document.getElementById('connection-status').innerHTML = '<div class="alert alert-warning mb-0"><i class="bx bx-error-circle me-2"></i><strong>Não foi possível verificar status</strong></div>';
                addLog('Erro ao verificar status', 'warning');
            });
    };

    window.conectar = function() {
        document.getElementById('qrcode-container').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Gerando QR Code...</p></div>';
        document.getElementById('qrcode-instructions').classList.remove('d-none');
        fetch(base + '/config/conectar')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data && res.data.base64) {
                    document.getElementById('qrcode-container').innerHTML = '<img src="' + res.data.base64 + '" class="img-fluid" style="max-width:300px">';
                    addLog('QR Code gerado com sucesso', 'success');
                    setTimeout(verificarStatus, 10000);
                } else {
                    document.getElementById('qrcode-container').innerHTML = '<div class="alert alert-danger">' + (res.error || 'Erro ao gerar QR Code') + '</div>';
                    addLog('Erro ao gerar QR Code', 'danger');
                }
            })
            .catch(function() {
                document.getElementById('qrcode-container').innerHTML = '<div class="alert alert-danger">Erro de conexão com a API.</div>';
                addLog('Erro de conexão', 'danger');
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
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Carregando...</td></tr>';
        fetch(base + '/config/instances')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">' + (res.error || 'Erro') + '</td></tr>';
                    return;
                }
                var data = Array.isArray(res.data) ? res.data : (res.data && res.data.instances) || [];
                if (!data.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Nenhuma instância (use o .env para Z-API).</td></tr>';
                    return;
                }
                var html = '';
                data.forEach(function(it) {
                    var inst = it.instance || it;
                    var name = inst.instanceName || '—';
                    var owner = inst.owner || '—';
                    var status = inst.status || 'active';
                    html += '<tr><td>' + name + '</td><td>' + owner + '</td><td><span class="badge bg-warning">' + status + '</span></td><td class="text-end"><button class="btn btn-sm btn-outline-secondary" onclick="verificarStatus()"><i class="bx bx-refresh"></i></button></td></tr>';
                });
                tbody.innerHTML = html;
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Não foi possível carregar.</td></tr>';
            });
    };

    window.criarInstancia = function() {
        var name = (document.getElementById('newInstanceName').value || '').trim();
        if (!name) { alert('Informe um nome para a instância'); return; }
        var formData = new FormData();
        formData.append('_token', csrf);
        formData.append('instanceName', name);
        fetch(base + '/config/instances', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) { carregarInstancias(); addLog('Instância ' + name + ' criada (configure no .env)', 'success'); }
                else { alert(res.error || 'Erro'); }
            })
            .catch(function() { alert('Falha na requisição'); });
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
