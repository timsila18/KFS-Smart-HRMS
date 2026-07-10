# KFS Smart HRMS Reporting Module

The reporting module is catalog driven. `report_catalogs` controls report names, modules, schedulability, filters, and output options, while `report_runs` records generated and scheduled activity.

## Delivered Reports

- Payroll Register, Payroll Summary, Department Payroll, Bank Schedule, Variance Report
- HRISKE Wagebill, Earnings & Deductions, Monthly Deduction Postings, Individual Payment Breakdown, Bank Summary
- P9, PAYE, SHA, NSSF, Housing Levy, HELB, KEFSSWA, Asili Sacco
- Employee Register, Contract Expiry, Leave, Performance, Training

## Outputs

- Inertia preview with filters and Chart.js visualization
- Excel export through Laravel Excel
- PDF export through DomPDF
- Scheduled report metadata through configurable frequency and recipients
- Scheduled generation through `reports:run-scheduled`, registered hourly in Laravel Scheduler

## Extension Pattern

Add a catalog record in `ReportDefinitionRegistry`, seed it through `ReportingSeeder`, then add a dataset method in `ReportDataService`. Report filters are validated by `RunReportRequest`.
