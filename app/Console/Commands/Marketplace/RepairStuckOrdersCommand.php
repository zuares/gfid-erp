<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceOrder;
use App\Models\OrderFulfillment;
use App\Services\OrderFulfillmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairStuckOrdersCommand extends Command
{
    protected $signature = 'marketplace:repair-stuck-orders
        {--store= : Hanya toko tertentu (stores.id)}
        {--order= : Hanya channel_order_id tertentu}
        {--limit=500 : Maksimum order yang diperiksa}
        {--apply : Terapkan perbaikan (default hanya preview)}';

    protected $description = 'Deteksi dan repair order marketplace yang status fulfillment-nya tidak sinkron.';

    public function handle(OrderFulfillmentService $fulfillmentService): int
    {
        $apply = (bool) $this->option('apply');
        $storeId = $this->option('store');
        $orderSn = trim((string) ($this->option('order') ?? ''));
        $limit = max(1, min(2000, (int) $this->option('limit')));

        $query = MarketplaceOrder::query()
            ->with([
                'store:id,name',
                'fulfillment:id,marketplace_order_id,status,warehouse_id',
            ])
            ->whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED', 'READY_TO_HANDOVER'])
            ->orderBy('id');

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
            $this->info('Tidak ada order aktif yang perlu diperiksa.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Memeriksa %d order%s (%s).',
            $orders->count(),
            $storeId !== null && $storeId !== '' ? " di store #{$storeId}" : '',
            $apply ? 'APPLY' : 'DRY-RUN'
        ));

        $stats = [
            'missing_fulfillment' => 0,
            'restored_handover' => 0,
            'already_ok' => 0,
            'waiting_manual' => 0,
            'cancelled_fulfillment' => 0,
        ];

        foreach ($orders as $order) {
            $fulfillment = $order->fulfillment;

            if (! $fulfillment) {
                $stats['missing_fulfillment']++;
                $this->line($this->formatLine($order, 'MISSING_FULFILLMENT', 'buat draft fulfillment'));

                if ($apply) {
                    try {
                        $fulfillmentService->createDraft($order);
                    } catch (\Throwable $e) {
                        $this->error("Gagal membuat draft order #{$order->id}: {$e->getMessage()}");
                        return self::FAILURE;
                    }
                }

                continue;
            }

            if ($fulfillment->status === OrderFulfillment::STATUS_CONFIRMED
                && in_array($order->order_status, ['READY_TO_SHIP', 'PROCESSED'], true)) {
                $stats['restored_handover']++;
                $this->line($this->formatLine($order, 'RESTORE_HANDOVER', 'status → READY_TO_HANDOVER'));

                if ($apply) {
                    $order->update([
                        'order_status' => 'READY_TO_HANDOVER',
                        'updated_at' => now(),
                    ]);
                }

                continue;
            }

            if ($fulfillment->status === OrderFulfillment::STATUS_CANCELLED) {
                $stats['cancelled_fulfillment']++;
                $this->line($this->formatLine($order, 'MANUAL', 'fulfillment cancelled — perlu keputusan manual'));
                continue;
            }

            if (in_array($fulfillment->status, [
                OrderFulfillment::STATUS_DRAFT,
                OrderFulfillment::STATUS_PENDING_REVIEW,
                OrderFulfillment::STATUS_PICKING,
                OrderFulfillment::STATUS_PACKED,
            ], true)) {
                $stats['waiting_manual']++;
                $this->line($this->formatLine($order, 'MANUAL', "fulfillment {$fulfillment->status} — jangan dipaksa selesai"));
                continue;
            }

            $stats['already_ok']++;
        }

        $this->newLine();
        $this->info('Ringkasan repair:');
        $this->line('Order diperiksa           : ' . $orders->count());
        $this->line('Fulfillment dibuat        : ' . $stats['missing_fulfillment']);
        $this->line('Status handover dipulihkan: ' . $stats['restored_handover']);
        $this->line('Menunggu tindakan manual  : ' . $stats['waiting_manual']);
        $this->line('Fulfillment cancelled     : ' . $stats['cancelled_fulfillment']);
        $this->line('Sudah sesuai              : ' . $stats['already_ok']);

        if (! $apply) {
            $this->comment('Preview selesai. Jalankan ulang dengan --apply untuk menerapkan repair yang aman.');
        } else {
            $this->info('Repair selesai.');
        }

        return self::SUCCESS;
    }

    private function formatLine(MarketplaceOrder $order, string $action, string $detail): string
    {
        return sprintf(
            '[%s] #%d %s | store=%s | order_status=%s | %s',
            $action,
            $order->id,
            $order->channel_order_id,
            $order->store?->name ?? ('#' . $order->store_id),
            $order->order_status,
            $detail
        );
    }
}
