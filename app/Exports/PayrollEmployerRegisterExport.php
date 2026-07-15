<?php

namespace App\Exports;

use App\Models\PayrollRun;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PayrollEmployerRegisterExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly PayrollRun $run)
    {
    }

    public function sheets(): array
    {
        $this->run->loadMissing(['items.employee.station', 'items.employee.department', 'items.payCode', 'period']);

        $codeColumns = $this->run->items
            ->pluck('payCode.code')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $employeeRows = $this->run->items
            ->groupBy('employee_id')
            ->map(function (Collection $items) use ($codeColumns): array {
                $employee = $items->first()->employee;
                $gross = (float) $items->where('amount', '>', 0)->sum('amount');
                $deductions = abs((float) $items->where('amount', '<', 0)->sum('amount'));
                $lineItems = $items->groupBy(fn ($item): string => (string) $item->payCode?->code)
                    ->map(fn (Collection $group): float => (float) $group->sum('amount'));

                $row = [
                    'payroll_period' => $this->run->period?->code,
                    'run_number' => $this->run->run_number,
                    'employer' => $employee?->employer ?: 'KFS',
                    'employee_number' => $employee?->employee_number,
                    'employee_name' => $employee?->full_name,
                    'station' => $employee?->station?->name,
                    'department' => $employee?->department?->name,
                    'gross_pay' => $gross,
                    'total_deductions' => $deductions,
                    'net_pay' => $gross - $deductions,
                ];

                foreach ($codeColumns as $code) {
                    $row[$code] = $lineItems->get($code, 0);
                }

                return $row;
            })
            ->values();

        $employers = collect(config('kfs.employers', ['KFS']))
            ->merge($employeeRows->pluck('employer')->filter())
            ->unique()
            ->values();

        return $employers
            ->map(fn (string $employer): PayrollEmployerRegisterSheet => new PayrollEmployerRegisterSheet(
                $employer,
                $employeeRows->where('employer', $employer)->values(),
                $codeColumns,
            ))
            ->all();
    }
}
