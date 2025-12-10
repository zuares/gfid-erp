<?php

namespace Database\Seeders;

use App\Helpers\CodeGenerator;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferLine;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryTransferDemoSeeder extends Seeder
{
    public function run(): void
    {
        /** @var InventoryService $inventory */
        $inventory = app(InventoryService::class);

        // Kalau sudah pernah buat transfer dengan notes khusus, skip seeder ini
        $alreadySeeded = InventoryTransfer::where('notes', 'Seeder transfer bahan ke cutting')->exists();

        if ($alreadySeeded) {
            echo "\n[InventoryTransferDemoSeeder] Sudah pernah dijalankan, skip.\n";
            return;
        }

        DB::transaction(function () use ($inventory) {

            // Cari user pertama sebagai created_by (fallback null kalau tidak ada)
            $userId = User::query()->value('id') ?? null;

            // 1. GUDANG
            $g1 = Warehouse::firstOrCreate(
                ['code' => 'RM'],
                [
                    'name' => 'Raw Material',
                    'type' => 'raw',
                    'active' => 1,
                ]
            );

            $g2 = Warehouse::firstOrCreate(
                ['code' => 'CUT'],
                [
                    'name' => 'Cutting Room',
                    'type' => 'production',
                    'active' => 1,
                ]
            );

            $g3 = Warehouse::firstOrCreate(
                ['code' => 'WIP-SEW'],
                [
                    'name' => 'WIP Sewing',
                    'type' => 'production',
                    'active' => 1,
                ]
            );

            // 2. ITEM
            $i1 = Item::firstOrCreate(
                ['code' => 'FLC280BLK'],
                [
                    'name' => 'Fleece 280gsm Black',
                    'unit' => 'kg',
                    'type' => 'material',
                    'item_category_id' => 1,
                    'active' => 1,
                ]
            );

            $i2 = Item::firstOrCreate(
                ['code' => 'FLC280NVY'],
                [
                    'name' => 'Fleece 280gsm Navy',
                    'unit' => 'kg',
                    'type' => 'material',
                    'item_category_id' => 1,
                    'active' => 1,
                ]
            );

            // 3. STOK AWAL DI GUDANG RM
            $openingDate = now()->subDays(3)->toDateString();

            $inventory->stockIn(
                warehouseId: $g1->id,
                itemId: $i1->id,
                qty: 100,
                date: $openingDate,
                sourceType: 'opening_balance',
                sourceId: null,
                notes: 'Saldo awal seeder FLC280BLK'
            );

            $inventory->stockIn(
                warehouseId: $g1->id,
                itemId: $i2->id,
                qty: 100,
                date: $openingDate,
                sourceType: 'opening_balance',
                sourceId: null,
                notes: 'Saldo awal seeder FLC280NVY'
            );

            // 4. TRANSFER #1 — RM → CUT
            $t1Date = now()->subDays(2)->toDateString();

            /** @var InventoryTransfer $t1 */
            $t1 = InventoryTransfer::create([
                'code' => CodeGenerator::generate('TRF'),
                'date' => $t1Date,
                'from_warehouse_id' => $g1->id,
                'to_warehouse_id' => $g2->id,
                'notes' => 'Seeder transfer bahan ke cutting',
                'created_by' => $userId,
            ]);

            $lines1 = [
                [
                    'item_id' => $i1->id,
                    'qty' => 50,
                    'notes' => 'Kain fleece hitam untuk cutting',
                ],
                [
                    'item_id' => $i2->id,
                    'qty' => 10,
                    'notes' => 'Kain fleece navy untuk cutting',
                ],
            ];

            foreach ($lines1 as $ln) {
                /** @var InventoryTransferLine $line */
                $line = InventoryTransferLine::create([
                    'inventory_transfer_id' => $t1->id,
                    'item_id' => $ln['item_id'],
                    'qty' => $ln['qty'],
                    'notes' => $ln['notes'],
                ]);

                $inventory->transfer(
                    fromWarehouseId: $t1->from_warehouse_id,
                    toWarehouseId: $t1->to_warehouse_id,
                    itemId: $line->item_id,
                    qty: $line->qty,
                    date: $t1->date,
                    sourceType: 'inventory_transfer',
                    sourceId: $t1->id,
                    notes: 'Seeder transfer ' . $t1->code . ' line ' . $line->id,
                    allowNegative: false,
                );
            }

            // 5. TRANSFER #2 — CUT → WIP-SEW
            $t2Date = now()->subDay()->toDateString();

            /** @var InventoryTransfer $t2 */
            $t2 = InventoryTransfer::create([
                'code' => CodeGenerator::generate('TRF'),
                'date' => $t2Date,
                'from_warehouse_id' => $g2->id,
                'to_warehouse_id' => $g3->id,
                'notes' => 'Seeder transfer hasil cutting ke sewing',
                'created_by' => $userId,
            ]);

            $lines2 = [
                [
                    'item_id' => $i1->id,
                    'qty' => 30,
                    'notes' => 'Part fleece setelah cutting',
                ],
            ];

            foreach ($lines2 as $ln) {
                /** @var InventoryTransferLine $line */
                $line = InventoryTransferLine::create([
                    'inventory_transfer_id' => $t2->id,
                    'item_id' => $ln['item_id'],
                    'qty' => $ln['qty'],
                    'notes' => $ln['notes'],
                ]);

                $inventory->transfer(
                    fromWarehouseId: $t2->from_warehouse_id,
                    toWarehouseId: $t2->to_warehouse_id,
                    itemId: $line->item_id,
                    qty: $line->qty,
                    date: $t2->date,
                    sourceType: 'inventory_transfer',
                    sourceId: $t2->id,
                    notes: 'Seeder transfer ' . $t2->code . ' line ' . $line->id,
                    allowNegative: false,
                );
            }
        });

        echo "\n[InventoryTransferDemoSeeder] Seeder transfer stok selesai.\n";
    }
}
