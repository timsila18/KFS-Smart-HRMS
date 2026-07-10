<?php
namespace App\Http\Requests\Payroll;
use Illuminate\Foundation\Http\FormRequest;
class OpenPayrollRequest extends FormRequest { public function authorize(): bool { return $this->user()?->can('payroll.create') ?? false; } public function rules(): array { return ['payroll_period_id'=>['required','integer','exists:payroll_periods,id'],'pay_group_id'=>['required','integer','exists:pay_groups,id']]; } }
