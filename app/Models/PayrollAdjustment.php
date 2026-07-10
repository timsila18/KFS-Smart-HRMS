<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class PayrollAdjustment extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','payroll_period_id','pay_code_id','amount','reason','approval_status']; protected function casts(): array { return ['amount'=>'decimal:18']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function payCode(): BelongsTo { return $this->belongsTo(PayCode::class); } }
