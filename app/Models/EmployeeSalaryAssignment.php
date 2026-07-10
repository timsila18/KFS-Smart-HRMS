<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmployeeSalaryAssignment extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','salary_scale_step_id','pay_group_id','effective_from','effective_to','status']; protected function casts(): array { return ['effective_from'=>'date','effective_to'=>'date']; } public function salaryScaleStep(): BelongsTo { return $this->belongsTo(SalaryScaleStep::class); } public function payGroup(): BelongsTo { return $this->belongsTo(PayGroup::class); } }
