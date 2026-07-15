<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KfsHrmsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedSettings();
            $this->seedAccessControl();
            $this->seedOrganization();
            $this->call(KfsStationSeeder::class);
            $this->seedEmployment();
            $this->seedPayroll();
            $this->call(PayrollAdministrationSeeder::class);
            $this->call(InitialAccessSeeder::class);
            $this->call(BankBranchSeeder::class);
            $this->call(ReportingSeeder::class);
            $this->seedLeaveAndAttendance();
            $this->seedReportsAndNotifications();
        });
    }

    private function seedSettings(): void
    {
        foreach ([
            ['code' => 'organization', 'name' => 'Organization', 'sort_order' => 10],
            ['code' => 'payroll', 'name' => 'Payroll', 'sort_order' => 20],
            ['code' => 'leave', 'name' => 'Leave', 'sort_order' => 30],
            ['code' => 'security', 'name' => 'Security', 'sort_order' => 40],
            ['code' => 'notifications', 'name' => 'Notifications', 'sort_order' => 50],
        ] as $group) {
            DB::table('setting_groups')->updateOrInsert(['code' => $group['code']], $this->row($group));
        }

        $groups = DB::table('setting_groups')->pluck('id', 'code');
        foreach ([
            ['group' => 'organization', 'key' => 'organization.name', 'value_type' => 'string', 'value' => 'Kenya Forest Service'],
            ['group' => 'organization', 'key' => 'organization.country_code', 'value_type' => 'string', 'value' => 'KEN'],
            ['group' => 'payroll', 'key' => 'payroll.currency', 'value_type' => 'string', 'value' => 'KES'],
            ['group' => 'payroll', 'key' => 'payroll.frequency', 'value_type' => 'string', 'value' => 'monthly'],
            ['group' => 'leave', 'key' => 'leave.default_calendar', 'value_type' => 'string', 'value' => 'KEN_PUBLIC'],
            ['group' => 'security', 'key' => 'security.password_expiry_days', 'value_type' => 'integer', 'value' => 90],
        ] as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $this->row([
                    'setting_group_id' => $groups[$setting['group']],
                    'value_type' => $setting['value_type'],
                    'value' => json_encode($setting['value']),
                    'is_encrypted' => false,
                    'is_public' => false,
                ])
            );
        }
    }

    private function seedAccessControl(): void
    {
        $modules = [
            'dashboard', 'users', 'roles', 'permissions', 'departments', 'stations',
            'employees', 'contracts', 'payroll', 'leave', 'attendance', 'performance',
            'training', 'ess', 'reports', 'audit', 'notifications', 'settings',
        ];

        foreach ([
            'super-admin',
            'hr-admin',
            'hr-manager',
            'hr-payroll-operator',
            'hr-director',
            'hr-officer',
            'payroll-manager',
            'station-manager',
            'employee',
        ] as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role, 'guard_name' => 'web'],
                $this->row(['scope' => 'system', 'description' => Str::headline($role), 'is_system' => true])
            );
        }

        foreach ($modules as $module) {
            foreach (['view', 'create', 'update', 'approve', 'export', 'delete'] as $action) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => "{$module}.{$action}", 'guard_name' => 'web'],
                    $this->row(['module' => $module, 'description' => Str::headline("{$action} {$module}")])
                );
            }
        }

        $this->assignRolePermissions();
    }

    private function assignRolePermissions(): void
    {
        $allPermissions = DB::table('permissions')->pluck('id', 'name');

        $profiles = [
            'super-admin' => $allPermissions->keys()->all(),
            'hr-admin' => $allPermissions->keys()->all(),
            'hr-manager' => $this->managerPermissions(),
            'hr-payroll-operator' => $this->supervisorPermissions(),
            'hr-director' => $this->managerPermissions(),
            'payroll-manager' => $this->managerPermissions(),
            'hr-officer' => $this->supervisorPermissions(),
            'employee' => $this->permissionsFor([
                'ess' => ['view', 'create'],
                'notifications' => ['view'],
            ]),
        ];

        foreach ($profiles as $roleName => $permissionNames) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');

            foreach ($permissionNames as $permissionName) {
                $permissionId = $allPermissions[$permissionName] ?? null;

                if (! $roleId || ! $permissionId) {
                    continue;
                }

                DB::table('role_has_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    $this->row()
                );
            }
        }
    }

    private function managerPermissions(): array
    {
        return $this->permissionsFor([
            'dashboard' => ['view'],
            'departments' => ['view', 'create', 'update', 'approve', 'export'],
            'stations' => ['view', 'create', 'update', 'approve', 'export'],
            'employees' => ['view', 'create', 'update', 'approve', 'export'],
            'contracts' => ['view', 'create', 'update', 'approve', 'export'],
            'payroll' => ['view', 'create', 'update', 'approve', 'export'],
            'leave' => ['view', 'create', 'update', 'approve', 'export'],
            'attendance' => ['view', 'create', 'update', 'approve', 'export'],
            'performance' => ['view', 'create', 'update', 'approve', 'export'],
            'training' => ['view', 'create', 'update', 'approve', 'export'],
            'ess' => ['view', 'approve'],
            'reports' => ['view', 'create', 'update', 'approve', 'export'],
            'audit' => ['view', 'export'],
            'notifications' => ['view', 'create', 'update', 'approve'],
        ]);
    }

    private function supervisorPermissions(): array
    {
        return $this->permissionsFor([
            'dashboard' => ['view'],
            'employees' => ['view', 'update', 'export'],
            'contracts' => ['view', 'update', 'export'],
            'payroll' => ['view', 'create', 'update', 'export'],
            'leave' => ['view', 'update', 'approve', 'export'],
            'attendance' => ['view', 'create', 'update', 'approve', 'export'],
            'performance' => ['view', 'create', 'update', 'export'],
            'training' => ['view', 'create', 'update', 'export'],
            'ess' => ['view', 'approve'],
            'reports' => ['view', 'export'],
            'notifications' => ['view', 'create', 'update'],
        ]);
    }

    private function permissionsFor(array $modules): array
    {
        return collect($modules)
            ->flatMap(fn (array $actions, string $module) => collect($actions)->map(fn (string $action) => "{$module}.{$action}"))
            ->values()
            ->all();
    }

    private function seedOrganization(): void
    {
        DB::table('departments')->updateOrInsert(['code' => 'HR'], $this->row(['name' => 'Human Resource Management', 'type' => 'directorate', 'is_active' => true]));
        DB::table('departments')->updateOrInsert(['code' => 'FIN'], $this->row(['name' => 'Finance and Accounts', 'type' => 'directorate', 'is_active' => true]));
        DB::table('stations')->updateOrInsert(['code' => 'HQ'], $this->row(['name' => 'KFS Headquarters', 'station_type' => 'headquarters', 'county' => 'Nairobi', 'region' => 'Nairobi', 'is_active' => true]));
        DB::table('stations')->updateOrInsert(['code' => 'CFA-NRB'], $this->row(['name' => 'Nairobi Conservancy', 'station_type' => 'conservancy', 'county' => 'Nairobi', 'region' => 'Central', 'is_active' => true]));
        DB::table('job_grades')->updateOrInsert(['code' => 'KFS-1'], $this->row(['name' => 'KFS Grade 1', 'rank_order' => 1, 'is_active' => true]));
        DB::table('job_grades')->updateOrInsert(['code' => 'KFS-2'], $this->row(['name' => 'KFS Grade 2', 'rank_order' => 2, 'is_active' => true]));
        $gradeId = DB::table('job_grades')->where('code', 'KFS-1')->value('id');
        DB::table('job_positions')->updateOrInsert(['code' => 'HRM'], $this->row(['job_grade_id' => $gradeId, 'title' => 'Human Resource Manager', 'description' => 'Leads HR operations.', 'is_active' => true]));
        DB::table('cost_centres')->updateOrInsert(['code' => 'HQ-HR'], $this->row(['station_id' => DB::table('stations')->where('code', 'HQ')->value('id'), 'name' => 'Headquarters HR', 'is_active' => true]));
    }

    private function seedEmployment(): void
    {
        foreach ([
            ['code' => 'PERM', 'name' => 'Permanent and Pensionable', 'is_pensionable' => true],
            ['code' => 'CONTRACT', 'name' => 'Fixed Term Contract', 'is_pensionable' => false],
            ['code' => 'CASUAL', 'name' => 'Casual', 'is_pensionable' => false],
        ] as $type) {
            DB::table('employment_types')->updateOrInsert(['code' => $type['code']], $this->row($type));
        }

        foreach ([
            ['code' => 'OPEN', 'name' => 'Open Ended', 'default_months' => null, 'requires_end_date' => false],
            ['code' => 'FIXED-12', 'name' => 'Fixed 12 Months', 'default_months' => 12, 'requires_end_date' => true],
            ['code' => 'FIXED-36', 'name' => 'Fixed 36 Months', 'default_months' => 36, 'requires_end_date' => true],
        ] as $type) {
            DB::table('contract_types')->updateOrInsert(['code' => $type['code']], $this->row($type));
        }
    }

    private function seedPayroll(): void
    {
        DB::table('pay_groups')->updateOrInsert(['code' => 'MONTHLY'], $this->row(['name' => 'Monthly Payroll', 'frequency' => 'monthly']));

        foreach ([
            ['code' => 'BASIC', 'name' => 'Basic Salary', 'pay_code_type' => 'earning', 'is_taxable' => true, 'is_pensionable' => true],
            ['code' => 'HOUSE', 'name' => 'House Allowance', 'pay_code_type' => 'earning', 'is_taxable' => true, 'is_pensionable' => false],
            ['code' => 'PAYE', 'name' => 'PAYE', 'pay_code_type' => 'deduction', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'NSSF', 'name' => 'NSSF', 'pay_code_type' => 'deduction', 'is_taxable' => false, 'is_pensionable' => false],
            ['code' => 'SHIF', 'name' => 'Social Health Insurance Fund', 'pay_code_type' => 'deduction', 'is_taxable' => false, 'is_pensionable' => false],
        ] as $code) {
            DB::table('pay_codes')->updateOrInsert(['code' => $code['code']], $this->row($code + ['calculation_rules' => json_encode([])]));
        }

        foreach ([
            ['code' => 'PAYE', 'name' => 'Pay As You Earn', 'deduction_type' => 'tax'],
            ['code' => 'NSSF', 'name' => 'National Social Security Fund', 'deduction_type' => 'social_security'],
            ['code' => 'SHIF', 'name' => 'Social Health Insurance Fund', 'deduction_type' => 'health'],
        ] as $deduction) {
            DB::table('statutory_deductions')->updateOrInsert(
                ['code' => $deduction['code']],
                $this->row($deduction + ['rules' => json_encode([]), 'effective_from' => now()->startOfYear()->toDateString()])
            );
        }

        DB::table('pension_schemes')->updateOrInsert(
            ['code' => 'KFS-PENSION'],
            $this->row(['name' => 'KFS Pension Scheme', 'employee_rate' => 0, 'employer_rate' => 0, 'rules' => json_encode([])])
        );
    }

    private function seedLeaveAndAttendance(): void
    {
        foreach ([
            ['code' => 'ANNUAL', 'name' => 'Annual Leave', 'is_paid' => true, 'requires_attachment' => false],
            ['code' => 'SICK', 'name' => 'Sick Leave', 'is_paid' => true, 'requires_attachment' => true],
            ['code' => 'MATERNITY', 'name' => 'Maternity Leave', 'is_paid' => true, 'requires_attachment' => true],
            ['code' => 'PATERNITY', 'name' => 'Paternity Leave', 'is_paid' => true, 'requires_attachment' => true],
        ] as $type) {
            DB::table('leave_types')->updateOrInsert(['code' => $type['code']], $this->row($type));
        }

        DB::table('holiday_calendars')->updateOrInsert(['code' => 'KEN_PUBLIC'], $this->row(['name' => 'Kenya Public Holidays', 'country_code' => 'KEN', 'is_active' => true]));
        DB::table('shifts')->updateOrInsert(['code' => 'DAY'], $this->row(['name' => 'Day Shift', 'starts_at' => '08:00:00', 'ends_at' => '17:00:00', 'grace_minutes' => 15]));
        DB::table('shift_patterns')->updateOrInsert(['code' => 'MON-FRI'], $this->row(['name' => 'Monday to Friday', 'pattern_rules' => json_encode(['days' => [1, 2, 3, 4, 5]])]));
    }

    private function seedReportsAndNotifications(): void
    {
        foreach ([
            ['code' => 'EMPLOYEE_REGISTER', 'name' => 'Employee Register', 'module' => 'employees'],
            ['code' => 'PAYROLL_SUMMARY', 'name' => 'Payroll Summary', 'module' => 'payroll'],
            ['code' => 'LEAVE_BALANCES', 'name' => 'Leave Balances', 'module' => 'leave'],
            ['code' => 'ATTENDANCE_SUMMARY', 'name' => 'Attendance Summary', 'module' => 'attendance'],
        ] as $report) {
            DB::table('report_catalogs')->updateOrInsert(
                ['code' => $report['code']],
                $this->row($report + ['parameters_schema' => json_encode([]), 'is_active' => true])
            );
        }

        foreach ([
            ['code' => 'LEAVE_REQUEST_SUBMITTED', 'channel' => 'mail', 'subject' => 'Leave request submitted', 'body' => 'A leave request has been submitted for approval.'],
            ['code' => 'PAYSLIP_PUBLISHED', 'channel' => 'mail', 'subject' => 'Payslip published', 'body' => 'Your payslip is available in ESS.'],
        ] as $template) {
            DB::table('notification_templates')->updateOrInsert(
                ['code' => $template['code']],
                $this->row($template + ['variables' => json_encode([]), 'is_active' => true])
            );
        }

        foreach ([
            ['code' => 'EMPLOYEE_FILE', 'name' => 'Employee File', 'allowed_mime_types' => json_encode(['application/pdf', 'image/jpeg', 'image/png']), 'max_size_kb' => 5120],
            ['code' => 'PAYROLL_IMPORT', 'name' => 'Payroll Import', 'allowed_mime_types' => json_encode(['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']), 'max_size_kb' => 10240],
        ] as $type) {
            DB::table('attachment_types')->updateOrInsert(['code' => $type['code']], $this->row($type));
        }
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function row(array $attributes = []): array
    {
        return $attributes + [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
