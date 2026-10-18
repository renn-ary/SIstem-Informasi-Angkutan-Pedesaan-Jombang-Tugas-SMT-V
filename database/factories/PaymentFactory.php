<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => fake()->numberBetween(5, 50) * 1000,
            'method' => fake()->randomElement(['tunai', 'transfer']),
            'paid_at' => now(),
            'status' => Payment::STATUS_LUNAS,
        ];
    }
}
