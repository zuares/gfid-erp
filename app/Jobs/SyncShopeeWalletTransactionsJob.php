<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\Marketplace\Shopee\ShopeeWalletTransactionStorageService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncShopeeWalletTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 2700;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public int $storeId,
        public string $dateFrom,
        public string $dateTo,
        public string $trigger = 'manual',
    ) {
        $this->onConnection('heavy')->onQueue('heavy');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("shopee-wallet-transactions:{$this->storeId}"))
                ->expireAfter(2800)
                ->releaseAfter(120),
        ];
    }

    public function handle(ShopeeWalletTransactionStorageService $service): void
    {
        $progressKey = self::progressKey($this->storeId);
        $activeKey = self::activeKey($this->storeId);
        $store = Store::query()->with('channel')->findOrFail($this->storeId);

        Cache::put($progressKey, [
            'status' => 'processing',
            'phase' => 'fetching',
            'percent' => 10,
            'label' => "Mengambil mutasi wallet Shopee {$store->name}…",
            'store_id' => $store->id,
            'store_name' => $store->name,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'trigger' => $this->trigger,
            'updated_at' => now()->toISOString(),
        ], 3600);

        try {
            $result = $service->sync(
                $store,
                Carbon::createFromFormat('!Y-m-d', $this->dateFrom, config('app.timezone'))->startOfDay(),
                Carbon::createFromFormat('!Y-m-d', $this->dateTo, config('app.timezone'))->endOfDay(),
            );
            $suffix = ($result['truncated'] ?? false) ? ' sebagian karena batas halaman API tercapai' : '';

            Cache::put($progressKey, [
                'status' => 'success',
                'phase' => 'completed',
                'percent' => 100,
                'label' => "Mutasi wallet Shopee {$store->name} selesai{$suffix}: {$result['stored']} baris diproses.",
                'store_id' => $store->id,
                'store_name' => $store->name,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'trigger' => $this->trigger,
                'result' => $result,
                'updated_at' => now()->toISOString(),
            ], 3600);
        } catch (\Throwable $e) {
            Cache::put($progressKey, [
                'status' => 'error',
                'phase' => 'failed',
                'percent' => 100,
                'label' => 'Sinkronisasi mutasi wallet Shopee gagal.',
                'store_id' => $store->id,
                'store_name' => $store->name,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'trigger' => $this->trigger,
                'message' => 'Sinkronisasi gagal. Periksa koneksi toko atau log server.',
                'updated_at' => now()->toISOString(),
            ], 3600);
            Log::error('[shopee-wallet-transactions] Sync job gagal', [
                'store_id' => $this->storeId,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'exception' => get_class($e),
            ]);

            throw $e;
        } finally {
            Cache::forget($activeKey);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Cache::forget(self::activeKey($this->storeId));
        Cache::put(self::progressKey($this->storeId), [
            'status' => 'error',
            'phase' => 'failed',
            'percent' => 100,
            'label' => 'Sinkronisasi mutasi wallet Shopee gagal setelah percobaan ulang.',
            'store_id' => $this->storeId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'message' => 'Sinkronisasi gagal setelah percobaan ulang. Periksa log server.',
            'updated_at' => now()->toISOString(),
        ], 3600);
    }

    public static function progressKey(int $storeId): string
    {
        return "marketplace:shopee-wallet-sync:progress:{$storeId}";
    }

    public static function activeKey(int $storeId): string
    {
        return "marketplace:shopee-wallet-sync:active:{$storeId}";
    }
}
