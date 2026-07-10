<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate_with_allowed_role(): void
    {
        $this->seedRoleAndPermission();
        $user = User::factory()->create(['email' => 'forest.officer@kfs.go.ke']);
        $user->assignRole('hr-officer');

        $response = $this->post('/login', [
            'email' => 'forest.officer@kfs.go.ke',
            'password' => 'Password!2026',
            'remember' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('user_login_histories', [
            'email' => 'forest.officer@kfs.go.ke',
            'status' => 'success',
        ]);
    }

    public function test_users_without_allowed_role_are_rejected(): void
    {
        Role::query()->create(['name' => 'external-auditor', 'guard_name' => 'web']);
        $user = User::factory()->create(['email' => 'external@example.test']);
        $user->assignRole('external-auditor');

        $this->post('/login', [
            'email' => 'external@example.test',
            'password' => 'Password!2026',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('user_login_histories', [
            'email' => 'external@example.test',
            'status' => 'failed',
            'failure_reason' => 'role_not_allowed',
        ]);
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $this->seedRoleAndPermission();
        $user = User::factory()->create();
        $user->assignRole('hr-officer');

        $this->actingAs($user)->put('/password', [
            'current_password' => 'Password!2026',
            'password' => 'NewPassword!2026',
            'password_confirmation' => 'NewPassword!2026',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event' => 'password.changed',
        ]);
    }

    public function test_dashboard_returns_kfs_metrics_payload(): void
    {
        $this->seedRoleAndPermission();
        $user = User::factory()->create();
        $user->assignRole('hr-officer');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('dashboard.cards.employees')
                ->has('dashboard.cards.payrollReady')
                ->has('dashboard.cards.contractsExpiring')
                ->has('dashboard.cards.employeesOnLeave')
                ->has('dashboard.charts.monthlyPayroll')
                ->has('dashboard.charts.employeeDistribution')
                ->has('dashboard.charts.leaveStatistics')
                ->has('dashboard.payrollCalendar')
                ->has('dashboard.notifications')
                ->has('dashboard.quickActions')
            );
    }

    private function seedRoleAndPermission(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'hr-officer', 'guard_name' => 'web']);
        $permission = Permission::query()->firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web'], ['module' => 'dashboard']);
        Permission::query()->firstOrCreate(['name' => 'ess.view', 'guard_name' => 'web'], ['module' => 'ess']);
        $role->givePermissionTo($permission);
    }
}
