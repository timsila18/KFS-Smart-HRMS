<?php

namespace App\Console\Commands;

use App\Exports\GenericReportExport;
use App\Models\ReportCatalog;
use App\Models\ReportRun;
use App\Services\Reports\ReportDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class RunScheduledReports extends Command
{
    protected $signature = 'reports:run-scheduled {--limit=25}';

    protected $description = 'Generate due scheduled KFS Smart HRMS reports.';

    public function handle(ReportDataService $reports): int
    {
        $catalogs = ReportCatalog::query()
            ->where('is_active', true)
            ->where('is_schedulable', true)
            ->whereNotNull('schedule_frequency')
            ->where(fn ($query) => $query->whereNull('next_run_at')->orWhere('next_run_at', '<=', now()))
            ->orderBy('next_run_at')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($catalogs as $catalog) {
            $dataset = $reports->dataset($catalog->code);
            $basename = str($catalog->code)->lower()->replace('_', '-')->append('-'.now()->format('Ymd-His').'.xlsx')->toString();
            $path = 'reports/'.$catalog->code.'/'.$basename;

            Excel::store(new GenericReportExport($basename, $dataset['columns'], $dataset['rows']), $path);

            ReportRun::query()->create([
                'report_catalog_id' => $catalog->id,
                'user_id' => null,
                'status' => 'completed',
                'parameters' => [
                    'filters' => [],
                    'format' => 'scheduled-excel',
                    'recipients' => $catalog->schedule_recipients ?? [],
                ],
                'file_path' => $path,
                'completed_at' => now(),
            ]);

            $catalog->update(['next_run_at' => $this->nextRunAt((string) $catalog->schedule_frequency)]);
            $this->info("Generated {$catalog->code}: {$path}");
        }

        $this->info("Scheduled reports processed: {$catalogs->count()}");

        return self::SUCCESS;
    }

    private function nextRunAt(string $frequency): ?Carbon
    {
        return match (config("reports.schedule_frequencies.{$frequency}.interval")) {
            'day' => now()->addDay()->startOfDay(),
            'week' => now()->addWeek()->startOfWeek(),
            'month' => now()->addMonthNoOverflow()->startOfMonth(),
            'quarter' => now()->addMonthsNoOverflow(3)->startOfQuarter(),
            default => null,
        };
    }
}
