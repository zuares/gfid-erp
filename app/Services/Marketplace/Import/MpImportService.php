<?php

namespace App\Services\Marketplace\Import;

use App\Models\MpShipment;
use App\Models\MpShipmentItem;
use App\Models\MarketplaceOrder;
use App\Services\Marketplace\Income\ImportedMarketplaceIncomeSyncService;
use App\Services\Marketplace\Import\Adapters\MpImportAdapterInterface;
use App\Services\Marketplace\MpPacketItemSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MpImportService
{
    /** @var array<string, MpImportAdapterInterface> */
    protected array $adapters = [];

    public function __construct(
        protected ImportedMarketplaceIncomeSyncService $storedIncomeSync,
    )
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
        bool $dryRun = false,
        ?string $batchId = null
    ): array {
        $channel = strtolower(trim($channel));

        if (!isset($this->adapters[$channel])) {
            throw new \InvalidArgumentException("Adapter untuk channel '{$channel}' belum ada.");
        }

        $adapter = $this->adapters[$channel];
        $batchId = $batchId ?: (string) Str::uuid();
        $now = now();

        $normalized = $adapter->parse($path, $storeId, $sourceFile);

        $stats = [
            'channel' => $channel,
            'store_id' => $storeId,
            'source_file' => $sourceFile,
            'import_batch_id' => $batchId,
            'rows' => count($normalized),
            'shipments_parsed' => count($normalized),
            'items_parsed' => array_sum(array_map(fn($s) => count($s['items'] ?? []), $normalized)),
            'inserted_shipments' => 0,
            'updated_shipments' => 0,
            'inserted_items' => 0,
            'new_shipments' => 0,
            'existing_shipments' => 0,
            'settlements_created' => 0,
            'settlements_updated' => 0,
            'api_settlements_preserved' => 0,
            'income_mismatches' => 0,
            'profit_incomplete' => 0,
            'purchase_channels' => [],
            'warnings' => [],
            'dry_run' => $dryRun,
        ];

        $importErrors = [];
        $ordersByPurchaseChannel = [];
        $orderChannels = [];
        $trackingPackages = [];
        $savedOrderChannels = collect();
        if ($channel === 'tiktok') {
            foreach (array_chunk(array_unique(array_column($normalized, 'platform_order_id')), 500) as $orderIds) {
                $savedOrderChannels = $savedOrderChannels->merge(
                    MarketplaceOrder::query()
                        ->where('store_id', $storeId)
                        ->whereIn('channel_order_id', $orderIds)
                        ->pluck('purchase_channel', 'channel_order_id')
                );
            }
        }

        foreach ($normalized as $s) {
            $purchaseChannel = $s['purchase_channel'] ?? null;
            $orderId = (string) ($s['platform_order_id'] ?? '');
            $tracking = trim((string) ($s['tracking_no'] ?? ''));
            $packageId = trim((string) ($s['platform_shipment_id'] ?? ''));

            if ($purchaseChannel !== null && isset($orderChannels[$orderId]) && $orderChannels[$orderId] !== $purchaseChannel) {
                $importErrors[] = ['key' => $orderId, 'message' => 'Satu order memiliki Purchase Channel berbeda antar paket.'];
            }
            $orderChannels[$orderId] = $purchaseChannel;

            if ($tracking !== '') {
                $trackingKey = $orderId.'|'.$tracking;
                if (isset($trackingPackages[$trackingKey]) && $trackingPackages[$trackingKey] !== $packageId) {
                    $importErrors[] = ['key' => $orderId, 'message' => 'Resi yang sama tercatat pada Package ID berbeda dalam file.'];
                }
                $trackingPackages[$trackingKey] = $packageId;
            }

            if ($channel === 'tiktok' && ($purchaseChannel === null || ! empty($s['purchase_channel_conflict']))) {
                $importErrors[] = [
                    'key' => $orderId,
                    'message' => ! empty($s['purchase_channel_conflict'])
                        ? 'Satu paket memiliki Purchase Channel berbeda pada baris itemnya.'
                        : 'Purchase Channel kosong atau tidak dikenali. Gunakan TikTok atau Tokopedia.',
                ];
            }

            if ($purchaseChannel !== null) {
                $stats['purchase_channels'][$purchaseChannel] ??= ['orders' => 0, 'shipments' => 0, 'items' => 0, 'qty' => 0];
                $stats['purchase_channels'][$purchaseChannel]['shipments']++;
                $stats['purchase_channels'][$purchaseChannel]['items'] += count($s['items'] ?? []);
                $stats['purchase_channels'][$purchaseChannel]['qty'] += (int) ($s['total_qty'] ?? 0);
                if (! isset($ordersByPurchaseChannel[$purchaseChannel][$orderId])) {
                    $ordersByPurchaseChannel[$purchaseChannel][$orderId] = true;
                    $stats['purchase_channels'][$purchaseChannel]['orders']++;
                }
            }

            [$existing, $lookupError] = $this->findExistingShipment($s, $storeId);
            if ($lookupError !== null) {
                $importErrors[] = ['key' => $orderId, 'message' => $lookupError];
            }
            if ($existing && $purchaseChannel !== null && filled($existing->purchase_channel)
                && $existing->purchase_channel !== $purchaseChannel) {
                $importErrors[] = ['key' => $orderId, 'message' => 'Purchase Channel file berbeda dari shipment yang sudah tersimpan.'];
            }
            $savedOrderChannel = $savedOrderChannels->get($orderId);
            if ($purchaseChannel !== null && filled($savedOrderChannel) && $savedOrderChannel !== $purchaseChannel) {
                $importErrors[] = ['key' => $orderId, 'message' => 'Purchase Channel file berbeda dari order yang sudah tersimpan.'];
            }
            $stats[$existing ? 'existing_shipments' : 'new_shipments']++;
        }

        if ($stats['shipments_parsed'] === 0) {
            $stats['warnings'][] = 'Tidak ada shipment yang berhasil dinormalisasi dari file.';
        }

        if ($dryRun) {
            return compact('stats', 'normalized') + ['import_errors' => $importErrors];
        }

        if ($importErrors !== []) {
            throw new \InvalidArgumentException('Import dibatalkan karena ada Purchase Channel atau identitas shipment yang tidak valid. Upload ulang dan periksa preview.');
        }

        DB::transaction(function () use (
            $normalized,
            $batchId,
            $now,
            $storeId,
            &$stats
        ) {
            $packetSync = app(MpPacketItemSyncService::class);
            $salesOrderSync = app(ImportedMarketplaceOrderSyncService::class);

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
                [$mp, $lookupError] = $this->findExistingShipment($s, $storeId);
                if ($lookupError !== null) {
                    throw new \InvalidArgumentException($lookupError);
                }

                $payload = [
                    'store_id' => (int) ($s['store_id'] ?? $storeId),
                    'channel' => $s['channel'],
                    'purchase_channel' => $s['purchase_channel'] ?? null,
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
                    'source_type' => 'import',
                    'source_updated_at' => $s['source_updated_at'] ?? null,
                    'raw_payload' => $s['raw_payload'] ?? null,
                ];

                $linkedOrderId = \App\Models\MarketplaceOrder::query()
                    ->where('store_id', $payload['store_id'])
                    ->where('channel_order_id', $payload['platform_order_id'])
                    ->value('id');

                if ($linkedOrderId) {
                    $payload['marketplace_order_id'] = $linkedOrderId;
                }

                $isNew = $mp === null;
                $refreshItems = $isNew;
                $refreshOrder = $isNew;

                if ($isNew) {
                    $mp = MpShipment::create($payload);
                    $stats['inserted_shipments']++;
                } elseif ($s['channel'] !== 'tiktok') {
                    // Jalur impor Shopee tetap mengikuti perilaku sebelumnya;
                    // proteksi snapshot Tahap 1 khusus untuk ekspor TikTok Shop.
                    $update = array_filter($payload, fn ($value, $key) =>
                        ! in_array($key, ['tracking_no', 'marketplace_status', 'status_norm', 'raw_payload', 'marketplace_order_id'], true)
                        || filled($value), ARRAY_FILTER_USE_BOTH);
                    $mp->fill($update)->save();
                    $refreshItems = true;
                    $refreshOrder = true;
                    $stats['updated_shipments']++;
                } else {
                    $source = strtolower(trim((string) $mp->source_type));
                    $apiOwned = $source !== '' && $source !== 'import';
                    $incomingUpdatedAt = $s['source_updated_at'] ?? null;
                    $currentUpdatedAt = $mp->source_updated_at?->toDateTimeString();
                    $newerImport = ! $apiOwned
                        && $incomingUpdatedAt !== null
                        && ($currentUpdatedAt === null || $incomingUpdatedAt > $currentUpdatedAt);

                    if ($newerImport) {
                        // Nilai payout/fee dari proses income tidak boleh direset oleh file order.
                        $update = collect($payload)->only([
                            'purchase_channel', 'tracking_no', 'marketplace_status', 'status_norm',
                            'order_created_at', 'paid_at', 'shipped_at', 'delivered_at', 'completed_at',
                            'total_qty', 'order_subtotal', 'discount_total', 'shipping_fee',
                            'grand_total', 'currency', 'refund_total', 'source_updated_at',
                            'raw_payload', 'source_file', 'import_batch_id', 'imported_at',
                            'marketplace_order_id', 'platform_shipment_id',
                        ])->all();
                        foreach ($update as $key => $value) {
                            if ($value === null || $value === '') {
                                unset($update[$key]);
                            }
                        }
                        $mp->fill($update)->save();
                        $refreshItems = true;
                        $refreshOrder = true;
                    } else {
                        // Snapshot API atau file yang lebih baru tetap menjadi sumber utama.
                        $this->fillMissingShipmentFields($mp, $payload);
                        $refreshItems = $mp->items()->doesntExist() && $items !== [];
                        $refreshOrder = $refreshItems;
                    }
                    $stats['updated_shipments']++;
                }

                /**
                 * ======================================
                 * REFRESH mp_shipment_items (raw)
                 * ======================================
                 */
                if ($refreshItems) {
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
                }

                // Sales dashboard membaca marketplace_orders, sedangkan
                // import manual utamanya menyimpan mp_shipments. Sinkronkan
                // snapshot import ke order tables tanpa menimpa order API.
                $salesOrder = $salesOrderSync->sync(
                    $mp,
                    $s,
                    $items,
                    $batchId,
                    $now->toDateTimeString(),
                    $refreshOrder,
                );
                if (! empty($salesOrder['order_id']) && (int) $mp->marketplace_order_id !== (int) $salesOrder['order_id']) {
                    $mp->forceFill(['marketplace_order_id' => (int) $salesOrder['order_id']])->save();
                }

                // Income bisa masuk lebih dulu daripada order. Setelah order
                // tersedia, sambungkan income tersimpan tanpa upload ulang.
                $incomeSync = $this->storedIncomeSync->syncStoredIncomeForOrder(
                    (int) $storeId,
                    (string) ($s['channel'] ?? $channel),
                    (string) ($s['platform_order_id'] ?? ''),
                );
                if (! empty($incomeSync['found'])) {
                    $stats['settlements_created'] += (int) ($incomeSync['settlements_created'] ?? 0);
                    $stats['settlements_updated'] += (int) ($incomeSync['settlements_updated'] ?? 0);
                    $stats['api_settlements_preserved'] += (int) ($incomeSync['api_settlements_preserved'] ?? 0);
                    $stats['income_mismatches'] += (int) ($incomeSync['income_mismatches'] ?? 0);
                    $stats['profit_incomplete'] += (int) ($incomeSync['profit_incomplete'] ?? 0);
                }

                $stats['warnings'] = array_values(array_unique(array_merge(
                    $stats['warnings'],
                    collect($items)
                        ->filter(fn ($it) => trim((string) ($it['sku_code'] ?? '')) === '')
                        ->isNotEmpty() ? ['Ada item tanpa Seller SKU.'] : []
                )));

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

                if ($refreshItems && $skuQtyMap) {
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

    /** @return array{0: ?MpShipment, 1: ?string} */
    private function findExistingShipment(array $shipment, int $storeId): array
    {
        $query = MpShipment::query()
            ->where('store_id', (int) ($shipment['store_id'] ?? $storeId))
            ->where('channel', $shipment['channel'])
            ->where('platform_order_id', $shipment['platform_order_id']);
        $packageId = trim((string) ($shipment['platform_shipment_id'] ?? ''));
        $tracking = trim((string) ($shipment['tracking_no'] ?? ''));

        $exact = (clone $query)
            ->when($packageId !== '', fn ($q) => $q->where('platform_shipment_id', $packageId), fn ($q) => $q->whereNull('platform_shipment_id'))
            ->first();
        if ($shipment['channel'] !== 'tiktok') {
            return [$exact, null];
        }
        if ($exact) {
            $existingTracking = trim((string) $exact->tracking_no);
            if ($tracking !== '' && $existingTracking !== '' && $tracking !== $existingTracking) {
                return [null, 'Package ID yang sama memiliki resi berbeda; perlu diperiksa sebelum import.'];
            }
            return [$exact, null];
        }

        if ($tracking !== '') {
            $trackingMatches = (clone $query)->where('tracking_no', $tracking)->limit(2)->get();
            if ($trackingMatches->count() === 1) {
                $match = $trackingMatches->first();
                if ($packageId !== '' && filled($match->platform_shipment_id)
                    && $match->platform_shipment_id !== $packageId) {
                    return [null, 'Resi yang sama sudah terhubung ke Package ID berbeda.'];
                }

                return [$match, null];
            }
            if ($trackingMatches->count() > 1) {
                return [null, 'Resi cocok dengan lebih dari satu shipment lama.'];
            }
        }

        if ((clone $query)->whereNull('platform_shipment_id')->exists() && $packageId !== '') {
            return [null, 'Order lama belum memiliki Package ID dan resinya tidak cocok; perlu diperiksa agar tidak duplikat.'];
        }
        if ($packageId === '' && $tracking === '' && (clone $query)->exists()) {
            return [null, 'Order lama sudah ada, tetapi file tidak memiliki Package ID atau resi untuk mencocokkan paket.'];
        }

        return [null, null];
    }

    private function fillMissingShipmentFields(MpShipment $shipment, array $payload): void
    {
        $fillable = [
            'purchase_channel', 'platform_shipment_id', 'tracking_no', 'marketplace_status',
            'status_norm', 'order_created_at', 'paid_at', 'shipped_at', 'delivered_at',
            'completed_at', 'raw_payload', 'marketplace_order_id',
        ];
        $changes = [];
        foreach ($fillable as $key) {
            $value = $payload[$key] ?? null;
            if (($shipment->{$key} === null || $shipment->{$key} === '')
                && $value !== null && $value !== '') {
                $changes[$key] = $value;
            }
        }

        if ($changes !== []) {
            $shipment->fill($changes)->save();
        }
    }
}
