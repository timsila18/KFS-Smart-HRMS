#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R kfs:kfs storage bootstrap/cache

if [ "${APP_ENV:-production}" = "production" ]; then
    su kfs -c "php artisan config:cache"
    su kfs -c "php artisan route:cache"
    su kfs -c "php artisan view:cache"
fi

exec /usr/bin/supervisord -c /etc/supervisord.conf
