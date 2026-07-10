# Enterprise Hardening

This pass adds production controls across the KFS Smart HRMS application.

## Performance

- Dashboard aggregation is cached through `KFS_DASHBOARD_SUMMARY_CACHE_SECONDS`.
- PostgreSQL partial indexes target active employee filters, payroll runs, payroll run items, reports, audit logs, and login history.
- Vite production assets are built into the Docker image.

## Security

- Security headers are applied to web responses.
- API routes are versioned under `/api/v1`, throttled, and protected by Sanctum plus Spatie permissions.
- API errors use `application/problem+json`.
- Every response receives `X-Request-Id` for traceability.

## Queues And Audit Logs

- Audit log writes are dispatched after response outside tests.
- `reports:run-scheduled` generates scheduled reports.
- Queue tables and failed job tables are included.

## Operations

- Docker Compose provides app, queue, scheduler, PostgreSQL, and Redis.
- GitHub Actions runs dependency install, migrations, Pint, tests, frontend build, and Docker image build.
- Production logs default to daily files locally and can use `stderr` in containers.
