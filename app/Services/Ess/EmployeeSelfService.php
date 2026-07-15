<?php

namespace App\Services\Ess;

use App\Models\Employee;
use App\Models\BankBranch;
use App\Models\EmployeeBankAccount;
use App\Models\EssRequest;
use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeSelfService
{
    public function employeeFor(User $user): ?Employee
    {
        return Employee::query()
            ->with(['station', 'department', 'jobPosition', 'contacts', 'bankAccounts.branch', 'dependants', 'documents', 'contracts.employmentType', 'contracts.contractType'])
            ->where('user_id', $user->id)
            ->first();
    }

    public function dashboard(User $user): array
    {
        $employee = $this->employeeFor($user);

        if (! $employee) {
            return [
                'employee' => null,
                'summary' => [],
                'employment' => [],
                'latestPayslip' => null,
                'leaveBalance' => ['available' => 0, 'upcoming' => 0],
                'profileCompletion' => 0,
                'notifications' => [],
                'requests' => [],
                'workspaceCards' => $this->workspaceCards(),
            ];
        }

        return [
            'employee' => $this->employeeSummary($employee),
            'summary' => [
                'payslips' => DB::table('payslips')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
                'leave_requests' => DB::table('leave_requests')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
                'training' => DB::table('training_enrollments')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
                'documents' => DB::table('employee_documents')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
                'messages' => DB::table('notifications')->where('user_id', $user->id)->whereNull('deleted_at')->count(),
                'attendance' => DB::table('attendance_records')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
            ],
            'employment' => $this->employmentDetails($employee),
            'latestPayslip' => $this->latestPayslip($employee),
            'leaveBalance' => $this->leaveBalance($employee),
            'profileCompletion' => $this->profileCompletion($employee),
            'notifications' => $this->notifications($user),
            'requests' => $this->requests($employee),
            'workspaceCards' => $this->workspaceCards(),
        ];
    }

    public function profile(User $user): array
    {
        $employee = $this->employeeFor($user);

        return [
            'employee' => $employee ? $this->employeeSummary($employee) : null,
            'contacts' => $employee?->contacts ?? [],
            'bank_accounts' => $employee?->bankAccounts?->map(fn (EmployeeBankAccount $account): array => [
                'uuid' => $account->uuid,
                'bank_branch_id' => $account->bank_branch_id,
                'bank_name' => $account->bank_name,
                'branch_name' => $account->branch_name,
                'bank_code' => $account->bank_code,
                'branch_code' => $account->branch_code,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'is_primary' => $account->is_primary,
            ])->values() ?? [],
            'dependants' => $employee?->dependants ?? [],
            'bank_branches' => $this->bankBranches(),
        ];
    }

    public function payslips(User $user): array
    {
        $employee = $this->employeeFor($user);
        if (! $employee) return [];

        return DB::table('payslips')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->leftJoin('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->where('payslips.employee_id', $employee->id)
            ->whereNull('payslips.deleted_at')
            ->orderByDesc('payslips.id')
            ->get(['payslips.uuid', 'payslips.payslip_number', 'payslips.gross_pay', 'payslips.total_deductions', 'payslips.net_pay', 'payslips.file_path', 'payroll_periods.code as period'])
            ->map(fn ($row) => [
                'uuid' => $row->uuid,
                'number' => $row->payslip_number,
                'period' => $row->period,
                'gross' => (float) $row->gross_pay,
                'deductions' => (float) $row->total_deductions,
                'net' => (float) $row->net_pay,
                'url' => $row->file_path ? Storage::disk('public')->url($row->file_path) : null,
            ])
            ->all();
    }

    public function p9(User $user): array
    {
        $employee = $this->employeeFor($user);
        if (! $employee) return [];

        return DB::table('payroll_run_items')
            ->join('pay_codes', 'pay_codes.id', '=', 'payroll_run_items.pay_code_id')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_run_items.payroll_run_id')
            ->leftJoin('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->where('payroll_run_items.employee_id', $employee->id)
            ->whereNull('payroll_run_items.deleted_at')
            ->selectRaw("coalesce(payroll_periods.code, payroll_runs.run_number) as period, sum(case when payroll_run_items.amount > 0 and pay_codes.is_taxable then payroll_run_items.amount else 0 end) as taxable_pay, sum(case when pay_codes.component_subtype = 'paye' then abs(payroll_run_items.amount) else 0 end) as paye")
            ->groupByRaw("coalesce(payroll_periods.code, payroll_runs.run_number)")
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => ['period' => $row->period, 'taxable_pay' => (float) $row->taxable_pay, 'paye' => (float) $row->paye])
            ->all();
    }

    public function leave(User $user): array
    {
        return collect($this->tableRows($user, 'leave_requests', ['uuid','start_date','end_date','requested_days','status','reason']))
            ->map(fn ($row): array => array_merge((array) $row, ['url' => "/ess/leave/{$row->uuid}/form"]))
            ->all();
    }
    public function training(User $user): array { return $this->tableRows($user, 'training_enrollments', ['uuid','status','score','completed_on']); }
    public function performance(User $user): array
    {
        return collect($this->tableRows($user, 'appraisal_reviews', ['uuid','review_stage','status','overall_score']))
            ->map(fn ($row): array => array_merge((array) $row, ['url' => "/ess/performance/{$row->uuid}/form"]))
            ->all();
    }
    public function payrollHistory(User $user): array { return $this->payslips($user); }
    public function contracts(User $user): array { $employee = $this->employeeFor($user); return $employee?->contracts?->values()->all() ?? []; }
    public function documents(User $user): array { $employee = $this->employeeFor($user); return $employee?->documents?->values()->all() ?? []; }
    public function serviceDocuments(User $user): array
    {
        $employee = $this->employeeFor($user);

        return collect($employee?->contracts?->values()->all() ?? [])
            ->map(fn ($contract) => [
                'uuid' => $contract['uuid'] ?? $contract->uuid ?? null,
                'document' => 'Service contract',
                'reference' => $contract['contract_number'] ?? $contract->contract_number ?? null,
                'status' => $contract['status'] ?? $contract->status ?? null,
                'effective_from' => $contract['start_date'] ?? $contract->start_date ?? null,
                'effective_to' => $contract['end_date'] ?? $contract->end_date ?? null,
            ])
            ->values()
            ->all();
    }

    public function attendance(User $user): array
    {
        return $this->tableRows($user, 'attendance_records', ['uuid','attendance_date','clock_in_at','clock_out_at','worked_hours','status']);
    }

    public function loansAndDeductions(User $user): array
    {
        $employee = $this->employeeFor($user);
        if (! $employee) return [];

        return DB::table('payroll_run_items')
            ->join('pay_codes', 'pay_codes.id', '=', 'payroll_run_items.pay_code_id')
            ->leftJoin('payroll_runs', 'payroll_runs.id', '=', 'payroll_run_items.payroll_run_id')
            ->leftJoin('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->where('payroll_run_items.employee_id', $employee->id)
            ->where('payroll_run_items.amount', '<', 0)
            ->whereNull('payroll_run_items.deleted_at')
            ->orderByDesc('payroll_run_items.id')
            ->limit(60)
            ->get([
                'payroll_run_items.uuid',
                'payroll_periods.code as period',
                'pay_codes.name as deduction',
                'pay_codes.component_subtype as category',
                'payroll_run_items.amount',
            ])
            ->map(fn ($row) => [
                'uuid' => $row->uuid,
                'period' => $row->period,
                'deduction' => $row->deduction,
                'category' => $row->category,
                'amount' => abs((float) $row->amount),
            ])
            ->all();
    }

    public function settings(User $user): array
    {
        return [[
            'uuid' => $user->uuid,
            'setting' => 'Account access',
            'value' => $user->email,
            'status' => $user->status,
            'action' => 'Use Change Password to update your KFS Smart HRMS access credentials.',
        ]];
    }

    public function messages(User $user): array { return $this->notifications($user); }
    public function notices(User $user): array { return $this->notifications($user); }

    public function notifications(User $user): array
    {
        return DB::table('notifications')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->latest()
            ->limit(30)
            ->get(['uuid','channel','subject','body','read_at','created_at'])
            ->map(fn ($row) => ['uuid'=>$row->uuid,'channel'=>$row->channel,'subject'=>$row->subject,'body'=>$row->body,'is_read'=>$row->read_at !== null,'created_at'=>$row->created_at])
            ->all();
    }
    public function requests(Employee $employee): array
    {
        return EssRequest::query()
            ->where('employee_id', $employee->id)
            ->latest()
            ->get()
            ->map(function (EssRequest $request): array {
                $row = $request->toArray();
                $payload = is_array($row['payload'] ?? null) ? $row['payload'] : [];

                if (($row['request_type'] ?? null) === 'leave' && ! empty($payload['leave_request_uuid'])) {
                    $row['url'] = "/ess/leave/{$payload['leave_request_uuid']}/form";
                }

                return $row;
            })
            ->all();
    }

    public function updateBankDetails(User $user, array $data): EmployeeBankAccount
    {
        $employee = $this->employeeFor($user);
        abort_unless($employee, 422, 'No employee profile is linked to this account.');

        $branch = BankBranch::query()
            ->where('is_active', true)
            ->where('id', $data['bank_branch_id'])
            ->firstOrFail();

        return DB::transaction(function () use ($employee, $branch, $data): EmployeeBankAccount {
            EmployeeBankAccount::query()
                ->where('employee_id', $employee->id)
                ->update(['is_primary' => false]);

            return EmployeeBankAccount::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'account_number' => $data['account_number']],
                [
                    'bank_branch_id' => $branch->id,
                    'bank_name' => $branch->bank_name,
                    'bank_code' => $branch->bank_code,
                    'branch_name' => $branch->branch_name,
                    'branch_code' => $branch->branch_code,
                    'account_name' => $data['account_name'],
                    'is_primary' => true,
                    'metadata' => ['source' => 'ess_profile_update'],
                ]
            );
        });
    }

    public function createRequest(User $user, array $data): EssRequest
    {
        $employee = $this->employeeFor($user);
        abort_unless($employee, 422, 'No employee profile is linked to this account.');

        return DB::transaction(function () use ($employee, $data): EssRequest {
            $requestType = $data['request_type'];
            $payload = $data['payload'] ?? [];

            if ($requestType === 'leave') {
                $leaveRequest = $this->createLeaveRequest($employee, $payload, $data['remarks'] ?? null);
                $payload['leave_request_uuid'] = $leaveRequest->uuid;
            }

            return EssRequest::query()->create([
                'employee_id' => $employee->id,
                'request_type' => $requestType,
                'status' => 'submitted',
                'payload' => $payload,
                'remarks' => $data['remarks'] ?? null,
            ]);
        });
    }

    private function tableRows(User $user, string $table, array $columns): array
    {
        $employee = $this->employeeFor($user);
        if (! $employee) return [];

        return DB::table($table)->where('employee_id', $employee->id)->whereNull('deleted_at')->latest('id')->get($columns)->toArray();
    }

    private function bankBranches(): array
    {
        return BankBranch::query()
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->orderBy('branch_name')
            ->limit(5000)
            ->get(['id', 'bank_name', 'branch_name', 'bank_code', 'branch_code'])
            ->map(fn (BankBranch $branch): array => [
                'id' => $branch->id,
                'label' => "{$branch->bank_name} - {$branch->branch_name}",
                'bank_name' => $branch->bank_name,
                'branch_name' => $branch->branch_name,
                'bank_code' => $branch->bank_code,
                'branch_code' => $branch->branch_code,
            ])
            ->all();
    }

    private function employeeSummary(Employee $employee): array
    {
        return [
            'uuid' => $employee->uuid,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->full_name,
            'status' => $employee->employment_status,
            'station' => $employee->station?->name,
            'department' => $employee->department?->name,
            'position' => $employee->jobPosition?->title,
            'hire_date' => $employee->hire_date?->toDateString(),
            'photo_url' => $employee->photo_path ? Storage::disk('public')->url($employee->photo_path) : null,
        ];
    }

    private function employmentDetails(Employee $employee): array
    {
        $contract = $employee->contracts?->sortByDesc('start_date')->first();

        return [
            'staff_number' => $employee->employee_number,
            'service_station' => $employee->station?->name ?? 'Unassigned',
            'directorate' => $employee->department?->name ?? 'Unassigned',
            'designation' => $employee->jobPosition?->title ?? 'Staff',
            'employment_type' => $contract?->employmentType?->name ?? 'Not captured',
            'reporting_officer' => 'To be assigned',
            'service_status' => ucfirst((string) $employee->employment_status),
        ];
    }

    private function latestPayslip(Employee $employee): ?array
    {
        $row = DB::table('payslips')
            ->leftJoin('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->leftJoin('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->where('payslips.employee_id', $employee->id)
            ->whereNull('payslips.deleted_at')
            ->orderByDesc('payslips.id')
            ->first(['payslips.uuid', 'payslips.net_pay', 'payroll_periods.code as period']);

        return $row ? ['uuid' => $row->uuid, 'period' => $row->period ?? 'Latest', 'net' => (float) $row->net_pay] : null;
    }

    private function leaveBalance(Employee $employee): array
    {
        $approvedDays = (float) DB::table('leave_requests')
            ->where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('requested_days');

        $upcoming = (int) DB::table('leave_requests')
            ->where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->whereIn('status', ['submitted', 'approved'])
            ->whereDate('start_date', '>=', now()->toDateString())
            ->count();

        return [
            'available' => max(0, 30 - $approvedDays),
            'upcoming' => $upcoming,
        ];
    }

    private function profileCompletion(Employee $employee): int
    {
        $checks = [
            filled($employee->employee_number),
            filled($employee->first_name),
            filled($employee->last_name),
            filled($employee->station_id),
            filled($employee->department_id),
            filled($employee->job_position_id),
            $employee->contacts->isNotEmpty(),
            $employee->bankAccounts->isNotEmpty(),
            $employee->dependants->isNotEmpty(),
            $employee->documents->isNotEmpty(),
        ];

        return (int) round((collect($checks)->filter()->count() / count($checks)) * 100);
    }

    private function workspaceCards(): array
    {
        return [
            ['title' => 'My Dashboard', 'href' => '/ess', 'description' => 'Today’s service summary, notices, leave, and payroll status.'],
            ['title' => 'My Leave', 'href' => '/ess/leave', 'description' => 'Apply, track balances, and monitor approval status.'],
            ['title' => 'My Payslips', 'href' => '/ess/payslips', 'description' => 'View and download payroll history.'],
            ['title' => 'My Documents', 'href' => '/ess/documents', 'description' => 'Review statutory identifiers, bank details, and service records.'],
            ['title' => 'Messages', 'href' => '/ess/messages', 'description' => 'Read notices from HR, payroll, and station administration.'],
            ['title' => 'Notifications', 'href' => '/ess/notifications', 'description' => 'Catch approvals, payroll alerts, and updates quickly.'],
            ['title' => 'My Profile', 'href' => '/ess/profile', 'description' => 'Review statutory, bank, next-of-kin, and contact details.'],
            ['title' => 'Issued Documents', 'href' => '/ess/service-documents', 'description' => 'Open contracts, posting letters, and service records.'],
            ['title' => 'Service Policies', 'href' => '/ess/notices', 'description' => 'Open KFS circulars, notices, manuals, and shared HR forms.'],
            ['title' => 'Performance', 'href' => '/ess/performance', 'description' => 'Follow targets, appraisals, and supervisor feedback.'],
            ['title' => 'Duty Attendance', 'href' => '/ess/attendance', 'description' => 'Review attendance records captured for duty days.'],
            ['title' => 'Loans & Deductions', 'href' => '/ess/loans-deductions', 'description' => 'Track statutory, SACCO, loan, and welfare deductions.'],
            ['title' => 'My Requests', 'href' => '/ess/requests', 'description' => 'Submit leave and personal HR service requests.'],
            ['title' => 'My Settings', 'href' => '/ess/settings', 'description' => 'Review account access and password guidance.'],
        ];
    }

    private function createLeaveRequest(Employee $employee, array $payload, ?string $remarks): LeaveRequest
    {
        $startDate = Carbon::parse($payload['start_date']);
        $endDate = Carbon::parse($payload['end_date']);
        $leaveCode = $payload['leave_type_code'] ?? 'ANNUAL';
        $leaveType = LeaveType::query()->firstOrCreate(
            ['code' => $leaveCode],
            [
                'name' => $this->leaveTypeName($leaveCode),
                'is_paid' => ! in_array($leaveCode, ['UNPAID'], true),
                'requires_attachment' => in_array($leaveCode, ['SICK', 'MATERNITY', 'PATERNITY', 'COMPASSIONATE', 'STUDY'], true),
            ]
        );
        $days = $payload['requested_days'] ?? $this->workingDays($startDate, $endDate);

        $leaveRequest = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'requested_days' => $days,
            'status' => 'submitted',
            'reason' => $remarks ?: ($payload['reason'] ?? null),
        ]);

        LeaveApproval::query()->create([
            'leave_request_id' => $leaveRequest->id,
            'approver_id' => $this->singleApproverId(),
            'approval_level' => 1,
            'status' => 'pending',
        ]);

        return $leaveRequest;
    }

    private function workingDays(Carbon $startDate, Carbon $endDate): int
    {
        $days = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (! $date->isWeekend()) {
                $days++;
            }
        }

        return max($days, 1);
    }

    private function leaveTypeName(string $code): string
    {
        return [
            'ANNUAL' => 'Annual Leave',
            'COMPASSIONATE' => 'Compassionate Leave',
            'MATERNITY' => 'Maternity Leave',
            'OFF_DAY' => 'Off Day Request',
            'PATERNITY' => 'Paternity Leave',
            'SICK' => 'Sick Leave',
            'SPECIAL' => 'Special Leave',
            'STUDY' => 'Study Leave',
            'UNPAID' => 'Unpaid Leave',
        ][$code] ?? str($code)->replace('_', ' ')->headline()->toString();
    }

    private function singleApproverId(): int
    {
        $approver = User::role('hr-admin')->where('status', 'active')->first()
            ?? User::role('hr-manager')->where('status', 'active')->first()
            ?? User::query()->where('status', 'active')->first();

        abort_unless($approver, 422, 'No leave approver has been configured.');

        return $approver->id;
    }
}
