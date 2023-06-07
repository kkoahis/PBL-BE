<?php

namespace App\Console;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // delete payment if payment_status = 0 every 1 minute using call method
        $schedule->call(function () {
            // Payment::where('payment_status', 0)->delete();
            // create new user every 1 minute using create method
            User::create([
                'name' => 'User ' . rand(1, 100),
                'email' => 'user' . rand(1, 100) . '@gmail.com',
                'password' => bcrypt('Password1'),
            ]);
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
