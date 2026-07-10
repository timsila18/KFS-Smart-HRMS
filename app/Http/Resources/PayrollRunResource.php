<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'run_number' => $this->run_number,
            'status' => $this->status,
            'gross_total' => (float) $this->gross_total,
            'deduction_total' => (float) $this->deduction_total,
            'net_total' => (float) $this->net_total,
            'period' => $this->whenLoaded('period'),
            'pay_group' => $this->whenLoaded('payGroup'),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'uuid' => $item->uuid,
                'employee' => $item->employee?->full_name,
                'employee_number' => $item->employee?->employee_number,
                'code' => $item->payCode?->code,
                'name' => $item->payCode?->name,
                'type' => $item->payCode?->pay_code_type,
                'amount' => (float) $item->amount,
            ])),
            'outputs' => $this->whenLoaded('outputFiles', fn () => $this->outputFiles->map(fn ($file) => [
                'uuid' => $file->uuid,
                'type' => $file->output_type,
                'name' => $file->file_name,
                'url' => Storage::disk('public')->url($file->file_path),
            ])),
        ];
    }
}
