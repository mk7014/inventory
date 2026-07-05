FROM node:22.12-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build
RUN test -f public/build/manifest.json && test -n "$(find public/build/assets -name '*.css' -print -quit)"

FROM serversideup/php:8.2-fpm-nginx

WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build
COPY --from=assets --chown=www-data:www-data /app/public/build /opt/manager/public-build
COPY --chmod=755 docker/entrypoint.d/10-laravel-migrate-seed.sh /etc/entrypoint.d/10-laravel-migrate-seed.sh

RUN test -f /var/www/html/public/build/manifest.json
RUN test -n "$(find /var/www/html/public/build/assets -name '*.css' -print -quit)"

RUN composer install --no-dev --optimize-autoloader --no-interaction
