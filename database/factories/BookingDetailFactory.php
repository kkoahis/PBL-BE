<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingDetail>
 */
class BookingDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //booking detail with bookingID available
            'booking_id' => DB::table('booking')->inRandomOrder()->first()->id,
            'room_id' => DB::table('room')->inRandomOrder()->first()->id,
        ];
    }
}
