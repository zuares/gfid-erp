<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Http\Client\Events\ResponseReceived::class, function ($event) {
            $url = $event->request->url();
            if (str_contains($url, 'shopee')) {
                try {
                    $log = \App\Models\ShopeeApiLog::create([
                        'method' => $event->request->method(),
                        'endpoint' => $url,
                        'request_payload' => $event->request->data(),
                        'response_payload' => $event->response->json() ?? ['raw' => $event->response->body()],
                        'status_code' => $event->response->status(),
                        'duration' => $event->response->handlerStats()['total_time'] * 1000 ?? 0,
                    ]);
                    event(new \App\Events\ShopeeApiLogged($log));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to log Shopee API: ' . $e->getMessage());
                }
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Http\Client\Events\ConnectionFailed::class, function ($event) {
            $url = $event->request->url();
            if (str_contains($url, 'shopee')) {
                try {
                    $log = \App\Models\ShopeeApiLog::create([
                        'method' => $event->request->method(),
                        'endpoint' => $url,
                        'request_payload' => $event->request->data(),
                        'response_payload' => null,
                        'status_code' => 500, // or 0
                        'duration' => 0,
                    ]);
                    event(new \App\Events\ShopeeApiLogged($log));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to log Shopee API (ConnectionFailed): ' . $e->getMessage());
                }
            }
        });
        \App\Models\PurchaseReturnLine::observe(\App\Observers\PurchaseReturnLineObserver::class);
        Paginator::useBootstrapFive();
        Carbon::setLocale('id');

        RateLimiter::for('stock-read', function ($request) {
            $token = $request->user()?->currentAccessToken();
            $key = $token?->id
                ?? $request->user()?->id
                ?? $request->ip();

            return [
                Limit::perMinute(30)->by((string) $key),
                Limit::perHour(500)->by((string) $key),
            ];
        });

        // Share 5 snapshot terbaru ke layout (untuk quick rollback di navbar)
        View::composer('layouts.app', function ($view) {
            $snapshotDir = storage_path('backups/snapshots');
            $snapshots = [];

            if (File::isDirectory($snapshotDir)) {
                $files = collect(File::files($snapshotDir))
                    ->filter(fn($f) => str_ends_with($f->getFilename(), '.sqlite'))
                    ->sortByDesc(fn($f) => $f->getMTime())
                    ->take(3);

                foreach ($files as $f) {
                    $name = $f->getFilename();
                    preg_match('/^snap_(\d{8})_(\d{6})(?:_(.+))?\.sqlite$/', $name, $m);
                    $date  = isset($m[1], $m[2])
                        ? Carbon::createFromFormat('Ymd His', $m[1] . ' ' . $m[2])
                        : null;
                    $label = isset($m[3]) ? str_replace('_', ' ', $m[3]) : null;

                    $snapshots[] = [
                        'filename' => $name,
                        'label'    => $label,
                        'date'     => $date,
                        'size_mb'  => round($f->getSize() / 1024 / 1024, 2),
                    ];
                }
            }

            $view->with('gfSnapshots', $snapshots);
        });
    }
}
