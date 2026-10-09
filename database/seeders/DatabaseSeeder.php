<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AngkutanSeeder::class,
            RuteSeeder::class,
            TitikPemberhentianSeeder::class,
            JadwalSeeder::class,
            TarifSeeder::class,
            VerifikasiSeeder::class,
            UserSeeder::class,
            JobSeeder::class,
        ]);
    }
}