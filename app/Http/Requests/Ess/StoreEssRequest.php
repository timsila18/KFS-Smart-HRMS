<?php
namespace App\Http\Requests\Ess;
use Illuminate\Foundation\Http\FormRequest;
class StoreEssRequest extends FormRequest { public function authorize(): bool { return (bool) (($this->user()?->can('ess.create')) || ($this->user()?->can('ess.view'))); } public function rules(): array { return ['request_type'=>['required','string','max:80'],'remarks'=>['nullable','string','max:1000'],'payload'=>['nullable','array']]; } }
