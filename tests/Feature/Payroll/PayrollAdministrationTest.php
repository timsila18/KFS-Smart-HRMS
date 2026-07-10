<?php

namespace Tests\Feature\Payroll;

use App\Models\PayCode;
use App\Models\PayrollInstitution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_administration_page_can_be_viewed(): void
    {
        $this->actingAs($this->payrollAdmin())
            ->get('/payroll/administration')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payroll/Admin/Index')
                ->has('components')
                ->has('institutions')
                ->has('lookups')
            );
    }

    public function test_administrator_can_create_payroll_component(): void
    {
        $this->actingAs($this->payrollAdmin())
            ->post('/payroll/administration/components', [
                'code' => 'FOREST_RISK',
                'name' => 'Forest Risk Allowance',
                'pay_code_type' => 'earning',
                'component_group' => 'allowance',
                'component_subtype' => 'risk_allowance',
                'calculation_method' => 'fixed',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_recurring' => true,
                'requires_membership' => false,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pay_codes', [
            'code' => 'FOREST_RISK',
            'component_group' => 'allowance',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payroll.component.created']);
    }

    public function test_administrator_can_create_sacco_and_product(): void
    {
        $user = $this->payrollAdmin();
        $this->actingAs($user)
            ->post('/payroll/administration/institutions', [
                'institution_type' => 'sacco',
                'code' => 'ASILI_TEST',
                'name' => 'Asili Test Sacco',
                'is_active' => true,
            ])
            ->assertRedirect();

        $institution = PayrollInstitution::query()->where('code', 'ASILI_TEST')->firstOrFail();
        $component = PayCode::query()->create([
            'code' => 'SACCO_TEST',
            'name' => 'SACCO Test',
            'pay_code_type' => 'deduction',
            'component_group' => 'sacco',
            'calculation_method' => 'manual',
            'calculation_rules' => [],
        ]);

        $this->actingAs($user)
            ->post("/payroll/administration/institutions/{$institution->uuid}/products", [
                'pay_code_id' => $component->id,
                'product_type' => 'loan',
                'code' => 'ASILI_DEV_LOAN',
                'name' => 'Asili Development Loan',
                'calculation_method' => 'fixed',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_institution_products', [
            'code' => 'ASILI_DEV_LOAN',
            'payroll_institution_id' => $institution->id,
        ]);
    }

    private function payrollAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'payroll-manager', 'guard_name' => 'web']);

        foreach (['payroll.view', 'payroll.create', 'payroll.update', 'payroll.delete'] as $permission) {
            $model = Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web'], ['module' => 'payroll']);
            $role->givePermissionTo($model);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
