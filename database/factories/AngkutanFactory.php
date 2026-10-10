<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AngkutanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->company(),
            'kode' => fake()->unique()->bothify('ANG-###'),
            'jenis' => fake()->randomElement([
                'Angkutan Pedesaan',
                'Minibus',
            ]),
            'status_operasional' => fake()->randomElement([
                'aktif',
                'tidak_aktif',
            ]),
            'operator_id' => User::factory()->state(['role' => 'pengemudi']),
            'vehicle_number' => fake()->unique()->bothify('VH-####'),
            'license_plate' => fake()->unique()->bothify('B #### ???'),
            'capacity' => fake()->numberBetween(1, 30),
        ];
    }
}
