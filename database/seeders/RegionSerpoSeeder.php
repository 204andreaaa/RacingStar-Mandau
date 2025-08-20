<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\Serpo;
use Illuminate\Support\Facades\DB;

class RegionSerpoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $data = [
                'North Sumatera' => [
                    'Banda Aceh','Sigli','Lhokseumawe','Langsa','Pelawi',
                    'Medan 1','Medan 2','Tebing Tinggi','Kisaran / Jadijaya',
                    'Ranto Prapat / Sripinang','Padangsidempuan','Tarutung',
                    'Saribudolok','Tiga Binanga','Subulussalam','Takengon',
                    'Ujung Tanjung','Kandis','Aster','Dumai','Jondul',
                    'Pekanbaru','Bangkinang','Merangin','Taluk Kuantan',
                    'Ukui','Selensen','Dusun Mundo','Dharmasraya','Solok',
                    'Lubuk Begalung','Payakumbuh','Bukit Tinggi','Bawaan',
                    'Panti','Kotanopan','Road Natal'
                ],
                'South Sumatera' => [
                    'Abung Selatan','Batam 01','Batam 02','Batam 03',
                    'Tempino','Bengkulu 01','Bengkulu 02','Curup','Jambi',
                    'Lahat','Lampung 01','Kedaton','Lubuklinggau 01',
                    'Muara Enim','Palembang 01','Palembang 02','Palembang 03',
                    'Prabumulih','Pulaunegara','Sungai Lilin','Liwa',
                    'Sribawono','Metro','Mengala/Gn Batin','Pringsewu/Kota Agung',
                    'Krui','Martapura','OKI Tugu Mulyo','Sekayu',
                    'Talangkawo (Ex-LubukLinggau 02)','Pagar Alam','Kuala Tungkal',
                    'Muara Bungo','Sarolangun','Manna','Pangkal Pinang',
                    'Sungai Penuh','Tapan','Add Kelapa'
                ],
                'Jabodetabek' => [
                    'Jakarta Pusat','Jakarta Selatan','Additional Jakarta Selatan',
                    'Jakarta Barat','Jakarta Timur','Additional Jakarta Utara',
                    'Jakarta Utara','Depok Outer','Depok Inner','Bogor Timur',
                    'Bogor Barat','Sukabumi Barat','Sukabumi Selatan','Sukabumi Utara',
                    'Bekasi Utara','Bekasi Selatan','Bekasi Barat','Cikarang',
                    'Karawang Timur','Karawang Barat','Tangerang Utara',
                    'Tangerang Selatan','Tangerang Inner','Cilegon','Serang Barat',
                    'Serang Timur','Pandeglang','Panimbang','Anyer','Cianjur',
                    'Sindangbarang','Sukanagara'
                ],
                'West Java' => [
                    'Bandung 1','Bandung 2','Bandung 3','Banjar','Ciamis',
                    'Cianjur','Cirebon 1','Cirebon 2','Ciwedey','Garut Selatan',
                    'Garut Utara','Indramayu','Kuningan','Losari','Majalengka',
                    'Purwakarta 1','Purwakarta 2','Subang','Tasikmalaya Selatan',
                    'Tasikmalaya Utara'
                ],
                'Central Java' => [
                    'Blora','Cilacap','Kebumen','Kendal','Klaten','Kudus',
                    'Magelang','Pati','Pekalongan','Pemalang','Purwodadi 1',
                    'Purwodadi 2','Purwokerto','Rembang','Salatiga','Semarang 1',
                    'Semarang 2','Semarang 3','Sleman','Sokaraja','Solo',
                    'Sragen','Tegal','Temanggung','Wonosari','Yogyakarta'
                ],
                'Kalimantan' => [
                    'Bengkayang','Ketapang','Nanga Tayap','Pahuman','Pontianak 1',
                    'Pontianak 2','Sambas','Sanggau','Semitau','Simpang Tayan',
                    'Singkawang','Putussibau','Sintang','Palangkaraya','Sampit',
                    'Pangkalan Bun','Nangabulik','Muara Teweh','Gunung Purei',
                    'Bentian','Banjarmasin 1','Banjarmasin 2','Banjarmasin 3',
                    'Tapin','Barabai','Tanjung','Batu Licin','Jambuk',
                    'Balikpapan 1','Balikpapan 2','Samarinda 1','Samarinda 2',
                    'Sempaja','Bontang','Bengalon','Muara Wahau','Melak',
                    'Kota Bangun','Petung','Km.38','Tanah Grogot','Tarakan'
                ]
            ];

            foreach ($data as $regionName => $serpos) {
                $region = Region::firstOrCreate(['nama_region' => $regionName]);

                foreach ($serpos as $serpoName) {
                    Serpo::firstOrCreate([
                        'id_region'   => $region->id_region,
                        'nama_serpo'  => $serpoName,
                    ]);
                }
            }
        });

        $this->command?->info('✅ Region & Serpo berhasil di-seed!');
    }
}
