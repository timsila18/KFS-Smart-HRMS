<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Contract extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','employment_type_id','contract_type_id','contract_number','start_date','end_date','status']; protected function casts(): array { return ['start_date'=>'date','end_date'=>'date']; } public function employmentType(): BelongsTo { return $this->belongsTo(EmploymentType::class); } public function contractType(): BelongsTo { return $this->belongsTo(ContractType::class); } }
