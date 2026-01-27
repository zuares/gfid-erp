<?php

// namespace App\Console;

// use Illuminate\Console\Scheduling\Schedule;
// use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// class Kernel extends ConsoleKernel
// {
//     /**
//      * Register the commands for the application.
//      */
//     protected function commands(): void
//     {
//         // Auto-load commands dari app/Console/Commands
//         $this->load(__DIR__ . '/Commands');

//         // Optional: routes/console.php
//         // require base_path('routes/console.php');
//     }

//     /**
//      * Define the application's command schedule.
//      */
//     protected function schedule(Schedule $schedule): void
//     {
//         // =========================
//         // SALES → DAILY ITEM SALES
//         // =========================
//         $schedule->command('sales:rebuild-daily-item-sales --days=90')
//             ->everyFifteenMinutes()
//             ->withoutOverlapping()
//             ->runInBackground();

//         // =========================
//         // INVENTORY → ADS
//         // =========================
//         $schedule->command('inventory:recalc-ads-from-daily --days=30 --only-active=1')
//             ->everyFifteenMinutes()
//             ->withoutOverlapping()
//             ->runInBackground();
//     }
// }

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            file_put_contents(
                storage_path('logs/cron-test.log'),
                "CRON OK @ " . now() . PHP_EOL,
                FILE_APPEND
            );
        })->everyMinute();

    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
