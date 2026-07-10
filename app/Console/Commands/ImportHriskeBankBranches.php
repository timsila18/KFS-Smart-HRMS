<?php

namespace App\Console\Commands;

use App\Services\Payroll\HriskePayrollImportService;
use Illuminate\Console\Command;

class ImportHriskeBankBranches extends Command
{
    protected $signature = 'payroll:import-hriske-bank-branches {path : CSV file with BankCode, BranchAreaCode, BankName, BranchName}';

    protected $description = 'Import official HRISKE bank and branch code register.';

    public function handle(HriskePayrollImportService $importer): int
    {
        $result = $importer->importBranchRegister((string) $this->argument('path'));
        $this->info('Imported branches: '.$result['branches_imported']);

        return self::SUCCESS;
    }
}
