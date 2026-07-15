<?php

namespace App\Services\Employees;

use App\Models\Attachment;
use App\Models\BankBranch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\JobPosition;
use App\Models\Station;
use App\Models\User;
use App\Models\UserProfile;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Auth\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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
            $this->syncEssAccount($employee, $payload);
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
            $this->syncEssAccount($employee, $payload);
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

    public function importSpreadsheet(UploadedFile $file, Request $request): array
    {
        $sheets = Excel::toCollection(null, $file);
        $rows = $sheets->first() ?? collect();

        if ($rows->isEmpty()) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $headers = collect($rows->shift())
            ->map(fn ($header): string => Str::of((string) $header)->lower()->replace([' ', '-', '.'], '_')->squish()->toString())
            ->all();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $headers, $request, &$created, &$updated, &$skipped): void {
            foreach ($rows as $row) {
                $data = $this->normaliseImportRow(array_combine($headers, $row->all()) ?: []);

                if (blank($data['employee_number']) || blank($data['first_name']) || blank($data['last_name'])) {
                    $skipped++;
                    continue;
                }

                $employee = Employee::query()->firstOrNew(['employee_number' => $data['employee_number']]);
                $wasRecentlyCreated = ! $employee->exists;

                $employee->fill([
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'],
                    'last_name' => $data['last_name'],
                    'gender' => $data['gender'],
                    'date_of_birth' => $data['date_of_birth'],
                    'hire_date' => $data['hire_date'],
                    'employment_status' => $data['employment_status'] ?: 'active',
                    'station_id' => $this->lookupId(Station::class, $data['station']),
                    'department_id' => $this->lookupId(Department::class, $data['department']),
                    'job_position_id' => $this->lookupId(JobPosition::class, $data['position'], 'title'),
                ])->save();

                $this->syncEssAccount($employee, [
                    'ess' => [
                        'email' => $data['email'],
                        'password' => $data['password'] ?: config('kfs-auth.default_ess_password', 'KfsEss@2026'),
                    ],
                ]);
                $this->syncImportedBankAccount($employee, $data);
                $this->activityLogger->record($request, $wasRecentlyCreated ? 'employee.imported' : 'employee.import_updated', $employee, [], $employee->only(['employee_number', 'first_name', 'last_name']));

                $wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        return compact('created', 'updated', 'skipped');
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

    private function normaliseImportRow(array $row): array
    {
        $value = fn (array $keys): ?string => collect($keys)
            ->map(fn (string $key) => $row[$key] ?? null)
            ->first(fn ($item) => filled($item));

        return [
            'employee_number' => $value(['employee_number', 'payroll_number', 'payroll_no', 'staff_number', 'staff_no']),
            'first_name' => $value(['first_name', 'firstname', 'given_name']),
            'middle_name' => $value(['middle_name', 'middlename', 'other_name']),
            'last_name' => $value(['last_name', 'lastname', 'surname']),
            'gender' => $value(['gender', 'sex']),
            'date_of_birth' => $this->importDate($value(['date_of_birth', 'dob'])),
            'hire_date' => $this->importDate($value(['hire_date', 'employment_date', 'date_joined'])),
            'employment_status' => Str::lower((string) ($value(['employment_status', 'status']) ?: 'active')),
            'station' => $value(['station', 'station_code', 'station_name']),
            'department' => $value(['department', 'department_code', 'directorate']),
            'position' => $value(['position', 'job_position', 'designation', 'title']),
            'email' => Str::lower((string) $value(['email', 'official_email', 'work_email', 'ess_email'])),
            'password' => $value(['password', 'ess_password', 'initial_password']),
            'bank_name' => $value(['bank_name', 'bank']),
            'bank_code' => $value(['bank_code']),
            'branch_name' => $value(['branch_name', 'branch']),
            'branch_code' => $value(['branch_code', 'sort_code']),
            'account_number' => $value(['account_number', 'bank_account', 'account_no']),
        ];
    }

    private function importDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return rescue(fn () => Carbon::parse((string) $value)->toDateString(), null, false);
    }

    private function lookupId(string $model, ?string $value, string $nameColumn = 'name'): ?int
    {
        if (blank($value)) {
            return null;
        }

        return $model::query()
            ->where('code', $value)
            ->orWhere($nameColumn, $value)
            ->value('id');
    }

    private function syncImportedBankAccount(Employee $employee, array $data): void
    {
        if (blank($data['account_number'])) {
            return;
        }

        $branch = filled($data['branch_code'])
            ? BankBranch::query()->where('branch_code', $data['branch_code'])->first()
            : null;

        EmployeeBankAccount::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'account_number' => $data['account_number']],
            [
                'bank_branch_id' => $branch?->id,
                'bank_code' => $data['bank_code'] ?: $branch?->bank_code,
                'bank_name' => $data['bank_name'] ?: $branch?->bank_name,
                'branch_code' => $data['branch_code'] ?: $branch?->branch_code,
                'branch_name' => $data['branch_name'] ?: $branch?->branch_name,
                'is_primary' => true,
            ]
        );
    }

    private function syncEssAccount(Employee $employee, array $payload): void
    {
        $email = Str::lower((string) ($payload['ess']['email'] ?? $this->primaryEmailFromContacts($payload['contacts'] ?? [])));

        if (blank($email)) {
            $email = $this->generatedEssEmail($employee);
        }

        $password = $payload['ess']['password'] ?? config('kfs-auth.default_ess_password', 'KfsEss@2026');

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $employee->full_name,
            'email' => $email,
            'status' => 'active',
        ]);

        if (! $user->exists || filled($payload['ess']['password'] ?? null)) {
            $user->password = $password;
        }

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();
        $user->assignRole('employee');

        $employee->forceFill(['user_id' => $user->id])->save();

        UserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_id' => $employee->id,
                'phone' => $this->primaryPhoneFromContacts($payload['contacts'] ?? []),
                'preferences' => ['landing_page' => '/ess'],
            ]
        );
    }

    private function primaryEmailFromContacts(array $contacts): ?string
    {
        return collect($contacts)
            ->first(fn ($row): bool => Str::lower((string) ($row['contact_type'] ?? '')) === 'email' && filled($row['value'] ?? null))['value'] ?? null;
    }

    private function primaryPhoneFromContacts(array $contacts): ?string
    {
        return collect($contacts)
            ->first(fn ($row): bool => in_array(Str::lower((string) ($row['contact_type'] ?? '')), ['mobile', 'phone', 'telephone'], true) && filled($row['value'] ?? null))['value'] ?? null;
    }

    private function generatedEssEmail(Employee $employee): string
    {
        $localPart = Str::of($employee->employee_number)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->toString();

        return "{$localPart}@".config('kfs-auth.ess_email_domain', 'kfs.go.ke');
    }
}
