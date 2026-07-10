<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportDataService
{
    public function dataset(string $code, array $filters = []): array
    {
        $rows = match ($code) {
            'PAYROLL_REGISTER' => $this->payrollLines($filters),
            'PAYROLL_SUMMARY' => $this->payrollSummary($filters),
            'EMPLOYEE_REGISTER' => $this->employeeRegister($filters),
            'DEPARTMENT_PAYROLL' => $this->departmentPayroll($filters),
            'P9' => $this->p9($filters),
            'BANK_SCHEDULE' => $this->bankSchedule($filters),
            'HRISKE_WAGEBILL' => $this->hriskeWagebill($filters),
            'HRISKE_EARNINGS_DEDUCTIONS' => $this->hriskeEarningsDeductions($filters),
            'HRISKE_DEDUCTION_POSTINGS' => $this->hriskeDeductionPostings($filters),
            'HRISKE_INDIVIDUAL_PAYMENT_BREAKDOWN' => $this->hriskeIndividualPaymentBreakdown($filters),
            'HRISKE_BANK_SUMMARY' => $this->hriskeBankSummary($filters),
            'BANK_BRANCH_REGISTER' => $this->bankBranchRegister($filters),
            'VARIANCE_REPORT' => $this->variance($filters),
            'CONTRACT_EXPIRY' => $this->contractExpiry($filters),
            'LEAVE_REPORT' => $this->leave($filters),
            'PERFORMANCE_REPORT' => $this->performance($filters),
            'TRAINING_REPORT' => $this->training($filters),
            default => $this->payCodeReport($code, $filters),
        };

        return [
            'columns' => $rows->flatMap(fn ($row) => array_keys((array) $row))->unique()->values()->all(),
            'rows' => $rows->map(fn ($row) => (array) $row)->values()->all(),
            'charts' => $this->charts($rows),
        ];
    }

    private function payrollLines(array $filters): Collection
    {
        return DB::table('payroll_run_items')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_run_items.payroll_run_id')
            ->join('employees', 'employees.id', '=', 'payroll_run_items.employee_id')
            ->join('pay_codes', 'pay_codes.id', '=', 'payroll_run_items.pay_code_id')
            ->leftJoin('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.starts_on', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.ends_on', '<=', $date))
            ->whereNull('payroll_run_items.deleted_at')
            ->get(['payroll_runs.run_number', 'payroll_periods.code as period', 'employees.employee_number', DB::raw("concat(employees.first_name,' ',employees.last_name) as employee"), 'pay_codes.code', 'pay_codes.name', 'pay_codes.pay_code_type', 'payroll_run_items.amount']);
    }

    private function payrollSummary(array $filters): Collection
    {
        return DB::table('payroll_runs')->leftJoin('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_period_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.starts_on', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.ends_on', '<=', $date))
            ->whereNull('payroll_runs.deleted_at')
            ->get(['payroll_runs.run_number', 'payroll_periods.code as period', 'payroll_runs.status', 'payroll_runs.gross_total', 'payroll_runs.deduction_total', 'payroll_runs.net_total']);
    }

    private function employeeRegister(array $filters): Collection
    {
        return DB::table('employees')->leftJoin('stations', 'stations.id', '=', 'employees.station_id')->leftJoin('departments', 'departments.id', '=', 'employees.department_id')->leftJoin('job_positions', 'job_positions.id', '=', 'employees.job_position_id')
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('employees.hire_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('employees.hire_date', '<=', $date))
            ->whereNull('employees.deleted_at')
            ->get(['employees.employee_number', DB::raw("concat(employees.first_name,' ',employees.last_name) as employee"), 'employees.employment_status', 'stations.name as station', 'departments.name as department', 'job_positions.title as position', 'employees.hire_date']);
    }

    private function departmentPayroll(array $filters): Collection
    {
        return DB::table('payroll_run_items')->join('employees', 'employees.id', '=', 'payroll_run_items.employee_id')->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_run_items.payroll_run_id')
            ->leftJoin('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.starts_on', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.ends_on', '<=', $date))
            ->selectRaw("coalesce(departments.name,'Unassigned') as department, sum(case when payroll_run_items.amount > 0 then payroll_run_items.amount else 0 end) as gross, abs(sum(case when payroll_run_items.amount < 0 then payroll_run_items.amount else 0 end)) as deductions, sum(payroll_run_items.amount) as net")
            ->groupByRaw("coalesce(departments.name,'Unassigned')")->get();
    }

    private function payCodeReport(string $code, array $filters): Collection
    {
        $map = ['PAYE' => 'paye', 'SHA' => 'sha', 'NSSF' => 'nssf', 'HOUSING_LEVY' => 'affordable_housing_levy', 'HELB' => 'helb', 'KEFSSWA' => 'kefsswa', 'ASILI_SACCO' => 'sacco'];
        return DB::table('payroll_run_items')->join('pay_codes', 'pay_codes.id', '=', 'payroll_run_items.pay_code_id')->join('employees', 'employees.id', '=', 'payroll_run_items.employee_id')->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_run_items.payroll_run_id')->leftJoin('payroll_periods', 'payroll_periods.id', '=', 'payroll_runs.payroll_period_id')
            ->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.starts_on', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.ends_on', '<=', $date))
            ->where('pay_codes.component_subtype', $map[$code] ?? strtolower($code))
            ->get(['payroll_periods.code as period', 'employees.employee_number', DB::raw("concat(employees.first_name,' ',employees.last_name) as employee"), 'pay_codes.name', 'payroll_run_items.amount']);
    }

    private function p9(array $filters): Collection { return $this->payCodeReport('PAYE', $filters); }
    private function bankSchedule(array $filters): Collection { return DB::table('payslips')->join('employees','employees.id','=','payslips.employee_id')->join('payroll_runs','payroll_runs.id','=','payslips.payroll_run_id')->leftJoin('employee_bank_accounts','employee_bank_accounts.employee_id','=','employees.id')->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id', $id))->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))->where('employee_bank_accounts.is_primary',true)->get(['employees.employee_number', DB::raw("concat(employees.first_name,' ',employees.last_name) as employee"), 'employee_bank_accounts.bank_name', 'employee_bank_accounts.account_number', 'payslips.net_pay']); }
    private function hriskeWagebill(array $filters): Collection { $summary = $this->payrollSummary($filters); $nssf = $this->payCodeTotal('nssf', $filters); $housing = $this->payCodeTotal('affordable_housing_levy', $filters); return $summary->map(fn ($row) => ['run_number'=>$row->run_number,'period'=>$row->period,'officers_included'=>$this->officerCount($filters),'earnings'=>(string)$row->gross_total,'deductions'=>(string)$row->deduction_total,'net_actual'=>(string)$row->net_total,'nssf_dues'=>(string)$nssf,'housing_levy'=>(string)$housing,'wagebill'=>(string)((float)$row->gross_total + (float)$nssf + (float)$housing)]); }
    private function hriskeEarningsDeductions(array $filters): Collection { return $this->payCodeSummary($filters)->map(fn ($row) => ['section'=>$row->pay_code_type === 'earning' ? 'Earnings Summary' : 'Deductions Summary','frequency'=>$row->frequency,'code'=>$row->code,'description'=>$row->name,'total_amount'=>$row->total_amount,'regular_or_effected'=>$row->total_amount,'arrears_or_deferred'=>0]); }
    private function hriskeDeductionPostings(array $filters): Collection { return $this->payCodeSummary($filters, 'deduction')->map(fn ($row) => ['frequency'=>$row->frequency,'deduction_code'=>$row->code,'description'=>$row->name,'deduction_amount'=>$row->total_amount,'commission'=>0,'cheque_amount'=>$row->total_amount]); }
    private function hriskeIndividualPaymentBreakdown(array $filters): Collection { $rows = DB::table('payroll_runs')->join('payroll_run_items','payroll_run_items.payroll_run_id','=','payroll_runs.id')->join('pay_codes','pay_codes.id','=','payroll_run_items.pay_code_id')->join('employees','employees.id','=','payroll_run_items.employee_id')->leftJoin('employee_bank_accounts','employee_bank_accounts.employee_id','=','employees.id')->leftJoin('bank_branches','bank_branches.id','=','employee_bank_accounts.bank_branch_id')->leftJoin('payroll_periods','payroll_periods.id','=','payroll_runs.payroll_period_id')->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id',$id))->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id',$id))->whereNull('payroll_run_items.deleted_at')->orderBy('employees.employee_number')->get(['payroll_periods.code as paymonth','employees.employee_number as payroll_num',DB::raw("concat(employees.first_name,' ',employees.last_name) as names"),'employee_bank_accounts.bank_name','employee_bank_accounts.branch_name','bank_branches.bank_code','bank_branches.branch_code','employee_bank_accounts.account_number','pay_codes.code','payroll_run_items.amount']); return $rows->groupBy('payroll_num')->map(function ($items) { $first = $items->first(); $gross = (float) $items->where('amount','>',0)->sum('amount'); $deductions = abs((float) $items->where('amount','<',0)->sum('amount')); $row = ['paymonth'=>$first->paymonth,'payroll_num'=>$first->payroll_num,'names'=>$first->names,'bank_name'=>$first->bank_name,'branch_name'=>$first->branch_name,'bank_code'=>$first->bank_code,'branch_code'=>$first->branch_code,'account_number'=>$first->account_number,'gross_pay'=>$gross,'total_deductions'=>$deductions,'net_pay'=>$gross - $deductions]; foreach ($items->sortBy('code') as $item) { $row[$item->code] = ($row[$item->code] ?? 0) + abs((float) $item->amount); } return (object) $row; })->values(); }
    private function hriskeBankSummary(array $filters): Collection { return DB::table('payslips')->join('payroll_runs','payroll_runs.id','=','payslips.payroll_run_id')->join('employees','employees.id','=','payslips.employee_id')->leftJoin('employee_bank_accounts','employee_bank_accounts.employee_id','=','employees.id')->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id',$id))->where('employee_bank_accounts.is_primary',true)->selectRaw("count(distinct employees.id) as officers, coalesce(employee_bank_accounts.bank_code,'') as bank_code, coalesce(employee_bank_accounts.bank_name,'Unassigned') as banking_institution, sum(payslips.net_pay) as total_amount")->groupByRaw("coalesce(employee_bank_accounts.bank_code,''), coalesce(employee_bank_accounts.bank_name,'Unassigned')")->orderBy('banking_institution')->get(); }
    private function bankBranchRegister(array $filters): Collection { return DB::table('bank_branches')->whereNull('deleted_at')->orderBy('bank_code')->orderBy('branch_code')->get(['bank_code','branch_code','bank_name','branch_name','is_active']); }
    private function variance(array $filters): Collection { return $this->payrollSummary($filters); }
    private function contractExpiry(array $filters): Collection { return DB::table('contracts')->join('employees','employees.id','=','contracts.employee_id')->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('contracts.end_date', '>=', $date))->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('contracts.end_date', '<=', $date))->whereNull('contracts.deleted_at')->whereNotNull('contracts.end_date')->orderBy('contracts.end_date')->get(['employees.employee_number', DB::raw("concat(employees.first_name,' ',employees.last_name) as employee"), 'contracts.contract_number', 'contracts.end_date', 'contracts.status']); }
    private function leave(array $filters): Collection { return DB::table('leave_requests')->join('employees','employees.id','=','leave_requests.employee_id')->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('leave_requests.start_date', '>=', $date))->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('leave_requests.end_date', '<=', $date))->whereNull('leave_requests.deleted_at')->get(['employees.employee_number', DB::raw("concat(employees.first_name,' ',employees.last_name) as employee"), 'leave_requests.start_date', 'leave_requests.end_date', 'leave_requests.requested_days', 'leave_requests.status']); }
    private function performance(array $filters): Collection { return DB::table('appraisal_reviews')->join('employees','employees.id','=','appraisal_reviews.employee_id')->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))->whereNull('appraisal_reviews.deleted_at')->get(['employees.employee_number', DB::raw("concat(employees.first_name,' ',employees.last_name) as employee"), 'appraisal_reviews.review_stage', 'appraisal_reviews.status', 'appraisal_reviews.overall_score']); }
    private function training(array $filters): Collection { return DB::table('training_enrollments')->join('employees','employees.id','=','training_enrollments.employee_id')->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('employees.department_id', $id))->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('training_enrollments.completed_on', '>=', $date))->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('training_enrollments.completed_on', '<=', $date))->whereNull('training_enrollments.deleted_at')->get(['employees.employee_number', DB::raw("concat(employees.first_name,' ',employees.last_name) as employee"), 'training_enrollments.status', 'training_enrollments.score', 'training_enrollments.completed_on']); }

    private function charts(Collection $rows): array
    {
        if ($rows->isEmpty()) return ['labels' => [], 'values' => []];
        $array = $rows->map(fn ($row) => (array) $row);
        $numeric = collect(array_keys($array->first()))->first(fn ($key) => is_numeric($array->first()[$key] ?? null));
        $label = collect(array_keys($array->first()))->first(fn ($key) => $key !== $numeric);
        return ['labels' => $array->pluck($label)->map(fn ($v) => (string) $v)->values()->all(), 'values' => $numeric ? $array->pluck($numeric)->map(fn ($v) => (float) $v)->values()->all() : []];
    }

    private function payCodeSummary(array $filters, ?string $type = null): Collection
    {
        return DB::table('payroll_run_items')->join('pay_codes','pay_codes.id','=','payroll_run_items.pay_code_id')->join('payroll_runs','payroll_runs.id','=','payroll_run_items.payroll_run_id')->leftJoin('payroll_periods','payroll_periods.id','=','payroll_runs.payroll_period_id')
            ->when($type, fn ($q, $value) => $q->where('pay_codes.pay_code_type', $value))
            ->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id',$id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.starts_on','>=',$date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('payroll_periods.ends_on','<=',$date))
            ->whereNull('payroll_run_items.deleted_at')
            ->selectRaw("count(*) as frequency, pay_codes.code, pay_codes.name, pay_codes.pay_code_type, abs(sum(payroll_run_items.amount)) as total_amount")
            ->groupBy('pay_codes.code','pay_codes.name','pay_codes.pay_code_type')->orderBy('pay_codes.pay_code_type')->orderBy('pay_codes.code')->get();
    }

    private function payCodeTotal(string $subtype, array $filters): float
    {
        return abs((float) DB::table('payroll_run_items')->join('pay_codes','pay_codes.id','=','payroll_run_items.pay_code_id')->join('payroll_runs','payroll_runs.id','=','payroll_run_items.payroll_run_id')->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id',$id))->where('pay_codes.component_subtype',$subtype)->whereNull('payroll_run_items.deleted_at')->sum('payroll_run_items.amount'));
    }

    private function officerCount(array $filters): int
    {
        return (int) DB::table('payroll_run_items')->join('payroll_runs','payroll_runs.id','=','payroll_run_items.payroll_run_id')->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('payroll_runs.payroll_period_id',$id))->whereNull('payroll_run_items.deleted_at')->distinct('employee_id')->count('employee_id');
    }
}
