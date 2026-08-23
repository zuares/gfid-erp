<?php

use App\Console\Commands\SendWeeklyCrmSummary;
use App\Console\Commands\SeedProductionBoms;
use App\Http\Middleware\EnsureMasterItemAccess;
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
            'master.items'     => EnsureMasterItemAccess::class,
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
        
        // 1. Sinkronisasi data terbaru setiap jam (hari ini & kemarin)
        $schedule->call(function () {
            \Illuminate\Support\Facades\Artisan::call('marketplace:sync-ads', [
                '--from'   => now()->subDay()->toDateString(),
                '--to'     => now()->toDateString(),
                '--hourly' => true,
            ]);
        })
        ->everyFiveMinutes()
        ->when(function () {
            $key = 'sync_ads_hourly_' . now()->format('Y-m-d-H');
            if (\Illuminate\Support\Facades\Cache::has($key)) {
                return false;
            }
            \Illuminate\Support\Facades\Cache::put($key, true, 3600);
            return true;
        })
        ->name('sync-ads-hourly-latest')
        ->withoutOverlapping(30);

        // 2. Verifikasi backfill harian (mundur 14 hari untuk update data telat dari Shopee)
        $schedule->command('marketplace:sync-ads')
            ->everyFiveMinutes()
            ->when(function () {
                $key = 'sync_ads_daily_' . now()->format('Y-m-d');
                if (\Illuminate\Support\Facades\Cache::has($key)) {
                    return false;
                }
                \Illuminate\Support\Facades\Cache::put($key, true, 86400);
                return true;
            })
            ->name('sync-ads-midnight-verify')
            ->withoutOverlapping(60)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/sync-ads.log'));

        // Backfill chat audit lama: isi raw_payload/raw_context dan kaitkan webhook_log_id
        // untuk message yang sempat terlewat dari jalur webhook lama.
        $schedule->command('marketplace:repair-chat-raw-payloads', ['--limit' => 1000])
            ->dailyAt('02:20')
            ->name('repair-chat-raw-payloads')
            ->withoutOverlapping();

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
