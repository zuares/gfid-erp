<?php

namespace App\Console\Commands;

use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedShipmentPilotDummy extends Command
{
    private const SHIPMENT_CODE = 'SHP-DUMMY-PILOT-HPP';

    private const ITEM_CODE = 'DUMMY-SHIPMENT-FG';

    protected $signature = 'production:seed-shipment-pilot-dummy';

    protected $description = '[DEV ONLY] Buat satu Shipment posted dengan stok keluar dan jurnal HPP legacy untuk pilot mirror.';

    public function handle(InventoryService $inventory, JournalService $journalService): int
    {
        if (app()->isProduction()) {
            $this->error('Command dummy tidak boleh dijalankan di production.');

            return self::FAILURE;
        }

        try {
            $shipment = DB::transaction(function () use ($inventory, $journalService): Shipment {
                $existing = Shipment::query()->where('code', self::SHIPMENT_CODE)->first();
                if ($existing) {
                    if ($existing->status !== 'posted' || ! $existing->posted_at) {
                        throw new \RuntimeException('Shipment dummy sudah ada tetapi belum posted; periksa manual sebelum melanjutkan.');
                    }

                    return $existing;
                }

                $warehouse = Warehouse::query()->where('code', 'WH-RTS')->where('active', true)->first();
                if (! $warehouse) {
                    throw new \RuntimeException('Warehouse WH-RTS belum tersedia.');
                }

                $item = Item::query()->firstOrCreate(
                    ['code' => self::ITEM_CODE],
                    [
                        'name' => 'Dummy Finished Good — Shipment HPP Pilot',
                        'unit' => 'pcs',
                        'type' => 'finished_good',
                        'item_role' => 'finished_good',
                        'active' => true,
                        'hpp' => 50000,
                        'last_purchase_price' => 50000,
                    ],
                );

                $shipmentPayload = [
                    'code' => self::SHIPMENT_CODE,
                    'shipment_type' => Shipment::TYPE_MANUAL,
                    'scan_mode' => 'item_first',
                    'date' => now()->toDateString(),
                    'status' => 'draft',
                    'total_qty' => 1,
                    'notes' => 'Dummy pilot Shipment: hanya stok keluar dan HPP; tanpa revenue, fee, payout, atau bank.',
                ];
                if (Schema::hasColumn('shipments', 'warehouse_id')) {
                    $shipmentPayload['warehouse_id'] = $warehouse->id;
                }

                $shipment = Shipment::create($shipmentPayload);
                $line = ShipmentLine::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $item->id,
                    'qty_scanned' => 1,
                    'allocated_qty' => 0,
                ]);

                $stock = InventoryStock::query()
                    ->where('warehouse_id', $warehouse->id)
                    ->where('item_id', $item->id)
                    ->lockForUpdate()
                    ->first();
                if (! $stock || (float) $stock->qty < 1) {
                    $inventory->stockIn(
                        warehouseId: $warehouse->id,
                        itemId: $item->id,
                        qty: 1,
                        date: $shipment->date,
                        sourceType: 'shipment_pilot_dummy_opening',
                        sourceId: $shipment->id,
                        notes: 'Opening stock dummy untuk pilot Shipment HPP',
                        unitCost: 50000,
                        affectLotCost: false,
                    );
                }

                $inventory->stockOut(
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    qty: 1,
                    date: $shipment->date,
                    sourceType: 'shipment',
                    sourceId: $shipment->id,
                    notes: 'Dummy Shipment HPP pilot',
                    allowNegative: false,
                    affectLotCost: false,
                    sourceLineId: $line->id,
                );

                $shipment->forceFill([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'total_qty' => 1,
                ])->save();

                $journalService->postShipmentCogsFromMutations($shipment->fresh());

                return $shipment->fresh();
            });
        } catch (\Throwable $e) {
            $this->error('Gagal membuat dummy Shipment: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode([
            'status' => 'READY',
            'shipment_id' => (int) $shipment->id,
            'code' => $shipment->code,
            'status_document' => $shipment->status,
            'message' => 'Dummy Shipment posted. Revenue, fee, payout, dan bank tidak dibuat.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
