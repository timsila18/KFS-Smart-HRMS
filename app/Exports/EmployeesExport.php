<?php

namespace App\Exports;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;

class EmployeesExport implements FromQuery, Responsable, WithHeadings, WithMapping
{
    use Exportable;

    private string $fileName = 'kfs-employee-register.xlsx';
    private string $writerType = Excel::XLSX;

    public function __construct(private readonly EmployeeRepositoryInterface $employees, private readonly array $filters = [])
    {
    }

    public function query()
    {
        return $this->employees->query($this->filters);
    }

    public function headings(): array
    {
        return ['Employee No', 'Name', 'Employer', 'Status', 'Station', 'Department', 'Position', 'Hire Date'];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_number,
            $employee->full_name,
            $employee->employer,
            $employee->employment_status,
            $employee->station?->name,
            $employee->department?->name,
            $employee->jobPosition?->title,
            $employee->hire_date?->toDateString(),
        ];
    }
}
