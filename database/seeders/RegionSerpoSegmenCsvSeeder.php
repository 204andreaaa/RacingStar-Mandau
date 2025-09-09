<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Region;
use App\Models\Serpo;
use App\Models\Segmen;

class RegionSerpoSegmenCsvSeeder extends Seeder
{
    private string $csvPath = 'database/seeders/data/region_serpo_segmen.csv';

    public function run(): void
    {
        $fullPath = base_path($this->csvPath);
        if (!file_exists($fullPath)) {
            $this->command?->error("CSV tidak ditemukan: {$fullPath}");
            return;
        }

        $rows = $this->readCsv($fullPath);

        DB::transaction(function () use ($rows) {
            $seen = [];
            $count = ['region'=>0,'serpo'=>0,'segmen'=>0];

            foreach ($rows as $row) {
                $regionRaw = trim((string)($row['Region'] ?? ''));
                $serpoName = trim((string)($row['Serpo']  ?? ''));
                $segmenName= trim((string)($row['Segmen'] ?? ''));

                if ($regionRaw === '' || $serpoName === '' || $segmenName === '') {
                    continue;
                }

                $regionName = $this->normalizeRegion($regionRaw);

                $dedupKey = mb_strtolower($regionName.'|'.$serpoName.'|'.$segmenName);
                if (isset($seen[$dedupKey])) continue;
                $seen[$dedupKey] = true;

                // Region
                $region = Region::firstOrCreate(['nama_region' => $regionName]);
                $count['region']++;

                // Serpo (scoped ke region)
                $serpo = Serpo::firstOrCreate([
                    'id_region'  => $region->id_region ?? $region->id,
                    'nama_serpo' => $serpoName,
                ]);
                $count['serpo']++;

                // Segmen (scoped ke serpo) — tanpa id_region
                Segmen::firstOrCreate([
                    'id_serpo'    => $serpo->id_serpo ?? $serpo->id,
                    'nama_segmen' => $segmenName,
                ]);
                $count['segmen']++;
            }

            $this->command?->info("✅ Import selesai. Region diproses: {$count['region']}, Serpo diproses: {$count['serpo']}, Segmen dibuat/ada: {$count['segmen']}");
        });
    }

    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) return [];
        $rows = [];

        $headers = [];
        if (($first = fgetcsv($fh)) !== false) {
            $headers = array_map(function ($h) {
                $h = str_replace("\xEF\xBB\xBF", '', (string)$h);
                $k = mb_strtolower(trim($h));
                if (in_array($k, ['region','wilayah'])) return 'Region';
                if (in_array($k, ['serpo','spv','area'])) return 'Serpo';
                if (in_array($k, ['segmen','segment','cluster'])) return 'Segmen';
                return trim($h);
            }, $first);
        }

        while (($cols = fgetcsv($fh)) !== false) {
            if (count($cols) === 1 && trim((string)$cols[0]) === '') continue;
            $row = [];
            foreach ($cols as $i => $val) {
                $key = $headers[$i] ?? "col{$i}";
                $row[$key] = is_string($val) ? trim($val) : $val;
            }
            $rows[] = [
                'Region' => (string)($row['Region'] ?? ''),
                'Serpo'  => (string)($row['Serpo']  ?? ''),
                'Segmen' => (string)($row['Segmen'] ?? ''),
            ];
        }

        fclose($fh);
        return $rows;
    }

    private function normalizeRegion(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/\(([^)]+)\)/u', $raw, $m) && !empty($m[1])) {
            return trim($m[1]);
        }
        $lower = mb_strtolower($raw);
        $aliases = [
            'nsro' => 'North Sumatera',
            'ssro' => 'South Sumatera',
            'jabodetabek' => 'Jabodetabek',
            'west java' => 'West Java',
            'central java' => 'Central Java',
            'kalimantan' => 'Kalimantan',
        ];
        foreach ($aliases as $k => $v) {
            if (strpos($lower, $k) !== false) return $v;
        }
        return $raw;
    }
}
