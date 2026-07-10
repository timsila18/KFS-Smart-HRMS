# Employee Register Module

The Employee Register module provides a production HR master-data workflow for KFS Smart HRMS.

## Backend

- `EmployeeController` provides Inertia CRUD, photo upload, attachments, Excel export, and PDF export.
- `Api\EmployeeController` exposes API resources for employee listing and profile retrieval.
- `EmployeeRegisterService` performs transactional create/update, photo upload, attachment storage, and activity logging.
- `EmployeeRepository` centralizes search, filters, pagination, and eager loading.
- `EmployeePolicy` protects view, create, update, delete, and export actions.
- `EmployeeResource` shapes profile responses for Inertia and API consumers.

## Data Covered

- Employee profile
- Employment details
- Contract details
- Salary details
- Bank details
- Next of kin
- Emergency contacts
- Documents
- Qualifications
- Professional memberships
- Medical records
- Dependants
- Attachments
- Photo upload
- Audit logs

## Exports

- Excel: `GET /employees/export/excel`
- PDF: `GET /employees/export/pdf`

Both endpoints accept the same filters as the register list.

## Frontend

- `resources/js/Pages/Employees/Index.tsx`
- `resources/js/Pages/Employees/Show.tsx`
- `resources/js/Pages/Employees/Form.tsx`

The UI is responsive, dark-mode ready, and follows the KFS Forest Green enterprise theme.
