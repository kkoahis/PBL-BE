<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\Account;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            "name" => $this->faker->name,
            "address" => $this->faker->address,
            "hotline" => $this->faker->phoneNumber,
            "email" => $this->faker->email,
            "description" => $this->faker->text,
            "room_total" => $this->faker->numberBetween(0, 50),
            "parking_slot" => $this->faker->numberBetween(0, 30),
            "bathrooms" => $this->faker->numberBetween(0, 50),
            "rating" => $this->faker->randomFloat(1, 4, 5),
            // create_by is foreign key of account table with role is admin with any id
            "created_by" => User::where('role', 'hotel')->inRandomOrder()->first()->id,
        ];
    }
}
