# Troubleshooting — problemas conhecidos

Incidentes já vividos no projeto, com sintoma → causa provável → solução. Consulte antes de reinvestigar do zero.

| Sintoma | Causa provável | Solução |
|---|---|---|
| **WhatsApp parou de enviar tudo** (comprovantes, alertas, enquetes, publicações em grupo) | Conexões esgotadas no Postgres do Evolution Go (`FATAL: sorry, too many clients already`) | [`INFRAESTRUTURA.md` § 1](INFRAESTRUTURA.md#1-postgresql-do-evolution-go--idle_session_timeout--crítico): verificar `SHOW idle_session_timeout;` e reaplicar `ALTER SYSTEM SET idle_session_timeout = '15min';` se retornar `0` |
| **Publicação agendada travada em "publicando"/"pendente"**; lembretes e alertas não disparam | Cron não está rodando no container (não sobe sozinho após reinício), ou erro no comando | [`INFRAESTRUTURA.md` § 2](INFRAESTRUTURA.md#2-cron--scheduler-no-easypanel--crítico): `ps aux \| grep cron`, `service cron start`; conferir `storage/logs/laravel.log` |
| **Erro 403 `ACCESS_TOKEN_SCOPE_INSUFFICIENT` no Google Drive** | `files->listFiles()` chamado **sem** o filtro `'{folderId}' in parents` (obrigatório com escopo `drive.file`) | [`INTEGRACOES.md` § 2](INTEGRACOES.md#2-google-drive-módulo-mídia): adicionar o filtro de pasta pai à query |
| **`Log [] is not defined`** (e funcionalidades diversas quebrando junto) | `config:cache` gerado com `LOG_CHANNEL` vazio no `.env` | [`INFRAESTRUTURA.md` § 3](INFRAESTRUTURA.md#3-cache-de-configuração-do-laravel-): corrigir o `.env` e rodar `php artisan config:clear && php artisan config:cache` |
| **Emojis viram quadrados (□) no PDF** | DomPDF não suporta emoji em nenhuma fonte embutida | Remover emoji antes de renderizar (`App\Support\PdfText::stripEmoji`) e usar fonte `DejaVu Sans` |
| **PDF com margem errada / conteúdo cortado** | Margem definida em `@page` **e** no `body` ao mesmo tempo (DomPDF soma as duas) | Definir margem **apenas** em `@page`; `body { margin: 0; }` |
| **Alteração no `.env` não surtiu efeito** | Produção roda com `config:cache`; o cache antigo continua valendo | `php artisan config:clear && php artisan config:cache` |
| **Imagens quebradas com aviso de Mixed Content (`http://...//storage/...`)** | `APP_URL` com `http` e/ou barra no final no `.env` de produção | `APP_URL=https://adelss.com.br` (sem barra final) + `config:clear && config:cache` |
| **CSS novo não aparece após deploy** | Cache do navegador em arquivo CSS sem versionamento | Usar cache-busting `?v=filemtime` no link do CSS; `php artisan view:clear` no servidor; `Ctrl+F5` no navegador |
| **`git pull` abortado em produção (`local changes would be overwritten`)** | Arquivos alterados manualmente no servidor | `git stash` → `git pull` → seguir o roteiro de deploy do README |
| **Envio duplicado ao WhatsApp em publicações agendadas** | Duas execuções simultâneas do comando de publicação (Instagram demorando > 5 min) | Já mitigado: claim atômico no comando + `withoutOverlapping(30)` no scheduler — não remover nenhum dos dois |
| **Inscrição paga em evento não confirma** | Webhook do Mercado Pago não chegou (URL errada no painel MP ou assinatura inválida) | Conferir cadastro do webhook no painel MP e `MP_WEBHOOK_SECRET`; ver [`INTEGRACOES.md` § 4](INTEGRACOES.md#4-mercado-pago) |
| **Token do Google Drive expira a cada 7 dias** | App OAuth em modo "Teste" no Google Cloud Console | Publicar o app (modo "Em produção"); ver [`INTEGRACOES.md` § 2](INTEGRACOES.md#2-google-drive-módulo-mídia) |
| **Post expirado do Instagram não é removido automaticamente** | Limitação do fluxo "API Setup with Instagram Login" (delete só é suportado no fluxo "Facebook Login") | Comportamento conhecido: o sistema alerta o administrador; remover manualmente no Instagram — ver [`INTEGRACOES.md` § 3](INTEGRACOES.md#3-instagram-módulo-mídia) |
| **E-mail de alerta não chega** | `MAIL_PASSWORD` não é Senha de App, ou config cacheada desatualizada | Gerar Senha de App (16 caracteres, exige 2FA) — ver [`INTEGRACOES.md` § 5](INTEGRACOES.md#5-e-mail-smtp) |
