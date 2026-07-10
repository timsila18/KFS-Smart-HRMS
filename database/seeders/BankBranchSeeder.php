<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use SplFileObject;

class BankBranchSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/hriske_bank_branches_2025.csv');

        if (! is_file($path)) {
            return;
        }

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

            $data = array_combine($headers, array_pad($row, count($headers), null)) ?: [];
            $bankCode = trim((string) ($data['BankCode'] ?? ''));
            $branchCode = trim((string) ($data['BranchAreaCode'] ?? ''));

            if ($bankCode === '' || $branchCode === '') {
                continue;
            }

            DB::table('bank_branches')->updateOrInsert(
                ['bank_code' => $bankCode, 'branch_code' => $branchCode],
                [
                    'bank_name' => trim((string) ($data['BankName'] ?? '')),
                    'branch_name' => trim((string) ($data['BranchName'] ?? '')),
                    'is_active' => true,
                    'metadata' => json_encode(['source' => 'hriske_branchreportexport2025']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
