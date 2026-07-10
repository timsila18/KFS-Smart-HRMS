# Employee Self Service

The ESS module is an employee-scoped portal for KFS Smart HRMS.

## Features

- Dashboard
- Profile
- Payslips
- P9
- Leave
- Training
- Performance
- Payroll History
- Contracts
- Documents
- Notifications
- Requests

## Architecture

`EmployeeSelfService` resolves the authenticated user's linked employee record and scopes all queries to that employee. Employees cannot browse other employees' HR or payroll records through ESS.

## Primary Files

- `app/Services/Ess/EmployeeSelfService.php`
- `app/Http/Controllers/Ess/EmployeeSelfServiceController.php`
- `app/Http/Requests/Ess/StoreEssRequest.php`
- `resources/js/Components/Ess/EssNav.tsx`
- `resources/js/Pages/Ess/Dashboard.tsx`
- `resources/js/Pages/Ess/Profile.tsx`
- `resources/js/Pages/Ess/ListPage.tsx`
- `resources/js/Pages/Ess/Requests.tsx`

## Routes

All routes are under `/ess` and protected by `auth` plus `ess.view`.
