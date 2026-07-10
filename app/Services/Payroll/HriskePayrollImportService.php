<?php

namespace App\Services\Payroll;

use App\Models\BankBranch;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use SplFileObject;

class HriskePayrollImportService
{
    public function importBranchRegister(string $path): array
    {
        $count = 0;

        foreach ($this->csvRows($path) as $row) {
            $bankCode = $this->value($row, ['BankCode', 'bank_code']);
            $branchCode = $this->value($row, ['BranchAreaCode', 'BranchCode', 'branch_code']);
            $bankName = $this->value($row, ['BankName', 'bank_name']);
            $branchName = $this->value($row, ['BranchName', 'branch_name']);

            if (! $bankCode || ! $branchCode || ! $bankName || ! $branchName) {
                continue;
            }

            BankBranch::query()->updateOrCreate(
                ['bank_code' => $bankCode, 'branch_code' => $branchCode],
                ['bank_name' => $bankName, 'branch_name' => $branchName, 'is_active' => true, 'metadata' => ['source' => 'hriske_branch_register']]
            );
            $count++;
        }

        return ['branches_imported' => $count];
    }

    public function importIndividualPaymentBreakdown(string $path): array
    {
        $updated = 0;
        $missingEmployees = 0;

        DB::transaction(function () use ($path, &$updated, &$missingEmployees): void {
            foreach ($this->csvRows($path) as $row) {
                $payrollNumber = $this->value($row, ['PayrollNum']);
                $employee = $payrollNumber ? Employee::query()->where('employee_number', $payrollNumber)->first() : null;

                if (! $employee) {
                    $missingEmployees++;
                    continue;
                }

                $bankName = $this->value($row, ['BankName']);
                $branchName = $this->value($row, ['BranchName']);
                $accountNumber = $this->value($row, ['AccountNum']);

                if (! $accountNumber) {
                    continue;
                }

                $branch = $this->matchBranch($bankName, $branchName);

                $employee->bankAccounts()->updateOrCreate(
                    ['account_number' => $accountNumber],
                    [
                        'bank_branch_id' => $branch?->id,
                        'bank_name' => $bankName,
                        'bank_code' => $branch?->bank_code,
                        'branch_name' => $branchName,
                        'branch_code' => $branch?->branch_code,
                        'account_name' => $this->value($row, ['names']) ?: $employee->full_name,
                        'is_primary' => true,
                        'metadata' => [
                            'source' => 'hriske_individual_payment_breakdown',
                            'votecode' => $this->value($row, ['Votecode']),
                            'paymonth' => $this->value($row, ['Paymonth']),
                            'tax_pin' => $this->value($row, ['TaxPIN']),
                            'id_number' => $this->value($row, ['IDNum']),
                        ],
                    ]
                );

                $updated++;
            }
        });

        return ['bank_accounts_updated' => $updated, 'missing_employees' => $missingEmployees];
    }

    private function matchBranch(?string $bankName, ?string $branchName): ?BankBranch
    {
        if (! $bankName || ! $branchName) {
            return null;
        }

        return BankBranch::query()
            ->whereRaw('lower(bank_name) = lower(?)', [$bankName])
            ->whereRaw('lower(branch_name) = lower(?)', [$branchName])
            ->first()
            ?: BankBranch::query()
                ->whereRaw('lower(bank_name) like lower(?)', ['%'.$bankName.'%'])
                ->whereRaw('lower(branch_name) like lower(?)', ['%'.str_replace(['KCB - ', 'COOP Bank - ', 'Equity - '], '', $branchName).'%'])
                ->first();
    }

    private function csvRows(string $path): iterable
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $headers = [];

        foreach ($file as $index => $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if ($index === 0) {
                $headers = array_map(fn ($value) => trim((string) $value), $row);
                continue;
            }

            yield array_combine($headers, array_pad($row, count($headers), null)) ?: [];
        }
    }

    private function value(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return null;
    }
}
