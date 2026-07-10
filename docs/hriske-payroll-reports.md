# HRISKE Payroll Reports

The payroll reporting module now includes report formats based on the Government HRISKE payroll exports supplied for vote `12103`.

## Added Payroll Reports

- HRISKE Wagebill
- HRISKE Earnings & Deductions
- HRISKE Monthly Deduction Postings
- HRISKE Individual Payment Breakdown
- HRISKE Bank Summary
- Bank Branch Register

These reports are available through the normal Reports module and can be previewed, filtered by payroll period, exported to Excel, and exported to PDF.

## Bank Branch Codes

Bank branches are normalized in `bank_branches` using:

- `bank_code`
- `branch_code`
- `bank_name`
- `branch_name`

Employee bank accounts now carry `bank_branch_id`, `bank_code`, and `branch_code`.

Import commands:

```bash
php artisan payroll:import-hriske-bank-branches storage/app/imports/branch-register.csv
php artisan payroll:import-hriske-payment-breakdown storage/app/imports/detailed-individual-payment-breakdown.csv
```

The payment breakdown importer updates employee bank accounts by matching `PayrollNum` to `employees.employee_number`.

## Salary Proration And NSSF

Basic salary and house allowance are prorated using the exact calendar-day fraction for the payroll period. The engine does not round formulas or prorated amounts to two decimals before storage. Payroll monetary columns are widened to 18 decimal places for calculation precision.

NSSF is configured as a fixed monthly deduction:

```env
PAYROLL_NSSF_MONTHLY_AMOUNT=1080.000000000000000000
```
