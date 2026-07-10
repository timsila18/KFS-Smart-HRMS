<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class JobPosition extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['job_grade_id','code','title','description','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } public function jobGrade(): BelongsTo { return $this->belongsTo(JobGrade::class); } }
