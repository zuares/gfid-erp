<?php

use App\Console\Commands\SeedProductionBoms;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'access' => EnsureModuleAccess::class,
            'role' => RoleMiddleware::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withCommands([
        SeedProductionBoms::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        // ✅ Ini yang bikin scheduler Laravel 12 aktif
        // Kamu bisa taruh TEST di sini dulu:

        // $schedule->call(function () {
        //     file_put_contents(
        //         storage_path('logs/cron-test.log'),
        //         "CRON OK @ " . now() . PHP_EOL,
        //         FILE_APPEND
        //     );
        // })->everyMinute();

        $schedule->command('sales:rebuild-daily-item-sales --days=90')
            ->dailyAt('01:00')
            ->withoutOverlapping();

        $schedule->command('inventory:recalc-ads-from-daily --days=30 --only-active=1')
            ->dailyAt('01:10')
            ->withoutOverlapping();

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
