# Roles And Rights

KFS Smart HRMS seeds three HR operating roles in addition to the legacy/base roles.

## HR Admin

Role: `hr-admin`

Rights profile: super administrator.

The role receives every configured permission, matching `super-admin`.

## HR Manager

Role: `hr-manager`

Rights profile: manager.

The role receives HR and payroll management permissions including view, create, update, approve, and export across employees, contracts, payroll, leave, attendance, performance, training, reports, notifications, departments, and stations. It can view and export audit logs.

## HR Payroll Operator

Role: `hr-payroll-operator`

Rights profile: supervisor.

The role receives operational payroll and HR supervisor permissions including payroll view, create, update, and export; employee and contract view/update/export; leave and attendance approvals; reporting export; and notification management. It does not receive delete or super-admin configuration rights.

## Seeded Access Accounts

Fresh seeded environments create these initial users unless overridden with env vars:

| Role | Email | Password |
| --- | --- | --- |
| HR Admin | `hr.admin@kfs.go.ke` | `KfsAdmin@2026` |
| HR Manager | `hr.manager@kfs.go.ke` | `KfsManager@2026` |
| HR Payroll Operator | `payroll.operator@kfs.go.ke` | `KfsPayroll@2026` |

Change these passwords immediately outside local testing.
