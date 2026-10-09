<?php

namespace Database\Seeders;

use App\Models\Rute;
use App\Models\TitikPemberhentian;
use Illuminate\Database\Seeder;

class TitikPemberhentianSeeder extends Seeder
{
    public function run(): void
    {
        Rute::all()->each(function ($rute) {
            $jumlah = rand(3, 6);

            for ($i = 1; $i <= $jumlah; $i++) {
                TitikPemberhentian::create([
                    'rute_id' => $rute->id,
                    'nama'    => fake()->streetName(),
                    'urutan'  => $i,
                ]);
            }
        });
    }
}
