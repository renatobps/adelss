<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembretes interrompidos</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:560px; margin:0 auto; padding:24px 16px;">
        <div style="background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e9ee;">
            <div style="padding:18px 24px; background:#dc3545; color:#ffffff;">
                <h1 style="margin:0; font-size:18px;">⚠️ Lembretes interrompidos</h1>
            </div>
            <div style="padding:24px; color:#2E353E; font-size:15px; line-height:1.6;">
                <p style="margin-top:0;">
                    O envio automático de lembretes da campanha <strong>{{ $campaignName }}</strong> foi
                    interrompido em <strong>{{ $detectedAt->format('d/m/Y \à\s H:i') }}</strong>.
                </p>
                <p><strong>Motivo:</strong> {{ $reason }}</p>
                <ul style="padding-left:20px; margin:8px 0 16px;">
                    <li>Mensagens enviadas antes da parada: <strong>{{ $sent }}</strong></li>
                    <li>Falhas de envio: <strong>{{ $failed }}</strong></li>
                </ul>
                <p>
                    Os patrocinadores que não receberam continuam elegíveis e entram no próximo
                    ciclo automaticamente — nenhuma cobrança foi perdida.
                </p>
                <p style="text-align:center; margin:24px 0;">
                    <a href="https://adelss.com.br/notificacoes/config"
                       style="background:#0088CC; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:8px; font-weight:bold; display:inline-block;">
                        Verificar conexão do WhatsApp
                    </a>
                </p>
            </div>
            <div style="padding:14px 24px; background:#f7f9fb; color:#6C757D; font-size:12px; border-top:1px solid #e5e9ee;">
                Alerta automático dos lembretes de campanha — ADELSS Sistema Web.
            </div>
        </div>
    </div>
</body>
</html>
