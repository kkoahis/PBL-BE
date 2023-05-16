<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Seeder;
use App\Models\Hotel;
use App\Models\HotelImage;
use App\Models\Room;
use App\Models\Category;
use App\Models\RoomImage;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Payment;
use App\Models\Reply;
use App\Models\Review;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Hotel::factory()->count(50)->create();
        HotelImage::factory(200)->create();

        Category::factory(150)->create();

        Room::factory(500)->create();
        RoomImage::factory(1500)->create();

        Booking::factory(50)->has(
            Payment::factory()->count(1),
        )->create();
    }
}
