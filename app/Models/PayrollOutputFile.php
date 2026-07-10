<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class PayrollOutputFile extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['payroll_run_id','output_type','file_name','file_path','mime_type','metadata']; protected function casts(): array { return ['metadata'=>'array']; } public function run(): BelongsTo { return $this->belongsTo(PayrollRun::class,'payroll_run_id'); } }
