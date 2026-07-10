<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Payslip extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['payroll_run_id','employee_id','payslip_number','gross_pay','total_deductions','net_pay','file_path']; protected function casts(): array { return ['gross_pay'=>'decimal:18','total_deductions'=>'decimal:18','net_pay'=>'decimal:18']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function run(): BelongsTo { return $this->belongsTo(PayrollRun::class,'payroll_run_id'); } }
