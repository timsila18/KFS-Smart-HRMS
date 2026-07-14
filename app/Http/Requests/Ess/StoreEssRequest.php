<?php
namespace App\Http\Requests\Ess;
use Illuminate\Foundation\Http\FormRequest;
class StoreEssRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (($this->user()?->can('ess.create')) || ($this->user()?->can('ess.view')));
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', 'string', 'max:80'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'payload' => ['nullable', 'array'],
            'payload.leave_type_code' => ['required_if:request_type,leave', 'string', 'exists:leave_types,code'],
            'payload.start_date' => ['required_if:request_type,leave', 'date'],
            'payload.end_date' => ['required_if:request_type,leave', 'date', 'after_or_equal:payload.start_date'],
            'payload.requested_days' => ['nullable', 'numeric', 'min:0.01'],
            'payload.reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
