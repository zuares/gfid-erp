<?php

namespace App\Jobs;

use App\Services\TikTokShop\TikTokShopOAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessTikTokShopOAuthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public string $authCode,
        public ?int $targetStoreId = null,
        public bool $readOnlyIntegration = false,
        public ?string $connectionId = null,
    ) {}

    public static function cacheKey(string $connectionId): string
    {
        return 'tiktok_oauth_connection:' . $connectionId;
    }

    public static function putStatus(string $connectionId, array $status): void
    {
        Cache::put(self::cacheKey($connectionId), [
            ...$status,
            'updated_at' => now()->toISOString(),
        ], now()->addMinutes(10));
    }

    public function handle(TikTokShopOAuthService $service): void
    {
        if ($this->connectionId) {
            self::putStatus($this->connectionId, [
                'status' => 'processing',
                'message' => 'TikTok Shop sedang diverifikasi.',
            ]);
        }

        try {
            $result = $service->process($this->authCode, $this->targetStoreId, $this->readOnlyIntegration);

            if ($this->connectionId) {
                self::putStatus($this->connectionId, $result);
            }
        } catch (\Throwable $e) {
            if ($this->connectionId) {
                self::putStatus($this->connectionId, [
                    'status' => 'failed',
                    'message' => 'Koneksi TikTok Shop gagal diproses. Cek konfigurasi dan log aplikasi.',
                ]);
            }

            throw $e;
        }
    }
}
