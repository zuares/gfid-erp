<?php

namespace App\Console\Commands;

use App\Models\CuttingJobBundle;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi pola "reject salah masuk WIP-CUT".
 *
 * Pola yang dideteksi (aman & spesifik):
 *   - Bundle punya qty_qc_reject > 0
 *   - TAPI tidak ada (atau kurang) entri ledger 'cutting_reject' di REJ-CUT
 *     yang menyebut bundle tsb → reject itu dulu ikut terposting ke WIP-CUT.
 *   - Akibatnya WIP-CUT kelebihan sebesar shortfall reject = sumber drift.
 *
 * Koreksi yang diusulkan: PINDAHKAN shortfall pcs dari WIP-CUT → REJ-CUT
 * (mereka memang barang reject). Ledger lain tidak disentuh.
 *
 * DEFAULT = DRY-RUN (preview). Tambahkan --apply untuk benar-benar memindah.
 */
class PreviewCutWipRejectFix extends Command
{
    protected $signature = 'inventory:fix-cutwip-reject {--apply : Benar-benar pindahkan stok (default: dry-run)}';

    protected $description = 'Preview/koreksi reject yang salah masuk WIP-CUT (pindah ke REJ-CUT). Default dry-run.';

    public function handle(InventoryService $inventory): int
    {
        $apply = (bool) $this->option('apply');

        $wipCut = Warehouse::where('code', 'WIP-CUT')->first();
        $rejCut = Warehouse::where('code', 'REJ-CUT')->first();
        if (!$wipCut || !$rejCut) {
            $this->error('Gudang WIP-CUT / REJ-CUT tidak ditemukan. Batal.');
            return self::FAILURE;
        }

        $this->info($apply ? '>>> MODE APPLY (akan memindah stok)' : '>>> MODE DRY-RUN (preview saja)');
        $this->line("WIP-CUT id={$wipCut->id} · REJ-CUT id={$rejCut->id}");
        $this->newLine();

        $eps = 0.0001;

        // Kandidat: bundle dengan reject > 0
        $bundles = CuttingJobBundle::with('cuttingJob')
            ->where('qty_qc_reject', '>', 0)
            ->orderBy('id')
            ->get();

        $rows = [];
        $plans = []; // detail untuk apply

        foreach ($bundles as $b) {
            $rejectQc = (float) ($b->qty_qc_reject ?? 0);
            $code = $b->bundle_code ?? ('#' . $b->id);

            // Berapa reject yang SUDAH benar tercatat di REJ-CUT untuk bundle ini?
            // Match by notes mengandung bundle_code (entri reject selalu sebut bundle_code).
            $rejPosted = (float) DB::table('inventory_mutations')
                ->where('warehouse_id', $rejCut->id)
                ->where('item_id', $b->finished_item_id)
                ->whereIn('source_type', ['cutting_reject', 'cutting_qc_adjust_in', 'cutting_qc_adjust_out'])
                ->where('notes', 'like', '%' . $code . '%')
                ->sum('qty_change');

            $shortfall = round($rejectQc - $rejPosted, 4);
            if ($shortfall <= $eps) {
                continue; // reject sudah benar tercatat → tidak ada masalah
            }

            // Stok WIP-CUT untuk item ini (tidak boleh negatif setelah dipindah)
            $wipBal = (float) DB::table('inventory_stocks')
                ->where('warehouse_id', $wipCut->id)
                ->where('item_id', $b->finished_item_id)
                ->value('qty');

            $move = min($shortfall, max($wipBal, 0));
            $note = $move < $shortfall ? 'CLAMPED (stok WIP-CUT < shortfall)' : 'ok';

            $rows[] = [
                $b->id,
                $code,
                $b->cuttingJob->code ?? '?',
                rtrim(rtrim(number_format($rejectQc, 3), '0'), '.'),
                rtrim(rtrim(number_format($rejPosted, 3), '0'), '.'),
                rtrim(rtrim(number_format($shortfall, 3), '0'), '.'),
                rtrim(rtrim(number_format($wipBal, 3), '0'), '.'),
                rtrim(rtrim(number_format($move, 3), '0'), '.'),
                $note,
            ];

            if ($move > $eps) {
                $plans[] = [
                    'bundle' => $b,
                    'item_id' => (int) $b->finished_item_id,
                    'job_id' => (int) $b->cutting_job_id,
                    'qty' => $move,
                    'code' => $code,
                ];
            }
        }

        if (empty($rows)) {
            $this->info('Tidak ada reject yang salah masuk WIP-CUT. Bersih.');
            return self::SUCCESS;
        }

        $this->table(
            ['Bundle', 'Kode', 'Job', 'Reject QC', 'Reject ke REJ-CUT', 'Shortfall', 'Stok WIP-CUT', 'Akan dipindah', 'Catatan'],
            $rows
        );

        $totalMove = array_sum(array_column($plans, 'qty'));
        $this->newLine();
        $this->line('Rencana: pindahkan total <fg=yellow>' . rtrim(rtrim(number_format($totalMove, 3), '0'), '.') . '</> pcs dari WIP-CUT → REJ-CUT.');
        $this->line('Ledger lain (cutting_wip, pickup, dll) TIDAK disentuh.');
        $this->newLine();

        if (!$apply) {
            $this->warn('Ini hanya PREVIEW. Untuk eksekusi:');
            $this->line('   php artisan inventory:fix-cutwip-reject --apply');
            return self::SUCCESS;
        }

        // ===== APPLY =====
        $date = now()->toDateString();
        DB::transaction(function () use ($plans, $inventory, $wipCut, $rejCut, $date) {
            foreach ($plans as $p) {
                $notes = "Koreksi reject salah masuk WIP-CUT → REJ-CUT (bundle {$p['code']})";
                $bundleId = isset($p['bundle']) && $p['bundle'] ? (int) $p['bundle']->id : null;
                $inventory->stockOut(
                    warehouseId: $wipCut->id,
                    itemId: $p['item_id'],
                    qty: $p['qty'],
                    date: $date,
                    sourceType: 'cutting_reject_correction',
                    sourceId: $p['job_id'],
                    notes: $notes,
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundleId,
                );
                $inventory->stockIn(
                    warehouseId: $rejCut->id,
                    itemId: $p['item_id'],
                    qty: $p['qty'],
                    date: $date,
                    sourceType: 'cutting_reject_correction',
                    sourceId: $p['job_id'],
                    notes: $notes,
                    lotId: null,
                    unitCost: null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundleId,
                );
            }
        });

        $this->info('✅ Selesai. ' . count($plans) . ' koreksi dipindahkan WIP-CUT → REJ-CUT.');
        return self::SUCCESS;
    }
}
