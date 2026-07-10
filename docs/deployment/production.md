# Production Deployment

## Runtime

- PHP 8.4 with OPcache
- PostgreSQL 17+
- Redis 7+ for cache, sessions, and queues
- Node 22 for asset builds
- HTTPS terminated at the load balancer or Nginx

## Required Commands

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm install
npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run workers:

```bash
php artisan queue:work redis --queue=default,audit,reports --tries=3 --backoff=5
php artisan schedule:work
```

## Docker

```bash
cp .env.example .env
docker compose up --build -d
docker compose exec app php artisan migrate --seed --force
```

For a production container platform that provides PostgreSQL and Redis outside the
compose project, use:

```bash
docker compose -f docker-compose.production.yml up --build -d
docker compose -f docker-compose.production.yml exec app php artisan migrate --seed --force
```

Set these values in the platform secret manager, not in source control:

- `APP_KEY`
- `APP_URL`
- `DB_URL` or `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `DB_SSLMODE=require` when using Supabase
- `REDIS_URL` or `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`
- mail provider credentials
- Supabase publishable and service keys, after rotating any keys shared in chat

## Render Blueprint

`render.yaml` defines one Docker web service, one queue worker, and one scheduler
worker. Connect the GitHub repository in Render, select the blueprint, then set
`APP_KEY`, `APP_URL`, `DB_URL`, and `REDIS_URL` as secret environment variables.

After the first successful deploy, run the one-time migration and seed command
from the Render shell:

```bash
php artisan migrate --seed --force
```

## Operational Checks

- `/up` must return healthy from Laravel.
- Queue worker lag must be monitored.
- Failed jobs must alert operations.
- PostgreSQL backups must run daily with point-in-time recovery enabled.
- Audit logs and login history must be retained according to KFS policy.
