<?php

namespace App\Console\Commands;

use App\Models\CuttingJobBundle;
use App\Models\SewingPickupLine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairPickedQty extends Command
{
    protected $signature = 'pickup:repair-picked-qty
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}
                            {--bundle= : Hanya perbaiki satu bundle ID}';

    protected $description = 'Recalculate sewing_picked_qty = SUM(sewing_pickup_lines.qty_bundle) '
        . 'untuk semua bundle (exclude void lines). Cap by qty_pcs.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyBundleId = $this->option('bundle') ? (int) $this->option('bundle') : null;

        if ($dryRun) {
            $this->warn('[DRY RUN] Tidak ada data yang akan disimpan.');
        }

        // Ambil semua bundle yang punya pickup lines ATAU sewing_picked_qty > 0
        $query = CuttingJobBundle::query()
            ->select(['id', 'bundle_code', 'qty_pcs', 'sewing_picked_qty']);

        if ($onlyBundleId) {
            $query->where('id', $onlyBundleId);
        }

        $bundles = $query->get();

        $this->info("Total bundle diperiksa: {$bundles->count()}");

        $fixed    = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($bundles as $bundle) {
            // Hitung SUM dari pickup lines aktif (non-void)
            $correctSum = (float) SewingPickupLine::query()
                ->where('cutting_job_bundle_id', $bundle->id)
                ->where('status', '!=', 'void')
                ->sum('qty_bundle');

            $qtyPcs = (float) ($bundle->qty_pcs ?? 0);

            // Invariant: tidak boleh melebihi qty_pcs
            if ($correctSum > $qtyPcs) {
                $this->warn(
                    "⚠ Bundle #{$bundle->id} [{$bundle->bundle_code}]: "
                    . "SUM={$correctSum} > qty_pcs={$qtyPcs}. "
                    . "Pickup lines melebihi qty_pcs — perlu investigasi manual!"
                );
                $errors[] = $bundle->id;
                // Cap tetap diterapkan agar tidak melebihi qty_pcs
                $correctSum = $qtyPcs;
            }

            $current = (float) ($bundle->sewing_picked_qty ?? 0);

            if (abs($current - $correctSum) < 0.000001) {
                $skipped++;
                continue;
            }

            $this->line(
                "Bundle #{$bundle->id} [{$bundle->bundle_code}]: "
                . "sewing_picked_qty {$current} → {$correctSum}"
            );

            if (!$dryRun) {
                DB::table('cutting_job_bundles')
                    ->where('id', $bundle->id)
                    ->update(['sewing_picked_qty' => $correctSum]);
            }

            $fixed++;
        }

        $this->newLine();
        $this->info("Selesai. Diperbaiki: {$fixed}, Tidak berubah: {$skipped}.");

        if (count($errors) > 0) {
            $this->error(
                count($errors) . ' bundle melebihi qty_pcs — perlu investigasi: '
                . implode(', ', $errors)
            );
        }

        // === POST-REPAIR CHECKS ===
        $this->newLine();
        $this->info('--- Post-repair checks ---');

        if (!$dryRun) {
            // Check 1: tidak ada sewing_picked_qty > qty_pcs
            $overPicked = CuttingJobBundle::query()
                ->whereRaw('COALESCE(sewing_picked_qty, 0) > COALESCE(qty_pcs, 0) + 0.000001')
                ->when($onlyBundleId, fn($q) => $q->where('id', $onlyBundleId))
                ->count();

            if ($overPicked > 0) {
                $this->error("[FAIL] Check 1: {$overPicked} bundle masih punya sewing_picked_qty > qty_pcs!");
            } else {
                $this->info('[OK] Check 1: Tidak ada sewing_picked_qty > qty_pcs.');
            }

            // Check 2: tidak ada pickup line tanpa cutting_job_bundle_id
            $noBundle = SewingPickupLine::query()
                ->whereNull('cutting_job_bundle_id')
                ->where('status', '!=', 'void')
                ->count();

            if ($noBundle > 0) {
                $this->error("[FAIL] Check 2: {$noBundle} pickup line aktif tanpa cutting_job_bundle_id!");
            } else {
                $this->info('[OK] Check 2: Semua pickup line aktif punya cutting_job_bundle_id.');
            }
        } else {
            $this->comment('(Post-repair checks dilewati pada dry-run)');
        }

        return self::SUCCESS;
    }
}
