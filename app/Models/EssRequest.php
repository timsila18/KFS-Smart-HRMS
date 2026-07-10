<?php
namespace App\Models;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class EssRequest extends Model { use HasAuditColumns, SoftDeletes; protected $fillable = ['employee_id','request_type','status','payload','remarks']; protected function casts(): array { return ['payload'=>'array']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
