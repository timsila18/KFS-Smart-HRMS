<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NetToBankReportExport implements FromArray, Responsable, WithEvents, WithTitle
{
    use Exportable;

    private string $writerType = Excel::XLSX;
    private ?array $preparedRows = null;

    /**
     * @param iterable<int, array<string, mixed>|object> $rows
     */
    public function __construct(
        private readonly string $fileName,
        private readonly iterable $rows,
        private readonly ?string $periodLabel = null,
    ) {
    }

    public function title(): string
    {
        return 'Net to Bank';
    }

    public function array(): array
    {
        $rows = [
            [$this->reportTitle(), '', '', '', '', '', '', ''],
            ['SNO', 'PayrollNum', 'Name', 'Bank', 'Branch Code', 'Branch', 'AccountNum', 'Net Pay Amount'],
        ];

        $serial = 1;

        foreach ($this->preparedRows() as $row) {
            $data = (array) $row;
            $accountNumber = (string) ($data['account_number'] ?? $data['accountNum'] ?? '');

            $rows[] = [
                $serial++,
                (string) ($data['employee_number'] ?? $data['payroll_num'] ?? $data['PayrollNum'] ?? ''),
                (string) ($data['employee'] ?? $data['name'] ?? $data['Name'] ?? ''),
                (string) ($data['bank_name'] ?? $data['bank'] ?? $data['Bank'] ?? ''),
                (string) ($data['branch_code'] ?? $data['Branch Code'] ?? ''),
                (string) ($data['branch_name'] ?? $data['branch'] ?? $data['Branch'] ?? ''),
                $accountNumber === '' ? '' : sprintf('="%s"', str_replace('"', '""', $accountNumber)),
                (float) ($data['net_pay'] ?? $data['amount'] ?? $data['Net Pay Amount'] ?? 0),
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max(2, $sheet->getHighestRow());

                $sheet->mergeCells('A1:H1');
                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(22);

                foreach (['A' => 4.86, 'B' => 11.43, 'C' => 31.57, 'D' => 19.71, 'E' => 12.0, 'F' => 17.0, 'G' => 16.14, 'H' => 15.57] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $border = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ];

                $sheet->getStyle("A1:H{$highestRow}")->applyFromArray($border);
                $sheet->getStyle('A1:H2')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAD3'],
                    ],
                ]);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:H2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A3:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E3:E{$highestRow}")->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle("G3:G{$highestRow}")->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle("H3:H{$highestRow}")->getNumberFormat()->setFormatCode('_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)');
            },
        ];
    }

    private function reportTitle(): string
    {
        return $this->employerTitle().' NET PAY FOR THE MONTH OF '.strtoupper($this->periodLabel ?: now()->format('F, Y'));
    }

    private function employerTitle(): string
    {
        $employers = collect($this->preparedRows())
            ->map(fn ($row): string => trim((string) (((array) $row)['employer'] ?? '')))
            ->filter()
            ->unique(fn (string $employer): string => strtoupper($employer))
            ->values();

        if ($employers->count() === 1) {
            return match (strtoupper($employers->first())) {
                'KFS' => 'KFS CONTRACT STAFF',
                default => strtoupper($employers->first()),
            };
        }

        return $employers->isEmpty() ? 'EMPLOYER' : 'MULTI-EMPLOYER';
    }

    private function preparedRows(): array
    {
        return $this->preparedRows ??= collect($this->rows)->all();
    }
}
