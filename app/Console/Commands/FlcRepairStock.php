<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlcRepairStock extends Command
{
    protected $signature = 'flc:repair-stock
                            {--dry-run : Preview perubahan tanpa menyimpan ke database}';

    protected $description = 'Repair stok FLC: ubah unit pcs→kg, bagi qty_onhand/1000 untuk lot x1000, recalculate total_cost.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('========================================');
        $this->info('  REPAIR STOK FLC — ' . now()->format('Y-m-d H:i:s'));
        if ($dryRun) {
            $this->warn('  MODE: DRY-RUN (tidak ada yang disimpan)');
        } else {
            $this->warn('  MODE: LIVE (data akan diubah!)');
        }
        $this->info('========================================');

        // ─── 0. Konfirmasi jika LIVE ─────────────────────────────────────
        if (!$dryRun) {
            if (!$this->confirm('Lanjutkan repair stok FLC? Pastikan backup sudah ada.', false)) {
                $this->warn('Dibatalkan.');
                return self::FAILURE;
            }
        }

        // ─── 1. Backup database SQLite ───────────────────────────────────
        $this->newLine();
        $this->info('【1】 BACKUP DATABASE');

        $dbPath = database_path('database_dev.sqlite');
        $backupDir = storage_path('app/backups/marketplace-sync-ux');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupFile = $backupDir . '/database_dev.BEFORE_FLC_REPAIR_' . now()->format('Ymd_His') . '.sqlite';

        if (!$dryRun) {
            if (!copy($dbPath, $backupFile)) {
                $this->error('GAGAL backup database. Repair dibatalkan.');
                return self::FAILURE;
            }
            $this->info("  ✓ Backup disimpan: {$backupFile}");
        } else {
            $this->comment("  [DRY-RUN] Backup akan ke: {$backupFile}");
        }

        // ─── 2. Update items.unit pcs → kg ──────────────────────────────
        $this->newLine();
        $this->info('【2】 UPDATE items.unit: pcs → kg untuk item FLC');

        $itemsToFix = DB::table('items')
            ->where('code', 'like', 'FLC%')
            ->where('unit', 'pcs')
            ->get(['id', 'code', 'name', 'unit']);

        if ($itemsToFix->isEmpty()) {
            $this->line('  Tidak ada item FLC dengan unit=pcs. Skip.');
        } else {
            $this->table(
                ['ID', 'Code', 'Name', 'Unit Sekarang', 'Unit Baru'],
                $itemsToFix->map(fn($r) => [$r->id, $r->code, $r->name, $r->unit, 'kg'])
            );
            $this->comment("  Total: {$itemsToFix->count()} item akan diubah unit → kg");

            if (!$dryRun) {
                $updatedItems = DB::table('items')
                    ->where('code', 'like', 'FLC%')
                    ->where('unit', 'pcs')
                    ->update(['unit' => 'kg', 'updated_at' => now()]);
                $this->info("  ✓ {$updatedItems} item diubah unit → kg");
            }
        }

        // ─── 3. Update lots qty_onhand / 1000 ───────────────────────────
        $this->newLine();
        $this->info('【3】 REPAIR lots.qty_onhand — bagi 1000 untuk lot x1000');
        $this->comment('  Kriteria: item FLC, qty_onhand > initial_qty * 100');
        $this->comment('  Lot normal (LOT-20260128-xxx) TIDAK disentuh.');

        $x1000Lots = DB::table('lots as l')
            ->join('items as i', 'i.id', '=', 'l.item_id')
            ->where('i.code', 'like', 'FLC%')
            ->whereRaw('l.qty_onhand > l.initial_qty * 100')
            ->orderBy('l.id')
            ->get([
                'l.id', 'l.code',
                'i.code as item_code',
                'l.initial_qty',
                'l.qty_onhand',
                'l.avg_cost',
                'l.total_cost',
            ]);

        $this->comment("  Total lot x1000 ditemukan: {$x1000Lots->count()}");

        $repaired     = 0;
        $skipped      = 0;
        $costUpdated  = 0;
        $log          = [];

        foreach ($x1000Lots as $lot) {
            $oldQty     = (float) $lot->qty_onhand;
            $newQty     = round($oldQty / 1000.0, 6);
            $avgCost    = (float) $lot->avg_cost;
            $newCost    = $avgCost > 0 ? round($newQty * $avgCost, 2) : 0;

            $log[] = [
                $lot->id,
                $lot->code,
                $lot->item_code,
                number_format($lot->initial_qty, 4),
                number_format($oldQty, 3),
                number_format($newQty, 6),
                number_format($avgCost, 0),
                $avgCost > 0 ? number_format($newCost, 0) : '(tetap 0)',
            ];

            if (!$dryRun) {
                $updateData = [
                    'qty_onhand'  => $newQty,
                    'updated_at'  => now(),
                ];

                // update total_cost hanya kalau avg_cost > 0
                if ($avgCost > 0) {
                    $updateData['total_cost'] = $newCost;
                    $costUpdated++;
                }

                DB::table('lots')->where('id', $lot->id)->update($updateData);
            }

            $repaired++;
        }

        // Tampilkan log
        $this->table(
            ['Lot ID', 'Lot Code', 'Item', 'initial_qty', 'qty_onhand LAMA', 'qty_onhand BARU', 'avg_cost', 'total_cost BARU'],
            $log
        );

        if ($dryRun) {
            $this->comment("  [DRY-RUN] {$repaired} lot akan direpair, {$skipped} dilewati.");
        } else {
            $this->info("  ✓ {$repaired} lot direpair.");
            $this->info("  ✓ {$costUpdated} lot total_cost diupdate (avg_cost > 0).");
        }

        // ─── 4. Simpan audit log ─────────────────────────────────────────
        if (!$dryRun) {
            $this->newLine();
            $this->info('【4】 SIMPAN AUDIT LOG');
            $logFile = $backupDir . '/flc-repair-log-' . now()->format('Ymd_His') . '.json';
            file_put_contents($logFile, json_encode([
                'timestamp'          => now()->toIso8601String(),
                'mode'               => 'live',
                'items_unit_updated' => $itemsToFix->count(),
                'lots_repaired'      => $repaired,
                'lots_cost_updated'  => $costUpdated,
                'backup_file'        => $backupFile,
                'detail_lots'        => $x1000Lots->map(fn($l) => [
                    'lot_id'         => $l->id,
                    'lot_code'       => $l->code,
                    'item_code'      => $l->item_code,
                    'initial_qty'    => $l->initial_qty,
                    'qty_onhand_old' => $l->qty_onhand,
                    'qty_onhand_new' => round((float)$l->qty_onhand / 1000.0, 6),
                    'avg_cost'       => $l->avg_cost,
                ])->toArray(),
            ], JSON_PRETTY_PRINT));
            $this->info("  ✓ Log disimpan: {$logFile}");
        }

        // ─── 5. Selesai ──────────────────────────────────────────────────
        $this->newLine();
        if ($dryRun) {
            $this->warn('DRY-RUN selesai. Tidak ada data yang diubah.');
            $this->line('Jalankan <info>php artisan flc:repair-stock</info> (tanpa --dry-run) untuk eksekusi.');
        } else {
            $this->info('Repair selesai!');
            $this->line('Selanjutnya jalankan:');
            $this->line('  php artisan optimize:clear');
            $this->line('  php artisan flc:verify-stock');
        }

        return self::SUCCESS;
    }
}
