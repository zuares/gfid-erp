<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceOrder;
use App\Services\Channels\ChannelManager;
use App\Services\Marketplace\MarketplaceTrackingStatusService;
use Illuminate\Console\Command;

class SyncFailedDeliveryTrackingCommand extends Command
{
    protected $signature = 'marketplace:sync-failed-deliveries
        {--store= : Hanya toko tertentu (stores.id)}
        {--order= : Hanya channel_order_id tertentu}
        {--limit=30 : Maksimum order yang dicek per eksekusi}
        {--apply : Simpan hasil tracking ke database (default hanya preview)}';

    protected $description = 'Sinkron status gagal kirim dari tracking Shopee untuk order yang sedang dikirim.';

    public function handle(ChannelManager $manager, MarketplaceTrackingStatusService $trackingStatus): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));
        $apply = (bool) $this->option('apply');
        $orderSn = trim((string) $this->option('order'));

        $query = MarketplaceOrder::query()
            ->with('store.channel')
            ->whereIn('order_status', ['SHIPPED', 'TO_CONFIRM_RECEIVE'])
            ->orderByDesc('shipped_at')
            ->orderByDesc('id');

        if ($orderSn !== '') {
            $query->where('channel_order_id', $orderSn);
        } else {
            $query->where(function ($q) {
                $q->whereNull('tracking_checked_at')
                    ->orWhere('tracking_checked_at', '<=', now()->subMinutes(30));
            });
        }

        if ($this->option('store') !== null && $this->option('store') !== '') {
            $query->where('store_id', (int) $this->option('store'));
        }

        $orders = $query->limit($limit)->get();
        $stats = ['checked' => 0, 'failed' => 0, 'clear' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($orders as $order) {
            $store = $order->store;
            if (! $store || ! $store->is_active || $store->connection_status !== 'CONNECTED') {
                $stats['skipped']++;
                continue;
            }

            try {
                $driver = $manager->driver($store);
                if (! method_exists($driver, 'getTrackingInfo')) {
                    $stats['skipped']++;
                    continue;
                }

                $response = $driver->getTrackingInfo($store, (string) $order->channel_order_id);
                if (! empty($response['error'])) {
                    throw new \RuntimeException((string) ($response['message'] ?? $response['error']));
                }

                $trackingInfo = $response['response']['tracking_info'] ?? $response['tracking_info'] ?? [];
                $state = $apply
                    ? $trackingStatus->record($order, is_array($trackingInfo) ? $trackingInfo : [])
                    : ['failed' => $this->containsFailure(is_array($trackingInfo) ? $trackingInfo : [])];
                $stats['checked']++;
                $stats[$state['failed'] ? 'failed' : 'clear']++;
                $this->line(sprintf('[%s] %s %s', $state['failed'] ? 'FAILED' : 'CLEAR', $store->name, $order->channel_order_id));
            } catch (\Throwable $e) {
                $stats['errors']++;
                $this->warn("Gagal {$order->channel_order_id}: {$e->getMessage()}");
            }
        }

        $this->info(sprintf(
            'Tracking: %d dicek, %d gagal, %d normal, %d dilewati, %d error.',
            $stats['checked'], $stats['failed'], $stats['clear'], $stats['skipped'], $stats['errors']
        ));

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @param array<int,array<string,mixed>> $trackingInfo */
    private function containsFailure(array $trackingInfo): bool
    {
        foreach ($trackingInfo as $event) {
            $status = strtoupper(trim((string) ($event['logistics_status'] ?? $event['status'] ?? '')));
            $description = mb_strtolower((string) ($event['description'] ?? $event['status_description'] ?? ''));
            if (in_array($status, ['FAILED_DELIVERY', 'DELIVERY_FAILED', 'UNDELIVERED', 'RETURN_TO_SELLER', 'RETURNED_TO_SELLER'], true)
                || str_contains($description, 'gagal dikirim')
                || str_contains($description, 'gagal pengiriman')
                || str_contains($description, 'delivery failed')
                || str_contains($description, 'undelivered')) {
                return true;
            }
        }

        return false;
    }
}
