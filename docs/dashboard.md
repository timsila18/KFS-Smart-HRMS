# KFS Dashboard

The KFS dashboard is an operational command view for HR and payroll leadership.

## Backend

Dashboard data is assembled by `App\Services\Dashboard\KfsDashboardService` and returned through `DashboardController`.

The service provides:

- Employee card: active employees.
- Payroll Ready card: active employees with a valid salary assignment and primary bank account.
- Contracts Expiring card: active contracts ending within `KFS_DASHBOARD_CONTRACT_EXPIRY_WINDOW_DAYS`.
- Employees on Leave card: employees with approved leave spanning today.
- Monthly Payroll chart: recent payroll run net totals by period.
- Employee Distribution chart: active employees by station region.
- Leave Statistics chart: requested leave days by leave type for the current year.
- Payroll Calendar: upcoming pay periods.
- Notifications: latest system notifications.
- Quick Actions: permission-aware workflow shortcuts.

## Frontend

The Inertia page lives at `resources/js/Pages/Dashboard.tsx` and uses:

- `MetricCard` for executive KPI cards.
- `ChartPanel` for Chart.js line, bar, and doughnut charts.
- Forest Green KFS theme variables from `resources/css/app.css`.
- Responsive grid layouts for desktop, tablet, and mobile.
- Dark mode via the existing appearance cookie and `ThemeToggle`.

## Configuration

```env
KFS_DASHBOARD_CONTRACT_EXPIRY_WINDOW_DAYS=90
```
