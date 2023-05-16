<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Review;
use App\Models\Booking;
use App\Models\Account;
use App\Models\Hotel;
use Faker\Provider\ar_EG\Text;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //each review belongs to a booking
            //each review belongs to an account
            // title = "title"
            'title' => $this->faker->sentence(3, true),
            'content' =>$this->faker->text(200),
            'date_review' => $this->faker->dateTimeBetween('-1 years', 'now'),
            'date_update' => $this->faker->dateTimeBetween('-1 years', 'now'),
            'rating' => $this->faker->numberBetween(3, 5),
        ];
    }
}
