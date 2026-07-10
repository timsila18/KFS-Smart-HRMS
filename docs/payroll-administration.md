# Payroll Administration

Payroll Administration is a configurable master-data module for KFS Smart HRMS.

## Design

The module does not hardcode payroll values into payroll processing logic. Administrators manage payroll components and institutions as database records.

## Payroll Components

Payroll components are stored in `pay_codes` and support:

- Unlimited earning types
- Unlimited deduction types
- Component grouping and subtype classification
- Fixed, percentage, formula, and manual calculation methods
- Taxable and pensionable flags
- Recurring or one-off behavior
- Membership requirement flags
- JSON calculation rules for future payroll engines

Seeded configurable earning examples include Basic Salary, House Allowance, Commuter, Extraneous, Special Salary, Acting Allowance, Leave Allowance, Transfer Allowance, Responsibility Allowance, Risk Allowance, Bonus, Arrears, and Overtime.

Seeded configurable deduction examples include PAYE, SHA, NSSF, Affordable Housing Levy, HELB, GOK Housing, Overpayment, Imprest Recovery, Salary Advance, Additional Medical Cover Recovery, Pension, Voluntary Pension, NSSF Voluntary, KEFSSWA, SACCO, Loans, Insurance, Bank Loans, Mortgage, Car Loan, and Emergency Loan.

## Institutions and Products

`payroll_institutions` supports unlimited SACCOs, welfare funds, banks, insurers, government agencies, and pension providers.

`payroll_institution_products` supports institution-specific loans, mortgage products, car loans, emergency loans, insurance products, welfare contributions, SACCO contributions, and recoveries.

Asili Sacco is seeded as a configurable institution, not coded into business logic.

## Primary Files

- `database/migrations/2026_07_10_030000_add_payroll_administration.php`
- `database/seeders/PayrollAdministrationSeeder.php`
- `app/Models/PayCode.php`
- `app/Models/PayrollInstitution.php`
- `app/Models/PayrollInstitutionProduct.php`
- `app/Http/Controllers/Payroll/PayrollAdministrationController.php`
- `app/Services/Payroll/PayrollAdministrationService.php`
- `resources/js/Pages/Payroll/Admin/Index.tsx`
