<?php

namespace App\Console\Commands;

use App\Models\CuttingJobBundle;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill kolom cutting-WIP yang baru (cut_wip_warehouse_id, cut_wip_qty)
 * dari data yang sudah ada.
 *
 * Tujuan: mengembalikan sisa cutting yang "nyangkut" agar muncul lagi di
 * halaman Ambil Jahit, TANPA menyentuh ledger (inventory_mutations).
 *
 * Sumber kebenaran cutting-WIP:
 *   - cut_wip_qty           = qty_cutting_ok (hasil QC Cutting yang otoritatif)
 *   - cut_wip_warehouse_id  = gudang WIP-CUT
 * untuk setiap bundle yang sudah pernah posting WIP (wip_posted_at terisi).
 *
 * DEFAULT = DRY RUN (preview saja). Tambahkan --apply untuk benar-benar menulis.
 */
class BackfillCutWip extends Command
{
    protected $signature = 'bundles:backfill-cut-wip {--apply : Benar-benar tulis perubahan (default: dry-run)}';

    protected $description = 'Isi kolom cut_wip_warehouse_id / cut_wip_qty dari data cutting yang ada (default dry-run)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $wipCut = Warehouse::where('code', 'WIP-CUT')->first();
        if (!$wipCut) {
            $this->error('Gudang WIP-CUT tidak ditemukan. Batal.');
            return self::FAILURE;
        }

        $this->info($apply ? '>>> MODE APPLY (akan menulis perubahan)' : '>>> MODE DRY-RUN (preview saja, tidak menulis)');
        $this->line("Gudang WIP-CUT id = {$wipCut->id}");
        $this->newLine();

        // Hanya bundle yang pernah posting WIP cutting (wip_posted_at terisi).
        $bundles = CuttingJobBundle::with(['qcResults', 'cutWipWarehouse'])
            ->whereNotNull('wip_posted_at')
            ->orderBy('id')
            ->get();

        $rows = [];
        $changeCount = 0;
        $itemDelta = []; // [item_id => net ready bertambah]

        foreach ($bundles as $b) {
            $targetQty = (float) $b->qty_cutting_ok; // accessor: qty_qc_ok → QC cutting → qty_pcs
            $targetWh = (int) $wipCut->id;

            $curQty = (float) ($b->cut_wip_qty ?? 0);
            $curWh = $b->cut_wip_warehouse_id !== null ? (int) $b->cut_wip_warehouse_id : null;

            $needsQty = abs($curQty - $targetQty) > 0.0001;
            $needsWh = $curWh !== $targetWh;

            if (!$needsQty && !$needsWh) {
                continue;
            }

            $picked = (float) ($b->sewing_picked_qty ?? 0);
            $oldNet = max($curQty - $picked, 0.0);
            $newNet = max($targetQty - $picked, 0.0);

            $rows[] = [
                $b->id,
                $b->bundle_code ?? ('#' . $b->id),
                $curWh === null ? '(null)' : $curWh,
                $targetWh,
                rtrim(rtrim(number_format($curQty, 3), '0'), '.'),
                rtrim(rtrim(number_format($targetQty, 3), '0'), '.'),
                rtrim(rtrim(number_format($oldNet, 3), '0'), '.') . ' → ' . rtrim(rtrim(number_format($newNet, 3), '0'), '.'),
            ];

            $itemDelta[$b->finished_item_id] = ($itemDelta[$b->finished_item_id] ?? 0) + ($newNet - $oldNet);
            $changeCount++;

            if ($apply) {
                $b->cut_wip_warehouse_id = $targetWh;
                $b->cut_wip_qty = $targetQty;
                $b->save();
            }
        }

        if ($changeCount === 0) {
            $this->info('Tidak ada bundle yang perlu di-backfill. Semua sudah konsisten.');
            return self::SUCCESS;
        }

        $this->table(
            ['Bundle ID', 'Kode', 'cut_wh (lama)', 'cut_wh (baru)', 'cut_qty (lama)', 'cut_qty (baru)', 'Sisa ready'],
            $rows
        );

        $this->newLine();
        $this->line("Total bundle berubah: <fg=yellow>{$changeCount}</>");
        $this->line('Perubahan sisa "ready jahit" per item (item_id => delta):');
        foreach ($itemDelta as $itemId => $delta) {
            if (abs($delta) < 0.0001) {
                continue;
            }
            $sign = $delta > 0 ? '+' : '';
            $this->line("  item #{$itemId}: {$sign}" . rtrim(rtrim(number_format($delta, 3), '0'), '.') . ' pcs');
        }

        $this->newLine();
        if ($apply) {
            $this->info('✅ Perubahan sudah ditulis. Ledger (inventory_mutations) TIDAK disentuh.');
        } else {
            $this->warn('Ini hanya PREVIEW. Jalankan ulang dengan --apply untuk menulis perubahan:');
            $this->line('   php artisan bundles:backfill-cut-wip --apply');
        }

        return self::SUCCESS;
    }
}
