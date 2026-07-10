<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollInstitutionProduct extends Model
{
    use HasAuditColumns;
    use SoftDeletes;

    protected $fillable = [
        'payroll_institution_id',
        'pay_code_id',
        'product_type',
        'code',
        'name',
        'calculation_method',
        'default_amount',
        'default_rate',
        'rules',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:18',
            'default_rate' => 'decimal:12',
            'rules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(PayrollInstitution::class, 'payroll_institution_id');
    }

    public function payCode(): BelongsTo
    {
        return $this->belongsTo(PayCode::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
