# Configuração do envio de mensagens por WhatsApp (Evolution API)

O módulo de Notificações usa a **Evolution API** para enviar mensagens e receber respostas via webhook.

---

## 1. Variáveis no `.env`

```env
WHATSAPP_API_URL=https://wpp.seudominio.com
WHATSAPP_API_KEY=sua_api_key
WHATSAPP_INSTANCE_NAME=evolutionapi
WHATSAPP_WEBHOOK_URL=https://seudominio.com/webhook
```

- **WHATSAPP_API_URL**: URL base da Evolution API.
- **WHATSAPP_API_KEY**: Chave enviada no header `apikey`.
- **WHATSAPP_INSTANCE_NAME**: Nome da instância conectada.
- **WHATSAPP_WEBHOOK_URL**: URL pública onde o Laravel recebe eventos (`POST /webhook`).

---

## 2. Desenvolvimento local (Ultrahook)

```bash
# Terminal 1
php artisan serve

# Terminal 2
ultrahook webhook http://127.0.0.1:8000/webhook
```

No `.env`:

```env
WHATSAPP_WEBHOOK_URL=https://seu-alias-webhook.ultrahook.com
```

Registre o webhook em **Notificações → Configuração WPP** ou via API Evolution (`POST /webhook/set/{instance}`).

---

## 3. Conectar o WhatsApp

1. Acesse **Notificações → Configuração WPP**.
2. Verifique o status da instância e conecte via QR Code na Evolution API, se necessário.
3. Envie uma mensagem de teste para validar o envio.

---

## 4. Webhook e enquetes

- Respostas de enquetes (clique em botões) chegam via evento `MESSAGES_UPSERT`.
- O Laravel processa em `POST /webhook` e grava em `enquete_respostas`.
- Após registrar a resposta, envia mensagem de agradecimento automaticamente.

---

## 5. Erros comuns

| Erro | Solução |
|------|--------|
| *"API não configurada"* | Preencha `WHATSAPP_API_URL`, `WHATSAPP_API_KEY` e `WHATSAPP_INSTANCE_NAME`. |
| *"Not Found" ao configurar webhook* | Use a tela de config (Evolution) ou confira a instância ativa. |
| Resposta de enquete não chega | Ultrahook rodando, webhook registrado na Evolution, rota `/webhook` ativa. |
| Mensagem não chega | Instância conectada; número no formato `55` + DDD + número. |

Consulte `storage/logs/laravel.log` para detalhes.
