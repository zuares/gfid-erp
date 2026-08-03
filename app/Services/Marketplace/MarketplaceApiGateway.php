<?php

namespace App\Services\Marketplace;

use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Gateway terpusat untuk semua pemanggilan API Marketplace.
 * Menggantikan direct calls ke ChannelManager::driver()->... di controller.
 * Menambahkan:
 * 1. Rate limiting (throttle) per store
 * 2. Centralized logging
 * 3. Error tracking (bisa di-extend untuk circuit breaker)
 */
class MarketplaceApiGateway
{
    public function __construct(
        protected ChannelManager $manager
    ) {}

    /**
     * Terapkan rate limit per store.
     * Secara default mengizinkan 10 request per detik per toko.
     * Menyesuaikan dengan typical marketplace rate limits.
     */
    protected function throttle(Store $store): void
    {
        // Max 10 requests per 1 second per store
        // Bisa disesuaikan jika ingin membedakan per channel (Shopee vs TikTok)
        $allowed = RateLimiter::attempt(
            'marketplace_api_gw_store_' . $store->id,
            10,
            static fn () => true,
            1
        );

        if ($allowed === false) {
            Log::warning("[ApiGateway] Rate limit exceeded for store {$store->id}");
            // Fallback to brief sleep to smooth out bursts instead of failing immediately.
            usleep(200000); // 200ms delay
        }
    }

    protected function logApiCall(Store $store, string $method, array $args, mixed $result, float $duration): void
    {
        $durationMs = round($duration * 1000, 2);
        Log::info("[ApiGateway][{$store->id}] {$method} - {$durationMs}ms", [
            'store_id' => $store->id,
            'method'   => $method,
            // 'args'     => $this->maskSensitiveArgs($args), // Opsi untuk masking
            'success'  => true,
        ]);
    }

    protected function logApiError(Store $store, string $method, array $args, \Throwable $e, float $duration): void
    {
        $durationMs = round($duration * 1000, 2);
        Log::error("[ApiGateway][{$store->id}] {$method} FAILED - {$durationMs}ms - " . $e->getMessage(), [
            'store_id' => $store->id,
            'method'   => $method,
            'error'    => $e->getMessage(),
            'file'     => $e->getFile(),
            'line'     => $e->getLine(),
        ]);
    }

    /**
     * Magic method untuk meneruskan semua pemanggilan ke driver aktual.
     * Asumsi: Parameter pertama dari setiap method API selalu instance Store.
     */
    public function __call(string $method, array $parameters)
    {
        $store = $parameters[0] ?? null;
        if (! $store instanceof Store) {
            throw new \InvalidArgumentException("Parameter pertama untuk ApiGateway::{$method} harus berupa instance App\Models\Store.");
        }

        $driver = $this->manager->driver($store);

        if (! method_exists($driver, $method) && ! ($driver instanceof \Mockery\MockInterface)) {
            throw new \BadMethodCallException("Method {$method} tidak ditemukan pada driver channel {$store->channel?->code}.");
        }

        $this->throttle($store);

        $startTime = microtime(true);
        try {
            $result = $driver->{$method}(...$parameters);
            $this->logApiCall($store, $method, $parameters, $result, microtime(true) - $startTime);
            return $result;
        } catch (\Throwable $e) {
            $this->logApiError($store, $method, $parameters, $e, microtime(true) - $startTime);
            throw $e;
        }
    }
}
