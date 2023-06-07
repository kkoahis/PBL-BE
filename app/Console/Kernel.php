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
        // delete payment after 1 hour
        $schedule->call(function () {
            // $payments = Payment::where('created_at', '<', now()->subHour())->get();
            // foreach ($payments as $payment) {
            //     $payment->delete();
            // }
            // create user in db

            $user = User::create([
                'name' => 'admin',
                'email' => 'test' . rand(1, 100) . '@gmail.com',
                'password' => 'testP123123',
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
