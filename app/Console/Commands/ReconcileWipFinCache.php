<?php

namespace App\Console\Commands;

use App\Models\CuttingJobBundle;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rekonsiliasi cache WIP-FIN (kolom bundle `wip_qty`) terhadap LEDGER.
 *
 * MASALAH:
 *   - Ledger WIP-FIN (inventory_stocks) adalah sumber kebenaran kuantitas.
 *   - Cache `wip_qty` di bundle (untuk daftar "Siap Finishing") bisa ketinggalan
 *     TINGGI: ledger sudah 0 (barang habis difinishing/adjust) tapi `wip_qty`
 *     masih menyimpan angka lama → muncul stok HANTU di daftar siap-finishing.
 *
 * KOREKSI (murni cache, NOL sentuh ledger & costing):
 *   - Per item, ambil saldo ledger WIP-FIN sebagai pagu.
 *   - Alokasikan pagu itu ke bundle secara FIFO (id terkecil dulu).
 *   - Bundle yang tidak kebagian backing ledger → `wip_qty` diturunkan
 *     (bisa sampai 0). Bundle paling awal tetap dipertahankan selama
 *     ledger masih cukup.
 *   - Ledger (inventory_mutations) TIDAK disentuh sama sekali → HPP/jurnal aman.
 *
 * DEFAULT = DRY-RUN (preview). Tambahkan --apply untuk benar-benar menulis.
 */
class ReconcileWipFinCache extends Command
{
    protected $signature = 'inventory:reconcile-wipfin {--apply : Benar-benar tulis perubahan (default: dry-run)}';

    protected $description = 'Preview/koreksi cache wip_qty WIP-FIN agar cocok dengan ledger (hapus stok hantu). Default dry-run.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $eps = 0.0001;

        $wipFin = Warehouse::where('code', 'WIP-FIN')->first();
        if (!$wipFin) {
            $this->error('Gudang WIP-FIN tidak ditemukan. Batal.');
            return self::FAILURE;
        }

        $this->info($apply ? '>>> MODE APPLY (akan menulis perubahan cache)' : '>>> MODE DRY-RUN (preview saja, tidak menulis)');
        $this->line("Gudang WIP-FIN id = {$wipFin->id}");
        $this->line('Ledger (inventory_mutations) TIDAK akan disentuh — costing/HPP/jurnal aman.');
        $this->newLine();

        // Saldo ledger WIP-FIN per item (sumber kebenaran).
        $ledgerByItem = DB::table('inventory_stocks')
            ->where('warehouse_id', $wipFin->id)
            ->pluck('qty', 'item_id'); // [item_id => qty]

        // Bundle yang cache-nya bilang masih di WIP-FIN dengan wip_qty > 0.
        $bundles = CuttingJobBundle::where('wip_warehouse_id', $wipFin->id)
            ->where('wip_qty', '>', 0)
            ->orderBy('finished_item_id')
            ->orderBy('id') // FIFO: bundle paling awal dapat alokasi ledger lebih dulu
            ->get();

        if ($bundles->isEmpty()) {
            $this->info('Tidak ada bundle ber-cache WIP-FIN. Bersih.');
            return self::SUCCESS;
        }

        $byItem = $bundles->groupBy('finished_item_id');

        $rows = [];
        $plans = [];        // [bundle_id => new_wip_qty]
        $itemSummary = [];  // [item_id => ['ledger'=>, 'cache'=>, 'phantom'=>]]

        foreach ($byItem as $itemId => $itemBundles) {
            $ledger = (float) ($ledgerByItem[$itemId] ?? 0);
            $cacheSum = (float) $itemBundles->sum('wip_qty');

            $itemSummary[$itemId] = [
                'ledger' => $ledger,
                'cache' => $cacheSum,
                'phantom' => max($cacheSum - $ledger, 0),
            ];

            // Kalau cache <= ledger → tidak ada stok hantu, lewati.
            if ($cacheSum <= $ledger + $eps) {
                continue;
            }

            // Alokasi FIFO: pagu = ledger; bundle id terkecil dapat dulu.
            $remaining = $ledger;
            foreach ($itemBundles as $b) {
                $cur = (float) $b->wip_qty;
                $keep = max(min($cur, $remaining), 0.0);
                $remaining = max($remaining - $keep, 0.0);

                if (abs($keep - $cur) <= $eps) {
                    continue; // bundle ini tidak berubah
                }

                $rows[] = [
                    $itemId,
                    $b->id,
                    $b->bundle_code ?? ('#' . $b->id),
                    rtrim(rtrim(number_format($cur, 3), '0'), '.'),
                    rtrim(rtrim(number_format($keep, 3), '0'), '.'),
                    '-' . rtrim(rtrim(number_format($cur - $keep, 3), '0'), '.'),
                ];

                $plans[$b->id] = $keep;
            }
        }

        // Ringkasan per item (yang ada hantunya).
        $sumRows = [];
        $totalPhantom = 0.0;
        foreach ($itemSummary as $itemId => $s) {
            if ($s['phantom'] <= $eps) {
                continue;
            }
            $totalPhantom += $s['phantom'];
            $sumRows[] = [
                $itemId,
                rtrim(rtrim(number_format($s['ledger'], 3), '0'), '.'),
                rtrim(rtrim(number_format($s['cache'], 3), '0'), '.'),
                rtrim(rtrim(number_format($s['phantom'], 3), '0'), '.'),
            ];
        }

        if (empty($sumRows)) {
            $this->info('Tidak ada stok hantu di WIP-FIN. Cache cocok dengan ledger.');
            return self::SUCCESS;
        }

        $this->line('<fg=cyan>Ringkasan per item (cache > ledger = hantu):</>');
        $this->table(['Item', 'Ledger WIP-FIN', 'Cache wip_qty', 'Hantu (akan dihapus)'], $sumRows);
        $this->newLine();

        $this->line('<fg=cyan>Detail bundle yang wip_qty-nya akan diturunkan:</>');
        $this->table(['Item', 'Bundle ID', 'Kode', 'wip_qty (lama)', 'wip_qty (baru)', 'Delta'], $rows);

        $this->newLine();
        $this->line('Total stok hantu yang akan dihapus dari daftar Siap Finishing: <fg=yellow>'
            . rtrim(rtrim(number_format($totalPhantom, 3), '0'), '.') . '</> pcs ('
            . count($plans) . ' bundle).');
        $this->newLine();

        if (!$apply) {
            $this->warn('Ini hanya PREVIEW. Untuk eksekusi:');
            $this->line('   php artisan inventory:reconcile-wipfin --apply');
            return self::SUCCESS;
        }

        // ===== APPLY (cache only) =====
        DB::transaction(function () use ($plans) {
            foreach ($plans as $bundleId => $newQty) {
                CuttingJobBundle::where('id', $bundleId)->update(['wip_qty' => $newQty]);
            }
        });

        $this->info('✅ Selesai. ' . count($plans) . ' bundle disinkronkan ke ledger. Ledger & costing TIDAK disentuh.');
        return self::SUCCESS;
    }
}
