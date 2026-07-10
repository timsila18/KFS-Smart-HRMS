<?php

namespace Tests\Feature\Reports;

use App\Models\ReportCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_is_available_to_authorized_users(): void
    {
        $user = $this->authorizedUser(['reports.view']);
        ReportCatalog::query()->create(['code' => 'PAYROLL_SUMMARY', 'name' => 'Payroll Summary', 'module' => 'payroll', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/reports')
            ->assertOk();
    }

    public function test_report_preview_returns_dataset_page(): void
    {
        $user = $this->authorizedUser(['reports.view']);
        ReportCatalog::query()->create(['code' => 'EMPLOYEE_REGISTER', 'name' => 'Employee Register', 'module' => 'employees', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/reports/EMPLOYEE_REGISTER')
            ->assertOk();
    }

    private function authorizedUser(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return $user;
    }
}
