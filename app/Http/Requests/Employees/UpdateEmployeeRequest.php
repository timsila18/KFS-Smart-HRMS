<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee && ($this->user()?->can('update', $employee) ?? false);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $rules = parent::rules();
        $rules['profile.employee_number'] = [
            'required',
            'string',
            'max:60',
            Rule::unique('employees', 'employee_number')->ignore($employee?->id),
        ];

        return $rules;
    }
}
