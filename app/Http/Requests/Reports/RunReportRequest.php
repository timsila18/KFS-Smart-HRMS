<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'employer' => ['nullable', 'string', 'max:120', Rule::in(config('kfs.employers', ['KFS']))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'schedule_frequency' => ['nullable', Rule::in(array_keys(config('reports.schedule_frequencies', [])))],
            'schedule_recipients' => ['nullable', 'array', 'max:25'],
            'schedule_recipients.*' => ['email'],
        ];
    }

    public function filters(): array
    {
        return collect($this->validated())
            ->only(['period_id', 'department_id', 'employer', 'date_from', 'date_to'])
            ->filter(fn ($value) => filled($value))
            ->all();
    }
}
