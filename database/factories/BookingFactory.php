<?php

namespace Database\Factories;

use App\Models\Angkutan;
use App\Models\Booking;
use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_code' => 'BK-'.fake()->unique()->numerify('######'),
            'user_id' => User::factory()->state(['role' => 'penumpang']),
            'schedule_id' => Jadwal::factory()->state([
                'angkutan_id' => Angkutan::factory(),
                'hari' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']),
                'jam_berangkat' => '08:00:00',
                'jam_selesai' => '10:00:00',
            ]),
            'passenger_count' => fake()->numberBetween(1, 4),
            'total_fare' => fake()->numberBetween(5, 50) * 1000,
            'booking_status' => Booking::STATUS_MENUNGGU_PEMBAYARAN,
            'payment_status' => Booking::PEMBAYARAN_MENUNGGU,
            'booked_at' => now(),
        ];
    }
}
