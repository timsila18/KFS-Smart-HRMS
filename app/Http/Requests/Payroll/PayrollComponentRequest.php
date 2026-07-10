<?php

namespace App\Http\Requests\Payroll;

use App\Models\PayCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollComponentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'default_amount' => $this->input('default_amount') === '' ? null : $this->input('default_amount'),
            'default_rate' => $this->input('default_rate') === '' ? null : $this->input('default_rate'),
            'component_subtype' => $this->input('component_subtype') === '' ? null : $this->input('component_subtype'),
            'is_taxable' => $this->boolean('is_taxable'),
            'is_pensionable' => $this->boolean('is_pensionable'),
            'is_recurring' => $this->boolean('is_recurring'),
            'requires_membership' => $this->boolean('requires_membership'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function authorize(): bool
    {
        $component = $this->route('component');

        return $component instanceof PayCode
            ? ($this->user()?->can('update', $component) ?? false)
            : ($this->user()?->can('create', PayCode::class) ?? false);
    }

    public function rules(): array
    {
        $component = $this->route('component');

        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('pay_codes', 'code')->ignore($component?->id)],
            'name' => ['required', 'string', 'max:160'],
            'pay_code_type' => ['required', 'string', Rule::in(config('payroll-admin.pay_code_types'))],
            'component_group' => ['required', 'string', 'max:80'],
            'component_subtype' => ['nullable', 'string', 'max:100'],
            'calculation_method' => ['required', 'string', Rule::in(config('payroll-admin.calculation_methods'))],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['boolean'],
            'is_pensionable' => ['boolean'],
            'is_recurring' => ['boolean'],
            'requires_membership' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'calculation_rules' => ['nullable', 'array'],
        ];
    }
}
