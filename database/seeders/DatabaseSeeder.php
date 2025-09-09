<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ActivitiesTableSeeder::class);
        $this->call(KategoriUserAndUserSeeder::class);
        $this->call(RegionSerpoSegmenCsvSeeder::class);
        $this->call(UsersFromCsvSeeder::class);
    }
}
