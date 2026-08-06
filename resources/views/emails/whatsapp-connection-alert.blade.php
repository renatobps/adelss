<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type === 'down' ? 'WhatsApp desconectado' : 'WhatsApp reconectado' }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:560px; margin:0 auto; padding:24px 16px;">
        <div style="background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e9ee;">
            <div style="padding:18px 24px; background:{{ $type === 'down' ? '#dc3545' : '#198754' }}; color:#ffffff;">
                <h1 style="margin:0; font-size:18px;">
                    {{ $type === 'down' ? '⚠️ WhatsApp desconectado' : '✅ WhatsApp reconectado' }}
                </h1>
            </div>
            <div style="padding:24px; color:#2E353E; font-size:15px; line-height:1.6;">
                @if($type === 'down')
                    <p style="margin-top:0;">
                        A instância <strong>{{ $instanceName ?: 'padrão' }}</strong> do WhatsApp do ADELSS está
                        <strong>desconectada</strong> desde
                        <strong>{{ $detectedAt->format('d/m/Y \à\s H:i') }}</strong>.
                    </p>
                    <p>Enquanto a conexão não for restabelecida, <strong>não estão sendo enviados</strong>:</p>
                    <ul style="padding-left:20px; margin:8px 0 16px;">
                        <li>Comprovantes de dízimos e ofertas</li>
                        <li>Alertas de despesas ao tesoureiro</li>
                        <li>Enquetes e notificações de escala</li>
                        <li>Publicações agendadas em grupos</li>
                    </ul>
                    <p>
                        Para reconectar, acesse a tela de configuração e escaneie o QR Code, se necessário.
                    </p>
                    <p style="text-align:center; margin:24px 0;">
                        <a href="https://adelss.com.br/notificacoes/config"
                           style="background:#0088CC; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:8px; font-weight:bold; display:inline-block;">
                            Abrir Configuração do WhatsApp
                        </a>
                    </p>
                @else
                    <p style="margin-top:0;">
                        A instância <strong>{{ $instanceName ?: 'padrão' }}</strong> do WhatsApp do ADELSS foi
                        <strong>reconectada</strong> em
                        <strong>{{ $detectedAt->format('d/m/Y \à\s H:i') }}</strong>.
                    </p>
                    @if($downtime)
                        <p>Tempo total fora do ar: <strong>{{ $downtime }}</strong>.</p>
                    @endif
                    <p>Os envios voltaram ao normal. Nenhuma ação é necessária.</p>
                @endif
            </div>
            <div style="padding:14px 24px; background:#f7f9fb; color:#6C757D; font-size:12px; border-top:1px solid #e5e9ee;">
                Alerta automático do monitoramento de conexão — ADELSS Sistema Web.
            </div>
        </div>
    </div>
</body>
</html>
