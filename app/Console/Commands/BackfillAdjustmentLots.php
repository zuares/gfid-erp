<?php

namespace App\Console\Commands;

use App\Helpers\CodeGenerator;
use App\Models\ItemRole;
use App\Models\Lot;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill LOT untuk stok kain (RM) hasil KOREKSI (stock opname / inventory
 * adjustment) yang mutasinya ter-catat dengan lot_id = NULL.
 *
 * Latar belakang:
 *   Halaman Cutting Job Create hanya menampilkan saldo kain yang menempel pada
 *   sebuah LOT (getAvailableLots => whereNotNull('lot_id')). Koreksi stok lama
 *   membuat mutasi lot_id = NULL, sehingga barang hasil koreksi TIDAK MUNCUL di
 *   halaman create cutting meskipun on-hand-nya benar.
 *
 * Perbaikan kode sudah dipasang di InventoryAdjustmentController (koreksi baru
 * otomatis bikin LOT). Command ini untuk membereskan DATA LAMA: reattach mutasi
 * lot_id NULL milik item RM ke sebuah LOT baru — tanpa mengubah saldo apa pun.
 *
 * Contoh:
 *   php artisan inventory:backfill-adjustment-lots --dry-run
 *   php artisan inventory:backfill-adjustment-lots
 *   php artisan inventory:backfill-adjustment-lots --warehouse=RM --item=KAIN-01
 */
class BackfillAdjustmentLots extends Command
{
    protected $signature = 'inventory:backfill-adjustment-lots
        {--warehouse=RM : Kode gudang yang diproses (default RM)}
        {--item= : (Opsional) batasi ke satu kode item saja}
        {--dry-run : Lihat preview tanpa mengubah data}';

    protected $description = 'Reattach mutasi stok koreksi (lot_id NULL) milik item RM ke LOT baru supaya muncul di Cutting Job Create.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $whCode = strtoupper(trim((string) $this->option('warehouse')));
        $itemCode = trim((string) $this->option('item'));

        $warehouse = Warehouse::where('code', $whCode)->first();
        if (!$warehouse) {
            $this->error("Gudang dengan code '{$whCode}' tidak ditemukan.");
            return self::FAILURE;
        }

        // Mutasi lot_id NULL, dikelompokkan per item, untuk item RM saja.
        $rows = DB::table('inventory_mutations as m')
            ->join('items as i', 'i.id', '=', 'm.item_id')
            ->where('m.warehouse_id', $warehouse->id)
            ->whereNull('m.lot_id')
            ->where('i.role_code', ItemRole::RM)
            ->when($itemCode !== '', fn($q) => $q->where('i.code', $itemCode))
            ->groupBy('m.item_id', 'i.code', 'i.name')
            ->select([
                'm.item_id',
                'i.code as item_code',
                'i.name as item_name',
                DB::raw('SUM(m.qty_change) as net_qty'),
                DB::raw('SUM(COALESCE(m.total_cost, 0)) as net_cost'),
                DB::raw('COUNT(*) as mutation_count'),
            ])
            ->havingRaw('SUM(m.qty_change) > 0.000001')
            ->get();

        if ($rows->isEmpty()) {
            $this->info("✅ Tidak ada stok koreksi RM lot_id NULL yang perlu diperbaiki di gudang {$whCode}.");
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$rows->count()} item RM dengan stok koreksi tak ber-LOT di gudang {$whCode}:");
        $this->table(
            ['Item', 'Nama', 'Net Qty', 'Jml Mutasi', 'LOT Baru'],
            $rows->map(fn($r) => [
                $r->item_code,
                \Illuminate\Support\Str::limit($r->item_name, 30),
                rtrim(rtrim(number_format((float) $r->net_qty, 3, '.', ''), '0'), '.'),
                $r->mutation_count,
                $dryRun ? '(dry-run)' : 'akan dibuat',
            ])->all()
        );

        if ($dryRun) {
            $this->warn('[DRY-RUN] Tidak ada perubahan disimpan. Jalankan tanpa --dry-run untuk menerapkan.');
            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($rows as $r) {
            DB::transaction(function () use ($r, $warehouse, &$fixed) {
                $netQty = (float) $r->net_qty;
                $netCost = (float) $r->net_cost;
                $avgCost = $netQty > 0 ? round($netCost / $netQty, 4) : 0;

                $lot = Lot::create([
                    'code'         => CodeGenerator::generate('LOT'),
                    'item_id'      => (int) $r->item_id,
                    'initial_qty'  => $netQty,
                    'initial_cost' => $avgCost,
                    'qty_onhand'   => $netQty,
                    'total_cost'   => $netCost,
                    'avg_cost'     => $avgCost,
                    'status'       => 'open',
                ]);

                // Reattach SEMUA mutasi lot_id NULL item ini di gudang ini ke LOT baru.
                // Saldo tidak berubah — hanya kolom lot_id yang diisi.
                DB::table('inventory_mutations')
                    ->where('warehouse_id', $warehouse->id)
                    ->where('item_id', (int) $r->item_id)
                    ->whereNull('lot_id')
                    ->update(['lot_id' => $lot->id]);

                $this->line("  ✔ {$r->item_code} → LOT {$lot->code} (qty {$netQty})");
                $fixed++;
            });
        }

        $this->info("✅ Selesai. {$fixed} item diperbaiki. Coba buka lagi halaman Cutting Job Create.");
        return self::SUCCESS;
    }
}
