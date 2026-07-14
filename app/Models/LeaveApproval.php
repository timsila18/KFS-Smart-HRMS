<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveApproval extends Model
{
    use HasAuditColumns;
    use SoftDeletes;

    protected $fillable = [
        'leave_request_id',
        'approver_id',
        'approval_level',
        'status',
        'remarks',
        'acted_at',
    ];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime'];
    }

    public function leaveRequest(): BelongsTo { return $this->belongsTo(LeaveRequest::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }
}
