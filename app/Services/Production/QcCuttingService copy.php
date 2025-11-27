<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\QcResult;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class QcCuttingService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Simpan hasil QC Cutting untuk satu CuttingJob:
     *
     * - Simpan / update qc_results per bundle (stage = cutting)
     * - Update qty_qc_ok / qty_qc_reject + status di cutting_job_bundles
     * - Set wip_warehouse_id + wip_qty di cutting_job_bundles
     * - INVENTORY:
     *      OUT:  habiskan saldo LOT kain di gudang RM (sekali per job)
     *      IN :  WIP-CUT per finished_item (total qty_ok)
     *
     * Catatan:
     * - Di sini kita anggap LOT dipakai HABIS untuk job ini.
     *   Qty OUT diambil dari saldo LOT aktual di inventory_mutations.
     */
    public function saveCuttingQc(CuttingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'];

            // gudang RM tempat LOT berada (diasumsikan di field warehouse_id di CuttingJob)
            $rmWarehouseId = $job->warehouse_id;

            // gudang WIP-CUT (pastikan ada di tabel warehouses dengan code = 'WIP-CUT')
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            if (!$rmWarehouseId || !$wipCutWarehouseId) {
                throw new \RuntimeException('Warehouse RM atau WIP-CUT belum dikonfigurasi.');
            }

            // Ambil semua bundle di job ini sebagai map [bundle_id => model]
            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            $totalOkByFinishedItem = []; // [finished_item_id => total_qty_ok]
            $hasAnyOk = false;

            // ===========================
            // 1) LOOP HASIL QC PER BUNDLE
            // ===========================
            foreach ($rows as $row) {
                $bundleId = (int) $row['bundle_id'];

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    // kalau data aneh (bundle_id tidak ada di job ini) → skip
                    continue;
                }

                $bundleQty = (float) $bundle->qty_pcs;
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                // Clamp supaya tidak melebihi qty bundle
                if ($qtyOk + $qtyReject > $bundleQty) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQty;

                    // Kurangi reject dulu, lalu ok
                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQty - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQty);

                // 1.a Simpan / update qc_results
                QcResult::updateOrCreate(
                    [
                        'stage' => 'cutting',
                        'cutting_job_id' => $job->id,
                        'cutting_job_bundle_id' => $bundleId,
                    ],
                    [
                        'qc_date' => $qcDate,
                        'qty_ok' => $qtyOk,
                        'qty_reject' => $qtyReject,
                        'operator_id' => $operatorId,
                        'status' => $status,
                        'notes' => $row['notes'] ?? null,
                    ],
                );

                // 1.b Update bundle QC fields
                $bundle->qty_qc_ok = $qtyOk;
                $bundle->qty_qc_reject = $qtyReject;
                $bundle->status = $status;

                // ❌ DULU di sini kamu set wip_warehouse_id + wip_qty ke WIP-CUT
                // ❌ Itu yang bikin nanti numbuk sama WIP-FIN.
                // ✅ Sekarang: JANGAN sentuh wip_warehouse_id / wip_qty di tahap cutting.
                //    Biar field itu dipakai khusus untuk saldo WIP-FIN dari Sewing Return.

                $bundle->save();

                // 1.d Akumulasi qty_ok per finished_item untuk WIP-CUT (inventory)
                if ($bundle->finished_item_id && $qtyOk > 0) {
                    $totalOkByFinishedItem[$bundle->finished_item_id] =
                        ($totalOkByFinishedItem[$bundle->finished_item_id] ?? 0) + $qtyOk;

                    $hasAnyOk = true;
                }
            }

            // Kalau tidak ada qty OK sama sekali → tidak perlu posting inventory
            if (!$hasAnyOk) {
                return;
            }

            // ===========================
            // 2) INVENTORY MOVEMENT
            // ===========================
            $lot = $job->lot;

            if (!$lot) {
                throw new \RuntimeException("CuttingJob {$job->id} tidak memiliki LOT terkait.");
            }

            // 2.a Ambil saldo LOT aktual dari inventory_mutations
            $lotQty = $this->inventory->getLotBalance(
                warehouseId: $rmWarehouseId,
                itemId: $lot->item_id,
                lotId: $lot->id,
            );

            if ($lotQty <= 0) {
                // Tidak ada saldo LOT di gudang RM → tidak bisa OUT
                return;
            }

            // 2.b STOCK OUT: habiskan saldo LOT di gudang RM
            $this->inventory->stockOut(
                warehouseId: $rmWarehouseId,
                itemId: $lot->item_id, // kain mentah
                qty: $lotQty, // habiskan saldo LOT (contoh 25kg)
                date: $qcDate,
                sourceType: 'cutting_qc_out',
                sourceId: $job->id,
                notes: "QC Cutting OUT full saldo LOT {$lotQty} untuk job {$job->code}",
                allowNegative: false,
                lotId: $lot->id,
            );

            // 2.c STOCK IN: WIP-CUT per finished_item (pcs hasil OK)
            foreach ($totalOkByFinishedItem as $finishedItemId => $qtyOkItem) {
                if ($qtyOkItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $finishedItemId, // barang WIP hasil cutting
                    qty: $qtyOkItem, // total OK (pcs) per item
                    date: $qcDate,
                    sourceType: 'cutting_qc_in',
                    sourceId: $job->id,
                    notes: "QC Cutting IN WIP-CUT {$qtyOkItem} pcs untuk job {$job->code}",
                    lotId: $lot->id,
                    unitCost: null,
                );
            }
        });
    }

    /**
     * Menentukan status bundle berdasarkan hasil QC.
     */
    protected function resolveBundleStatus(float $qtyOk, float $qtyReject, float $bundleQty): string
    {
        if ($qtyOk <= 0 && $qtyReject <= 0) {
            return 'cut'; // belum ada hasil QC
        }

        if ($qtyOk > 0 && $qtyReject <= 0) {
            return 'qc_ok';
        }

        if ($qtyOk > 0 && $qtyReject > 0) {
            return 'qc_mixed';
        }

        // qtyOk = 0, qtyReject > 0
        return 'qc_reject';
    }
}
