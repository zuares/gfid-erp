<?php

use App\Console\Commands\SendWeeklyCrmSummary;
use App\Console\Commands\SeedProductionBoms;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TrackStorefrontVisitor;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'access'           => EnsureModuleAccess::class,
            'role'             => RoleMiddleware::class,
            'track.storefront' => TrackStorefrontVisitor::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withCommands([
        SeedProductionBoms::class,
        SendWeeklyCrmSummary::class,
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

        // CRM: weekly summary ke admin WA setiap Senin jam 08:00
        $schedule->command('crm:weekly-summary')
            ->weeklyOn(1, '08:00')
            ->withoutOverlapping();

        $schedule->command('sales:rebuild-daily-item-sales --days=90')
            ->dailyAt('01:00')
            ->withoutOverlapping();

        $schedule->command('inventory:recalc-ads-from-daily --days=30 --only-active=1')
            ->dailyAt('01:10')
            ->withoutOverlapping();

        // ── Shopee Ads: sinkronisasi otomatis ───────────────────────────
        // Setiap 4 jam: sync data harian (Balance + Campaigns + CPC + GMS) 3 hari terakhir
        $schedule->command('marketplace:sync-ads')
            ->everyFourHours()
            ->withoutOverlapping(30)
            ->name('sync-ads-main');

        // Setiap jam: sync data per jam (untuk heatmap)
        $schedule->command('marketplace:sync-ads', ['--hourly'])
            ->hourly()
            ->withoutOverlapping(30)
            ->name('sync-ads-hourly');

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
