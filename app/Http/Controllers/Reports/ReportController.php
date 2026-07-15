<?php

namespace App\Http\Controllers\Reports;

use App\Exports\GenericReportExport;
use App\Exports\NetToBankReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\RunReportRequest;
use App\Models\ReportCatalog;
use App\Models\ReportRun;
use App\Services\Reports\ReportDataService;
use App\Support\SimplePdf;
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
        $reports = ReportCatalog::query()
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name', 'module', 'is_schedulable', 'schedule_frequency', 'next_run_at'])
            ->map(fn (ReportCatalog $report): array => $this->reportPayload($report))
            ->groupBy('module');

        $latestRuns = ReportRun::query()
            ->with('report:id,code,name,module')
            ->latest()
            ->limit(12)
            ->get(['id', 'uuid', 'report_catalog_id', 'user_id', 'status', 'parameters', 'file_path', 'completed_at', 'created_at'])
            ->map(fn (ReportRun $run): array => $this->runPayload($run));

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'latestRuns' => $latestRuns,
        ]);
    }

    public function show(ReportCatalog $report, RunReportRequest $request): Response
    {
        $dataset = $this->reports->dataset($report->code, $request->filters());

        return Inertia::render('Reports/Show', [
            'report' => $this->reportPayload($report, ['schedule_recipients']),
            'filters' => $request->filters(),
            'dataset' => $dataset,
            'scheduleFrequencies' => config('reports.schedule_frequencies'),
            'periods' => DB::table('payroll_periods')
                ->whereNull('deleted_at')
                ->orderByDesc('starts_on')
                ->limit(36)
                ->get(['id', 'code', DB::raw('code as name')]),
            'departments' => DB::table('departments')->whereNull('deleted_at')->orderBy('name')->get(['id', 'code', 'name']),
            'employers' => config('kfs.employers', ['KFS']),
        ]);
    }

    public function export(ReportCatalog $report, RunReportRequest $request, string $format): Responsable|SymfonyResponse
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);
        abort_unless($request->user()?->can('reports.export'), 403);

        $dataset = $this->reports->dataset($report->code, $request->filters());
        $fileName = str($this->displayCode($report->code))->lower()->replace('_', '-')->append('-'.now()->format('Ymd-His'))->toString();

        $this->recordRun($report, $request, 'completed', $format);

        if ($format === 'pdf') {
            return response(SimplePdf::table($this->displayName($report), $dataset['columns'], $dataset['rows']))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'.pdf"');
        }

        if ($report->code === 'BANK_SCHEDULE') {
            return new NetToBankReportExport($fileName.'.xlsx', $dataset['rows'], $this->periodLabel($request->filters()));
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

    private function periodLabel(array $filters): ?string
    {
        $periodId = $filters['period_id'] ?? null;

        if (! $periodId) {
            return null;
        }

        $startsOn = DB::table('payroll_periods')
            ->where('id', $periodId)
            ->value('starts_on');

        return $startsOn ? Carbon::parse($startsOn)->format('F, Y') : null;
    }

    private function reportPayload(ReportCatalog $report, array $extra = []): array
    {
        $payload = $report->only(array_merge([
            'id',
            'uuid',
            'code',
            'module',
            'is_schedulable',
            'schedule_frequency',
            'next_run_at',
        ], $extra));

        $payload['name'] = $this->displayName($report);
        $payload['display_code'] = $this->displayCode($report->code);

        return $payload;
    }

    private function runPayload(ReportRun $run): array
    {
        $payload = $run->only(['id', 'uuid', 'status', 'parameters', 'file_path', 'completed_at', 'created_at']);
        $payload['report'] = $run->report ? $this->reportPayload($run->report) : null;

        return $payload;
    }

    private function displayName(ReportCatalog $report): string
    {
        $name = str_replace(['HRISKE ', 'HRIS KE ', 'HRIS-KE '], '', $report->name);

        return trim($name);
    }

    private function displayCode(string $code): string
    {
        return str_replace(['HRISKE_', 'HRIS_KE_', 'HRIS-KE_'], '', $code);
    }

}
