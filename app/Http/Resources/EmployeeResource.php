<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'hire_date' => $this->hire_date?->toDateString(),
            'employment_status' => $this->employment_status,
            'payroll_status' => $this->payroll_status ?? 'live',
            'account_status' => $this->account_status ?? $this->user?->status ?? 'active',
            'separated_at' => $this->separated_at?->toISOString(),
            'reinstated_at' => $this->reinstated_at?->toISOString(),
            'employer' => $this->employer,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'ess_email' => $this->user?->email,
            'user_status' => $this->user?->status,
            'station' => $this->whenLoaded('station'),
            'department' => $this->whenLoaded('department'),
            'job_position' => $this->whenLoaded('jobPosition'),
            'identifications' => $this->whenLoaded('identifications'),
            'contacts' => $this->whenLoaded('contacts'),
            'addresses' => $this->whenLoaded('addresses'),
            'dependants' => $this->whenLoaded('dependants'),
            'emergency_contacts' => $this->whenLoaded('emergencyContacts'),
            'next_of_kin' => $this->whenLoaded('nextOfKin'),
            'qualifications' => $this->whenLoaded('education'),
            'professional_memberships' => $this->whenLoaded('professionalQualifications'),
            'bank_accounts' => $this->whenLoaded('bankAccounts'),
            'documents' => $this->whenLoaded('documents'),
            'medical_records' => $this->whenLoaded('medicalRecords'),
            'contracts' => $this->whenLoaded('contracts'),
            'salary_assignments' => $this->whenLoaded('salaryAssignments'),
            'attachments' => $this->whenLoaded('attachments'),
        ];
    }
}
