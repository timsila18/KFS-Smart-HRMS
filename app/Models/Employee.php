<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasAuditColumns;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'station_id',
        'department_id',
        'job_position_id',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'employment_status',
        'employer',
        'photo_path',
        'hire_date',
    ];

    protected $appends = ['full_name'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function station(): BelongsTo { return $this->belongsTo(Station::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function jobPosition(): BelongsTo { return $this->belongsTo(JobPosition::class); }
    public function identifications(): HasMany { return $this->hasMany(EmployeeIdentification::class); }
    public function contacts(): HasMany { return $this->hasMany(EmployeeContact::class); }
    public function addresses(): HasMany { return $this->hasMany(EmployeeAddress::class); }
    public function dependants(): HasMany { return $this->hasMany(EmployeeDependant::class); }
    public function emergencyContacts(): HasMany { return $this->hasMany(EmployeeEmergencyContact::class); }
    public function nextOfKin(): HasMany { return $this->hasMany(EmployeeNextOfKin::class); }
    public function education(): HasMany { return $this->hasMany(EmployeeEducation::class); }
    public function professionalQualifications(): HasMany { return $this->hasMany(EmployeeProfessionalQualification::class); }
    public function bankAccounts(): HasMany { return $this->hasMany(EmployeeBankAccount::class); }
    public function documents(): HasMany { return $this->hasMany(EmployeeDocument::class); }
    public function medicalRecords(): HasMany { return $this->hasMany(EmployeeMedicalRecord::class); }
    public function contracts(): HasMany { return $this->hasMany(Contract::class); }
    public function salaryAssignments(): HasMany { return $this->hasMany(EmployeeSalaryAssignment::class); }
    public function attachments(): HasMany { return $this->hasMany(Attachment::class, 'attachable_id')->where('attachable_type', self::class); }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' ');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
