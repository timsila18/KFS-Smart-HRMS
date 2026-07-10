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
        ];
    }
}
