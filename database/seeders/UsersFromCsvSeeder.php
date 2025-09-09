<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\Region;
use App\Models\Serpo;
use App\Models\Segmen;
use App\Models\KategoriUser;
use App\Models\UserBestrising;

class UsersFromCsvSeeder extends Seeder
{
    private string $csvPath = 'database/seeders/data/users_seed.csv';

    public function run(): void
    {
        $fullPath = base_path($this->csvPath);
        if (!file_exists($fullPath)) {
            $this->command?->error("CSV tidak ditemukan: {$fullPath}");
            return;
        }

        // ===== deteksi kolom di tabel user_bestrising =====
        $cols = Schema::getColumnListing('user_bestrising');

        // login key (email atau username)
        $loginCol = null;
        foreach (['email','username','user_name','login','uid'] as $c) {
            if (in_array($c, $cols, true)) { $loginCol = $c; break; }
        }
        if (!$loginCol) {
            $this->command?->error("Tabel user_bestrising tidak punya kolom login (email/username). Tambahkan salah satu kolom itu dulu.");
            return;
        }

        $nameCol     = in_array('nama', $cols, true) ? 'nama' : (in_array('name', $cols, true) ? 'name' : null);
        $passwordCol = in_array('password', $cols, true) ? 'password' : null;
        $nikCol      = in_array('nik', $cols, true) ? 'nik' : null;
        $regionFk    = in_array('id_region', $cols, true) ? 'id_region' : null;
        $serpoFk     = in_array('id_serpo',  $cols, true) ? 'id_serpo'  : null;
        $katFk       = in_array('kategori_user_id', $cols, true) ? 'kategori_user_id' : null;

        $rows = $this->readCsv($fullPath);

        DB::transaction(function () use ($rows, $loginCol, $nameCol, $passwordCol, $nikCol, $regionFk, $serpoFk, $katFk) {
            $seen = [];
            $ok = $skip = 0;

            // cari id kategori default: SERPO > NOC > ADMIN
            $katSerpo = KategoriUser::where('nama_kategoriuser', 'like', '%SERPO%')->value('id_kategoriuser');
            $katNoc   = KategoriUser::where('nama_kategoriuser', 'like', '%NOC%')->value('id_kategoriuser');
            $katAdmin = KategoriUser::where('nama_kategoriuser', 'like', '%ADMIN%')->value('id_kategoriuser');

            foreach ($rows as $row) {
                $regionRaw = trim((string)($row['Region'] ?? ''));
                $serpoName = trim((string)($row['Serpo']  ?? ''));
                $name      = trim((string)($row['Name']   ?? ''));
                $loginVal  = trim((string)($row['Username'] ?? '')); // dari CSV: Username (isi email)
                $plainPass = (string)($row['Password'] ?? '');

                if ($regionRaw === '' || $serpoName === '' || $loginVal === '' || $name === '') { $skip++; continue; }

                $key = mb_strtolower($loginVal);
                if (isset($seen[$key])) { $skip++; continue; }
                $seen[$key] = true;

                // normalisasi region (ambil isi kurung)
                $regionName = $this->normalizeRegion($regionRaw);

                // upsert region & serpo
                $region = Region::firstOrCreate(['nama_region' => $regionName]);
                $serpo  = Serpo::firstOrCreate([
                    'id_region'  => $region->id_region ?? $region->id,
                    'nama_serpo' => $serpoName,
                ]);

                // siapkan defaults untuk insert
                $defaults = [];

                if ($nameCol)     $defaults[$nameCol] = $name;
                if ($passwordCol) $defaults[$passwordCol] = Hash::make($plainPass !== '' ? $plainPass : 'password123');
                if ($regionFk)    $defaults[$regionFk] = $region->id_region ?? $region->id;
                if ($serpoFk)     $defaults[$serpoFk]  = $serpo->id_serpo ?? $serpo->id;

                // kategori default → SERPO (kalau ada kolomnya)
                if ($katFk) {
                    $defaults[$katFk] = $katSerpo ?? $katNoc ?? $katAdmin ?? null;
                }

                // generate nik ala controller (RS-YYYYMMDD-xxxx)
                if ($nikCol) {
                    $defaults[$nikCol] = $this->generateNik();
                }

                // insert/find by login
                $user = UserBestrising::firstOrCreate(
                    [$loginCol => $loginVal],
                    $defaults
                );

                // AUTO SYNC segmen kalau kategori yg dipakai = SERPO
                if ($katFk && $user->$katFk && $user->$katFk === ($katSerpo ?? -1)) {
                    $segmenIds = Segmen::where('id_serpo', $serpo->id_serpo ?? $serpo->id)->pluck('id_segmen')->all();
                    $user->segmens()->sync($segmenIds);
                }

                $ok++;
            }

            $this->command?->info("✅ Users seeding selesai. OK: {$ok}, dilewati: {$skip}. (login key: {$loginCol})");
        });
    }

    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'r'); if (!$fh) return [];
        $rows = [];
        $headers = [];
        if (($first = fgetcsv($fh)) !== false) {
            $headers = array_map(fn($h) => trim(str_replace("\xEF\xBB\xBF",'',(string)$h)), $first);
        }
        while (($cols = fgetcsv($fh)) !== false) {
            if (count($cols) === 1 && trim((string)$cols[0]) === '') continue;
            $row = [];
            foreach ($cols as $i => $val) {
                $key = $headers[$i] ?? "col{$i}";
                $row[$key] = is_string($val) ? trim($val) : $val;
            }
            $rows[] = [
                'Region'   => (string)($row['Region'] ?? ''),
                'Serpo'    => (string)($row['Serpo'] ?? ''),
                'Name'     => (string)($row['Name'] ?? ''),
                'Username' => (string)($row['Username'] ?? ''), // isi email
                'Password' => (string)($row['Password'] ?? ''),
            ];
        }
        fclose($fh);
        return $rows;
    }

    private function normalizeRegion(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/\(([^)]+)\)/u', $raw, $m) && !empty($m[1])) return trim($m[1]);
        $lower = mb_strtolower($raw);
        $aliases = [
            'nsro' => 'North Sumatera',
            'ssro' => 'South Sumatera',
            'jabodetabek' => 'Jabodetabek',
            'west java' => 'West Java',
            'central java' => 'Central Java',
            'kalimantan' => 'Kalimantan',
        ];
        foreach ($aliases as $k => $v) if (strpos($lower, $k) !== false) return $v;
        return $raw;
    }

    // === sama persis dengan versi di controller kamu ===
    private function generateNik(): string
    {
        $prefixDate = Carbon::now()->format('Ymd');
        $prefix = "RS-{$prefixDate}-";

        $lastNik = UserBestrising::where('nik', 'like', $prefix.'%')
            ->orderByDesc('nik')
            ->value('nik');

        $next = 1;
        if ($lastNik) {
            $num = (int) substr($lastNik, -4);
            $next = $num + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }
}
