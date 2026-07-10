<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\PayCode;
use App\Models\PayGroup;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SalaryScaleStep;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_engine_calculates_from_configured_formula_rules(): void
    {
        $payGroup = PayGroup::query()->create(['code' => 'MONTHLY', 'name' => 'Monthly', 'frequency' => 'monthly']);
        $period = PayrollPeriod::query()->create(['code' => '2026-07', 'starts_on' => '2026-07-01', 'ends_on' => '2026-07-31', 'pay_date' => '2026-07-25', 'status' => 'open']);
        $employee = Employee::factory()->create();
        $step = SalaryScaleStep::query()->create(['salary_scale_id' => null, 'step_number' => 1, 'basic_salary' => 100000, 'house_allowance' => 20000]);
        EmployeeSalaryAssignment::query()->create(['employee_id' => $employee->id, 'salary_scale_step_id' => $step->id, 'pay_group_id' => $payGroup->id, 'effective_from' => '2026-07-01', 'status' => 'active']);
        PayCode::query()->create(['code' => 'BASIC', 'name' => 'Basic Salary', 'pay_code_type' => 'earning', 'component_group' => 'salary', 'calculation_method' => 'formula', 'calculation_rules' => ['method' => 'formula', 'expression' => 'basic_salary'], 'is_active' => true]);
        PayCode::query()->create(['code' => 'PENSION_TEST', 'name' => 'Pension Test', 'pay_code_type' => 'deduction', 'component_group' => 'pension', 'calculation_method' => 'formula', 'calculation_rules' => ['method' => 'formula', 'expression' => 'basic_salary * pension_rate / 100'], 'default_rate' => 0, 'is_active' => true]);

        $deduction = PayCode::query()->where('code', 'PENSION_TEST')->first();
        $deduction->update(['calculation_rules' => ['method' => 'formula', 'expression' => 'basic_salary * 10 / 100']]);

        $run = PayrollRun::query()->create(['payroll_period_id' => $period->id, 'pay_group_id' => $payGroup->id, 'run_number' => 'RUN-1', 'status' => 'draft', 'gross_total' => 0, 'deduction_total' => 0, 'net_total' => 0]);

        app(PayrollCalculationEngine::class)->calculate($run);

        $this->assertDatabaseHas('payroll_runs', ['id' => $run->id, 'gross_total' => 100000, 'deduction_total' => 10000, 'net_total' => 90000]);
        $this->assertDatabaseHas('payroll_run_items', ['payroll_run_id' => $run->id, 'amount' => 100000]);
        $this->assertDatabaseHas('payroll_run_items', ['payroll_run_id' => $run->id, 'amount' => -10000]);
    }

    public function test_engine_prorates_salary_without_two_decimal_rounding(): void
    {
        $payGroup = PayGroup::query()->create(['code' => 'MONTHLY', 'name' => 'Monthly', 'frequency' => 'monthly']);
        $period = PayrollPeriod::query()->create(['code' => '2026-06', 'starts_on' => '2026-06-01', 'ends_on' => '2026-06-30', 'pay_date' => '2026-06-25', 'status' => 'open']);
        $employee = Employee::factory()->create(['hire_date' => '2026-06-11']);
        $step = SalaryScaleStep::query()->create(['salary_scale_id' => null, 'step_number' => 1, 'basic_salary' => 100000, 'house_allowance' => 0]);
        EmployeeSalaryAssignment::query()->create(['employee_id' => $employee->id, 'salary_scale_step_id' => $step->id, 'pay_group_id' => $payGroup->id, 'effective_from' => '2026-06-11', 'status' => 'active']);
        PayCode::query()->create(['code' => 'BASIC', 'name' => 'Basic Salary', 'pay_code_type' => 'earning', 'component_group' => 'salary', 'calculation_method' => 'formula', 'calculation_rules' => ['method' => 'formula', 'expression' => 'basic_salary', 'prorate' => true], 'is_active' => true]);

        $run = PayrollRun::query()->create(['payroll_period_id' => $period->id, 'pay_group_id' => $payGroup->id, 'run_number' => 'RUN-PRORATE', 'status' => 'draft', 'gross_total' => 0, 'deduction_total' => 0, 'net_total' => 0]);

        app(PayrollCalculationEngine::class)->calculate($run);

        $amount = (float) $run->fresh()->items()->first()->amount;
        $this->assertGreaterThan(66666.666666666, $amount);
        $this->assertLessThan(66666.666666667, $amount);
    }

    public function test_payroll_processing_page_can_be_viewed(): void
    {
        $user = $this->payrollUser();

        $this->actingAs($user)
            ->get('/payroll/processing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payroll/Processing/Index')
                ->has('runs')
                ->has('periods')
                ->has('payGroups')
            );
    }

    private function payrollUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'payroll-manager', 'guard_name' => 'web']);
        foreach (['payroll.view', 'payroll.create', 'payroll.update', 'payroll.approve'] as $permission) {
            $model = Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web'], ['module' => 'payroll']);
            $role->givePermissionTo($model);
        }
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
