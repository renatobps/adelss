#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."
umask 0002

mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/framework/testing \
  storage/app/public \
  storage/logs \
  bootstrap/cache

fix_storage_perms() {
  # root (cron/artisan) e www-data (PHP-FPM) precisam escrever nos mesmos arquivos.
  chmod -R a+rwX storage bootstrap/cache 2>/dev/null || true
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
  # Repair old non-searchable cache directories and preserve group inheritance.
  if [ "$(id -u)" -eq 0 ]; then
    bash scripts/fix-cache-permissions.sh
  fi
}

fix_storage_perms

php artisan storage:link --force 2>/dev/null || php artisan storage:link || true
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# artisan acima pode recriar arquivos como root; reabre permissão para o PHP-FPM.
fix_storage_perms

php scripts/diagnose-production.php || true

echo "Deploy bootstrap concluído."
