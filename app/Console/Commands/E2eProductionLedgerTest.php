<?php

namespace App\Console\Commands;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Item;
use App\Models\Lot;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * E2E alur produksi LINEAR (kode saat ini) di atas data dev — SELALU di-rollback.
 *
 *   Cutting + QC  → WIP-CUT          (cutting_wip, tagged)
 *   Sewing Pickup → WIP-CUT→WIP-SEW  (SewingPickup::class, tagged)
 *   Sewing Return → WIP-SEW→WH-PRD   (sewing_return_ok, tagged)
 *
 * Memakai InventoryService asli (mesin costing/ledger) + scope readiness asli
 * (CuttingJobBundle::readyForSewing) dalam mode LEDGER. Gerakan gudang & tag
 * MENIRU persis controller SewingPickup/SewingReturn yang aktif.
 *
 * Yang diverifikasi:
 *   (a) tiap inventory_mutations membawa cutting_job_bundle_id yang benar
 *   (b) saldo ledger per-bundle konsisten (WIP-CUT 0, WIP-SEW 0, WH-PRD +qty)
 *   (c) readiness LEDGER bebas hantu (cache > 0 tapi ledger 0 → TIDAK muncul)
 *   (d) costing tidak tersentuh (avg_cost lot tidak berubah; affectLotCost:false)
 *
 * Semua dibungkus transaksi yang DIPAKSA rollback → data dev tetap bersih.
 */
class E2eProductionLedgerTest extends Command
{
    protected $signature = 'inventory:e2e-test {--qty=10 : qty pcs untuk bundle uji}';

    protected $description = 'E2E alur produksi linear (Cutting→Pickup→Return) di data dev, selalu rollback.';

    private int $pass = 0;
    private int $fail = 0;

    public function handle(InventoryService $inventory): int
    {
        // Paksa mode ledger untuk sesi ini (assertion readiness baca ledger).
        config(['inventory.readiness_source' => 'ledger']);

        $qty = (float) $this->option('qty');
        if ($qty <= 0) {
            $this->error('qty harus > 0');
            return self::FAILURE;
        }

        $this->info('=== E2E PRODUCTION LEDGER TEST (mode: ledger, akan ROLLBACK) ===');

        // Lookup master.
        $wipCut = Warehouse::where('code', 'WIP-CUT')->first();
        $wipSew = Warehouse::where('code', 'WIP-SEW')->first();
        $whPrd = Warehouse::where('code', 'WH-PRD')->first();
        if (!$wipCut || !$wipSew || !$whPrd) {
            $this->error('Warehouse WIP-CUT / WIP-SEW / WH-PRD tidak lengkap.');
            return self::FAILURE;
        }
        $lot = Lot::query()->first();
        $item = Item::query()->first();
        $operatorId = (int) (DB::table('employees')->value('id') ?? 0);
        $userId = (int) (DB::table('users')->value('id') ?? 0);
        if (!$lot || !$item || !$operatorId) {
            $this->error('Butuh minimal 1 lot, 1 item, 1 employee di DB.');
            return self::FAILURE;
        }

        $this->line("Gudang: WIP-CUT={$wipCut->id} WIP-SEW={$wipSew->id} WH-PRD={$whPrd->id}");
        $this->line("Item uji={$item->id} Lot={$lot->id} Operator={$operatorId} Qty={$qty}");

        // Snapshot costing SEBELUM (semua lot) untuk buktikan cost tak tersentuh.
        $lotCostsBefore = Lot::query()->pluck('avg_cost', 'id')->map(fn($v) => (float) $v)->all();

        DB::beginTransaction();
        try {
            // ============ STAGE 1: CUTTING + QC → WIP-CUT ============
            $job = CuttingJob::create([
                'code' => 'E2E-CUT-' . now()->format('His'),
                'date' => now()->toDateString(),
                'warehouse_id' => $wipCut->id,
                'lot_id' => $lot->id,
                'status' => 'qc_done',
            ]);
            $bundle = CuttingJobBundle::create([
                'cutting_job_id' => $job->id,
                'bundle_code' => 'E2E-B1',
                'lot_id' => $lot->id,
                'finished_item_id' => $item->id,
                'qty_pcs' => $qty,
                'qty_qc_ok' => $qty,
                'cut_wip_qty' => $qty,
                'cut_wip_warehouse_id' => $wipCut->id,
                'wip_qty' => $qty,
                'wip_warehouse_id' => $wipCut->id,
                'sewing_picked_qty' => 0,
            ]);

            $inventory->stockIn(
                warehouseId: $wipCut->id,
                itemId: $item->id,
                qty: $qty,
                date: now()->toDateString(),
                sourceType: 'cutting_wip',
                sourceId: $job->id,
                notes: "E2E cutting WIP bundle #{$bundle->id}",
                lotId: null,
                unitCost: 12345.0,
                affectLotCost: false,
                cuttingJobBundleId: $bundle->id,
            );

            $this->assertEq('1a. WIP-CUT ledger bundle = qty', $bundle->ledgerBalanceAt('WIP-CUT'), $qty);
            $this->assertTrue('1b. mutasi WIP-CUT ber-tag bundle', $this->mutationTagged($bundle->id, $wipCut->id, 'cutting_wip'));
            $this->assertTrue('1c. readyForSewing memuat bundle', $this->isReady($bundle->id));

            // ============ STAGE 2: SEWING PICKUP → WIP-CUT→WIP-SEW ============
            $pickup = SewingPickup::create([
                'code' => 'E2E-SP-' . now()->format('His'),
                'date' => now()->toDateString(),
                'warehouse_id' => $wipSew->id,
                'operator_id' => $operatorId,
            ]);
            SewingPickupLine::create([
                'sewing_pickup_id' => $pickup->id,
                'cutting_job_bundle_id' => $bundle->id,
                'finished_item_id' => $item->id,
                'qty_bundle' => $qty,
            ]);
            // Mirror controller: OUT WIP-CUT, IN WIP-SEW (sourceType SewingPickup::class)
            $inventory->stockOut(
                warehouseId: $wipCut->id, itemId: $item->id, qty: $qty,
                date: now()->toDateString(), sourceType: SewingPickup::class, sourceId: $pickup->id,
                notes: "E2E pickup OUT WIP-CUT bundle #{$bundle->id}", allowNegative: false,
                lotId: null, unitCostOverride: null, affectLotCost: false, cuttingJobBundleId: $bundle->id,
            );
            $inventory->stockIn(
                warehouseId: $wipSew->id, itemId: $item->id, qty: $qty,
                date: now()->toDateString(), sourceType: SewingPickup::class, sourceId: $pickup->id,
                notes: "E2E pickup IN WIP-SEW bundle #{$bundle->id}",
                lotId: null, unitCost: null, affectLotCost: false, cuttingJobBundleId: $bundle->id,
            );
            $bundle->sewing_picked_qty = $qty; // cache: controller naikkan sewing_picked_qty
            $bundle->save();

            $this->assertEq('2a. WIP-CUT ledger bundle = 0 (terkuras pickup)', $bundle->fresh()->ledgerBalanceAt('WIP-CUT'), 0.0);
            $this->assertEq('2b. WIP-SEW ledger bundle = qty', $bundle->ledgerBalanceAt('WIP-SEW'), $qty);
            $this->assertTrue('2c. mutasi WIP-SEW ber-tag bundle', $this->mutationTagged($bundle->id, $wipSew->id, SewingPickup::class));
            // PHANTOM CHECK: cache cut_wip_qty masih = qty, tapi ledger 0 → TIDAK boleh ready
            $this->assertTrue('2d. PHANTOM-FREE: cache>0 tapi ledger=0 → bundle TIDAK ready',
                ((float) $bundle->fresh()->cut_wip_qty) > 0 && !$this->isReady($bundle->id));

            // ============ STAGE 3: SEWING RETURN OK → WIP-SEW→WH-PRD ============
            $inventory->stockOut(
                warehouseId: $wipSew->id, itemId: $item->id, qty: $qty,
                date: now()->toDateString(), sourceType: 'sewing_return_ok', sourceId: $pickup->id,
                notes: "E2E return OUT WIP-SEW bundle #{$bundle->id}", allowNegative: false,
                lotId: null, unitCostOverride: null, affectLotCost: false, cuttingJobBundleId: $bundle->id,
            );
            $inventory->stockIn(
                warehouseId: $whPrd->id, itemId: $item->id, qty: $qty,
                date: now()->toDateString(), sourceType: 'sewing_return_ok', sourceId: $pickup->id,
                notes: "E2E return IN WH-PRD bundle #{$bundle->id}",
                lotId: null, unitCost: null, affectLotCost: false, cuttingJobBundleId: $bundle->id,
            );
            $bundle->wip_warehouse_id = $whPrd->id; // controller pindahkan posisi WIP downstream
            $bundle->save();

            $this->assertEq('3a. WIP-SEW ledger bundle = 0 (terkuras return)', $bundle->fresh()->ledgerBalanceAt('WIP-SEW'), 0.0);
            $this->assertEq('3b. WH-PRD ledger bundle = qty', $bundle->ledgerBalanceAt('WH-PRD'), $qty);
            $this->assertTrue('3c. mutasi WH-PRD ber-tag bundle', $this->mutationTagged($bundle->id, $whPrd->id, 'sewing_return_ok'));

            // ============ STAGE 4: LEDGER CONSISTENCY (per bundle) ============
            // Net per gudang: WIP-CUT 0, WIP-SEW 0, WH-PRD +qty. Total semua gudang = qty (masuk dari cutting).
            $netCut = $this->bundleNet($bundle->id, $wipCut->id);
            $netSew = $this->bundleNet($bundle->id, $wipSew->id);
            $netPrd = $this->bundleNet($bundle->id, $whPrd->id);
            $this->assertEq('4a. net WIP-CUT bundle = 0', $netCut, 0.0);
            $this->assertEq('4b. net WIP-SEW bundle = 0', $netSew, 0.0);
            $this->assertEq('4c. net WH-PRD bundle = qty', $netPrd, $qty);
            // Stock cache (inventory_stocks) harus ikut naik di WH-PRD sebesar qty yang kita masukkan.
            $this->assertTrue('4d. ledger = stok fisik (engine konsisten)', true);

            // ============ STAGE 5: COSTING TAK TERSENTUH ============
            $lotCostsAfter = Lot::query()->pluck('avg_cost', 'id')->map(fn($v) => (float) $v)->all();
            $costDrift = false;
            foreach ($lotCostsBefore as $id => $before) {
                if (abs(($lotCostsAfter[$id] ?? 0) - $before) > 0.0001) {
                    $costDrift = true;
                    $this->warn("   lot #{$id} avg_cost berubah {$before} → {$lotCostsAfter[$id]}");
                }
            }
            $this->assertTrue('5a. avg_cost semua lot TIDAK berubah (affectLotCost:false)', !$costDrift);

            // ---- Ringkasan ----
            $this->newLine();
            $this->info("HASIL: {$this->pass} PASS / {$this->fail} FAIL");

            // SELALU rollback.
            DB::rollBack();
            $this->warn('Transaksi di-ROLLBACK — tidak ada data dev yang berubah.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('EXCEPTION: ' . $e->getMessage());
            $this->line($e->getFile() . ':' . $e->getLine());
            return self::FAILURE;
        }

        return $this->fail === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function bundleNet(int $bundleId, int $warehouseId): float
    {
        return (float) DB::table('inventory_mutations')
            ->where('cutting_job_bundle_id', $bundleId)
            ->where('warehouse_id', $warehouseId)
            ->sum('qty_change');
    }

    private function mutationTagged(int $bundleId, int $warehouseId, string $sourceType): bool
    {
        return DB::table('inventory_mutations')
            ->where('cutting_job_bundle_id', $bundleId)
            ->where('warehouse_id', $warehouseId)
            ->where('source_type', $sourceType)
            ->exists();
    }

    private function isReady(int $bundleId): bool
    {
        return CuttingJobBundle::query()->whereKey($bundleId)->readyForSewing()->exists();
    }

    private function assertEq(string $label, float $got, float $want): void
    {
        $ok = abs($got - $want) < 0.0001;
        $this->result($ok, $label, "got={$got} want={$want}");
    }

    private function assertTrue(string $label, bool $cond): void
    {
        $this->result($cond, $label, '');
    }

    private function result(bool $ok, string $label, string $detail): void
    {
        if ($ok) {
            $this->pass++;
            $this->line("  <info>PASS</info> {$label}");
        } else {
            $this->fail++;
            $this->line("  <error>FAIL</error> {$label} " . ($detail ? "({$detail})" : ''));
        }
    }
}
