#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure the public/storage symlink exists so uploaded files (e.g. profile
# avatars) are served. Idempotent — --force recreates it if already present.
php artisan storage:link --force

if [ "${RUN_LARAVEL_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_LARAVEL_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
fi
