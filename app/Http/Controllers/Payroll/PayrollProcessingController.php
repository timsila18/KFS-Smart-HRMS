<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\ImportPayrollAdjustmentsRequest;
use App\Http\Requests\Payroll\OpenPayrollRequest;
use App\Http\Requests\Payroll\ReversePayrollRequest;
use App\Http\Resources\PayrollRunResource;
use App\Models\PayGroup;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollProcessingController extends Controller
{
    public function __construct(private readonly PayrollProcessingService $payroll)
    {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', PayrollRun::class);

        return Inertia::render('Payroll/Processing/Index', [
            'runs' => PayrollRunResource::collection(PayrollRun::query()->with(['period', 'payGroup', 'outputFiles'])->latest()->paginate(12)),
            'periods' => PayrollPeriod::query()->orderByDesc('starts_on')->get(['id', 'uuid', 'code', 'starts_on', 'ends_on', 'status']),
            'payGroups' => PayGroup::query()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function show(PayrollRun $run): Response
    {
        $this->authorize('view', $run);

        return Inertia::render('Payroll/Processing/Show', [
            'run' => new PayrollRunResource($run->load(['period', 'payGroup', 'items.employee', 'items.payCode', 'outputFiles'])),
        ]);
    }

    public function open(OpenPayrollRequest $request): RedirectResponse
    {
        $run = $this->payroll->open(
            PayrollPeriod::query()->findOrFail($request->integer('payroll_period_id')),
            PayGroup::query()->findOrFail($request->integer('pay_group_id')),
            $request
        );

        return redirect()->route('payroll.processing.show', $run)->with('status', 'Payroll opened.');
    }

    public function importAdjustments(ImportPayrollAdjustmentsRequest $request): RedirectResponse
    {
        $count = $this->payroll->importAdjustments(PayrollPeriod::query()->findOrFail($request->integer('payroll_period_id')), $request->file('file'), $request);

        return back()->with('status', "{$count} payroll adjustments imported.");
    }

    public function calculate(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('update', $run);
        $this->payroll->calculate($run, $request);

        return back()->with('status', 'Payroll calculated.');
    }

    public function approve(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('approve', $run);
        $this->payroll->approve($run, $request);

        return back()->with('status', 'Payroll approved.');
    }

    public function lock(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('approve', $run);
        $this->payroll->lock($run, $request);

        return back()->with('status', 'Payroll locked.');
    }

    public function reverse(ReversePayrollRequest $request, PayrollRun $run): RedirectResponse
    {
        $reversal = $this->payroll->reverse($run, $request->string('reason')->toString(), $request);

        return redirect()->route('payroll.processing.show', $reversal)->with('status', 'Payroll reversed.');
    }

    public function outputs(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('update', $run);
        $this->payroll->generateOutputs($run, $request);

        return back()->with('status', 'Payroll outputs generated.');
    }
}
