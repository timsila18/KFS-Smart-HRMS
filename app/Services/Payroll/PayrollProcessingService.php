<?php

namespace App\Services\Payroll;

use App\Models\PayGroup;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\Auth\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollProcessingService
{
    public function __construct(
        private readonly PayrollCalculationEngine $engine,
        private readonly PayrollOutputService $outputs,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function open(PayrollPeriod $period, PayGroup $payGroup, Request $request): PayrollRun
    {
        return DB::transaction(function () use ($period, $payGroup, $request): PayrollRun {
            $existingRun = PayrollRun::query()
                ->where('payroll_period_id', $period->id)
                ->where('pay_group_id', $payGroup->id)
                ->whereIn('status', ['draft', 'calculated', 'approved', 'locked'])
                ->latest('id')
                ->first();

            if ($existingRun) {
                $this->activityLogger->record($request, 'payroll.open_existing', $existingRun, [], $existingRun->only(['run_number', 'status']));

                return $existingRun;
            }

            $run = PayrollRun::query()->create([
                'uuid' => (string) Str::uuid(),
                'payroll_period_id' => $period->id,
                'pay_group_id' => $payGroup->id,
                'run_number' => $period->code.'-'.$payGroup->code.'-'.now()->format('YmdHisv'),
                'status' => 'draft',
                'gross_total' => 0,
                'deduction_total' => 0,
                'net_total' => 0,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $this->activityLogger->record($request, 'payroll.opened', $run, [], $run->toArray());

            return $run;
        });
    }

    public function importAdjustments(PayrollPeriod $period, UploadedFile $file, Request $request): int
    {
        $rows = array_map('str_getcsv', file($file->getRealPath()) ?: []);
        $header = array_map('trim', array_shift($rows) ?: []);
        $count = 0;

        DB::transaction(function () use ($rows, $header, $period, &$count): void {
            foreach ($rows as $row) {
                $data = array_combine($header, $row);
                if (! $data || empty($data['employee_id']) || empty($data['pay_code_id']) || empty($data['amount'])) {
                    continue;
                }
                PayrollAdjustment::query()->create([
                    'employee_id' => $data['employee_id'],
                    'payroll_period_id' => $period->id,
                    'pay_code_id' => $data['pay_code_id'],
                    'amount' => $data['amount'],
                    'reason' => $data['reason'] ?? 'Imported adjustment',
                    'approval_status' => $data['approval_status'] ?? 'approved',
                ]);
                $count++;
            }
        });

        $this->activityLogger->record($request, 'payroll.adjustments_imported', $period, [], ['rows' => $count]);

        return $count;
    }

    public function calculate(PayrollRun $run, Request $request): PayrollRun
    {
        $this->ensureStatus($run, ['draft', 'calculated']);
        $run = $this->engine->calculate($run);
        $this->activityLogger->record($request, 'payroll.calculated', $run, [], $run->only(['gross_total', 'deduction_total', 'net_total']));

        return $run;
    }

    public function approve(PayrollRun $run, Request $request): PayrollRun
    {
        $this->ensureStatus($run, ['calculated']);
        $run->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        $this->activityLogger->record($request, 'payroll.approved', $run);

        return $run->fresh();
    }

    public function lock(PayrollRun $run, Request $request): PayrollRun
    {
        $this->ensureStatus($run, ['approved']);
        $run->update(['status' => 'locked', 'locked_by' => $request->user()->id, 'locked_at' => now()]);
        $this->activityLogger->record($request, 'payroll.locked', $run);

        return $run->fresh();
    }

    public function reverse(PayrollRun $run, string $reason, Request $request): PayrollRun
    {
        $this->ensureStatus($run, ['locked']);

        return DB::transaction(function () use ($run, $reason, $request): PayrollRun {
            $run->update(['status' => 'reversed', 'reversed_by' => $request->user()->id, 'reversed_at' => now(), 'reversal_reason' => $reason]);
            $reversal = $run->replicate(['uuid']);
            $reversal->run_number = $run->run_number.'-REV';
            $reversal->status = 'locked';
            $reversal->reversal_of_run_id = $run->id;
            $reversal->gross_total = -1 * (float) $run->gross_total;
            $reversal->deduction_total = -1 * (float) $run->deduction_total;
            $reversal->net_total = -1 * (float) $run->net_total;
            $reversal->save();

            foreach ($run->items as $item) {
                $copy = $item->replicate(['uuid']);
                $copy->payroll_run_id = $reversal->id;
                $copy->amount = -1 * (float) $item->amount;
                $copy->save();
            }

            $this->activityLogger->record($request, 'payroll.reversed', $run, [], ['reason' => $reason, 'reversal_run_id' => $reversal->id]);

            return $reversal;
        });
    }

    public function generateOutputs(PayrollRun $run, Request $request): void
    {
        $this->ensureStatus($run, ['approved', 'locked']);
        $this->outputs->payslips($run);
        $this->outputs->p9($run);
        $this->outputs->payrollRegisterByEmployer($run);
        $this->outputs->approvalMemos($run);
        $this->outputs->bankFile($run);
        $this->outputs->statutoryReports($run);
        $this->activityLogger->record($request, 'payroll.outputs_generated', $run);
    }

    private function ensureStatus(PayrollRun $run, array $allowed): void
    {
        abort_unless(in_array($run->status, $allowed, true), 422, 'Payroll run status does not allow this action.');
    }
}
