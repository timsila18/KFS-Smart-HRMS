<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class PayrollRun extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['payroll_period_id','pay_group_id','run_number','status','processed_at','approved_by','approved_at','locked_by','locked_at','reversed_by','reversed_at','reversal_of_run_id','reversal_reason','gross_total','deduction_total','net_total']; protected function casts(): array { return ['processed_at'=>'datetime','approved_at'=>'datetime','locked_at'=>'datetime','reversed_at'=>'datetime','gross_total'=>'decimal:18','deduction_total'=>'decimal:18','net_total'=>'decimal:18']; } public function period(): BelongsTo { return $this->belongsTo(PayrollPeriod::class,'payroll_period_id'); } public function payGroup(): BelongsTo { return $this->belongsTo(PayGroup::class); } public function items(): HasMany { return $this->hasMany(PayrollRunItem::class); } public function payslips(): HasMany { return $this->hasMany(Payslip::class); } public function outputFiles(): HasMany { return $this->hasMany(PayrollOutputFile::class); } public function getRouteKeyName(): string { return 'uuid'; } }
