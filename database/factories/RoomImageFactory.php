<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RoomImage;
use App\Models\Room;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomImage>
 */
class RoomImageFactory extends Factory
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
            "room_id" => Room::inRandomOrder()->first()->id,
            "image_url" => $this->faker->imageUrl(),
            "image_description" => $this->faker->text(30),
        ];
    }
}
