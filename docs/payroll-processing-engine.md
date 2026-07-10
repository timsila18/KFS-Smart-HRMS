# Payroll Processing Engine

The payroll processing engine is built as a service-layer pipeline.

## Lifecycle

- Open payroll run
- Import payroll adjustments
- Calculate payroll
- Preview payroll
- Approve payroll
- Lock payroll
- Reverse locked payroll
- Generate payslips
- Generate P9
- Generate bank files
- Generate statutory reports

## Configuration-Driven Calculations

Calculations are driven by `pay_codes` configuration:

- `calculation_method`
- `default_amount`
- `default_rate`
- `calculation_rules`

Supported methods:

- `fixed`
- `percentage_of_basic`
- `percentage_of_gross`
- `formula`
- `manual`

Formula examples are stored in the database as JSON:

```json
{
  "method": "formula",
  "expression": "basic_salary * 10 / 100"
}
```

The engine evaluates formulas through `FormulaEvaluator`, not PHP `eval`, and uses only variables supplied by the payroll context.

Salary proration uses exact calendar-day fractions from the payroll period, employee hire date, and salary assignment effective dates. Formula and proration results are not rounded to two decimal places before storage; payroll amount columns store 18 decimal places.

NSSF is configured as a fixed monthly deduction using `PAYROLL_NSSF_MONTHLY_AMOUNT`, currently defaulting to `1080.000000000000000000`.

## Primary Files

- `app/Services/Payroll/FormulaEvaluator.php`
- `app/Services/Payroll/PayrollCalculationEngine.php`
- `app/Services/Payroll/PayrollProcessingService.php`
- `app/Services/Payroll/PayrollOutputService.php`
- `app/Http/Controllers/Payroll/PayrollProcessingController.php`
- `resources/js/Pages/Payroll/Processing/Index.tsx`
- `resources/js/Pages/Payroll/Processing/Show.tsx`
