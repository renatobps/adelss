# Integrações externas

Para cada integração: qual serviço, qual fluxo de autenticação foi escolhido, onde ficam as credenciais e as limitações conhecidas.

---

## 1. WhatsApp — Evolution Go

- **Serviço**: [Evolution Go](https://docs.evolutionfoundation.com.br/evolution-go), API **não-oficial** baseada em `whatsmeow` (protocolo do WhatsApp Web). Roda como serviço próprio no EasyPanel (`evolutiongo`), com PostgreSQL dedicado (ver [`INFRAESTRUTURA.md`](INFRAESTRUTURA.md), seção 1).
- **Autenticação**: `apikey` (header) + `instanceId` (UUID). Diferente da Evolution API v1/v2, **não usa nome de instância no path** das URLs.
- **Credenciais**: `.env` → `config/whatsapp.php` (`WHATSAPP_API_URL`, `WHATSAPP_API_KEY`, `WHATSAPP_INSTANCE_ID`, `WHATSAPP_NUMBER`, `WHATSAPP_ALERT_EMAILS`).
- **Instância ativa**: selecionável na tela `/notificacoes/config` (módulo Notificações), que também exibe o status e o histórico de conexão.
- **Monitoramento**: comando `whatsapp:check-connection` a cada 10 min; alerta de queda/recuperação **por e-mail** (nunca por WhatsApp, que estaria fora do ar).

### ⚠️ Risco documentado — banimento

Por ser uma API não-oficial, o número está sujeito a **banimento pela Meta**. Já houve um banimento anterior, provavelmente relacionado ao envio de **mensagens com botões** (recurso que só existe oficialmente na API de negócios). Por isso o sistema envia apenas texto, mídia e enquetes nativas.

A migração para a **API oficial (WhatsApp Cloud API)** foi analisada e está **pendente de decisão** (custos por conversa vs. risco de banimento).

---

## 2. Google Drive (módulo Mídia)

- **Fluxo**: OAuth 2.0 com escopo **`drive.file`** — acesso restrito a arquivos criados pelo próprio app (não vê o Drive inteiro da conta).
- **Credenciais**: `GOOGLE_DRIVE_CLIENT_ID` / `GOOGLE_DRIVE_CLIENT_SECRET` no `.env` (`config/services.php`). Os tokens (access/refresh) ficam **criptografados na tabela `google_drive_settings`**, carregados pelo `GoogleDriveServiceProvider`. A conexão/desconexão é feita em Mídia → Configurações.

### ⚠️ Armadilha crítica — `listFiles()` sem filtro de pasta

Com o escopo `drive.file`, **toda** chamada `files->listFiles()` **precisa** restringir a busca por pasta pai:

```php
$service->files->listFiles([
    'q' => "'{$folderId}' in parents and trashed = false",
    // ...
]);
```

Chamadas sem esse filtro falham com **`ACCESS_TOKEN_SCOPE_INSUFFICIENT` (HTTP 403)**, mesmo com o token válido. **Esse erro já ocorreu duas vezes no projeto**, em pontos diferentes do código — ao escrever qualquer consulta nova ao Drive, incluir o filtro de pasta pai é obrigatório.

### App OAuth em modo "Em produção"

O app OAuth no Google Cloud Console precisa estar em modo **"Em produção"** (não "Teste"). Em modo Teste, o refresh token **expira a cada 7 dias**, forçando reconexão manual constante.

---

## 3. Instagram (módulo Mídia)

- **Fluxo**: **"API Setup with Instagram Login"** (login direto com a conta Instagram profissional) — **não** o fluxo "Facebook Login". Isso determina os endpoints corretos:
  - Autorização: `https://www.instagram.com/oauth/authorize` (não `facebook.com/dialog/oauth`)
  - Token: `https://api.instagram.com/oauth/access_token`
- **Credenciais**: `INSTAGRAM_APP_ID` / `INSTAGRAM_APP_SECRET` no `.env` (`config/services.php`, com fallback para `META_APP_ID`/`META_APP_SECRET`). O token de longa duração fica no banco, gerenciado pela tela de configurações do Instagram no módulo Mídia.
- **`redirect_uri`**: precisa estar cadastrado em Meta for Developers → App → API do Instagram → **"Configuração da API com login da empresa"** (não confundir com o campo de webhook), e deve bater **caractere por caractere** com o valor enviado pelo código (protocolo, domínio, path, sem barra extra).

### ⚠️ Limitação documentada — exclusão de mídia

A exclusão de mídia publicada via API é oficialmente suportada **apenas no fluxo "Facebook Login"**. No fluxo usado por este projeto, a remoção automática de posts (`midia:remove-expired-instagram-posts`) **pode não funcionar** — o código trata a falha e alerta o administrador para remoção manual.

---

## 4. Mercado Pago

- **Uso**: pagamentos **PIX e cartão** em duas frentes:
  - **Financeiro**: cobrança de receitas (links de pagamento de dízimos/ofertas/contribuições).
  - **Agenda/Eventos**: inscrições pagas em eventos — o pagamento aprovado confirma a inscrição e dispara o comprovante em PDF por WhatsApp.
- **Credenciais**: `MP_ACCESS_TOKEN`, `MP_PUBLIC_KEY`, `MP_WEBHOOK_SECRET` no `.env` (`config/mercadopago.php`).
- **Webhook**: `POST /webhooks/mercado-pago` (`MercadoPagoWebhookController`), rota pública (fora do grupo `auth`), com validação de assinatura via `MP_WEBHOOK_SECRET`. O webhook sincroniza o status do pagamento dentro de transação de banco; o envio do comprovante por WhatsApp acontece **fora** da transação (falha no envio não desfaz a confirmação).
- **Observação**: o webhook precisa estar cadastrado no painel do Mercado Pago apontando para a URL de produção; pagamentos ficam "pendentes" no sistema se o webhook não chegar (há também consulta ativa de status como contingência).

---

## 5. E-mail (SMTP)

- **Serviço**: Gmail SMTP com a conta `midia.adelss@gmail.com`.
- **Autenticação**: exige **Senha de App** de 16 caracteres (não a senha normal da conta), o que por sua vez exige **verificação em duas etapas ativa** na conta Google. A senha de app é gerada em [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords).
- **Credenciais**: `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_USERNAME`, `MAIL_PASSWORD` (senha de app), `MAIL_ENCRYPTION=tls` no `.env` (`config/mail.php`).
- **Uso atual**: alertas de desconexão do WhatsApp (`whatsapp:check-connection` → destinatários em `WHATSAPP_ALERT_EMAILS`). É o canal de contingência quando o WhatsApp está fora — por isso e-mail **nunca** deve depender do WhatsApp.
- Lembrete: após alterar qualquer `MAIL_*` no `.env` de produção, rodar `php artisan config:clear && php artisan config:cache`.
