# KFS Smart HRMS Authentication Architecture

This authentication module follows Laravel Breeze conventions with Inertia React pages and production controls required for KFS Smart HRMS.

## Implemented Capabilities

- Login and logout through session authentication.
- Remember-me support through Laravel's `remember_token`.
- Forgot password and reset password through Laravel password broker.
- Authenticated change-password flow with current password validation.
- Optional two-factor challenge route controlled by `KFS_AUTH_TWO_FACTOR_ENABLED`.
- Role-based login gate using Spatie roles and `KFS_AUTH_ALLOWED_LOGIN_ROLES`.
- Permission middleware on authenticated routes.
- Session timeout through `EnforceSessionTimeout`.
- Login history in `user_login_histories`.
- Activity logs in `audit_logs`.
- Modern responsive React UI with dark-mode-ready KFS styling.

## Operational Notes

- Default session driver is `file` because the master database includes an auditable HRMS `sessions` table, not Laravel's native database session table structure.
- For production database sessions, add a separate Laravel-compatible session table or intentionally alter the master `sessions` table contract.
- Two-factor support is intentionally optional. The challenge endpoint is ready, but provisioning authenticator secrets and recovery codes should be added when KFS confirms the MFA policy and provider.
- Role and permission seeds are provided in `KfsHrmsDatabaseSeeder`.

## Security Configuration

Relevant environment variables:

```env
KFS_AUTH_SESSION_TIMEOUT_MINUTES=30
KFS_AUTH_TWO_FACTOR_ENABLED=false
KFS_AUTH_ALLOWED_LOGIN_ROLES=super-admin,hr-admin,hr-manager,hr-payroll-operator,hr-director,hr-officer,payroll-manager,station-manager,employee
```

## Primary Files

- `routes/auth.php`
- `routes/web.php`
- `app/Http/Controllers/Auth/*`
- `app/Http/Requests/Auth/*`
- `app/Http/Middleware/EnforceSessionTimeout.php`
- `app/Services/Auth/ActivityLogger.php`
- `resources/js/Pages/Auth/*`
- `resources/js/Layouts/AuthLayout.tsx`
- `resources/js/Layouts/AppLayout.tsx`
