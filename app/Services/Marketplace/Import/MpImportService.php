<?php

namespace App\Services\Marketplace\Import;

use App\Models\MpShipment;
use App\Models\MpShipmentItem;
use App\Services\Marketplace\Import\Adapters\MpImportAdapterInterface;
use App\Services\Marketplace\MpPacketItemSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MpImportService
{
    /** @var array<string, MpImportAdapterInterface> */
    protected array $adapters = [];

    public function __construct()
    {
        $this->register(app(\App\Services\Marketplace\Import\Adapters\ShopeeImportAdapter::class));
        $this->register(app(\App\Services\Marketplace\Import\Adapters\TiktokImportAdapter::class));
    }

    public function register(MpImportAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->channel()] = $adapter;
    }

    public function import(
        string $channel,
        string $path,
        int $storeId,
        string $sourceFile,
        bool $dryRun = false
    ): array {
        $channel = strtolower(trim($channel));

        if (!isset($this->adapters[$channel])) {
            throw new \InvalidArgumentException("Adapter untuk channel '{$channel}' belum ada.");
        }

        $adapter = $this->adapters[$channel];
        $batchId = (string) Str::uuid();
        $now = now();

        $normalized = $adapter->parse($path, $storeId, $sourceFile);

        $stats = [
            'channel' => $channel,
            'store_id' => $storeId,
            'source_file' => $sourceFile,
            'import_batch_id' => $batchId,
            'shipments_parsed' => count($normalized),
            'items_parsed' => array_sum(array_map(fn($s) => count($s['items'] ?? []), $normalized)),
            'inserted_shipments' => 0,
            'updated_shipments' => 0,
            'inserted_items' => 0,
            'dry_run' => $dryRun,
        ];

        if ($dryRun) {
            return compact('stats', 'normalized');
        }

        DB::transaction(function () use (
            $normalized,
            $batchId,
            $now,
            $storeId,
            &$stats
        ) {
            $packetSync = app(MpPacketItemSyncService::class);

            foreach ($normalized as $s) {
                $items = $s['items'] ?? [];

                $totalQty = (int) (
                    $s['total_qty'] ?? array_sum(array_map(fn($i) => (int) ($i['qty'] ?? 0), $items))
                );

                $grand = (float) (
                    $s['grand_total'] ?? array_sum(array_map(fn($i) => (float) ($i['subtotal'] ?? 0), $items))
                );

                /**
                 * ======================================
                 * UPSERT mp_shipment
                 * ======================================
                 */
                $mp = MpShipment::query()
                    ->where('store_id', $s['store_id'] ?? $storeId)
                    ->where('channel', $s['channel'])
                    ->where('platform_order_id', $s['platform_order_id'])
                    ->where(function ($q) use ($s) {
                        if (!empty($s['platform_shipment_id'])) {
                            $q->where('platform_shipment_id', $s['platform_shipment_id']);
                        } else {
                            $q->whereNull('platform_shipment_id');
                        }
                    })
                    ->first();

                $payload = [
                    'store_id' => (int) ($s['store_id'] ?? $storeId),
                    'channel' => $s['channel'],
                    'platform_order_id' => $s['platform_order_id'],
                    'platform_shipment_id' => $s['platform_shipment_id'] ?? null,
                    'tracking_no' => $s['tracking_no'] ?? null,

                    'marketplace_status' => $s['marketplace_status'] ?? null,
                    'status_norm' => $s['status_norm'] ?? null,

                    'order_created_at' => $s['order_created_at'] ?? null,
                    'paid_at' => $s['paid_at'] ?? null,
                    'shipped_at' => $s['shipped_at'] ?? null,
                    'delivered_at' => $s['delivered_at'] ?? null,
                    'completed_at' => $s['completed_at'] ?? null,

                    'total_qty' => $totalQty,
                    'order_subtotal' => $s['order_subtotal'] ?? 0,
                    'discount_total' => $s['discount_total'] ?? 0,
                    'shipping_fee' => $s['shipping_fee'] ?? 0,
                    'grand_total' => $grand,
                    'currency' => $s['currency'] ?? 'IDR',

                    'platform_fee_total' => $s['platform_fee_total'] ?? 0,
                    'refund_total' => $s['refund_total'] ?? 0,
                    'net_payout_actual' => $s['net_payout_actual'] ?? 0,
                    'released_at' => $s['released_at'] ?? null,

                    'import_batch_id' => $batchId,
                    'source_file' => $s['source_file'] ?? null,
                    'imported_at' => $now,
                    'raw_payload' => $s['raw_payload'] ?? null,
                ];

                if (!$mp) {
                    $mp = MpShipment::create($payload);
                    $stats['inserted_shipments']++;
                } else {
                    $update = $payload;

                    if (empty($update['tracking_no'])) {
                        unset($update['tracking_no']);
                    }

                    if (empty($update['marketplace_status'])) {
                        unset($update['marketplace_status']);
                    }

                    if (empty($update['status_norm'])) {
                        unset($update['status_norm']);
                    }

                    if (empty($update['raw_payload'])) {
                        unset($update['raw_payload']);
                    }

                    $mp->fill($update)->save();
                    $stats['updated_shipments']++;
                }

                /**
                 * ======================================
                 * REFRESH mp_shipment_items (raw)
                 * ======================================
                 */
                MpShipmentItem::where('mp_shipment_id', $mp->id)->delete();

                foreach ($items as $it) {
                    MpShipmentItem::create([
                        'mp_shipment_id' => $mp->id,
                        'sku_code' => $it['sku_code'] ?? null,
                        'sku_parent' => $it['sku_parent'] ?? null,
                        'product_name' => $it['product_name'] ?? null,
                        'variant_name' => $it['variant_name'] ?? null,
                        'qty' => (int) ($it['qty'] ?? 0),
                        'unit_price' => (float) ($it['unit_price'] ?? 0),
                        'subtotal' => (float) ($it['subtotal'] ?? 0),
                        'raw_line' => $it['raw_line'] ?? null,
                    ]);
                    $stats['inserted_items']++;
                }

                /**
                 * ======================================
                 * BUILD mp_packet_items (ERP-aware)
                 * ======================================
                 */
                $skuQtyMap = [];

                foreach ($items as $it) {
                    $sku = $it['sku_code'] ?? $it['sku_parent'] ?? null;
                    $qty = (int) ($it['qty'] ?? 0);

                    if ($sku && $qty > 0) {
                        $sku = strtoupper(trim($sku));
                        $skuQtyMap[$sku] = ($skuQtyMap[$sku] ?? 0) + $qty;
                    }
                }

                if ($skuQtyMap) {
                    $packetSync->syncAutoSkuMap(
                        (string) $mp->id,
                        $skuQtyMap,
                        [
                            'channel' => $mp->channel,
                            'store' => 'store#' . $mp->store_id,
                        ]
                    );
                }
            }
        });

        return compact('stats');
    }
}
