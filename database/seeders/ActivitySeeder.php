<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['team_id'=>1,'name'=>'Rajin dan cepat memberikan Update Progress, kelengkapan laporan dengan foto before-after, lokasi, kronologis (Patrol, PM, CM)','description'=>'Bersihin meja/kursi/peralatan','point'=>10,'is_active'=>true],
            ['team_id'=>1,'name'=>'Deklar OP sesuai dan tepat waktu','description'=>'Cek alat sebelum dipakai','point'=>15,'is_active'=>true],
            ['team_id'=>1,'name'=>'Menggunakan APD yang sesuai dalam pelaksanaan pekerjaan','description'=>'Isi form setiap akhir shift','point'=>5,'is_active'=>true],
            ['team_id'=>1,'name'=>'Melakukan perawatan berkala untuk OTDR, Splicer, & All Tools Pendukung','description'=>'Cek & catat stok','point'=>20,'is_active'=>true],
            ['team_id'=>1,'name'=>'Kebersihan Serpo','description'=>'Bersih area sekitar rutin','point'=>8,'is_active'=>true],
            ['team_id'=>1,'name'=>'Kebersihan Kendaraan Oprational','description'=>'Bersih area sekitar rutin','point'=>8,'is_active'=>true],
            ['team_id'=>1,'name'=>'Laporan Temuan (dan perbaikan langsung jika memungkinkan) : SPOF, tiang/kabel unproper, thieft/vandalism, akses issue, dll','description'=>'Bersih area sekitar rutin','point'=>8,'is_active'=>true],



            ['team_id'=>2,'name'=>'Waktu respon pertama (First Response Time)','description'=>'Bersih area sekitar rutin','point'=>8,'is_active'=>true],
            ['team_id'=>2,'name'=>'Komunikasi & Etika','description'=>'Bersih area sekitar rutin','point'=>8,'is_active'=>true],
            ['team_id'=>2,'name'=>'Produktivitas','description'=>'Bersih area sekitar rutin','point'=>8,'is_active'=>true],
            ['team_id'=>2,'name'=>'Dokumentasi & Laporan (CIR)','description'=>'Bersih area sekitar rutin','point'=>8,'is_active'=>true],
        ];

        foreach ($data as $item) {
            Activity::create($item);
        }
    }
}
