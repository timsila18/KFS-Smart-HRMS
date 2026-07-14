<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->accounts() as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            $roleId = DB::table('roles')->where('name', $account['role'])->where('guard_name', 'web')->value('id');

            if ($roleId) {
                DB::table('model_has_roles')->updateOrInsert(
                    ['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $user->id, 'station_id' => null],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            if (($account['employee_number'] ?? null) !== null) {
                DB::table('employees')->updateOrInsert(
                    ['employee_number' => $account['employee_number']],
                    [
                        'user_id' => $user->id,
                        'station_id' => DB::table('stations')->where('code', 'HQ')->value('id'),
                        'department_id' => DB::table('departments')->where('code', 'HR')->value('id'),
                        'job_position_id' => DB::table('job_positions')->where('code', 'HRM')->value('id'),
                        'first_name' => 'ESS',
                        'middle_name' => null,
                        'last_name' => 'Employee',
                        'gender' => 'unspecified',
                        'employment_status' => 'active',
                        'hire_date' => now()->startOfYear()->toDateString(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function accounts(): array
    {
        return [
            [
                'name' => 'HR Admin',
                'email' => env('KFS_SEED_HR_ADMIN_EMAIL', 'hr.admin@kfs.go.ke'),
                'password' => env('KFS_SEED_HR_ADMIN_PASSWORD', 'KfsAdmin@2026'),
                'role' => 'hr-admin',
            ],
            [
                'name' => 'HR Manager',
                'email' => env('KFS_SEED_HR_MANAGER_EMAIL', 'hr.manager@kfs.go.ke'),
                'password' => env('KFS_SEED_HR_MANAGER_PASSWORD', 'KfsManager@2026'),
                'role' => 'hr-manager',
            ],
            [
                'name' => 'HR Payroll Operator',
                'email' => env('KFS_SEED_HR_PAYROLL_OPERATOR_EMAIL', 'payroll.operator@kfs.go.ke'),
                'password' => env('KFS_SEED_HR_PAYROLL_OPERATOR_PASSWORD', 'KfsPayroll@2026'),
                'role' => 'hr-payroll-operator',
            ],
            [
                'name' => 'ESS Employee',
                'email' => env('KFS_SEED_ESS_EMAIL', 'ess.employee@kfs.go.ke'),
                'password' => env('KFS_SEED_ESS_PASSWORD', 'KfsEss@2026'),
                'role' => 'employee',
                'employee_number' => env('KFS_SEED_ESS_EMPLOYEE_NUMBER', 'KFS-ESS-001'),
            ],
        ];
    }
}
