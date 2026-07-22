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

### Instagram (API Setup with Instagram Login)
1. Conta Instagram **Business ou Creator**.
2. No Meta for Developers, configure **API Setup with Instagram Login** (OAuth do Instagram, não Facebook Login).
3. Redirect: `https://{seu-dominio}/midia/instagram/callback`.
4. No `.env` use o **Instagram App ID / Secret** (não o App ID genérico do Facebook, se forem diferentes):
   ```
   INSTAGRAM_APP_ID=...
   INSTAGRAM_APP_SECRET=...
   ```
5. Fluxo usado pelo ADELSS:
   - Authorize: `https://www.instagram.com/oauth/authorize`
   - Code → short-lived token: `POST https://api.instagram.com/oauth/access_token`
   - Short → long-lived (~60 dias): `GET https://graph.instagram.com/access_token?grant_type=ig_exchange_token`
   - Publicação: `https://graph.instagram.com/{ig-user-id}/media` e `/media_publish`

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
