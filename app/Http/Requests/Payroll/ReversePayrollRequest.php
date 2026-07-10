<?php
namespace App\Http\Requests\Payroll;
use Illuminate\Foundation\Http\FormRequest;
class ReversePayrollRequest extends FormRequest { public function authorize(): bool { return $this->user()?->can('payroll.approve') ?? false; } public function rules(): array { return ['reason'=>['required','string','max:1000']]; } }
