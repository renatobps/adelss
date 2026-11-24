#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

WEB_USER="${1:-www-data}"
WEB_GROUP="$(id -gn "$WEB_USER")"

if [ "$(id -u)" -ne 0 ]; then
  echo 'Execute como root para reparar o proprietário do cache compartilhado.' >&2
  exit 1
fi

umask 0002
mkdir -p storage/framework/cache/data bootstrap/cache

# Keep root's scheduled commands and PHP-FPM in the same inherited group.
# Do not clear cache entries, sessions, uploads or form submissions.
chown -R "$WEB_USER:$WEB_GROUP" storage/framework/cache bootstrap/cache
find storage/framework/cache bootstrap/cache -type d -exec chmod 2775 {} +
find storage/framework/cache bootstrap/cache -type f -exec chmod 0664 {} +

echo "Permissões do cache reparadas para $WEB_USER:$WEB_GROUP."
echo 'Recarregue a configuração do Laravel e execute Artisan com esse usuário ou com umask 0002.'
