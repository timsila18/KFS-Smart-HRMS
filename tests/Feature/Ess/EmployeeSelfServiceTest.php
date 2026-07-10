<?php

namespace Tests\Feature\Ess;

use App\Models\Employee;
use App\Models\EssRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_view_ess_dashboard(): void
    {
        [$user] = $this->essUser();

        $this->actingAs($user)
            ->get('/ess')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ess/Dashboard')
                ->has('employee')
                ->has('summary')
                ->has('notifications')
                ->has('requests')
            );
    }

    public function test_employee_can_submit_ess_request(): void
    {
        [$user, $employee] = $this->essUser();

        $this->actingAs($user)
            ->post('/ess/requests', [
                'request_type' => 'profile_update',
                'remarks' => 'Please update my postal address.',
                'payload' => ['postal_code' => '00100'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $employee->id,
            'request_type' => 'profile_update',
            'status' => 'submitted',
        ]);
    }

    public function test_employee_can_view_requests_page(): void
    {
        [$user, $employee] = $this->essUser();
        EssRequest::query()->create(['employee_id' => $employee->id, 'request_type' => 'leave_query', 'status' => 'submitted']);

        $this->actingAs($user)
            ->get('/ess/requests')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ess/Requests')
                ->has('rows', 1)
            );
    }

    private function essUser(): array
    {
        $role = Role::query()->firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        foreach (['ess.view', 'ess.create'] as $permission) {
            $model = Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web'], ['module' => 'ess']);
            $role->givePermissionTo($model);
        }

        $user = User::factory()->create();
        $user->assignRole($role);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'employee_number' => 'KFS-ESS-001']);

        return [$user, $employee];
    }
}
