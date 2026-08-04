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
                    $endpoint = parse_url($url, PHP_URL_PATH);
                    $endpoint = is_string($endpoint) && $endpoint !== ''
                        ? $endpoint
                        : preg_replace('/\?.*$/', '', $url);

                    // Simpan metadata/error ringkas saja. Payload penuh dan query
                    // string berisi token Shopee sehingga tidak boleh masuk DB.
                    $responsePayload = [
                        'error' => $event->response->json('error'),
                        'message' => $event->response->json('message'),
                        'request_id' => $event->response->json('request_id'),
                    ];
                    $responsePayload = array_filter(
                        $responsePayload,
                        static fn ($value) => $value !== null && $value !== ''
                    );

                    $log = \App\Models\ShopeeApiLog::create([
                        'method' => $event->request->method(),
                        'endpoint' => $endpoint,
                        'request_payload' => null,
                        'response_payload' => $responsePayload ?: null,
                        'status_code' => $event->response->status(),
                        'duration' => ((float) ($event->response->handlerStats()['total_time'] ?? 0)) * 1000,
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
                    $endpoint = parse_url($url, PHP_URL_PATH);
                    $endpoint = is_string($endpoint) && $endpoint !== ''
                        ? $endpoint
                        : preg_replace('/\?.*$/', '', $url);

                    $log = \App\Models\ShopeeApiLog::create([
                        'method' => $event->request->method(),
                        'endpoint' => $endpoint,
                        'request_payload' => null,
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
