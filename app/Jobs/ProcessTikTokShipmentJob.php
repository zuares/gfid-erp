<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\Marketplace\MarketplaceLogisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TikTok's ship endpoint is run outside the browser request. This avoids the
 * ngrok/FPM upstream timeout while waiting for TikTok's fulfillment API.
 */
class ProcessTikTokShipmentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;
    public int $uniqueFor = 3600;

    public function __construct(
        public int $storeId,
        public string $orderSn,
        public array $params = [],
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return $this->storeId . ':' . $this->orderSn;
    }

    public function handle(MarketplaceLogisticsService $logistics): void
    {
        $store = Store::find($this->storeId);
        if (! $store) {
            Log::warning('[TikTokShipment] Store tidak ditemukan.', [
                'store_id' => $this->storeId,
                'order_sn' => $this->orderSn,
            ]);
            return;
        }

        $result = $logistics->arrangeShipment($store, $this->orderSn, $this->params);

        if (! empty($result['error'])) {
            Log::warning('[TikTokShipment] Pengiriman gagal.', [
                'store_id' => $this->storeId,
                'order_sn' => $this->orderSn,
                'error' => $result['error'],
                'message' => $result['message'] ?? null,
                'code' => $result['code'] ?? null,
            ]);
        } else {
            Log::info('[TikTokShipment] Pengiriman berhasil diproses.', [
                'store_id' => $this->storeId,
                'order_sn' => $this->orderSn,
                'package_id' => $result['package_id'] ?? null,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[TikTokShipment] Job gagal.', [
            'store_id' => $this->storeId,
            'order_sn' => $this->orderSn,
            'message' => $e->getMessage(),
        ]);
    }
}
