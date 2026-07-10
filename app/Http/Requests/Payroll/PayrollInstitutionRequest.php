<?php

namespace App\Http\Requests\Payroll;

use App\Models\PayrollInstitution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollInstitutionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'registration_number' => $this->input('registration_number') === '' ? null : $this->input('registration_number'),
            'contact_person' => $this->input('contact_person') === '' ? null : $this->input('contact_person'),
            'phone' => $this->input('phone') === '' ? null : $this->input('phone'),
            'email' => $this->input('email') === '' ? null : $this->input('email'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function authorize(): bool
    {
        $institution = $this->route('institution');

        return $institution instanceof PayrollInstitution
            ? ($this->user()?->can('update', $institution) ?? false)
            : ($this->user()?->can('create', PayrollInstitution::class) ?? false);
    }

    public function rules(): array
    {
        $institution = $this->route('institution');

        return [
            'institution_type' => ['required', 'string', Rule::in(config('payroll-admin.institution_types'))],
            'code' => ['required', 'string', 'max:80', Rule::unique('payroll_institutions', 'code')->ignore($institution?->id)],
            'name' => ['required', 'string', 'max:190'],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'contact_person' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'configuration' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
