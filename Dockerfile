FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json tailwind.config.ts postcss.config.js ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader

FROM php:8.4-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
        bash icu-dev libzip-dev oniguruma-dev postgresql-dev supervisor nginx \
    && docker-php-ext-install intl mbstring opcache pcntl pdo_pgsql zip \
    && addgroup -g 1000 kfs \
    && adduser -D -G kfs -u 1000 kfs

COPY --chown=kfs:kfs . .
COPY --from=vendor --chown=kfs:kfs /app/vendor ./vendor
COPY --from=frontend --chown=kfs:kfs /app/public/build ./public/build
COPY docker/start.sh /usr/local/bin/kfs-start
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R kfs:kfs storage bootstrap/cache \
    && chmod +x /usr/local/bin/kfs-start

USER kfs
RUN php artisan package:discover --ansi || true
USER root

EXPOSE 8080
CMD ["kfs-start"]
