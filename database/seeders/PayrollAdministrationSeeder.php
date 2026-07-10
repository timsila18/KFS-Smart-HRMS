<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollAdministrationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->components() as $component) {
            DB::table('pay_codes')->updateOrInsert(
                ['code' => $component['code']],
                $component + [
                    'calculation_method' => $component['calculation_method'] ?? 'manual',
                    'calculation_rules' => json_encode($component['calculation_rules'] ?? []),
                    'is_recurring' => $component['is_recurring'] ?? true,
                    'requires_membership' => $component['requires_membership'] ?? false,
                    'is_active' => true,
                    'sort_order' => $component['sort_order'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        foreach ($this->institutions() as $institution) {
            DB::table('payroll_institutions')->updateOrInsert(
                ['code' => $institution['code']],
                $institution + [
                    'configuration' => json_encode([]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function components(): array
    {
        return [
            ['code' => 'BASIC', 'name' => 'Basic Salary', 'pay_code_type' => 'earning', 'component_group' => 'salary', 'component_subtype' => 'basic_salary', 'calculation_method' => 'formula', 'calculation_rules' => ['method' => 'formula', 'expression' => 'basic_salary', 'prorate' => true], 'is_taxable' => true, 'is_pensionable' => true],
            ['code' => 'HOUSE', 'name' => 'House Allowance', 'pay_code_type' => 'earning', 'component_group' => 'allowance', 'component_subtype' => 'house_allowance', 'calculation_method' => 'formula', 'calculation_rules' => ['method' => 'formula', 'expression' => 'house_allowance', 'prorate' => true], 'is_taxable' => true, 'is_pensionable' => false],
            ['code' => 'COMMUTER', 'name' => 'Commuter Allowance', 'pay_code_type' => 'earning', 'component_group' => 'allowance', 'component_subtype' => 'commuter', 'is_taxable' => true, 'is_pensionable' => false],
            ['code' => 'EXTRANEOUS', 'name' => 'Extraneous Allowance', 'pay_code_type' => 'earning', 'component_group' => 'allowance', 'component_subtype' => 'extraneous', 'is_taxable' => true, 'is_pensionable' => false],
            ['code' => 'SPECIAL_SALARY', 'name' => 'Special Salary', 'pay_code_type' => 'earning', 'component_group' => 'salary', 'component_subtype' => 'special_salary', 'is_taxable' => true, 'is_pensionable' => true],
            ['code' => 'ACTING', 'name' => 'Acting Allowance', 'pay_code_type' => 'earning', 'component_group' => 'allowance', 'component_subtype' => 'acting_allowance', 'is_taxable' => true, 'is_pensionable' => false],
            ['code' => 'LEAVE_ALLOWANCE', 'name' => 'Leave Allowance', 'pay_code_type' => 'earning', 'component_group' => 'allowance', 'component_subtype' => 'leave_allowance', 'is_taxable' => true, 'is_pensionable' => false, 'is_recurring' => false],
            ['code' => 'TRANSFER', 'name' => 'Transfer Allowance', 'pay_code_type' => 'earning', 'component_group' => 'allowance', 'component_subtype' => 'transfer_allowance', 'is_taxable' => true, 'is_pensionable' => false, 'is_recurring' => false],
            ['code' => 'RESPONSIBILITY', 'name' => 'Responsibility Allowance', 'pay_code_type' => 'earning', 'component_group' => 'allowance', 'component_subtype' => 'responsibility_allowance', 'is_taxable' => true, 'is_pensionable' => false],
            ['code' => 'RISK', 'name' => 'Risk Allowance', 'pay_code_type' => 'earning', 'component_group' => 'allowance', 'component_subtype' => 'risk_allowance', 'is_taxable' => true, 'is_pensionable' => false],
            ['code' => 'BONUS', 'name' => 'Bonus', 'pay_code_type' => 'earning', 'component_group' => 'bonus', 'component_subtype' => 'bonus', 'is_taxable' => true, 'is_pensionable' => false, 'is_recurring' => false],
            ['code' => 'ARREARS', 'name' => 'Arrears', 'pay_code_type' => 'earning', 'component_group' => 'arrears', 'component_subtype' => 'arrears', 'is_taxable' => true, 'is_pensionable' => false, 'is_recurring' => false],
            ['code' => 'OVERTIME', 'name' => 'Overtime', 'pay_code_type' => 'earning', 'component_group' => 'overtime', 'component_subtype' => 'overtime', 'is_taxable' => true, 'is_pensionable' => false, 'is_recurring' => false],
            ['code' => 'PAYE', 'name' => 'PAYE', 'pay_code_type' => 'deduction', 'component_group' => 'statutory', 'component_subtype' => 'paye', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'SHA', 'name' => 'SHA', 'pay_code_type' => 'deduction', 'component_group' => 'statutory', 'component_subtype' => 'sha', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'NSSF', 'name' => 'NSSF', 'pay_code_type' => 'deduction', 'component_group' => 'statutory', 'component_subtype' => 'nssf', 'calculation_method' => 'fixed', 'calculation_rules' => ['method' => 'fixed', 'amount' => config('payroll-admin.statutory_defaults.nssf_monthly_amount')], 'default_amount' => config('payroll-admin.statutory_defaults.nssf_monthly_amount'), 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'AHL', 'name' => 'Affordable Housing Levy', 'pay_code_type' => 'deduction', 'component_group' => 'statutory', 'component_subtype' => 'affordable_housing_levy', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'HELB', 'name' => 'HELB', 'pay_code_type' => 'deduction', 'component_group' => 'departmental', 'component_subtype' => 'helb', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'GOK_HOUSING', 'name' => 'GOK Housing', 'pay_code_type' => 'deduction', 'component_group' => 'departmental', 'component_subtype' => 'gok_housing', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'OVERPAYMENT', 'name' => 'Overpayment Recovery', 'pay_code_type' => 'deduction', 'component_group' => 'departmental', 'component_subtype' => 'overpayment', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'IMPREST', 'name' => 'Imprest Recovery', 'pay_code_type' => 'deduction', 'component_group' => 'departmental', 'component_subtype' => 'imprest_recovery', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'SALARY_ADVANCE', 'name' => 'Salary Advance', 'pay_code_type' => 'deduction', 'component_group' => 'departmental', 'component_subtype' => 'salary_advance', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'MEDICAL_COVER_RECOVERY', 'name' => 'Additional Medical Cover Recovery', 'pay_code_type' => 'deduction', 'component_group' => 'departmental', 'component_subtype' => 'additional_medical_cover_recovery', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'PENSION', 'name' => 'Pension', 'pay_code_type' => 'deduction', 'component_group' => 'pension', 'component_subtype' => 'pension', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'VOL_PENSION', 'name' => 'Voluntary Pension', 'pay_code_type' => 'deduction', 'component_group' => 'pension', 'component_subtype' => 'voluntary_pension', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'NSSF_VOL', 'name' => 'NSSF Voluntary', 'pay_code_type' => 'deduction', 'component_group' => 'pension', 'component_subtype' => 'nssf_voluntary', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'KEFSSWA', 'name' => 'KEFSSWA', 'pay_code_type' => 'deduction', 'component_group' => 'welfare', 'component_subtype' => 'kefsswa', 'is_taxable' => false, 'is_pensionable' => false, 'requires_membership' => true],
            ['code' => 'SACCO', 'name' => 'SACCO', 'pay_code_type' => 'deduction', 'component_group' => 'sacco', 'component_subtype' => 'sacco', 'is_taxable' => false, 'is_pensionable' => false, 'requires_membership' => true],
            ['code' => 'LOAN', 'name' => 'Loan', 'pay_code_type' => 'deduction', 'component_group' => 'loan', 'component_subtype' => 'loan', 'is_taxable' => false, 'is_pensionable' => false, 'requires_membership' => true],
            ['code' => 'INSURANCE', 'name' => 'Insurance', 'pay_code_type' => 'deduction', 'component_group' => 'insurance', 'component_subtype' => 'insurance', 'is_taxable' => false, 'is_pensionable' => false, 'requires_membership' => true],
            ['code' => 'BANK_LOAN', 'name' => 'Bank Loan', 'pay_code_type' => 'deduction', 'component_group' => 'bank_loan', 'component_subtype' => 'bank_loan', 'is_taxable' => false, 'is_pensionable' => false, 'requires_membership' => true],
            ['code' => 'MORTGAGE', 'name' => 'Mortgage', 'pay_code_type' => 'deduction', 'component_group' => 'bank_loan', 'component_subtype' => 'mortgage', 'is_taxable' => false, 'is_pensionable' => false, 'requires_membership' => true],
            ['code' => 'CAR_LOAN', 'name' => 'Car Loan', 'pay_code_type' => 'deduction', 'component_group' => 'loan', 'component_subtype' => 'car_loan', 'is_taxable' => false, 'is_pensionable' => false, 'requires_membership' => true],
            ['code' => 'EMERGENCY_LOAN', 'name' => 'Emergency Loan', 'pay_code_type' => 'deduction', 'component_group' => 'loan', 'component_subtype' => 'emergency_loan', 'is_taxable' => false, 'is_pensionable' => false, 'requires_membership' => true],
        ];
    }

    private function institutions(): array
    {
        return [
            ['institution_type' => 'sacco', 'code' => 'ASILI_SACCO', 'name' => 'Asili Sacco'],
            ['institution_type' => 'welfare', 'code' => 'KEFSSWA', 'name' => 'KEFSSWA'],
        ];
    }
}
