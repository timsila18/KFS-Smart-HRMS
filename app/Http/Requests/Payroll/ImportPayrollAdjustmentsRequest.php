<?php
namespace App\Http\Requests\Payroll;
use Illuminate\Foundation\Http\FormRequest;
class ImportPayrollAdjustmentsRequest extends FormRequest { public function authorize(): bool { return $this->user()?->can('payroll.update') ?? false; } public function rules(): array { return ['payroll_period_id'=>['required','integer','exists:payroll_periods,id'],'file'=>['required','file','mimes:csv,txt','max:10240']]; } }
