<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Review;
use App\Models\Account;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reply>
 */
class ReplyFactory extends Factory
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
            'review_id' => Review::inRandomOrder()->first()->id,
            'account_id' => User::inRandomOrder()->first()->id,
            'content' => $this->faker->text(200),
            'date_reply' => $this->faker->dateTimeBetween('-1 years', 'now'),
            'date_update' => $this->faker->dateTimeBetween('-1 years', 'now'),
        ];
    }
}
