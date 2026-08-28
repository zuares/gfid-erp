<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceOrder;
use App\Services\Channels\ChannelManager;
use App\Services\Marketplace\MarketplaceTrackingStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncFailedDeliveryTrackingCommand extends Command
{
    protected $signature = 'marketplace:sync-failed-deliveries
        {--store= : Hanya toko tertentu (stores.id)}
        {--order= : Hanya channel_order_id tertentu}
        {--limit=30 : Maksimum order yang dicek per eksekusi}
        {--backfill : Periksa histori pengiriman/retur, diproses bertahap dengan checkpoint}
        {--from= : Tanggal awal backfill (YYYY-MM-DD)}
        {--to= : Tanggal akhir backfill (YYYY-MM-DD)}
        {--apply : Simpan hasil tracking ke database (default hanya preview)}';

    protected $description = 'Sinkron status gagal kirim dari tracking Shopee untuk order yang sedang dikirim.';

    public function handle(ChannelManager $manager, MarketplaceTrackingStatusService $trackingStatus): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));
        $apply = (bool) $this->option('apply');
        $orderSn = trim((string) $this->option('order'));
        $backfill = (bool) $this->option('backfill');
        $from = trim((string) $this->option('from'));
        $to = trim((string) $this->option('to'));

        if (($from !== '' && ! $this->isDate($from)) || ($to !== '' && ! $this->isDate($to)) || ($from !== '' && $to !== '' && $from > $to)) {
            $this->error('Rentang --from/--to harus memakai YYYY-MM-DD yang valid.');
            return self::FAILURE;
        }

        $checkpointKey = $this->checkpointKey((string) $this->option('store'), $from, $to);
        $afterId = $backfill && $orderSn === '' ? (int) Cache::get($checkpointKey, 0) : 0;

        $query = MarketplaceOrder::query()
            ->with('store.channel')
            ->whereIn('order_status', $backfill
                ? ['SHIPPED', 'TO_CONFIRM_RECEIVE', 'TO_RETURN', 'RETURNING', 'RETURNED']
                : ['SHIPPED', 'TO_CONFIRM_RECEIVE']);

        if ($backfill) {
            $query->where('id', '>', $afterId)->orderBy('id');
            $this->applyDateRange($query, $from, $to);
        } else {
            $query->orderByDesc('shipped_at')->orderByDesc('id');
        }

        if ($orderSn !== '') {
            $query->where('channel_order_id', $orderSn);
        } elseif (! $backfill) {
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
                $trackingInfo = is_array($trackingInfo) ? $trackingInfo : [];
                $state = $apply
                    ? $trackingStatus->record($order, $trackingInfo)
                    : $trackingStatus->summarize($trackingInfo);
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

        if ($backfill && $orderSn === '') {
            $lastId = (int) ($orders->last()?->id ?? 0);
            if ($orders->count() === $limit && $lastId > 0) {
                Cache::put($checkpointKey, $lastId, now()->addDays(7));
                $this->comment("Checkpoint tersimpan di ID {$lastId}. Jalankan ulang perintah yang sama untuk batch berikutnya.");
            } else {
                Cache::forget($checkpointKey);
                $this->info('Backfill selesai; tidak ada batch berikutnya.');
            }
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function checkpointKey(string $store, string $from, string $to): string
    {
        return 'marketplace:failed-delivery-backfill:' . sha1($store . '|' . $from . '|' . $to);
    }

    private function isDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            && \DateTimeImmutable::createFromFormat('!Y-m-d', $value)?->format('Y-m-d') === $value;
    }

    private function applyDateRange($query, string $from, string $to): void
    {
        if ($from !== '') {
            $query->where(function ($q) use ($from) {
                $q->where('shipped_at', '>=', $from . ' 00:00:00')
                    ->orWhere(function ($fallback) use ($from) {
                        $fallback->whereNull('shipped_at')->where('ordered_at', '>=', $from . ' 00:00:00');
                    });
            });
        }

        if ($to !== '') {
            $query->where(function ($q) use ($to) {
                $q->where('shipped_at', '<=', $to . ' 23:59:59')
                    ->orWhere(function ($fallback) use ($to) {
                        $fallback->whereNull('shipped_at')->where('ordered_at', '<=', $to . ' 23:59:59');
                    });
            });
        }
    }
}
