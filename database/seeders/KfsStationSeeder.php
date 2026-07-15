<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KfsStationSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/KFS Stations.xlsx');

        if (! file_exists($path)) {
            return;
        }

        $sheet = IOFactory::load($path)->getActiveSheet();
        $parentIds = [];

        foreach ($this->stationRows($sheet) as $station) {
            $parentId = match ($station['station_type']) {
                'county' => $parentIds['conservancy'][$station['region']] ?? null,
                'forest_station' => $parentIds['county'][$station['region'].'|'.$station['county']] ?? null,
                default => null,
            };

            DB::table('stations')->updateOrInsert(
                ['code' => $station['code']],
                $this->row([
                    'parent_id' => $parentId,
                    'name' => $station['name'],
                    'station_type' => $station['station_type'],
                    'county' => $station['county'],
                    'region' => $station['region'],
                    'is_active' => true,
                ])
            );

            $id = DB::table('stations')->where('code', $station['code'])->value('id');

            if ($station['station_type'] === 'conservancy') {
                $parentIds['conservancy'][$station['region']] = $id;
            }

            if ($station['station_type'] === 'county') {
                $parentIds['county'][$station['region'].'|'.$station['county']] = $id;
            }
        }
    }

    /**
     * @return array<int, array{code: string, name: string, station_type: string, county: ?string, region: string}>
     */
    private function stationRows(Worksheet $sheet): array
    {
        $currentConservancy = null;
        $currentCounty = null;
        $rows = [];

        foreach ($sheet->toArray(null, true, true, true) as $index => $row) {
            if ($index < 3) {
                continue;
            }

            $conservancy = $this->clean($row['B'] ?? null);
            $county = $this->clean($row['C'] ?? null);
            $station = $this->clean($row['D'] ?? null);

            if ($conservancy) {
                $currentConservancy = Str::headline($conservancy);
            }

            if ($county) {
                $currentCounty = $county;
            }

            if ($currentConservancy) {
                $rows[] = [
                    'station_type' => 'conservancy',
                    'name' => $currentConservancy,
                    'region' => $currentConservancy,
                    'county' => null,
                ];
            }

            if ($currentConservancy && $currentCounty) {
                $rows[] = [
                    'station_type' => 'county',
                    'name' => $currentCounty,
                    'region' => $currentConservancy,
                    'county' => $currentCounty,
                ];
            }

            if ($currentConservancy && $currentCounty && $station) {
                $rows[] = [
                    'station_type' => 'forest_station',
                    'name' => $station,
                    'region' => $currentConservancy,
                    'county' => $currentCounty,
                ];
            }
        }

        return collect($rows)
            ->unique(fn (array $row): string => implode('|', [
                $row['station_type'],
                Str::lower($row['name']),
                Str::lower($row['region']),
                Str::lower((string) $row['county']),
            ]))
            ->values()
            ->map(fn (array $row): array => $row + ['code' => $this->code($row)])
            ->all();
    }

    private function clean(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Str::of((string) $value)
            ->replace("\u{00A0}", ' ')
            ->replaceMatches('/^\d+\.\s*/', '')
            ->squish()
            ->toString();
    }

    /**
     * @param array{name: string, station_type: string, county: ?string, region: string} $row
     */
    private function code(array $row): string
    {
        $parts = match ($row['station_type']) {
            'conservancy' => ['CONS', $row['name']],
            'county' => ['CNTY', $row['region'], $row['name']],
            default => ['STN', $row['region'], $row['county'], Str::replace(' Forest Station', '', $row['name'])],
        };

        return Str::of(implode('-', array_filter($parts)))
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->limit(40, '')
            ->toString();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function row(array $attributes = []): array
    {
        return $attributes + [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
