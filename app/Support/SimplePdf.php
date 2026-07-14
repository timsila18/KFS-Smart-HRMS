<?php

namespace App\Support;

class SimplePdf
{
    /**
     * @param array<int, string> $columns
     * @param array<int, array<string, mixed>> $rows
     */
    public static function table(string $title, array $columns, array $rows): string
    {
        $objects = [];
        $pages = [];
        $chunks = array_chunk($rows, 30);
        if ($chunks === []) {
            $chunks = [[]];
        }

        foreach ($chunks as $pageIndex => $chunk) {
            $lines = [$title, 'Generated: '.now()->format('Y-m-d H:i'), ''];
            $lines[] = implode(' | ', array_map(fn ($column) => str($column)->headline()->toString(), $columns));
            $lines[] = str_repeat('-', 110);

            foreach ($chunk as $row) {
                $lines[] = self::line($columns, $row);
            }

            $content = "BT\n/F1 8 Tf\n50 550 Td\n";
            foreach ($lines as $line) {
                $content .= '('.self::escape($line).") Tj\n0 -14 Td\n";
            }
            $content .= "ET\n";

            $contentId = count($objects) + 1;
            $objects[] = "<< /Length ".strlen($content)." >>\nstream\n{$content}endstream";
            $pageId = count($objects) + 1;
            $pages[] = $pageId;
            $objects[] = "<< /Type /Page /Parent 0 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 0 0 R >> >> /Contents {$contentId} 0 R >>";
        }

        $fontId = count($objects) + 1;
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $pagesId = count($objects) + 1;
        $kids = implode(' ', array_map(fn ($id) => "{$id} 0 R", $pages));
        $objects[] = "<< /Type /Pages /Kids [{$kids}] /Count ".count($pages).' >>';
        $catalogId = count($objects) + 1;
        $objects[] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        foreach ($pages as $pageId) {
            $objects[$pageId - 1] = str_replace(['/Parent 0 0 R', '/F1 0 0 R'], ["/Parent {$pagesId} 0 R", "/F1 {$fontId} 0 R"], $objects[$pageId - 1]);
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private static function line(array $columns, array $row): string
    {
        return collect($columns)
            ->map(fn (string $column) => str((string) ($row[$column] ?? '-'))->limit(28, '')->toString())
            ->implode(' | ');
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\(', '\)', ' ', ' '], $value);
    }
}
