<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\KategoriUser;
use App\Models\UserBestrising;
use App\Models\Region;
use App\Models\Serpo;
use App\Models\Segmen;

class KategoriUserAndUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /** ----------------------------------------------------------------
             * 1) Seed KATEGORI USER (Admin, NOC, Serpo)
             * ----------------------------------------------------------------*/
            $kategoriNames = ['Admin', 'NOC', 'Serpo'];
            $kategoriMap   = [];

            foreach ($kategoriNames as $name) {
                $kat = KategoriUser::firstOrCreate(
                    ['nama_kategoriuser' => $name],
                    [] // kolom lain default
                );
                $kategoriMap[$name] = $kat->id_kategoriuser;
            }

            /** ----------------------------------------------------------------
             * 2) Seed MASTER minimal: Region, Serpo, Segmen
             *    - Kalau master sudah ada, baris2 ini tetap aman (firstOrCreate)
             * ----------------------------------------------------------------*/
            // Region contoh
            $region = Region::firstOrCreate(
                ['nama_region' => 'Region Sample'],
                []
            );

            // Serpo yang berada di region tsb
            $serpo = Serpo::firstOrCreate(
                ['nama_serpo' => 'Serpo Sample', 'id_region' => $region->id_region],
                []
            );

            // Beberapa segmen di serpo tsb
            $segmenNames = ['Segmen Sample'];
            $segmenIds   = [];
            foreach ($segmenNames as $sn) {
                $seg = Segmen::firstOrCreate(
                    ['nama_segmen' => $sn, 'id_serpo' => $serpo->id_serpo],
                    []
                );
                $segmenIds[] = $seg->id_segmen;
            }

            /** ----------------------------------------------------------------
             * 3) Seed USERS:
             *    - Admin & NOC: tanpa region/serpo/segmen
             *    - Serpo User: 1 region, 1 serpo, banyak segmen (pivot)
             * ----------------------------------------------------------------*/
            
            // Super Admin
                $superAdmin = UserBestrising::firstOrCreate(
                    ['email' => 'superadmin@mandau.id'],
                    [
                        'nik'              => 'RS-Mdu-01',
                        'nama'             => 'Super Admin Racing Star',
                        'password'         => Hash::make('Mdu2025!'),
                        'kategori_user_id' => $kategoriMap['Admin'],
                        'id_region'        => null,
                        'id_serpo'         => null,
                        ]
                    );
                    $superAdmin->segmens()->sync([]); // admin tidak punya segmen
            // Admin
                $admin = UserBestrising::firstOrCreate(
                    ['email' => 'admin@mandau.id'],
                    [
                        'nik'              => 'RS-Mdu-02',
                        'nama'             => 'Admin Racing Star',
                        'password'         => Hash::make('Tnc912@)!@'),
                        'kategori_user_id' => $kategoriMap['Admin'],
                        'id_region'        => null,
                        'id_serpo'         => null,
                    ]
                );
                $admin->segmens()->sync([]);
        });
        $this->command?->info('Seeded: kategoriuser, region/serpo/segmen contoh, dan 1 users (Admin) dengan relasi segmen via pivot.');
    }
}
