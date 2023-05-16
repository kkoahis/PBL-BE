<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('review')->insert([
            'booking_id' => 3,
            'user_id' => 25,
            'title' => 'Review 3',
            'content' => 'Review 3',
            'date_review' => now(),
            'date_update' => now(),
            'rating' => 5,
            'created_at' => now(),
        ]);
    }
}
