<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayCode;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollCalculationEngine
{
    public function __construct(private readonly FormulaEvaluator $formulas)
    {
    }

    public function calculate(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run): PayrollRun {
            $run->loadMissing('period');
            $run->items()->delete();

            $employees = $this->eligibleEmployees($run);
            $payCodes = PayCode::query()->where('is_active', true)->orderBy('sort_order')->get();

            foreach ($employees as $employee) {
                $context = $this->employeeContext($run, $employee);
                $gross = 0.0;

                foreach ($payCodes->where('pay_code_type', 'earning') as $payCode) {
                    $amount = $this->amountFor($payCode, $context + ['gross' => $gross]);
                    if ($amount > 0) {
                        $gross += $amount;
                        $this->createLine($run, $employee, $payCode, $amount, $context);
                    }
                }

                foreach ($payCodes->where('pay_code_type', 'deduction') as $payCode) {
                    $amount = $this->amountFor($payCode, $context + ['gross' => $gross]);
                    if ($amount > 0) {
                        $this->createLine($run, $employee, $payCode, -abs($amount), $context + ['gross' => $gross]);
                    }
                }

                $this->applyAdjustments($run, $employee);
            }

            $grossTotal = (float) $run->items()->where('amount', '>', 0)->sum('amount');
            $deductionTotal = abs((float) $run->items()->where('amount', '<', 0)->sum('amount'));

            $run->update([
                'status' => 'calculated',
                'processed_at' => now(),
                'gross_total' => $grossTotal,
                'deduction_total' => $deductionTotal,
                'net_total' => $grossTotal - $deductionTotal,
            ]);

            return $run->fresh(['items.payCode', 'items.employee']);
        });
    }

    private function amountFor(PayCode $payCode, array $context): float
    {
        $rules = $payCode->calculation_rules ?? [];
        $method = $rules['method'] ?? $payCode->calculation_method;

        $amount = match ($method) {
            'fixed' => (float) ($rules['amount'] ?? $payCode->default_amount ?? 0),
            'percentage_of_basic' => ((float) ($rules['rate'] ?? $payCode->default_rate ?? 0) / 100) * (float) ($context['basic_salary'] ?? 0),
            'percentage_of_gross' => ((float) ($rules['rate'] ?? $payCode->default_rate ?? 0) / 100) * (float) ($context['gross'] ?? 0),
            'formula' => $this->formulas->evaluate((string) ($rules['expression'] ?? '0'), $context),
            'manual' => 0.0,
            default => 0.0,
        };

        if (($rules['prorate'] ?? false) === true) {
            return $this->prorate((float) $amount, (int) $context['payable_days'], (int) $context['period_days']);
        }

        return (float) $amount;
    }

    private function prorate(float $amount, int $payableDays, int $periodDays): float
    {
        if ($periodDays <= 0 || $payableDays <= 0) {
            return 0.0;
        }

        return ($amount * $payableDays) / $periodDays;
    }

    private function createLine(PayrollRun $run, Employee $employee, PayCode $payCode, float $amount, array $context): void
    {
        $run->items()->create([
            'employee_id' => $employee->id,
            'pay_code_id' => $payCode->id,
            'quantity' => 1,
            'rate' => $payCode->default_rate ?? 0,
            'amount' => $amount,
            'calculation_snapshot' => [
                'pay_code' => $payCode->only(['code', 'name', 'pay_code_type', 'component_group', 'calculation_method', 'calculation_rules']),
                'context' => $context,
            ],
        ]);
    }

    private function applyAdjustments(PayrollRun $run, Employee $employee): void
    {
        PayrollAdjustment::query()
            ->with('payCode')
            ->where('payroll_period_id', $run->payroll_period_id)
            ->where('employee_id', $employee->id)
            ->where('approval_status', 'approved')
            ->get()
            ->each(function (PayrollAdjustment $adjustment) use ($run, $employee): void {
                $sign = $adjustment->payCode?->pay_code_type === 'deduction' ? -1 : 1;
                $this->createLine($run, $employee, $adjustment->payCode, $sign * abs((float) $adjustment->amount), ['source' => 'adjustment']);
            });
    }

    private function eligibleEmployees(PayrollRun $run): Collection
    {
        return Employee::query()
            ->where('employment_status', 'active')
            ->whereHas('salaryAssignments', fn ($query) => $query->where('pay_group_id', $run->pay_group_id)->where('status', 'active'))
            ->with(['salaryAssignments.salaryScaleStep', 'bankAccounts.branch'])
            ->get();
    }

    private function employeeContext(PayrollRun $run, Employee $employee): array
    {
        $salary = $employee->salaryAssignments->first();
        $step = $salary?->salaryScaleStep;
        $periodStart = CarbonImmutable::parse($run->period?->starts_on ?? now()->startOfMonth());
        $periodEnd = CarbonImmutable::parse($run->period?->ends_on ?? now()->endOfMonth());
        $effectiveStart = $periodStart;
        foreach ([$employee->hire_date, $salary?->effective_from] as $date) {
            if ($date && CarbonImmutable::parse($date)->gt($effectiveStart)) {
                $effectiveStart = CarbonImmutable::parse($date);
            }
        }
        $effectiveEnd = $periodEnd;
        if ($salary?->effective_to && CarbonImmutable::parse($salary->effective_to)->lt($effectiveEnd)) {
            $effectiveEnd = CarbonImmutable::parse($salary->effective_to);
        }
        $periodDays = (int) $periodStart->diffInDays($periodEnd) + 1;
        $payableDays = $effectiveStart && $effectiveEnd && $effectiveStart->lte($effectiveEnd)
            ? (int) $effectiveStart->diffInDays($effectiveEnd) + 1
            : 0;

        return [
            'basic_salary' => (float) ($step?->basic_salary ?? 0),
            'house_allowance' => (float) ($step?->house_allowance ?? 0),
            'period_days' => $periodDays,
            'payable_days' => $payableDays,
            'proration_factor' => $periodDays > 0 ? $payableDays / $periodDays : 0,
        ];
    }
}
