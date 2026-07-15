<?php

namespace App\Services\Payroll;

use App\Exports\PayrollEmployerRegisterExport;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PayrollOutputService
{
    public function payslips(PayrollRun $run): void
    {
        $run->loadMissing(['items.payCode', 'items.employee', 'period']);
        $groups = $run->items->groupBy('employee_id');

        foreach ($groups as $employeeId => $items) {
            $gross = (float) $items->where('amount', '>', 0)->sum('amount');
            $deductions = abs((float) $items->where('amount', '<', 0)->sum('amount'));
            $payslipNumber = "{$run->run_number}-{$employeeId}";
            $path = "payroll/{$run->uuid}/payslips/{$payslipNumber}.pdf";

            $pdf = Pdf::loadView('exports.payslip', ['run' => $run, 'items' => $items, 'employee' => $items->first()->employee]);
            Storage::disk('public')->put($path, $pdf->output());

            Payslip::query()->updateOrCreate(
                ['payslip_number' => $payslipNumber],
                [
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employeeId,
                    'gross_pay' => $gross,
                    'total_deductions' => $deductions,
                    'net_pay' => $gross - $deductions,
                    'file_path' => $path,
                ]
            );
        }

        $this->record($run, 'payslips', "payroll/{$run->uuid}/payslips", 'application/pdf');
    }

    public function p9(PayrollRun $run): void
    {
        $path = "payroll/{$run->uuid}/p9/p9-summary.pdf";
        $pdf = Pdf::loadView('exports.p9', ['run' => $run->loadMissing('items.payCode', 'items.employee')]);
        Storage::disk('public')->put($path, $pdf->output());
        $this->record($run, 'p9', $path, 'application/pdf');
    }

    public function bankFile(PayrollRun $run): void
    {
        $run->loadMissing('items.employee.bankAccounts');
        $rows = ["employee_number,employee_name,bank_name,account_number,amount"];

        foreach ($run->items->groupBy('employee_id') as $items) {
            $employee = $items->first()->employee;
            $bank = $employee->bankAccounts->firstWhere('is_primary', true);
            $amount = (float) $items->sum('amount');
            $rows[] = implode(',', [
                $employee->employee_number,
                Str::of($employee->full_name)->replace(',', ' '),
                Str::of($bank?->bank_name ?? '')->replace(',', ' '),
                $bank?->account_number ?? '',
                number_format($amount, 2, '.', ''),
            ]);
        }

        $path = "payroll/{$run->uuid}/bank/bank-file.csv";
        Storage::disk('public')->put($path, implode("\n", $rows));
        $this->record($run, 'bank_file', $path, 'text/csv');
    }

    public function payrollRegisterByEmployer(PayrollRun $run): void
    {
        $path = "payroll/{$run->uuid}/reports/payroll-register-by-employer.xlsx";

        Excel::store(new PayrollEmployerRegisterExport($run), $path, 'public');
        $this->record($run, 'payroll_register_by_employer', $path, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function statutoryReports(PayrollRun $run): void
    {
        $run->loadMissing('items.payCode', 'items.employee');
        $statutory = $run->items
            ->filter(fn ($item): bool => $item->payCode?->component_group === 'statutory')
            ->groupBy(fn ($item): string => $item->payCode->code);

        foreach ($statutory as $code => $items) {
            $path = "payroll/{$run->uuid}/statutory/{$code}.csv";
            $rows = ["employee_number,employee_name,code,amount"];
            foreach ($items as $item) {
                $rows[] = implode(',', [
                    $item->employee->employee_number,
                    Str::of($item->employee->full_name)->replace(',', ' '),
                    $code,
                    number_format(abs((float) $item->amount), 2, '.', ''),
                ]);
            }
            Storage::disk('public')->put($path, implode("\n", $rows));
            $this->record($run, 'statutory_'.$code, $path, 'text/csv');
        }
    }

    private function record(PayrollRun $run, string $type, string $path, string $mime): void
    {
        $run->outputFiles()->updateOrCreate(
            ['output_type' => $type, 'file_path' => $path],
            ['file_name' => basename($path), 'mime_type' => $mime, 'metadata' => []]
        );
    }
}
