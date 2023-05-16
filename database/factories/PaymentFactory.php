<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Payment;
use App\Models\Booking;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
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
            //payment with bookingID available
            'qr_code' => $this->faker->text(50),
            'qr_code_url' => $this->faker->text(50),
            'total_amount' => $this->faker->numberBetween(100, 1000),
            'payment_status' => $this->faker->randomElement([0, 1]),
            'discount' => $this->faker->numberBetween(5, 10),
            'booking_id' => Booking::all()->random()->id,
        ];
    }
}
