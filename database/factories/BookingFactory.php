<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
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
            //is booking with user has role user in database hotel
            "user_id" => User::where('role', 'user')->inRandomOrder()->first()->id,
            "hotel_id" => Hotel::inRandomOrder()->first()->id,
            "room_count" => $this->faker->numberBetween(1, 5),
            // total amount is Dollar
            "total_amount" => $this->faker->numberBetween(100, 2000),
            "status" => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            "description" => $this->faker->text(100),
            "is_payment" => $this->faker->boolean,
            "payment_type" => $this->faker->randomElement(['cash', 'card']),

            // date checkin and checkout
            "date_in" => $this->faker->dateTimeBetween('now', '+1 years'),
            "date_out" => $this->faker->dateTimeBetween('+1 years', '+2 years'),
            "date_booking" => $this->faker->dateTimeBetween('-1 years', 'now'),
        ];
    }
}
