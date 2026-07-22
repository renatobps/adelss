# Módulo Mídia — Google Drive + Instagram

## Pré-requisitos externos

### Google Drive
1. Crie um projeto no [Google Cloud Console](https://console.cloud.google.com/).
2. Ative a **Google Drive API**.
3. Crie credenciais OAuth 2.0 (tipo **Web application**).
4. URI de redirecionamento: `https://{seu-dominio}/midia/google/callback` (local: `http://127.0.0.1:8001/midia/google/callback`).
5. No `.env`:
   ```
   GOOGLE_DRIVE_CLIENT_ID=...
   GOOGLE_DRIVE_CLIENT_SECRET=...
   GOOGLE_DRIVE_ROOT_FOLDER=ADELSS
   ```

### Instagram (Graph API)
1. Conta Instagram **Business ou Creator** vinculada a uma Página do Facebook.
2. No app Meta (pode reutilizar o do WhatsApp), adicione **Instagram Graph API**.
3. Redirect: `https://{seu-dominio}/midia/instagram/callback`.
4. No `.env`:
   ```
   INSTAGRAM_APP_ID=...   # ou META_APP_ID
   INSTAGRAM_APP_SECRET=... # ou META_APP_SECRET
   ```

## Instalação no ADELSS
```bash
php artisan migrate
php artisan db:seed --class=PermissionSeeder
php artisan storage:link   # já deve existir
```

Agendamento (cron do servidor):
```
* * * * * php /caminho/do/projeto/artisan schedule:run
```
O comando `midia:publish-instagram-posts` roda a cada 5 minutos.

## Fluxo Instagram + Drive
Posts com foto do Drive baixam o arquivo para `storage/app/public/instagram-temp/` e usam URL pública `/storage/instagram-temp/...` na API do Instagram. Após publicar, o temporário é removido.

## Permissões
- `midia.arquivos.view|upload|delete`
- `midia.pastas.manage`
- `midia.configuracoes.manage`
- `midia.instagram.view|schedule`
- `midia.instagram.configuracoes.manage`
