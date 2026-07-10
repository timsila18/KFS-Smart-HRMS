<?php

namespace App\Console\Commands;

use App\Services\Payroll\HriskePayrollImportService;
use Illuminate\Console\Command;

class ImportHriskePaymentBreakdown extends Command
{
    protected $signature = 'payroll:import-hriske-payment-breakdown {path : HRISKE Detailed Individual Payment Breakdown CSV}';

    protected $description = 'Import HRISKE individual payment breakdown bank details into employee bank accounts.';

    public function handle(HriskePayrollImportService $importer): int
    {
        $result = $importer->importIndividualPaymentBreakdown((string) $this->argument('path'));
        $this->info('Bank accounts updated: '.$result['bank_accounts_updated']);
        $this->warn('Missing employees: '.$result['missing_employees']);

        return self::SUCCESS;
    }
}
