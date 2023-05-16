<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Reply;
use Illuminate\Support\Facades\DB;

class ReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('reply')->insert([
            'review_id' => 2,
            'user_id' => 31,
            'content' => 'This is a reply from admin',
            'date_reply' => now(),
            'date_update' => now(),
        ]);
    }
}
