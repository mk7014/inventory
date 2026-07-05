#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ "${RUN_LARAVEL_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_LARAVEL_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
fi
