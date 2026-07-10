<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class PayrollRunItem extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['payroll_run_id','employee_id','pay_code_id','quantity','rate','amount','calculation_snapshot']; protected function casts(): array { return ['quantity'=>'decimal:12','rate'=>'decimal:12','amount'=>'decimal:18','calculation_snapshot'=>'array']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function payCode(): BelongsTo { return $this->belongsTo(PayCode::class); } }
