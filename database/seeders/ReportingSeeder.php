<?php

namespace Database\Seeders;

use App\Services\Reports\ReportDefinitionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ReportDefinitionRegistry::DEFINITIONS as $code => [$name, $module]) {
            DB::table('report_catalogs')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'module' => $module,
                    'parameters_schema' => json_encode([
                        'filters' => ['period_id', 'department_id', 'date_from', 'date_to'],
                        'outputs' => ['preview', 'excel', 'pdf', 'chart'],
                    ]),
                    'is_active' => true,
                    'is_schedulable' => false,
                    'schedule_frequency' => null,
                    'schedule_recipients' => json_encode([]),
                    'next_run_at' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
