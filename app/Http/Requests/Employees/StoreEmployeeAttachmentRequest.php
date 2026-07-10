<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee && ($this->user()?->can('update', $employee) ?? false);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:80'],
            'file' => ['required', 'file', 'max:10240'],
        ];
    }
}
