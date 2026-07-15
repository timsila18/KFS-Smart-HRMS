<?php

namespace App\Services\Reports;

class ReportDefinitionRegistry
{
    public const DEFINITIONS = [
        'PAYROLL_REGISTER' => ['Payroll Register', 'payroll'],
        'PAYROLL_SUMMARY' => ['Payroll Summary', 'payroll'],
        'EMPLOYEE_REGISTER' => ['Employee Register', 'employees'],
        'DEPARTMENT_PAYROLL' => ['Department Payroll', 'payroll'],
        'P9' => ['P9', 'payroll'],
        'PAYE' => ['PAYE', 'statutory'],
        'SHA' => ['SHA', 'statutory'],
        'NSSF' => ['NSSF', 'statutory'],
        'HOUSING_LEVY' => ['Housing Levy', 'statutory'],
        'HELB' => ['HELB', 'deductions'],
        'KEFSSWA' => ['KEFSSWA', 'deductions'],
        'ASILI_SACCO' => ['Asili Sacco', 'deductions'],
        'BANK_SCHEDULE' => ['Bank Schedule', 'payroll'],
        'HRISKE_WAGEBILL' => ['Wagebill', 'payroll'],
        'HRISKE_EARNINGS_DEDUCTIONS' => ['Earnings & Deductions', 'payroll'],
        'HRISKE_DEDUCTION_POSTINGS' => ['Monthly Deduction Postings', 'payroll'],
        'HRISKE_INDIVIDUAL_PAYMENT_BREAKDOWN' => ['Individual Payment Breakdown', 'payroll'],
        'HRISKE_BANK_SUMMARY' => ['Bank Summary', 'payroll'],
        'BANK_BRANCH_REGISTER' => ['Bank Branch Register', 'payroll'],
        'VARIANCE_REPORT' => ['Variance Report', 'payroll'],
        'CONTRACT_EXPIRY' => ['Contract Expiry', 'contracts'],
        'LEAVE_REPORT' => ['Leave Report', 'leave'],
        'PERFORMANCE_REPORT' => ['Performance Report', 'performance'],
        'TRAINING_REPORT' => ['Training Report', 'training'],
    ];

    public function all(): array
    {
        return collect(self::DEFINITIONS)->map(fn ($meta, $code) => ['code' => $code, 'name' => $meta[0], 'module' => $meta[1]])->values()->all();
    }
}
