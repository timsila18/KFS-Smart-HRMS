<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Employee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'profile.employee_number' => ['required', 'string', 'max:60', 'unique:employees,employee_number'],
            'profile.first_name' => ['required', 'string', 'max:120'],
            'profile.middle_name' => ['nullable', 'string', 'max:120'],
            'profile.last_name' => ['required', 'string', 'max:120'],
            'profile.gender' => ['nullable', 'string', 'max:30'],
            'profile.date_of_birth' => ['nullable', 'date', 'before:today'],
            'profile.hire_date' => ['nullable', 'date'],
            'profile.employment_status' => ['required', 'string', 'max:60'],
            'profile.station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'profile.department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'profile.job_position_id' => ['nullable', 'integer', 'exists:job_positions,id'],
            'profile.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'identifications' => ['array'],
            'identifications.*.id_type' => ['required_with:identifications', 'string', 'max:80'],
            'identifications.*.id_number' => ['required_with:identifications', 'string', 'max:120'],
            'contacts' => ['array'],
            'contacts.*.contact_type' => ['required_with:contacts', 'string', 'max:60'],
            'contacts.*.value' => ['required_with:contacts', 'string', 'max:190'],
            'contacts.*.is_primary' => ['boolean'],
            'addresses' => ['array'],
            'dependants' => ['array'],
            'emergency_contacts' => ['array'],
            'next_of_kin' => ['array'],
            'qualifications' => ['array'],
            'professional_memberships' => ['array'],
            'bank_accounts' => ['array'],
            'documents' => ['array'],
            'medical_records' => ['array'],
        ];
    }
}
