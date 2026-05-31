<?php

namespace App\Console\Commands;

use App\Models\InventoryStock;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TransferWipFinToWhPrd extends Command
{
    protected $signature = 'inventory:move-wipfin-to-prd';
    protected $description = 'Transfer semua stok dari WIP-FIN ke WH-PRD';

    public function handle(InventoryService $inventory)
    {
        DB::transaction(function () use ($inventory) {

            $wipFin = Warehouse::where('code', 'WIP-FIN')->first();
            $whPrd = Warehouse::where('code', 'WH-PRD')->first();

            if (!$wipFin || !$whPrd) {
                $this->error('Warehouse tidak ditemukan.');
                return;
            }

            $stocks = InventoryStock::where('warehouse_id', $wipFin->id)
                ->where('qty', '>', 0)
                ->get();

            foreach ($stocks as $stock) {

                $inventory->move(
                    $stock->item_id,
                    $wipFin->id,
                    $whPrd->id,
                    $stock->qty,
                    'system_adjustment_wipfin_to_prd',
                    null,
                    'Migrasi WIP-FIN ke WH-PRD (Perubahan Alur)',
                    now(),
                    false,
                    null
                );

                $this->info("Moved item {$stock->item_id} qty {$stock->qty}");
            }

        });

        $this->info('Selesai.');
    }
}
