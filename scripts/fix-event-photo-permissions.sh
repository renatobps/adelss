#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p storage/app/public/events/speakers
PUBLIC_ROOT="$(readlink -f storage/app/public)"
EVENT_ROOT="$(readlink -f storage/app/public/events)"
if [ "$EVENT_ROOT" != "$PUBLIC_ROOT/events" ]; then
  echo 'A pasta de eventos aponta para um destino inesperado. Nenhuma permissão foi alterada.' >&2
  exit 1
fi
WEB_USER="${1:-}"
if [ -z "$WEB_USER" ]; then
  WEB_USER="$(ps -eo user=,args= | awk '$1 != "root" && ($0 ~ /php-fpm: pool/ || $2 ~ /(^|\/)(apache2|httpd)$/) {if (!found) print $1; found=1}')"
fi
if [ -z "$WEB_USER" ]; then
  echo 'Não foi possível identificar o usuário do PHP. Veja com: ps -eo user,args | grep -E "php-fpm|apache2|httpd"' >&2
  echo 'Depois execute: bash scripts/fix-event-photo-permissions.sh USUARIO_DO_PHP' >&2
  exit 1
fi
id "$WEB_USER" >/dev/null
WEB_GROUP="$(id -gn "$WEB_USER")"
chown -R "$WEB_USER:$WEB_GROUP" "$EVENT_ROOT"
find "$EVENT_ROOT" -type d -exec chmod 755 {} +
find "$EVENT_ROOT" -type f -exec chmod 644 {} +
runuser -u "$WEB_USER" -- sh -c 'probe=$(mktemp "$1/.upload-check.XXXXXX"); test -f "$probe"; rm -- "$probe"' sh "$EVENT_ROOT/speakers"
echo "OK: fotos dos eventos graváveis pelo usuário $WEB_USER."
