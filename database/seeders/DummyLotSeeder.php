<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder dummy — buat banyak LOT kain di gudang RM untuk test tampilan.
 * Jalankan: php artisan db:seed --class=DummyLotSeeder
 * Hapus lagi: php artisan db:seed --class=DummyLotSeeder --rollback (atau hapus manual dari tinker)
 */
class DummyLotSeeder extends Seeder
{
    // Tag unik agar seeder bisa di-skip jika sudah pernah jalan
    const TAG = 'dummy-lot-seeder-v1';

    public function run(): void
    {
        $alreadyRun = \App\Models\Lot::where('code', 'like', 'LOT-DUMMY-%')->exists();

        if ($alreadyRun) {
            $this->command->info('[DummyLotSeeder] Sudah ada dummy LOT, skip. Hapus dulu dengan DummyLotSeeder --fresh.');
            return;
        }

        /** @var InventoryService $inventory */
        $inventory = app(InventoryService::class);

        // Ambil gudang RM (harus sudah ada)
        $rm = Warehouse::where('code', 'RM')->first();
        if (!$rm) {
            $this->command->error('Gudang RM tidak ditemukan. Jalankan InventoryTransferDemoSeeder dulu.');
            return;
        }

        // Ambil item kain RM yang sudah ada (main raw material)
        $fabricItems = Item::where('type', 'material')
            ->where('active', 1)
            ->whereIn('code', ['FLC280BLK', 'FLC280NVY', 'CTN200WHT', 'RYN150RED'])
            ->get();

        if ($fabricItems->isEmpty()) {
            // Fallback: ambil item material pertama yang ada
            $fabricItems = Item::where('type', 'material')->where('active', 1)->limit(3)->get();
        }

        if ($fabricItems->isEmpty()) {
            $this->command->error('Tidak ada item kain (material) di database.');
            return;
        }

        $this->command->info('[DummyLotSeeder] Membuat dummy LOT...');

        DB::transaction(function () use ($inventory, $rm, $fabricItems) {

            $counter = 1;
            $today = now()->toDateString();

            foreach ($fabricItems as $item) {
                // Buat 8 LOT per item kain
                for ($i = 1; $i <= 8; $i++) {
                    $lotCode = 'LOT-DUMMY-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
                    $qty     = round(rand(5, 80) + (rand(0, 99) / 100), 2);

                    // 1. Buat record Lot
                    $lot = Lot::create([
                        'code'         => $lotCode,
                        'item_id'      => $item->id,
                        'initial_qty'  => $qty,
                        'initial_cost' => 0,
                        'qty_onhand'   => $qty,
                        'total_cost'   => 0,
                        'avg_cost'     => 0,
                        'status'       => 'active',
                    ]);

                    // 2. Buat mutasi stock IN di gudang RM
                    $inventory->stockIn(
                        warehouseId:  $rm->id,
                        itemId:       $item->id,
                        qty:          $qty,
                        date:         $today,
                        sourceType:   'opening_balance',
                        sourceId:     null,
                        notes:        "Dummy LOT seeder: {$lotCode}",
                        lotId:        $lot->id,
                        affectLotCost: false,
                    );

                    $this->command->line("  ✓ {$lotCode} — {$item->code} — {$qty} kg");
                    $counter++;
                }
            }
        });

        $this->command->info('[DummyLotSeeder] Selesai. Buka /production/cutting-jobs/create untuk lihat hasilnya.');
    }
}
