<?php

namespace App\Jobs;

use App\Events\OrderUpdated;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Tarik escrow untuk satu order segera setelah webhook membuat order menjadi
 * eligible. Shopee sering belum menyediakan income pada percobaan pertama,
 * sehingga job dicoba ulang dengan jeda tanpa menahan proses webhook.
 */
class SyncOrderEscrowJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $uniqueFor = 3600;

    public function __construct(
        public int $storeId,
        public string $orderSn,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return "{$this->storeId}:{$this->orderSn}";
    }

    protected function nextDelay(): int
    {
        return [300, 900][$this->attempts() - 1] ?? 900;
    }

    public function handle(MarketplaceSyncService $sync): void
    {
        $store = Store::find($this->storeId);
        if (! $store || ! $store->is_active) {
            return;
        }

        $order = MarketplaceOrder::where('store_id', $this->storeId)
            ->where('channel_order_id', $this->orderSn)
            ->first();

        if (! $order || ! in_array($order->order_status, MarketplaceSyncService::SETTLEMENT_ELIGIBLE_ORDER_STATUSES, true)) {
            return;
        }

        // Retry memakai resync agar percobaan pertama yang terlalu dini tidak
        // mengunci order selama cooldown batch reguler satu jam.
        $result = $sync->syncSettlements(
            store: $store,
            orderSn: $this->orderSn,
            resync: $this->attempts() > 1,
            limit: 1,
        );

        if (($result['synced'] ?? 0) > 0) {
            $fresh = $order->fresh();
            event(new OrderUpdated($store->id, $this->orderSn, $fresh?->order_status));
            Log::info("SyncOrderEscrowJob: escrow order {$this->orderSn} tersimpan.");
            return;
        }

        $shouldRetry = (($result['skipped'] ?? 0) > 0) || (($result['errors'] ?? 0) > 0);
        if ($shouldRetry && $this->attempts() < $this->tries) {
            $delay = $this->nextDelay();
            Log::info("SyncOrderEscrowJob: escrow {$this->orderSn} belum tersedia, retry dalam {$delay} detik.", [
                'attempt' => $this->attempts(),
                'result' => $result,
            ]);
            $this->release($delay);
            return;
        }

        Log::warning("SyncOrderEscrowJob: escrow {$this->orderSn} belum berhasil setelah {$this->attempts()} attempt.", [
            'result' => $result,
        ]);
    }
}
