<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayCode extends Model
{
    use HasAuditColumns;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'pay_code_type',
        'component_group',
        'component_subtype',
        'calculation_method',
        'default_amount',
        'default_rate',
        'is_taxable',
        'is_pensionable',
        'is_recurring',
        'requires_membership',
        'is_active',
        'sort_order',
        'calculation_rules',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:18',
            'default_rate' => 'decimal:12',
            'is_taxable' => 'boolean',
            'is_pensionable' => 'boolean',
            'is_recurring' => 'boolean',
            'requires_membership' => 'boolean',
            'is_active' => 'boolean',
            'calculation_rules' => 'array',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(PayrollInstitutionProduct::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
