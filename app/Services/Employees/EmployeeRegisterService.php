<?php

namespace App\Services\Employees;

use App\Models\Attachment;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Auth\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeRegisterService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload, Request $request): Employee
    {
        return DB::transaction(function () use ($payload, $request): Employee {
            $employee = Employee::query()->create($this->cleanRow($payload['profile']));
            $this->syncDetails($employee, $payload);
            $this->activityLogger->record($request, 'employee.created', $employee, [], $employee->only(['employee_number', 'first_name', 'last_name']));

            return $this->employees->findByUuid($employee->uuid);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Employee $employee, array $payload, Request $request): Employee
    {
        return DB::transaction(function () use ($employee, $payload, $request): Employee {
            $old = $employee->toArray();
            $employee->update($this->cleanRow($payload['profile']));
            $this->syncDetails($employee, $payload);
            $this->activityLogger->record($request, 'employee.updated', $employee, $old, $employee->fresh()->toArray());

            return $this->employees->findByUuid($employee->uuid);
        });
    }

    public function uploadPhoto(Employee $employee, UploadedFile $photo, Request $request): Employee
    {
        return DB::transaction(function () use ($employee, $photo, $request): Employee {
            $path = $photo->store("employees/{$employee->uuid}/photos", 'public');
            $old = ['photo_path' => $employee->photo_path];
            $employee->update(['photo_path' => $path]);
            $this->activityLogger->record($request, 'employee.photo_uploaded', $employee, $old, ['photo_path' => $path]);

            return $employee->fresh();
        });
    }

    public function attachFile(Employee $employee, UploadedFile $file, string $type, Request $request): Attachment
    {
        return DB::transaction(function () use ($employee, $file, $type, $request): Attachment {
            $path = $file->store("employees/{$employee->uuid}/attachments", 'public');
            $attachment = Attachment::query()->create([
                'attachment_type_id' => null,
                'attachable_type' => Employee::class,
                'attachable_id' => $employee->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            $this->activityLogger->record($request, 'employee.attachment_uploaded', $employee, [], ['type' => $type, 'path' => $path]);

            return $attachment;
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function syncDetails(Employee $employee, array $payload): void
    {
        $relations = [
            'identifications' => 'identifications',
            'contacts' => 'contacts',
            'addresses' => 'addresses',
            'dependants' => 'dependants',
            'emergency_contacts' => 'emergencyContacts',
            'next_of_kin' => 'nextOfKin',
            'qualifications' => 'education',
            'professional_memberships' => 'professionalQualifications',
            'bank_accounts' => 'bankAccounts',
            'documents' => 'documents',
            'medical_records' => 'medicalRecords',
        ];

        foreach ($relations as $payloadKey => $relation) {
            if (! array_key_exists($payloadKey, $payload)) {
                continue;
            }

            $employee->{$relation}()->delete();
            foreach ($payload[$payloadKey] ?? [] as $row) {
                $row = $this->cleanRow($row);
                if ($row !== []) {
                    $employee->{$relation}()->create($row);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function cleanRow(array $row): array
    {
        $clean = [];

        foreach ($row as $key => $value) {
            if (in_array($key, ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $clean[$key] = $value === '' ? null : $value;
        }

        return collect($clean)
            ->filter(fn ($value): bool => $value !== null && $value !== [] && $value !== false)
            ->isEmpty()
            ? []
            : $clean;
    }
}
