<?php

namespace App\Services\Ess;

use App\Models\Employee;
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
            ->with(['station', 'department', 'jobPosition', 'contacts', 'bankAccounts', 'dependants', 'documents', 'contracts.employmentType', 'contracts.contractType'])
            ->where('user_id', $user->id)
            ->first();
    }

    public function dashboard(User $user): array
    {
        $employee = $this->employeeFor($user);

        if (! $employee) {
            return ['employee' => null, 'summary' => [], 'notifications' => [], 'requests' => []];
        }

        return [
            'employee' => $this->employeeSummary($employee),
            'summary' => [
                'payslips' => DB::table('payslips')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
                'leave_requests' => DB::table('leave_requests')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
                'training' => DB::table('training_enrollments')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
                'documents' => DB::table('employee_documents')->where('employee_id', $employee->id)->whereNull('deleted_at')->count(),
            ],
            'notifications' => $this->notifications($user),
            'requests' => $this->requests($employee),
        ];
    }

    public function profile(User $user): array
    {
        $employee = $this->employeeFor($user);

        return [
            'employee' => $employee ? $this->employeeSummary($employee) : null,
            'contacts' => $employee?->contacts ?? [],
            'bank_accounts' => $employee?->bankAccounts ?? [],
            'dependants' => $employee?->dependants ?? [],
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

    public function leave(User $user): array { return $this->tableRows($user, 'leave_requests', ['uuid','start_date','end_date','requested_days','status','reason']); }
    public function training(User $user): array { return $this->tableRows($user, 'training_enrollments', ['uuid','status','score','completed_on']); }
    public function performance(User $user): array { return $this->tableRows($user, 'appraisal_reviews', ['uuid','review_stage','status','overall_score']); }
    public function payrollHistory(User $user): array { return $this->payslips($user); }
    public function contracts(User $user): array { $employee = $this->employeeFor($user); return $employee?->contracts?->values()->all() ?? []; }
    public function documents(User $user): array { $employee = $this->employeeFor($user); return $employee?->documents?->values()->all() ?? []; }
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
    public function requests(Employee $employee): array { return EssRequest::query()->where('employee_id', $employee->id)->latest()->get()->toArray(); }

    public function createRequest(User $user, array $data): EssRequest
    {
        $employee = $this->employeeFor($user);
        abort_unless($employee, 422, 'No employee profile is linked to this account.');

        return DB::transaction(function () use ($employee, $data): EssRequest {
            $requestType = $data['request_type'];
            $payload = $data['payload'] ?? [];

            if ($requestType === 'leave') {
                $this->createLeaveRequest($employee, $payload, $data['remarks'] ?? null);
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

    private function createLeaveRequest(Employee $employee, array $payload, ?string $remarks): LeaveRequest
    {
        $startDate = Carbon::parse($payload['start_date']);
        $endDate = Carbon::parse($payload['end_date']);
        $leaveType = LeaveType::query()->where('code', $payload['leave_type_code'] ?? 'ANNUAL')->firstOrFail();
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

    private function singleApproverId(): int
    {
        $approver = User::role('hr-manager')->where('status', 'active')->first()
            ?? User::role('hr-admin')->where('status', 'active')->first()
            ?? User::query()->where('status', 'active')->first();

        abort_unless($approver, 422, 'No leave approver has been configured.');

        return $approver->id;
    }
}
