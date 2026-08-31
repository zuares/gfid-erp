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
use App\Models\ShipmentWave;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedShipmentDailyWavesData extends Command
{
    protected $signature = 'shipments:seed-daily-waves
                            {--reset : Hapus dummy selama belum ada gelombang yang diposting}
                            {--wave=1 : Seed gelombang 1 atau 2}';

    protected $description = '[DEV ONLY] Buat dummy satu shipment marketplace dengan gelombang siang/sore.';

    private const SHIPMENT_CODE = 'SHP-DUMMY-DAILY-WAVES';
    private const STORE_CODE = 'SHP-DUMMY-DAILY';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Command dummy tidak boleh dijalankan di production.');
            return self::FAILURE;
        }

        if (!Schema::hasTable('shipment_waves')) {
            $this->error('Migration shipment_waves belum dijalankan.');
            $this->line('Jalankan: php artisan migrate --path=database/migrations/2026_08_31_090000_create_shipment_waves_table.php');
            return self::FAILURE;
        }

        $waveNumber = (int) $this->option('wave');
        if (!in_array($waveNumber, [1, 2], true)) {
            $this->error('Opsi --wave hanya boleh 1 atau 2.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($waveNumber): void {
                $warehouse = Warehouse::where('code', 'WH-RTS')->first();
                if (!$warehouse) {
                    throw new \RuntimeException('Warehouse WH-RTS belum ada.');
                }

                $shipment = Shipment::where('code', self::SHIPMENT_CODE)->first();
                if ($this->option('reset') && $shipment) {
                    $this->resetDummy($shipment, $warehouse);
                    $shipment = null;
                }

                $items = $this->ensureItems();
                $this->ensureStock($warehouse, $items);
                $store = $this->ensureStore();
                $shipment ??= $this->ensureShipment($warehouse, $store);

                if ($waveNumber === 1) {
                    $this->seedWave(
                        shipment: $shipment,
                        warehouse: $warehouse,
                        items: $items,
                        sequence: 1,
                        orders: ['DUMMY-WAVE-001', 'DUMMY-WAVE-002'],
                    );
                } else {
                    $firstWave = $shipment->waves()->where('sequence', 1)->first();
                    if (!$firstWave || $firstWave->status !== ShipmentWave::STATUS_POSTED) {
                        throw new \RuntimeException('Gelombang 1 harus diposting dulu sebelum seed gelombang 2.');
                    }

                    $this->seedWave(
                        shipment: $shipment,
                        warehouse: $warehouse,
                        items: $items,
                        sequence: 2,
                        orders: ['DUMMY-WAVE-003', 'DUMMY-WAVE-004'],
                    );
                }

                $this->outputData($shipment, $waveNumber);
            });
        } catch (\Throwable $e) {
            $this->error('Gagal membuat dummy daily waves: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @return array{shirt: Item, cap: Item} */
    private function ensureItems(): array
    {
        return [
            'shirt' => Item::firstOrCreate(
                ['code' => 'DUMMY-WAVE-TSHIRT-BLK'],
                [
                    'name' => 'Dummy Wave T-Shirt Hitam',
                    'unit' => 'pcs',
                    'type' => 'finished_good',
                    'active' => true,
                    'hpp' => 50000,
                    'last_purchase_price' => 50000,
                ]
            ),
            'cap' => Item::firstOrCreate(
                ['code' => 'DUMMY-WAVE-CAP-BLK'],
                [
                    'name' => 'Dummy Wave Topi Hitam',
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

    private function ensureStore(): Store
    {
        $channel = Channel::firstOrCreate(
            ['code' => 'SHP'],
            ['name' => 'Shopee', 'status' => 'active', 'is_active' => true]
        );

        return Store::firstOrCreate(
            ['code' => self::STORE_CODE],
            [
                'channel_id' => $channel->id,
                'name' => 'Dummy Shopee Daily Waves',
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }

    private function ensureShipment(Warehouse $warehouse, Store $store): Shipment
    {
        $attributes = [
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'dispatch_mode' => Shipment::DISPATCH_DAILY,
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
            'notes' => 'Dummy satu shipment harian: gelombang siang lalu gelombang sore.',
        ];

        if (Schema::hasColumn('shipments', 'warehouse_id')) {
            $attributes['warehouse_id'] = $warehouse->id;
        }

        return Shipment::firstOrCreate(['code' => self::SHIPMENT_CODE], $attributes);
    }

    /**
     * @param array{shirt: Item, cap: Item} $items
     * @param array<int, string> $orders
     */
    private function seedWave(
        Shipment $shipment,
        Warehouse $warehouse,
        array $items,
        int $sequence,
        array $orders,
    ): void {
        $wave = ShipmentWave::firstOrCreate(
            ['shipment_id' => $shipment->id, 'sequence' => $sequence],
            [
                'code' => self::SHIPMENT_CODE . '-W' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
                'label' => $sequence === 1 ? 'Siang' : 'Sore',
                'status' => ShipmentWave::STATUS_OPEN,
                'opened_at' => now(),
            ]
        );

        if ($wave->status !== ShipmentWave::STATUS_OPEN) {
            $this->warn('Gelombang ' . $wave->code . ' sudah ada dengan status ' . $wave->status . '.');
            return;
        }

        foreach ($orders as $orderNo) {
            [$order, $fulfillment] = $this->ensureOrder($warehouse, $items, $orderNo);

            $scan = ShipmentOrderScan::firstOrCreate(
                ['shipment_id' => $shipment->id, 'order_no' => $orderNo],
                [
                    'shipment_wave_id' => $wave->id,
                    'fulfillment_id' => $fulfillment->id,
                    'status' => 'pending',
                    'source' => 'marketplace',
                    'raw_payload' => $this->scanPayload($orderNo, $fulfillment, $items),
                ]
            );

            if (!$scan->shipment_wave_id) {
                $scan->update(['shipment_wave_id' => $wave->id]);
            }

            foreach ($fulfillment->lines as $fulfillmentLine) {
                $line = ShipmentLine::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'shipment_wave_id' => $wave->id,
                        'shipment_order_scan_id' => $scan->id,
                        'item_id' => $fulfillmentLine->item_id,
                    ],
                    [
                        'qty_scanned' => (int) $fulfillmentLine->qty_fulfilled,
                        'allocated_qty' => (int) $fulfillmentLine->qty_fulfilled,
                    ]
                );

                if ($line->wasRecentlyCreated) {
                    $stock = InventoryStock::query()
                        ->where('warehouse_id', $warehouse->id)
                        ->where('item_id', $fulfillmentLine->item_id)
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        $stock->allocated_qty = (float) $stock->allocated_qty + (int) $fulfillmentLine->qty_fulfilled;
                        $stock->save();
                    }
                }
            }
        }

        $wave->update([
            'total_qty' => (int) $wave->lines()->sum('qty_scanned'),
        ]);
    }

    /** @param array{shirt: Item, cap: Item} $items */
    private function ensureOrder(Warehouse $warehouse, array $items, string $orderNo): array
    {
        $isFirstWave = in_array($orderNo, ['DUMMY-WAVE-001', 'DUMMY-WAVE-002'], true);
        $shirtQty = $isFirstWave ? 2 : 3;
        $capQty = $isFirstWave ? 1 : ($orderNo === 'DUMMY-WAVE-003' ? 1 : 2);

        $order = MarketplaceOrder::updateOrCreate(
            ['store_id' => $this->ensureStore()->id, 'external_order_id' => $orderNo],
            [
                'channel_order_id' => $orderNo,
                'booking_sn' => $orderNo,
                'order_status' => 'READY_TO_SHIP',
                'status' => 'new',
                'order_date' => now(),
                'ordered_at' => now()->subMinutes(15),
                'currency' => 'IDR',
                'total_amount' => ($shirtQty * 125000) + ($capQty * 75000),
                'buyer_username' => 'dummy_wave_buyer',
                'shipping_carrier' => 'JNE',
                'synced_at' => now(),
            ]
        );

        $fulfillment = OrderFulfillment::updateOrCreate(
            ['marketplace_order_id' => $order->id],
            [
                'warehouse_id' => $warehouse->id,
                'status' => OrderFulfillment::STATUS_PENDING_REVIEW,
            ]
        );

        $definitions = [
            [$items['shirt'], $shirtQty, 125000, 1],
            [$items['cap'], $capQty, 75000, 2],
        ];

        foreach ($definitions as [$item, $qty, $price, $lineNo]) {
            $orderItem = MarketplaceOrderItem::updateOrCreate(
                ['order_id' => $order->id, 'line_no' => $lineNo],
                [
                    'marketplace_order_id' => $order->id,
                    'external_item_id' => 'DUMMY-WAVE-' . $item->id,
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

        return [$order->fresh(), $fulfillment->fresh('lines.item')];
    }

    /** @param array{shirt: Item, cap: Item} $items */
    private function scanPayload(string $orderNo, OrderFulfillment $fulfillment, array $items): array
    {
        $lines = $fulfillment->lines->map(fn ($line) => [
            'item_id' => (int) $line->item_id,
            'item_code' => $line->item?->code,
            'item_name' => $line->item?->name,
            'qty_need' => (int) $line->qty_fulfilled,
            'qty_alloc' => (int) $line->qty_fulfilled,
            'qty_short' => 0,
            'status' => 'complete',
        ])->values()->all();

        return [
            'mode' => 'auto_link',
            'no' => $orderNo,
            'found' => true,
            'order' => [
                'order_no' => $orderNo,
                'source' => 'marketplace',
                'lines' => $lines,
            ],
            'allocations' => collect($lines)->map(fn ($line) => [
                'item_id' => $line['item_id'],
                'qty' => $line['qty_alloc'],
            ])->values()->all(),
            'decision' => 'confirm',
            'subs' => [],
        ];
    }

    private function resetDummy(Shipment $shipment, Warehouse $warehouse): void
    {
        if ($shipment->waves()->where('status', ShipmentWave::STATUS_POSTED)->exists()) {
            throw new \RuntimeException('Dummy sudah punya gelombang posted. Jangan reset agar stok dan jurnal tidak tertinggal.');
        }

        $orderNos = ['DUMMY-WAVE-001', 'DUMMY-WAVE-002', 'DUMMY-WAVE-003', 'DUMMY-WAVE-004'];
        foreach ($shipment->lines()->get(['item_id', 'allocated_qty']) as $line) {
            $allocatedQty = (float) ($line->allocated_qty ?? 0);
            if ($allocatedQty <= 0) {
                continue;
            }

            $stock = InventoryStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('item_id', $line->item_id)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->allocated_qty = max(0, (float) $stock->allocated_qty - $allocatedQty);
                $stock->save();
            }
        }

        $shipment->delete();
        MarketplaceOrder::whereIn('external_order_id', $orderNos)->delete();
        $this->warn('Dummy daily waves di-reset. Item dan stok dummy dipertahankan.');
    }

    private function outputData(Shipment $shipment, int $waveNumber): void
    {
        $this->info('Dummy daily waves siap.');
        $this->line('Shipment       : ' . $shipment->code);
        $this->line('Gelombang seed  : W0' . $waveNumber . ' (' . ($waveNumber === 1 ? 'Siang' : 'Sore') . ')');
        $this->line('Tes UI          : /sales/shipments/' . $shipment->code . '/edit');
        $this->line('Tes rekonsiliasi: /sales/shipments/' . $shipment->code . '/rekon');
        if ($waveNumber === 1) {
            $this->line('Alur berikutnya : posting W01, lalu jalankan ulang dengan --wave=2 untuk order gelombang sore.');
        } else {
            $this->line('Alur berikutnya : posting W02, lalu klik Tutup Shipment Harian.');
        }
    }
}
