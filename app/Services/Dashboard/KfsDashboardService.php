<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use Throwable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KfsDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        try {
            return Cache::remember('dashboard.summary', (int) config('kfs-dashboard.summary_cache_seconds', 300), fn (): array => $this->buildSummary());
        } catch (Throwable $exception) {
            report($exception);

            return $this->buildSummary();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(): array
    {
        $today = CarbonImmutable::today();
        $contractsCutoff = $today->addDays((int) config('kfs-dashboard.contract_expiry_window_days', 90));

        return [
            'cards' => [
                'employees' => [
                    'label' => 'Employees',
                    'value' => $this->countEmployees(),
                    'meta' => 'Active employee register',
                    'trend' => $this->countEmployeesJoinedThisMonth(),
                ],
                'payrollReady' => [
                    'label' => 'Payroll Ready',
                    'value' => $this->countPayrollReadyEmployees(),
                    'meta' => 'Bank and salary assignment complete',
                    'trend' => $this->currentPayrollPeriodLabel(),
                ],
                'contractsExpiring' => [
                    'label' => 'Contracts Expiring',
                    'value' => $this->countContractsExpiring($today, $contractsCutoff),
                    'meta' => "Within {$today->diffInDays($contractsCutoff)} days",
                    'trend' => $contractsCutoff->toFormattedDateString(),
                ],
                'employeesOnLeave' => [
                    'label' => 'Employees on Leave',
                    'value' => $this->countEmployeesOnLeave($today),
                    'meta' => 'Approved leave today',
                    'trend' => $today->toFormattedDateString(),
                ],
            ],
            'charts' => [
                'monthlyPayroll' => $this->monthlyPayrollChart(),
                'employeeDistribution' => $this->employeeDistributionChart(),
                'leaveStatistics' => $this->leaveStatisticsChart(),
            ],
            'payrollCalendar' => $this->payrollCalendar(),
            'notifications' => $this->notifications(),
            'quickActions' => $this->quickActions(),
        ];
    }

    private function countEmployees(): int
    {
        return (int) DB::table('employees')
            ->whereNull('deleted_at')
            ->where('employment_status', 'active')
            ->count();
    }

    private function countEmployeesJoinedThisMonth(): string
    {
        $count = DB::table('employees')
            ->whereNull('deleted_at')
            ->whereBetween('hire_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        return "{$count} joined this month";
    }

    private function countPayrollReadyEmployees(): int
    {
        return (int) DB::table('employees')
            ->whereNull('employees.deleted_at')
            ->where('employees.employment_status', 'active')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('employee_salary_assignments')
                    ->whereColumn('employee_salary_assignments.employee_id', 'employees.id')
                    ->whereNull('employee_salary_assignments.deleted_at')
                    ->where('employee_salary_assignments.status', 'active')
                    ->whereDate('employee_salary_assignments.effective_from', '<=', now()->toDateString())
                    ->where(function ($query): void {
                        $query->whereNull('employee_salary_assignments.effective_to')
                            ->orWhereDate('employee_salary_assignments.effective_to', '>=', now()->toDateString());
                    });
            })
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('employee_bank_accounts')
                    ->whereColumn('employee_bank_accounts.employee_id', 'employees.id')
                    ->whereNull('employee_bank_accounts.deleted_at')
                    ->where('employee_bank_accounts.is_primary', true);
            })
            ->count();
    }

    private function currentPayrollPeriodLabel(): string
    {
        $period = DB::table('payroll_periods')
            ->whereNull('deleted_at')
            ->whereDate('starts_on', '<=', now()->toDateString())
            ->whereDate('ends_on', '>=', now()->toDateString())
            ->first(['code', 'status']);

        return $period ? "{$period->code} {$period->status}" : 'No open period';
    }

    private function countContractsExpiring(CarbonImmutable $today, CarbonImmutable $cutoff): int
    {
        return (int) DB::table('contracts')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->whereBetween('end_date', [$today->toDateString(), $cutoff->toDateString()])
            ->count();
    }

    private function countEmployeesOnLeave(CarbonImmutable $today): int
    {
        return (int) DB::table('leave_requests')
            ->whereNull('deleted_at')
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->distinct('employee_id')
            ->count('employee_id');
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function monthlyPayrollChart(): array
    {
        $rows = DB::table('payroll_runs')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->whereNull('payroll_runs.deleted_at')
            ->whereNull('payroll_periods.deleted_at')
            ->whereDate('payroll_periods.starts_on', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->orderBy('payroll_periods.starts_on')
            ->get(['payroll_periods.starts_on', 'payroll_runs.net_total'])
            ->groupBy(fn ($row): string => CarbonImmutable::parse($row->starts_on)->format('M Y'))
            ->map(fn ($group): float => (float) $group->sum('net_total'));

        return [
            'labels' => $rows->keys()->values()->all(),
            'values' => $rows->values()->all(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function employeeDistributionChart(): array
    {
        $rows = DB::table('employees')
            ->leftJoin('stations', 'stations.id', '=', 'employees.station_id')
            ->whereNull('employees.deleted_at')
            ->where('employees.employment_status', 'active')
            ->selectRaw("coalesce(stations.region, 'Unassigned') as label, count(*) as value")
            ->groupByRaw("coalesce(stations.region, 'Unassigned')")
            ->orderByDesc('value')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'values' => $rows->pluck('value')->map(fn ($value): int => (int) $value)->values()->all(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function leaveStatisticsChart(): array
    {
        $rows = DB::table('leave_requests')
            ->leftJoin('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
            ->whereNull('leave_requests.deleted_at')
            ->whereYear('leave_requests.start_date', now()->year)
            ->selectRaw("coalesce(leave_types.name, 'Other') as label, sum(leave_requests.requested_days) as value")
            ->groupByRaw("coalesce(leave_types.name, 'Other')")
            ->orderByDesc('value')
            ->get();

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'values' => $rows->pluck('value')->map(fn ($value): float => (float) $value)->values()->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function payrollCalendar(): array
    {
        return DB::table('payroll_periods')
            ->whereNull('deleted_at')
            ->whereDate('pay_date', '>=', now()->startOfMonth()->toDateString())
            ->orderBy('pay_date')
            ->limit(6)
            ->get(['uuid', 'code', 'starts_on', 'ends_on', 'pay_date', 'status'])
            ->map(fn ($period): array => [
                'uuid' => $period->uuid,
                'code' => $period->code,
                'range' => CarbonImmutable::parse($period->starts_on)->format('d M').' - '.CarbonImmutable::parse($period->ends_on)->format('d M Y'),
                'payDate' => CarbonImmutable::parse($period->pay_date)->toFormattedDateString(),
                'status' => $period->status,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function notifications(): array
    {
        return DB::table('notifications')
            ->whereNull('deleted_at')
            ->latest()
            ->limit(8)
            ->get(['uuid', 'channel', 'subject', 'body', 'read_at', 'created_at'])
            ->map(fn ($notification): array => [
                'uuid' => $notification->uuid,
                'channel' => $notification->channel,
                'subject' => $notification->subject ?: 'System notification',
                'body' => $notification->body,
                'isRead' => $notification->read_at !== null,
                'createdAt' => CarbonImmutable::parse($notification->created_at)->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function quickActions(): array
    {
        return [
            ['label' => 'Register Employee', 'description' => 'Create a new employee record', 'href' => '/employees/create', 'permission' => 'employees.create'],
            ['label' => 'Run Payroll', 'description' => 'Prepare the active payroll period', 'href' => '/payroll/runs/create', 'permission' => 'payroll.create'],
            ['label' => 'Approve Leave', 'description' => 'Review pending leave applications', 'href' => '/leave/approvals', 'permission' => 'leave.approve'],
            ['label' => 'Export Report', 'description' => 'Generate HR or payroll reports', 'href' => '/reports', 'permission' => 'reports.export'],
        ];
    }
}
