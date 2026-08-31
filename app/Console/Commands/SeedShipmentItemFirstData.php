<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\OrderFulfillment;
use App\Models\OrderFulfillmentLine;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\ShipmentOrderScan;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedShipmentItemFirstData extends Command
{
    protected $signature = 'shipments:seed-item-first
                            {--store= : ID store untuk dummy order marketplace}
                            {--reset : Hapus hanya shipment/order dummy yang dibuat command ini}';

    protected $description = '[DEV ONLY] Buat dummy order, fulfillment, stok, dan shipment item-first.';

    private const LINKED_SHIPMENT_CODE = 'SHP-DUMMY-ITEM-FIRST';
    private const EXTERNAL_SHIPMENT_CODE = 'TTK-DUMMY-ITEM-FIRST';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Command dummy tidak boleh dijalankan di production.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function (): void {
                if ($this->option('reset')) {
                    $this->resetDummyData();
                }

                $warehouse = Warehouse::where('code', 'WH-RTS')->first();
                if (!$warehouse) {
                    throw new \RuntimeException('Warehouse WH-RTS belum ada.');
                }

                $items = $this->ensureItems();
                $this->ensureStock($warehouse, $items);

                $linkedStore = $this->ensureLinkedStore();
                if ($this->option('store')) {
                    $linkedStore = Store::find((int) $this->option('store'));
                    if (!$linkedStore) {
                        throw new \RuntimeException('Store dari opsi --store tidak ditemukan.');
                    }
                }

                $linkedOrders = $this->ensureLinkedOrders($linkedStore, $items);
                $this->ensureLinkedShipment($warehouse, $linkedStore, $linkedOrders, $items);

                $externalStore = $this->ensureExternalStore();
                $this->ensureExternalShipment($warehouse, $externalStore, $items);

                $this->outputData($linkedStore, $externalStore, $linkedOrders);
            });
        } catch (\Throwable $e) {
            $this->error('Gagal membuat dummy data: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @return array{shirt: Item, cap: Item} */
    private function ensureItems(): array
    {
        return [
            'shirt' => Item::firstOrCreate(
                ['code' => 'DUMMY-TSHIRT-BLK'],
                [
                    'name' => 'Dummy T-Shirt Hitam',
                    'unit' => 'pcs',
                    'type' => 'finished_good',
                    'active' => true,
                    'hpp' => 50000,
                    'last_purchase_price' => 50000,
                ]
            ),
            'cap' => Item::firstOrCreate(
                ['code' => 'DUMMY-CAP-BLK'],
                [
                    'name' => 'Dummy Topi Hitam',
                    'unit' => 'pcs',
                    'type' => 'finished_good',
                    'active' => true,
                    'hpp' => 30000,
                    'last_purchase_price' => 30000,
                ]
            ),
        ];
    }

    /** @param array{shirt: Item, cap: Item} $items */
    private function ensureStock(Warehouse $warehouse, array $items): void
    {
        foreach ($items as $item) {
            InventoryStock::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'item_id' => $item->id],
                ['qty' => 100, 'allocated_qty' => 0]
            );
        }
    }

    private function ensureLinkedStore(): Store
    {
        $channel = Channel::firstOrCreate(
            ['code' => 'SHP'],
            ['name' => 'Shopee', 'status' => 'active', 'is_active' => true]
        );

        return Store::firstOrCreate(
            ['code' => 'SHP-DUMMY'],
            [
                'channel_id' => $channel->id,
                'name' => 'Dummy Shopee Store',
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }

    private function ensureExternalStore(): Store
    {
        $channel = Channel::firstOrCreate(
            ['code' => 'TTK'],
            ['name' => 'Tiktok', 'status' => 'active', 'is_active' => true]
        );

        return Store::firstOrCreate(
            ['code' => 'TTK-DUMMY'],
            [
                'channel_id' => $channel->id,
                'name' => 'Dummy TikTok External',
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }

    /** @param array{shirt: Item, cap: Item} $items */
    private function ensureLinkedOrders(Store $store, array $items): array
    {
        $definitions = [
            [
                'order_no' => 'DUMMY-SHP-001',
                'lines' => [[$items['shirt'], 2, 125000], [$items['cap'], 1, 75000]],
            ],
            [
                'order_no' => 'DUMMY-SHP-002',
                'lines' => [[$items['shirt'], 2, 125000], [$items['cap'], 1, 75000]],
            ],
            [
                'order_no' => 'DUMMY-SHP-003',
                'lines' => [[$items['shirt'], 2, 125000], [$items['cap'], 1, 75000]],
            ],
            [
                'order_no' => 'DUMMY-SHP-004',
                'lines' => [[$items['shirt'], 4, 125000], [$items['cap'], 2, 150000]],
            ],
        ];

        $orders = [];
        foreach ($definitions as $definition) {
            $order = MarketplaceOrder::updateOrCreate(
                ['store_id' => $store->id, 'external_order_id' => $definition['order_no']],
                [
                    'channel_order_id' => $definition['order_no'],
                    'booking_sn' => $definition['order_no'],
                    'order_status' => 'READY_TO_SHIP',
                    'status' => 'new',
                    'order_date' => now(),
                    'ordered_at' => now()->subMinutes(15),
                    'currency' => 'IDR',
                    'total_amount' => collect($definition['lines'])->sum(fn ($line) => $line[1] * $line[2]),
                    'buyer_username' => 'dummy_buyer',
                    'shipping_carrier' => 'JNE',
                    'synced_at' => now(),
                ]
            );

            $fulfillment = OrderFulfillment::updateOrCreate(
                ['marketplace_order_id' => $order->id],
                [
                    'warehouse_id' => Warehouse::where('code', 'WH-RTS')->value('id'),
                    'status' => OrderFulfillment::STATUS_PENDING_REVIEW,
                ]
            );

            foreach ($definition['lines'] as $index => [$item, $qty, $price]) {
                $orderItem = MarketplaceOrderItem::updateOrCreate(
                    ['order_id' => $order->id, 'line_no' => $index + 1],
                    [
                        'marketplace_order_id' => $order->id,
                        'external_item_id' => 'DUMMY-' . $item->id,
                        'item_id' => $item->id,
                        'internal_item_id' => $item->id,
                        'item_name' => $item->name,
                        'item_sku' => $item->code,
                        'model_sku' => $item->code,
                        'marketplace_sku' => $item->code,
                        'item_code_snapshot' => $item->code,
                        'item_name_snapshot' => $item->name,
                        'qty' => $qty,
                        'price' => $price,
                        'price_original' => $price,
                        'price_after_discount' => $price,
                        'line_gross_amount' => $qty * $price,
                        'line_net_amount' => $qty * $price,
                        'mapping_status' => 'mapped',
                        'data_status' => 'valid',
                        'hpp_snapshot' => $item->hpp ?: 0,
                    ]
                );

                OrderFulfillmentLine::updateOrCreate(
                    ['fulfillment_id' => $fulfillment->id, 'item_id' => $item->id],
                    [
                        'marketplace_order_item_id' => $orderItem->id,
                        'marketplace_sku' => $item->code,
                        'marketplace_item_name' => $item->name,
                        'qty_ordered' => $qty,
                        'qty_fulfilled' => $qty,
                        'stock_available' => 100,
                    ]
                );
            }

            $orders[] = [$order, $fulfillment];
        }

        return $orders;
    }

    /** @param array<int, array{0: MarketplaceOrder, 1: OrderFulfillment}> $orders */
    /** @param array{shirt: Item, cap: Item} $items */
    private function ensureLinkedShipment(Warehouse $warehouse, Store $store, array $orders, array $items): void
    {
        $attributes = [
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
            'notes' => 'Dummy item-first: empat order linked, SKU T-Shirt dan Topi tersebar antar order.',
        ];
        if (Schema::hasColumn('shipments', 'warehouse_id')) {
            $attributes['warehouse_id'] = $warehouse->id;
        }

        $shipment = Shipment::firstOrCreate(
            ['code' => self::LINKED_SHIPMENT_CODE],
            $attributes
        );

        if (!$shipment->wasRecentlyCreated) {
            return;
        }

        foreach ($orders as [$order, $fulfillment]) {
            $fulfillment->load('lines.item');
            ShipmentOrderScan::create([
                'shipment_id' => $shipment->id,
                'fulfillment_id' => $fulfillment->id,
                'order_no' => $order->channel_order_id,
                'status' => 'pending',
                'source' => 'marketplace',
                'raw_payload' => [
                    'mode' => 'auto_link',
                    'no' => $order->channel_order_id,
                    'found' => true,
                    'order' => [
                        'order_no' => $order->channel_order_id,
                        'source' => 'marketplace',
                        'lines' => $fulfillment->lines->map(fn ($line) => [
                            'item_id' => $line->item_id,
                            'item_code' => $line->item?->code ?? '-',
                            'item_name' => $line->item?->name ?? '-',
                            'qty_need' => (int) $line->qty_fulfilled,
                            'qty_alloc' => 0,
                            'qty_short' => (int) $line->qty_fulfilled,
                            'status' => 'short',
                        ])->values()->all(),
                    ],
                    'decision' => 'pending',
                    'subs' => [],
                ],
            ]);
        }

        $this->createUnmappedLine($shipment, $items['shirt'], 10);
        $this->createUnmappedLine($shipment, $items['cap'], 5);
    }

    /** @param array{shirt: Item, cap: Item} $items */
    private function ensureExternalShipment(Warehouse $warehouse, Store $store, array $items): void
    {
        $attributes = [
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
            'notes' => 'Dummy item-first: TikTok external order belum tersinkron.',
        ];
        if (Schema::hasColumn('shipments', 'warehouse_id')) {
            $attributes['warehouse_id'] = $warehouse->id;
        }

        $shipment = Shipment::firstOrCreate(
            ['code' => self::EXTERNAL_SHIPMENT_CODE],
            $attributes
        );

        if (!$shipment->wasRecentlyCreated) {
            return;
        }

        ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'DUMMY-TTK-001',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => [
                'mode' => 'record_only',
                'lookup_status' => 'not_found',
                'no' => 'DUMMY-TTK-001',
                'found' => false,
                'order' => ['order_no' => 'DUMMY-TTK-001', 'source' => 'external', 'status' => 'pending', 'lines' => []],
                'decision' => 'pending',
                'subs' => [],
            ],
        ]);

        $this->createUnmappedLine($shipment, $items['shirt'], 1);
    }

    private function createUnmappedLine(Shipment $shipment, Item $item, int $qty): void
    {
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => $qty,
            'allocated_qty' => $qty,
        ]);
    }

    private function resetDummyData(): void
    {
        $shipmentIds = Shipment::whereIn('code', [self::LINKED_SHIPMENT_CODE, self::EXTERNAL_SHIPMENT_CODE])->pluck('id');
        if ($shipmentIds->isNotEmpty()) {
            Shipment::whereIn('id', $shipmentIds)->delete();
        }

        MarketplaceOrder::whereIn('external_order_id', [
            'DUMMY-SHP-001',
            'DUMMY-SHP-002',
            'DUMMY-SHP-003',
            'DUMMY-SHP-004',
        ])->delete();
        $this->warn('Dummy shipment dan order lama dihapus. Data item/stok dummy dipertahankan.');
    }

    /** @param array<int, array{0: MarketplaceOrder, 1: OrderFulfillment}> $orders */
    private function outputData(Store $linkedStore, Store $externalStore, array $orders): void
    {
        $this->info('Dummy data siap digunakan.');
        $this->line('Linked shipment : ' . self::LINKED_SHIPMENT_CODE . ' (' . $linkedStore->code . ')');
        $this->line('External shipment: ' . self::EXTERNAL_SHIPMENT_CODE . ' (' . $externalStore->code . ')');
        $this->line('Order linked    : ' . collect($orders)->map(fn ($row) => $row[0]->channel_order_id)->implode(', '));
        $this->line('Order external  : DUMMY-TTK-001');
        $this->line('Tes item-first  : /sales/shipments/' . self::LINKED_SHIPMENT_CODE . '/edit');
        $this->line('Tes rekonsiliasi: /sales/shipments/' . self::LINKED_SHIPMENT_CODE . '/rekon');
    }
}
