<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyProcessedOrdersCommand extends Command
{
    protected $signature = 'marketplace:verify-processed-orders
        {--store= : Hanya toko tertentu (stores.id)}
        {--order= : Hanya channel_order_id tertentu}
        {--limit=50 : Maksimum order yang diverifikasi}
        {--apply : Terapkan status API ke database (default hanya preview)}';

    protected $description = 'Verifikasi order PROCESSED ke API marketplace dan pindahkan jika statusnya sudah maju.';

    /**
     * Status lokal PROCESSED tidak boleh dipindahkan hanya karena fulfillment
     * lokal terlihat selesai. Status ini dipindahkan hanya bila API mengirim
     * status yang memang sudah keluar dari tahap Sedang Dikemas.
     */
    private const ADVANCED_STATUSES = [
        'READY_TO_HANDOVER',
        'SHIPPED',
        'TO_CONFIRM_RECEIVE',
        'COMPLETED',
        'CANCELLED',
        'IN_CANCEL',
        'TO_RETURN',
        'RETURNING',
        'RETURNED',
        'REFUNDED',
    ];

    public function handle(MarketplaceApiGateway $gateway): int
    {
        $storeId = $this->option('store');
        $orderSn = trim((string) ($this->option('order') ?? ''));
        $limit = max(1, min(500, (int) $this->option('limit')));
        $apply = (bool) $this->option('apply');

        $query = MarketplaceOrder::query()
            ->with('store.channel')
            ->where('order_status', 'PROCESSED')
            ->orderBy('id');

        // Scheduler berjalan tiap 5 menit. Jeda ini membuat antrean adil bila
        // jumlah order PROCESSED lebih besar dari limit per eksekusi.
        if ($orderSn === '') {
            $query->where(function ($q) {
                $q->whereNull('processed_api_checked_at')
                    ->orWhere('processed_api_checked_at', '<=', now()->subMinutes(10));
            });
        }

        if ($storeId !== null && $storeId !== '') {
            if (! is_numeric($storeId) || ! DB::table('stores')->where('id', (int) $storeId)->exists()) {
                $this->error("Store #{$storeId} tidak ditemukan.");
                return self::FAILURE;
            }

            $query->where('store_id', (int) $storeId);
        }

        if ($orderSn !== '') {
            $query->where('channel_order_id', $orderSn);
        }

        $orders = $query->limit($limit)->get();

        if ($orders->isEmpty()) {
            $this->info('Tidak ada order PROCESSED yang perlu diverifikasi.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Memverifikasi %d order PROCESSED ke API (%s).',
            $orders->count(),
            $apply ? 'APPLY' : 'DRY-RUN'
        ));

        $stats = [
            'checked' => 0,
            'moved' => 0,
            'unchanged' => 0,
            'not_found' => 0,
            'unknown_status' => 0,
            'skipped_store' => 0,
            'errors' => 0,
        ];

        foreach ($orders->groupBy('store_id') as $storeOrders) {
            /** @var MarketplaceOrder $firstOrder */
            $firstOrder = $storeOrders->first();
            $store = $firstOrder->store;

            if (! $store || ! $store->is_active || $store->status !== 'active') {
                $this->warn("Store #{$firstOrder->store_id} tidak aktif; order dilewati.");
                $stats['skipped_store'] += $storeOrders->count();
                continue;
            }

            if (! in_array(strtolower((string) $store->channel?->code), ['shopee', 'shp', 'tiktok', 'ttk', 'tt'], true)) {
                $this->warn("Store {$store->name} belum memiliki adapter verifikasi API; order dilewati.");
                $stats['skipped_store'] += $storeOrders->count();
                continue;
            }

            if ($store->connection_status !== 'CONNECTED') {
                $this->warn("Store {$store->name} koneksinya {$store->connection_status}; order dilewati.");
                $stats['skipped_store'] += $storeOrders->count();
                continue;
            }

            $detailsByOrder = [];
            foreach (array_chunk($storeOrders->pluck('channel_order_id')->filter()->unique()->values()->all(), 50) as $chunk) {
                try {
                    $response = $gateway->getOrderDetail($store, $chunk);

                    if (! empty($response['error']) || (isset($response['code']) && (int) $response['code'] !== 0)) {
                        throw new \RuntimeException($response['message'] ?? $response['error'] ?? 'API mengembalikan error.');
                    }

                    foreach ($this->extractOrderDetails($response) as $detail) {
                        $id = $this->detailOrderId($detail);
                        if ($id !== null) {
                            $detailsByOrder[$id] = $detail;
                        }
                    }
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $this->error("Gagal verifikasi store {$store->name}: {$e->getMessage()}");
                }
            }

            foreach ($storeOrders as $order) {
                $stats['checked']++;
                $detail = $detailsByOrder[(string) $order->channel_order_id] ?? null;

                if (! $detail) {
                    $stats['not_found']++;
                    $this->line($this->formatLine($order, 'NOT_FOUND', 'order tidak dikembalikan API'));
                    $this->markChecked($order, $apply);
                    continue;
                }

                $apiStatus = $this->canonicalStatus($detail['order_status'] ?? $detail['status'] ?? null);

                if ($apiStatus === null) {
                    $stats['unknown_status']++;
                    $this->line($this->formatLine($order, 'UNKNOWN', 'status API tidak dikenali'));
                    $this->markChecked($order, $apply);
                    continue;
                }

                if (! in_array($apiStatus, self::ADVANCED_STATUSES, true)) {
                    $stats['unchanged']++;
                    $this->line($this->formatLine($order, 'UNCHANGED', "API status={$apiStatus}"));
                    $this->markChecked($order, $apply);
                    continue;
                }

                $stats['moved']++;
                $this->line($this->formatLine($order, 'MOVE', "API status={$apiStatus} → lokal={$apiStatus}"));

                if ($apply) {
                    $order->update([
                        'order_status' => $apiStatus,
                        'status' => $this->legacyStatus($apiStatus, $order->status),
                        'raw_json' => $detail,
                        'synced_at' => now(),
                        'processed_api_checked_at' => now(),
                    ]);
                }
            }
        }

        $this->newLine();
        $this->info('Ringkasan verifikasi:');
        $this->line('Order dicek       : ' . $stats['checked']);
        $this->line('Dipindahkan        : ' . $stats['moved']);
        $this->line('Tetap PROCESSED    : ' . $stats['unchanged']);
        $this->line('Tidak ditemukan API: ' . $stats['not_found']);
        $this->line('Status tidak dikenal: ' . $stats['unknown_status']);
        $this->line('Store dilewati     : ' . $stats['skipped_store']);
        $this->line('Gagal              : ' . $stats['errors']);

        if (! $apply) {
            $this->comment('Preview selesai. Jalankan ulang dengan --apply untuk memindahkan status ke database.');
        } else {
            $this->info('Verifikasi dan pemindahan selesai.');
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function markChecked(MarketplaceOrder $order, bool $apply): void
    {
        if ($apply) {
            $order->update(['processed_api_checked_at' => now()]);
        }
    }

    private function extractOrderDetails(array $response): array
    {
        $details = data_get($response, 'response.order_list');

        if (! is_array($details)) {
            $details = data_get($response, 'data.orders');
        }

        if (! is_array($details)) {
            $details = data_get($response, 'data.order_list');
        }

        return is_array($details) ? array_values(array_filter($details, 'is_array')) : [];
    }

    private function detailOrderId(array $detail): ?string
    {
        $id = $detail['order_sn'] ?? $detail['order_id'] ?? $detail['id'] ?? null;

        return $id === null || $id === '' ? null : (string) $id;
    }

    private function canonicalStatus(mixed $status): ?string
    {
        if (! is_string($status) || trim($status) === '') {
            return null;
        }

        $status = strtoupper(trim($status));

        return [
            'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
            'AWAITING_COLLECTION' => 'READY_TO_HANDOVER',
            'IN_TRANSIT' => 'SHIPPED',
            'DELIVERED' => 'COMPLETED',
        ][$status] ?? $status;
    }

    private function legacyStatus(string $status, ?string $current): string
    {
        return [
            'READY_TO_HANDOVER' => 'packed',
            'SHIPPED' => 'shipped',
            'TO_CONFIRM_RECEIVE' => 'shipped',
            'COMPLETED' => 'completed',
            'CANCELLED' => 'cancelled',
            'IN_CANCEL' => 'cancelled',
            'TO_RETURN' => 'shipped',
            'RETURNING' => 'shipped',
            'RETURNED' => 'shipped',
            'REFUNDED' => 'cancelled',
        ][$status] ?? ($current ?: 'packed');
    }

    private function formatLine(MarketplaceOrder $order, string $action, string $detail): string
    {
        return sprintf(
            '[%s] #%d %s | store=%s | %s',
            $action,
            $order->id,
            $order->channel_order_id,
            $order->store?->name ?? ('#' . $order->store_id),
            $detail
        );
    }
}
