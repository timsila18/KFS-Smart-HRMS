<?php

namespace App\Services\Payroll;

use App\Exports\PayrollEmployerRegisterExport;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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

    public function approvalMemos(PayrollRun $run): void
    {
        $run->loadMissing(['items.employee', 'period']);

        $memos = $run->items
            ->groupBy(fn ($item): string => $item->employee?->employer ?: 'KFS')
            ->map(fn (Collection $items, string $employer): array => $this->memoData($run, $employer, $items))
            ->filter(fn (array $memo): bool => $memo['staff_count'] > 0)
            ->values();

        if ($memos->isEmpty()) {
            return;
        }

        $path = "payroll/{$run->uuid}/memos/payroll-approval-memos.pdf";
        $pdf = Pdf::loadView('exports.payroll-approval-memos', ['run' => $run, 'memos' => $memos])->setPaper('a4');

        Storage::disk('public')->put($path, $pdf->output());
        $this->record($run, 'payroll_approval_memos', $path, 'application/pdf');
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

    private function memoData(PayrollRun $run, string $employer, Collection $items): array
    {
        $staffCount = $items->pluck('employee_id')->unique()->count();
        $grossPay = (float) $items->where('amount', '>', 0)->sum('amount');
        $nssf = $staffCount * (float) config('kfs.payroll_memos.employer_nssf_monthly', 1080);
        $housing = $grossPay * (float) config('kfs.payroll_memos.employer_housing_levy_rate', 0.015);
        $nita = $staffCount * (float) config('kfs.payroll_memos.employer_nita_monthly', 50);
        $total = $nssf + $housing + $nita + $grossPay;
        $periodDate = $run->period?->starts_on ? $run->period->starts_on->copy() : now();
        $meta = config("kfs.payroll_memos.employers.{$employer}", []);

        return [
            'employer' => $employer,
            'from' => config('kfs.payroll_memos.from', 'DEPUTY DIRECTOR, HRM & DEVELOPMENT'),
            'through' => $meta['through'] ?? null,
            'ref_no' => ($meta['ref_no'] ?? 'HRA/4/KFS').' ('.$run->id.')',
            'date' => now()->format('jS F, Y'),
            'period_subject' => Str::upper($periodDate->format('F Y')),
            'period_sentence' => $periodDate->format('F, Y'),
            'subject_suffix' => $meta['subject_suffix'] ?? 'CONTRACT',
            'description' => $meta['description'] ?? 'contractual employees',
            'staff_count' => $staffCount,
            'staff_words' => Str::headline($this->numberToWords($staffCount)),
            'employer_nssf_formatted' => number_format($nssf, 2),
            'employer_housing_levy_formatted' => number_format($housing, 2),
            'employer_nita_formatted' => number_format($nita, 2),
            'gross_pay_formatted' => number_format($grossPay, 2),
            'total_formatted' => number_format($total, 2),
            'prepared_by' => config('kfs.payroll_memos.prepared_by', 'P.L TIALAL'),
            'initials' => config('kfs.payroll_memos.initials', 'PK/vvm'),
            'enclosure' => $meta['enclosure'] ?? null,
        ];
    }

    private function numberToWords(int $number): string
    {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        if ($number < 20) {
            return $ones[$number] ?: 'zero';
        }

        if ($number < 100) {
            return trim($tens[intdiv($number, 10)].' '.$ones[$number % 10]);
        }

        if ($number < 1000) {
            return trim($ones[intdiv($number, 100)].' hundred '.($number % 100 ? 'and '.$this->numberToWords($number % 100) : ''));
        }

        return trim($this->numberToWords(intdiv($number, 1000)).' thousand '.($number % 1000 ? $this->numberToWords($number % 1000) : ''));
    }
}
