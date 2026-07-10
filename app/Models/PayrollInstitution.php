<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollInstitution extends Model
{
    use HasAuditColumns;
    use SoftDeletes;

    protected $fillable = [
        'institution_type',
        'code',
        'name',
        'registration_number',
        'contact_person',
        'phone',
        'email',
        'configuration',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'is_active' => 'boolean',
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
