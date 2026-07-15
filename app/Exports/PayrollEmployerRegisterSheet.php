<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollEmployerRegisterSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @param array<int, string> $payCodeColumns
     */
    public function __construct(
        private readonly string $employer,
        private readonly Collection $rows,
        private readonly array $payCodeColumns,
    ) {
    }

    public function title(): string
    {
        return str($this->employer)
            ->replaceMatches('/[\\\\\\/\\?\\*\\[\\]:]/', ' ')
            ->squish()
            ->limit(31, '')
            ->toString();
    }

    public function headings(): array
    {
        return collect($this->columns())
            ->map(fn (string $column): string => str($column)->replace('_', ' ')->headline()->toString())
            ->all();
    }

    public function array(): array
    {
        $columns = $this->columns();

        return $this->rows
            ->map(fn (array $row): array => collect($columns)->map(fn (string $column) => $row[$column] ?? null)->all())
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function columns(): array
    {
        return [
            'payroll_period',
            'run_number',
            'employer',
            'employee_number',
            'employee_name',
            'station',
            'department',
            'gross_pay',
            'total_deductions',
            'net_pay',
            ...$this->payCodeColumns,
        ];
    }
}
