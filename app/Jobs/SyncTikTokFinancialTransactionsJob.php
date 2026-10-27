<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\Marketplace\TikTokShop\TikTokFinancialTransactionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncTikTokFinancialTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 2700;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public int $storeId,
        public string $dateFrom,
        public string $dateTo,
        public string $source = 'all',
        public string $trigger = 'manual',
    ) {
        $this->onConnection('heavy')->onQueue('heavy');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("tiktok-financial-transactions:{$this->storeId}"))
                ->expireAfter(2800)
                ->releaseAfter(120),
        ];
    }

    public function handle(TikTokFinancialTransactionService $service): void
    {
        $progressKey = self::progressKey($this->storeId);
        $activeKey = self::activeKey($this->storeId);
        $store = Store::query()->with('channel')->findOrFail($this->storeId);

        Cache::put($progressKey, [
            'status' => 'processing',
            'phase' => 'fetching',
            'percent' => 10,
            'label' => "Mengambil Finance TikTok {$store->name}…",
            'store_id' => $store->id,
            'store_name' => $store->name,
            'source' => $this->source,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'trigger' => $this->trigger,
            'updated_at' => now()->toISOString(),
        ], 3600);

        try {
            $result = $service->syncStoredTransactions(
                $store,
                Carbon::createFromFormat('!Y-m-d', $this->dateFrom, config('app.timezone'))->startOfDay(),
                Carbon::createFromFormat('!Y-m-d', $this->dateTo, config('app.timezone'))->endOfDay(),
                $this->source,
            );

            Cache::put($progressKey, [
                'status' => 'success',
                'phase' => 'completed',
                'percent' => 100,
                'label' => "Finance TikTok {$store->name} selesai: {$result['total']} transaksi disimpan.",
                'store_id' => $store->id,
                'store_name' => $store->name,
                'source' => $this->source,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'result' => $result,
                'updated_at' => now()->toISOString(),
            ], 3600);
        } catch (\Throwable $e) {
            Cache::put($progressKey, [
                'status' => 'error',
                'phase' => 'failed',
                'percent' => 100,
                'label' => 'Sinkronisasi Finance TikTok gagal.',
                'store_id' => $store->id,
                'store_name' => $store->name,
                'source' => $this->source,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'message' => 'Sinkronisasi Finance TikTok gagal. Periksa koneksi toko atau log server.',
                'updated_at' => now()->toISOString(),
            ], 3600);
            Log::error('[tiktok-financial-transactions] Sync job gagal', [
                'store_id' => $store->id,
                'source' => $this->source,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'error' => $e->getMessage(),
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
            'label' => 'Sinkronisasi Finance TikTok gagal setelah percobaan ulang.',
            'store_id' => $this->storeId,
            'source' => $this->source,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'message' => 'Sinkronisasi Finance TikTok gagal setelah percobaan ulang. Periksa log server.',
            'updated_at' => now()->toISOString(),
        ], 3600);
    }

    public static function progressKey(int $storeId): string
    {
        return "marketplace:tiktok-financial-sync:progress:{$storeId}";
    }

    public static function activeKey(int $storeId): string
    {
        return "marketplace:tiktok-financial-sync:active:{$storeId}";
    }
}
