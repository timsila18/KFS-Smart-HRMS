<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function query(array $filters = []): Builder
    {
        return Employee::query()
            ->with(['station', 'department', 'jobPosition'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $term = '%'.mb_strtolower($search).'%';
                    $query->whereRaw('lower(employee_number) like ?', [$term])
                        ->orWhereRaw('lower(first_name) like ?', [$term])
                        ->orWhereRaw('lower(coalesce(middle_name, \'\')) like ?', [$term])
                        ->orWhereRaw('lower(last_name) like ?', [$term]);
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('employment_status', $status))
            ->when($filters['station_id'] ?? null, fn (Builder $query, int|string $stationId) => $query->where('station_id', $stationId))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int|string $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['job_position_id'] ?? null, fn (Builder $query, int|string $positionId) => $query->where('job_position_id', $positionId))
            ->latest('id');
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    public function findByUuid(string $uuid): Employee
    {
        return Employee::query()
            ->with([
                'station',
                'department',
                'jobPosition.jobGrade',
                'identifications',
                'contacts',
                'addresses',
                'dependants',
                'emergencyContacts',
                'nextOfKin',
                'education',
                'professionalQualifications',
                'bankAccounts',
                'documents',
                'medicalRecords',
                'contracts.employmentType',
                'contracts.contractType',
                'salaryAssignments.salaryScaleStep',
                'salaryAssignments.payGroup',
                'attachments',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
