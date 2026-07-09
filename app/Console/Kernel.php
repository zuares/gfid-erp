<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        $schedule->command('inventory:audit-allocated')->dailyAt('01:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        // require base_path('routes/console.php'); // opsional
    }
}
