# Supabase Database Setup

KFS Smart HRMS can use Supabase Postgres as its PostgreSQL backend.

## Security

Never commit real Supabase passwords or secret keys. Keep them only in `.env`, hosting secrets, or CI/CD secret stores.

If credentials were pasted into chat or logs, rotate them in Supabase before production use.

## Recommended Connection

For Laravel web requests, use Supabase Shared Pooler on port `6543`:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-west-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.your-project-ref
DB_PASSWORD=replace-with-rotated-password
DB_SSLMODE=require
```

If using URL form, percent-encode special characters in the password. For example, `@` becomes `%40`:

```env
DB_URL=postgresql://postgres.your-project-ref:replace-with-percent-encoded-password@aws-0-eu-west-1.pooler.supabase.com:6543/postgres?sslmode=require
```

## Direct Connection

Use the direct host on port `5432` for migrations or administrative operations only when needed:

```env
DB_HOST=db.your-project-ref.supabase.co
DB_PORT=5432
```

## Supabase API Keys

```env
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_PUBLISHABLE_KEY=replace-with-publishable-key
SUPABASE_SECRET_KEY=replace-with-secret-key
```

Only expose the publishable key to browser code. The secret key must remain server-side.

## Migration

```bash
php artisan migrate --force
php artisan db:seed --force
```
