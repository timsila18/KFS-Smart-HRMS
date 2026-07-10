<?php

namespace App\Http\Requests\Payroll;

use App\Models\PayrollInstitution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollInstitutionProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'pay_code_id' => $this->input('pay_code_id') === '' ? null : $this->input('pay_code_id'),
            'default_amount' => $this->input('default_amount') === '' ? null : $this->input('default_amount'),
            'default_rate' => $this->input('default_rate') === '' ? null : $this->input('default_rate'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function authorize(): bool
    {
        $institution = $this->route('institution');

        return $institution instanceof PayrollInstitution && ($this->user()?->can('update', $institution) ?? false);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'pay_code_id' => ['nullable', 'integer', 'exists:pay_codes,id'],
            'product_type' => ['required', 'string', Rule::in(config('payroll-admin.product_types'))],
            'code' => ['required', 'string', 'max:80', Rule::unique('payroll_institution_products', 'code')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:190'],
            'calculation_method' => ['required', 'string', Rule::in(config('payroll-admin.calculation_methods'))],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'rules' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
