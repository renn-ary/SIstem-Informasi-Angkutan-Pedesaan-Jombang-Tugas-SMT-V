<?php

namespace Database\Factories;

use App\Models\Angkutan;
use App\Models\Report;
use App\Models\Rute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'penumpang']),
            'route_id' => Rute::factory()->state([
                'angkutan_id' => Angkutan::factory(),
                'asal' => fake()->city(),
                'tujuan' => fake()->city(),
                'jalur' => fake()->sentence(3),
            ]),
            'category' => fake()->randomElement(['Pelayanan', 'Jadwal', 'Tarif', 'Kebersihan']),
            'description' => fake()->paragraph(),
            'photo' => null,
            'latitude' => null,
            'longitude' => null,
            'status' => Report::STATUS_MENUNGGU_TINDAK_LANJUT,
            'response' => null,
        ];
    }
}
