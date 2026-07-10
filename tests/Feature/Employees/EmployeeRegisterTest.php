<?php

namespace Tests\Feature\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_register_can_be_viewed(): void
    {
        $user = $this->hrUser();
        Employee::factory()->create(['employee_number' => 'KFS-001']);

        $this->actingAs($user)
            ->get('/employees')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Employees/Index')
                ->has('employees.data')
                ->has('lookups')
            );
    }

    public function test_employee_can_be_created_with_register_details(): void
    {
        $user = $this->hrUser();

        $this->actingAs($user)
            ->post('/employees', [
                'profile' => [
                    'employee_number' => 'KFS-2026-001',
                    'first_name' => 'Amina',
                    'last_name' => 'Mwangi',
                    'employment_status' => 'active',
                ],
                'contacts' => [['contact_type' => 'mobile', 'value' => '+254700000000', 'is_primary' => true]],
                'next_of_kin' => [['full_name' => 'Peter Mwangi', 'relationship' => 'Spouse', 'phone' => '+254711111111', 'is_primary' => true]],
                'medical_records' => [['blood_group' => 'O+', 'medical_scheme' => 'KFS Medical']],
                'bank_accounts' => [['bank_name' => 'KCB', 'account_number' => '123456789', 'is_primary' => true]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['employee_number' => 'KFS-2026-001']);
        $this->assertDatabaseHas('employee_next_of_kin', ['full_name' => 'Peter Mwangi']);
        $this->assertDatabaseHas('employee_medical_records', ['blood_group' => 'O+']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee.created']);
    }

    private function hrUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'hr-officer', 'guard_name' => 'web']);
        foreach (['employees.view', 'employees.create', 'employees.update', 'employees.delete', 'employees.export'] as $permission) {
            $model = Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web'], ['module' => 'employees']);
            $role->givePermissionTo($model);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
