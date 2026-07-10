<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel;

class GenericReportExport implements FromArray, Responsable, ShouldAutoSize, WithHeadings
{
    use Exportable;

    private string $writerType = Excel::XLSX;

    /**
     * @param array<int, string> $columns
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(
        private readonly string $fileName,
        private readonly array $columns,
        private readonly array $rows,
    ) {
    }

    public function headings(): array
    {
        return collect($this->columns)
            ->map(fn (string $column): string => str($column)->headline()->toString())
            ->all();
    }

    public function array(): array
    {
        return collect($this->rows)
            ->map(fn (array $row): array => collect($this->columns)->map(fn (string $column) => $row[$column] ?? null)->all())
            ->all();
    }
}
