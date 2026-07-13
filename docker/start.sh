#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R kfs:kfs storage bootstrap/cache

if [ "${APP_ENV:-production}" = "production" ]; then
    su kfs -c "php artisan config:cache" || true
    su kfs -c "php artisan route:cache" || true
    su kfs -c "php artisan view:cache" || true
fi

exec /usr/bin/supervisord -c /etc/supervisord.conf
