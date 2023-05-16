<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
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
            "category_id" => Category::inRandomOrder()->first()->id,
            "name" => $this->faker->name,
            "size" => $this->faker->numberBetween(10, 100),
            "bed" => $this->faker->numberBetween(1, 2),
            "bathroom_facilities" => $this->faker->text(100),
            "amenities" => $this->faker->text(100),
            "directions_view" => $this->faker->text(100),
            "description" => $this->faker->text(100),
            "status" => $this->faker->boolean(),
            "max_people" => $this->faker->numberBetween(1, 3),
            "price" => $this->faker->numberBetween(100, 1000),
            "is_smoking" => $this->faker->boolean(),
        ];
    }
}
