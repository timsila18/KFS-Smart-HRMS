# Kenya Forest Service HR & Payroll Management System

KFS Smart HRMS is an enterprise Laravel 12, PostgreSQL, Inertia React, TailwindCSS, and shadcn/ui-ready HR and payroll platform for Kenya Forest Service.

## Current Build

- Master PostgreSQL database architecture with 98 normalized tables.
- Laravel Breeze-style authentication.
- React/Inertia authentication UI.
- Spatie role and permission gates.
- Session timeout, login history, and audit activity logging.
- Dark-mode-ready KFS interface.
- Production hardening: security headers, request correlation IDs, API problem responses, database indexes, cache tables, queues, failed jobs, and scheduler support.
- Enterprise reporting with Excel/PDF exports and scheduled report generation.
- Docker, GitHub Actions CI, and deployment documentation.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

PHP 8.4, Composer, Node 22+, and PostgreSQL are required.

## Production Operations

- Use Redis for cache, sessions, and queues in production.
- Run queue workers for `default,audit,reports`.
- Run `php artisan schedule:work` or a system cron invoking `php artisan schedule:run`.
- Serve only over HTTPS with `APP_DEBUG=false`.
- Review [Production Deployment](docs/deployment/production.md), [API](docs/api.md), and module docs before release.
