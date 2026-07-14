<?php

namespace App\Http\Controllers\Reports;

use App\Exports\GenericReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\RunReportRequest;
use App\Models\ReportCatalog;
use App\Models\ReportRun;
use App\Services\Reports\ReportDataService;
use App\Support\SimplePdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportDataService $reports)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Reports/Index', [
            'reports' => ReportCatalog::query()
                ->where('is_active', true)
                ->orderBy('module')
                ->orderBy('name')
                ->get(['id', 'uuid', 'code', 'name', 'module', 'is_schedulable', 'schedule_frequency', 'next_run_at'])
                ->groupBy('module'),
            'latestRuns' => ReportRun::query()
                ->with('report:id,code,name,module')
                ->latest()
                ->limit(12)
                ->get(['id', 'uuid', 'report_catalog_id', 'user_id', 'status', 'parameters', 'file_path', 'completed_at', 'created_at']),
        ]);
    }

    public function show(ReportCatalog $report, RunReportRequest $request): Response
    {
        $dataset = $this->reports->dataset($report->code, $request->filters());

        return Inertia::render('Reports/Show', [
            'report' => $report->only(['id', 'uuid', 'code', 'name', 'module', 'is_schedulable', 'schedule_frequency', 'schedule_recipients', 'next_run_at']),
            'filters' => $request->filters(),
            'dataset' => $dataset,
            'scheduleFrequencies' => config('reports.schedule_frequencies'),
            'periods' => DB::table('payroll_periods')
                ->whereNull('deleted_at')
                ->orderByDesc('starts_on')
                ->limit(36)
                ->get(['id', 'code', DB::raw('code as name')]),
            'departments' => DB::table('departments')->whereNull('deleted_at')->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function export(ReportCatalog $report, RunReportRequest $request, string $format): Responsable|SymfonyResponse
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);
        abort_unless($request->user()?->can('reports.export'), 403);

        $dataset = $this->reports->dataset($report->code, $request->filters());
        $fileName = str($report->code)->lower()->replace('_', '-')->append('-'.now()->format('Ymd-His'))->toString();

        $this->recordRun($report, $request, 'completed', $format);

        if ($format === 'pdf') {
            return response(SimplePdf::table($report->name, $dataset['columns'], $dataset['rows']))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'.pdf"');
        }

        return new GenericReportExport($fileName.'.xlsx', $dataset['columns'], $dataset['rows']);
    }

    public function schedule(ReportCatalog $report, RunReportRequest $request): SymfonyResponse
    {
        abort_unless($request->user()?->can('reports.update'), 403);

        $data = $request->validated();
        $frequency = $data['schedule_frequency'] ?? null;

        $report->update([
            'is_schedulable' => filled($frequency),
            'schedule_frequency' => $frequency,
            'schedule_recipients' => $data['schedule_recipients'] ?? [],
            'next_run_at' => $frequency ? $this->nextRunAt($frequency) : null,
        ]);

        $this->recordRun($report, $request, 'scheduled', 'schedule');

        return back()->with('status', 'Report schedule updated.');
    }

    private function recordRun(ReportCatalog $report, Request $request, string $status, string $format): void
    {
        ReportRun::query()->create([
            'report_catalog_id' => $report->id,
            'user_id' => $request->user()?->id,
            'status' => $status,
            'parameters' => ['filters' => $request instanceof RunReportRequest ? $request->filters() : [], 'format' => $format],
            'file_path' => null,
            'completed_at' => now(),
        ]);
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

    private function pdfOptions(): array
    {
        $base = env('APP_RUNNING_ON_VERCEL') ? '/tmp/kfs-smart-hrms/dompdf' : storage_path('app/dompdf');

        foreach ([$base, "{$base}/fonts", "{$base}/temp"] as $directory) {
            if (! is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }

        return [
            'tempDir' => "{$base}/temp",
            'fontDir' => "{$base}/fonts",
            'fontCache' => "{$base}/fonts",
            'isRemoteEnabled' => false,
        ];
    }
}
