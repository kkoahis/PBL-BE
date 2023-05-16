<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\HotelImage;
use App\Models\Hotel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HotelImage>
 */
class HotelImageFactory extends Factory
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
            "hotel_id" => Hotel::inRandomOrder()->first()->id,
            "image_url" => $this->faker->imageUrl(),
            "image_description" => $this->faker->text(30),
        ];
    }
}
