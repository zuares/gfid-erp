<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi lot yang salah akibat bug num() membaca "25.000" (decimal:3 cast) sebagai 25000.
 *
 * Lot 142 (LOT-20260615-004) dibuat dari PO approval — qty seharusnya 25 kg @ 54.000,
 * tapi addReceipt() menambahkan 25 ke atas 25000 (salah baca), sehingga qty_onhand=25025.
 *
 * Data yang benar: qty_onhand=25, avg_cost=54000, total_cost=1350000.
 * inventory_stocks dan inventory_mutations sudah benar (tidak perlu diubah).
 */
class FixLot142 extends Command
{
    protected $signature = 'fix:lot142 {--dry-run : Lihat preview tanpa mengubah data}';

    protected $description = 'Koreksi lot 142 (LOT-20260615-004) yang qty_onhand-nya salah akibat bug num() decimal cast.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $lot = DB::table('lots')->where('id', 142)->first();

        if (!$lot) {
            $this->error('Lot ID 142 tidak ditemukan.');
            return self::FAILURE;
        }

        $this->info('=== CEK LOT 142 ===');
        $this->table(
            ['Field', 'Nilai Sekarang', 'Nilai Benar'],
            [
                ['code',       $lot->code,       $lot->code],
                ['initial_qty', $lot->initial_qty, $lot->initial_qty],
                ['qty_onhand',  $lot->qty_onhand,  '25'],
                ['avg_cost',    $lot->avg_cost,    '54000'],
                ['total_cost',  $lot->total_cost,  '1350000'],
            ]
        );

        // Cek apakah memang salah
        $needsFix = (float)$lot->qty_onhand != 25.0
            || round((float)$lot->avg_cost, 0) != 54000
            || round((float)$lot->total_cost, 0) != 1350000;

        if (!$needsFix) {
            $this->info('✅ Data lot 142 sudah benar. Tidak perlu diubah.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('[DRY-RUN] Tidak ada perubahan disimpan.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Lanjutkan koreksi lot 142?', true)) {
            $this->warn('Dibatalkan.');
            return self::FAILURE;
        }

        DB::table('lots')->where('id', 142)->update([
            'qty_onhand'  => 25,
            'avg_cost'    => 54000,
            'total_cost'  => 1350000,
            'updated_at'  => now(),
        ]);

        $after = DB::table('lots')->where('id', 142)->first();
        $this->info('✅ Lot 142 berhasil dikoreksi:');
        $this->line("  qty_onhand = {$after->qty_onhand}");
        $this->line("  avg_cost   = {$after->avg_cost}");
        $this->line("  total_cost = {$after->total_cost}");

        // Verifikasi inventory_stocks masih benar
        $stock = DB::table('inventory_stocks')
            ->where('warehouse_id', 2)
            ->where('item_id', 112)
            ->first();
        $this->newLine();
        $this->info("Cek inventory_stocks (gudang RM, item FLC240ABT): qty = {$stock?->qty}");

        return self::SUCCESS;
    }
}
