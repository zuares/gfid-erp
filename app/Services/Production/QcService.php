<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\CuttingJobLot;
use App\Models\FinishingJob;
use App\Models\QcResult;
use App\Models\SewingJob;
use App\Models\Warehouse;
use App\Services\Costing\FinishingRmHppService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class QcService
{
    public function __construct(
        protected InventoryService $inventory,
        protected FinishingRmHppService $finishingRmHpp,
    ) {}

    /* ============================================================
     * 1) QC CUTTING
     * ============================================================
     */

    public function saveCuttingQc(CuttingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            $rmWarehouseId = $job->warehouse_id;
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            if (!$rmWarehouseId || !$wipCutWarehouseId) {
                throw new \RuntimeException('Warehouse RM atau WIP-CUT belum dikonfigurasi.');
            }

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            $totalOkByFinishedItem = []; // [finished_item_id => total qty ok]
            $hasAnyOk = false;

            // ===================================================
            // 0) UPDATE used_fabric_qty PER cutting_job_lot (JIKA
            //     DIKIRIM DARI FORM QC)
            // payload['lots'][] contoh:
            // [
            //   'id'              => cutting_job_lot_id,
            //   'used_fabric_qty' => '12.5',
            // ]
            // ===================================================
            $lotsPayload = $payload['lots'] ?? null;
            if (is_array($lotsPayload) && !empty($lotsPayload)) {
                $jobLotsMap = $job->lots()->get()->keyBy('id');

                foreach ($lotsPayload as $lotRow) {
                    $jobLotId = (int) ($lotRow['id'] ?? $lotRow['cutting_job_lot_id'] ?? 0);
                    if ($jobLotId <= 0) {
                        continue;
                    }

                    /** @var CuttingJobLot|null $jobLot */
                    $jobLot = $jobLotsMap->get($jobLotId);
                    if (!$jobLot) {
                        continue;
                    }

                    $usedQty = $this->num($lotRow['used_fabric_qty'] ?? $lotRow['qty_used'] ?? 0);
                    dd($usedQty);
                    if ($usedQty < 0) {
                        $usedQty = 0;
                    }

                    // Kalau 0, biarkan saja (nanti fallback ke planned_fabric_qty)
                    if ($usedQty > 0) {
                        $jobLot->used_fabric_qty = $usedQty;
                        $jobLot->save();
                    }
                }

                // refresh relasi
                $job->load('lots.lot');
            }

            // ===========================
            // 1) PROCESS QC PER BUNDLE
            // ===========================
            foreach ($rows as $row) {
                $bundleId = (int) ($row['cutting_job_bundle_id'] ?? $row['bundle_id'] ?? 0);
                if ($bundleId <= 0) {
                    continue;
                }

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundleQty = (float) $bundle->qty_pcs;

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }

                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

                if ($qtyOk + $qtyReject > $bundleQty) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQty;

                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQty - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQty);

                QcResult::updateOrCreate(
                    [
                        'stage' => QcResult::STAGE_CUTTING,
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
                        'reject_reason' => $row['reject_reason'] ?? null,
                    ],
                );

                $bundle->qty_qc_ok = $qtyOk;
                $bundle->qty_qc_reject = $qtyReject;
                $bundle->status = $status;
                $bundle->save();

                if ($bundle->finished_item_id && $qtyOk > 0) {
                    $totalOkByFinishedItem[$bundle->finished_item_id] =
                        ($totalOkByFinishedItem[$bundle->finished_item_id] ?? 0) + $qtyOk;

                    $hasAnyOk = true;
                }
            }

            if (!$hasAnyOk) {
                // Tidak ada qty OK → tidak usah mutasi stok
                return;
            }

            // ===========================
            // 2) MULTI-LOT RM → WIP-CUT
            // ===========================

            // Ambil semua LOT yang terdaftar di cutting_job_lots
            $jobLots = CuttingJobLot::query()
                ->with('lot')
                ->where('cutting_job_id', $job->id)
                ->get();

            if ($jobLots->isEmpty()) {
                // fallback: kalau belum pakai pivot, pakai header lot_id
                $singleLot = $job->lot;
                if (!$singleLot) {
                    throw new \RuntimeException("Cutting job {$job->code} tidak punya LOT terhubung.");
                }

                // bikin object "virtual" mirip CuttingJobLot
                $virtual = new CuttingJobLot();
                $virtual->lot_id = $singleLot->id;
                $virtual->planned_fabric_qty = 0; // tidak tahu rencana → treat 0
                $virtual->used_fabric_qty = 0;
                $virtual->setRelation('lot', $singleLot);

                $jobLots = collect([$virtual]);
            }

            $totalOkAll = array_sum($totalOkByFinishedItem);
            $totalRmCost = 0.0;
            $totalRmQty = 0.0;

            // 2.a OUT dari semua LOT kain yang dipakai job ini
            foreach ($jobLots as $jobLot) {
                $lot = $jobLot->lot;
                if (!$lot) {
                    continue;
                }

                $lotId = $lot->id;
                $itemId = $lot->item_id;

                // qty yang dipakai job ini:
                // pakai used_fabric_qty jika > 0, kalau tidak fallback planned_fabric_qty
                $qtyUsed = (float) ($jobLot->used_fabric_qty ?? 0);
                if ($qtyUsed <= 0) {
                    $qtyUsed = (float) ($jobLot->planned_fabric_qty ?? 0);
                }

                if ($qtyUsed <= 0) {
                    continue;
                }

                // cek saldo LOT di gudang RM
                $saldoLot = $this->inventory->getLotBalance($rmWarehouseId, $itemId, $lotId);
                if ($saldoLot <= 0) {
                    continue;
                }

                // jaga-jaga: jangan OUT lebih besar dari saldo
                $qtyForOut = min($qtyUsed, $saldoLot);

                // ambil avg cost per unit untuk LOT ini
                $unitCostLot = $this->inventory->getLotMovingAverageUnitCost(
                    warehouseId: $rmWarehouseId,
                    itemId: $itemId,
                    lotId: $lotId,
                );

                if ($unitCostLot === null) {
                    $unitCostLot = 0.0;
                }

                $lineCost = $unitCostLot * $qtyForOut;

                // akumulasi total cost & qty
                $totalRmQty += $qtyForOut;
                $totalRmCost += $lineCost;

                // Stock OUT kain dari gudang RM per LOT
                $this->inventory->stockOut(
                    warehouseId: $rmWarehouseId,
                    itemId: $itemId,
                    qty: $qtyForOut,
                    date: $qcDate,
                    sourceType: 'cutting_qc_out',
                    sourceId: $job->id,
                    notes: "QC Cutting OUT {$qtyForOut} kain LOT {$lot->code} untuk job {$job->code}",
                    allowNegative: false,
                    lotId: $lotId,
                    // unit_cost pakai LotCost/moving average internal
                );
            }

            if ($totalRmQty <= 0 || $totalRmCost <= 0 || $totalOkAll <= 0) {
                // tidak ada kain yang benar-benar keluar → STOP di sini
                return;
            }

            // HPP RM per pcs FG (weighted average across LOT)
            $rmUnitCostPerPcs = $totalRmCost / $totalOkAll;

            // 2.b IN WIP-CUT untuk tiap finished_item
            foreach ($totalOkByFinishedItem as $finishedItemId => $qtyOkItem) {
                if ($qtyOkItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $finishedItemId,
                    qty: $qtyOkItem,
                    date: $qcDate,
                    sourceType: 'cutting_qc_in',
                    sourceId: $job->id,
                    notes: "QC Cutting IN WIP-CUT {$qtyOkItem} pcs untuk job {$job->code}",
                    lotId: null,
                    unitCost: $rmUnitCostPerPcs,
                    affectLotCost: false,
                );
            }
        });
    }

    /* ============================================================
     * 2) QC SEWING
     * ============================================================
     */
    public function saveSewingQc(SewingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            // ===========================
            // 0) WAREHOUSE SETUP
            // ===========================
            $wipSewWarehouseId = Warehouse::where('code', 'WIP-SEW')->value('id');
            $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
            $rejSewWarehouseId = Warehouse::where('code', 'REJ-SEW')->value('id');

            if (!$wipSewWarehouseId || !$wipFinWarehouseId || !$rejSewWarehouseId) {
                throw new \RuntimeException('Warehouse WIP-SEW / WIP-FIN / REJ-SEW belum dikonfigurasi.');
            }

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            $totalProcessedByItem = []; // [item_id => total (OK+Reject)]
            $totalOkByItem = []; // [item_id => total OK]
            $totalRejectByItem = []; // [item_id => total Reject]
            $hasAnyMovement = false;

            // ===========================
            // 1) LOOP HASIL QC PER BUNDLE
            // ===========================
            foreach ($rows as $row) {

                if (empty($row['bundle_id'])) {
                    continue;
                }

                $bundleId = (int) $row['bundle_id'];

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundleQtyBase = (float) ($bundle->qty_qc_ok ?? $bundle->qty_pcs ?? 0);

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }
                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

                if ($qtyOk + $qtyReject > $bundleQtyBase) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQtyBase;

                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQtyBase - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQtyBase);
                $rejectReason = $row['reject_reason'] ?? null;
                $notes = $row['notes'] ?? null;

                // 1.a Simpan QC ke qc_results (stage sewing)
                $this->upsertBundleQc(
                    stage: QcResult::STAGE_SEWING,
                    bundle: $bundle,
                    qcDate: $qcDate,
                    qtyOk: $qtyOk,
                    qtyReject: $qtyReject,
                    status: $status,
                    operatorId: $operatorId,
                    notes: $notes,
                    rejectReason: $rejectReason,
                    cuttingJobId: $bundle->cutting_job_id,
                    sewingJobId: $job->id,
                    finishingJobId: null,
                );

                // 1.b Akumulasi untuk mutasi stok
                if ($bundle->finished_item_id) {
                    $itemId = $bundle->finished_item_id;
                    $processedQty = $qtyOk + $qtyReject;

                    if ($processedQty > 0) {
                        $totalProcessedByItem[$itemId] =
                            ($totalProcessedByItem[$itemId] ?? 0) + $processedQty;
                        $hasAnyMovement = true;
                    }

                    if ($qtyOk > 0) {
                        $totalOkByItem[$itemId] =
                            ($totalOkByItem[$itemId] ?? 0) + $qtyOk;
                    }

                    if ($qtyReject > 0) {
                        $totalRejectByItem[$itemId] =
                            ($totalRejectByItem[$itemId] ?? 0) + $qtyReject;
                    }
                }
            }

            if (!$hasAnyMovement) {
                return;
            }

            // ===========================
            // 2) INVENTORY MOVEMENT + COST
            // ===========================
            // - OUT: WIP-SEW (OK + Reject) pakai cost avg WIP-SEW
            // - IN : WIP-FIN (OK) pakai cost yang sama
            // - IN : REJ-SEW (Reject) pakai cost yang sama

            // siapkan map unit_cost di WIP-SEW per item
            $unitCostWipSewPerItem = [];
            foreach (array_keys($totalProcessedByItem) as $itemId) {
                $unit = $this->inventory->getItemIncomingUnitCost($wipSewWarehouseId, $itemId);
                $unitCostWipSewPerItem[$itemId] = $unit > 0 ? $unit : null;
            }

            // 2.a OUT dari WIP-SEW
            foreach ($totalProcessedByItem as $itemId => $qtyProcessed) {
                if ($qtyProcessed <= 0) {
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: $wipSewWarehouseId,
                    itemId: $itemId,
                    qty: $qtyProcessed,
                    date: $qcDate,
                    sourceType: 'sewing_qc_out',
                    sourceId: $job->id,
                    notes: "QC Sewing OUT {$qtyProcessed} pcs dari WIP-SEW untuk job {$job->code}",
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: $unitCostWipSewPerItem[$itemId] ?? null,
                    affectLotCost: false,
                );
            }

            // 2.b IN ke WIP-FIN (OK)
            foreach ($totalOkByItem as $itemId => $qtyOkItem) {
                if ($qtyOkItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $wipFinWarehouseId,
                    itemId: $itemId,
                    qty: $qtyOkItem,
                    date: $qcDate,
                    sourceType: 'sewing_qc_in',
                    sourceId: $job->id,
                    notes: "QC Sewing IN WIP-FIN {$qtyOkItem} pcs untuk job {$job->code}",
                    lotId: null,
                    unitCost: $unitCostWipSewPerItem[$itemId] ?? null,
                    affectLotCost: false,
                );
            }

            // 2.c IN ke REJ-SEW (Reject)
            foreach ($totalRejectByItem as $itemId => $qtyRejectItem) {
                if ($qtyRejectItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $rejSewWarehouseId,
                    itemId: $itemId,
                    qty: $qtyRejectItem,
                    date: $qcDate,
                    sourceType: 'sewing_qc_reject',
                    sourceId: $job->id,
                    notes: "QC Sewing REJECT {$qtyRejectItem} pcs untuk job {$job->code}",
                    lotId: null,
                    unitCost: $unitCostWipSewPerItem[$itemId] ?? null,
                    affectLotCost: false,
                );
            }
        });
    }

    /* ============================================================
     * 3) QC FINISHING (KERANGKA)
     * ============================================================
     */
    public function saveFinishingQc(FinishingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            $totalOkByItem = []; // [finished_item_id => total qty OK finishing]
            $hasAnyMovement = false;

            foreach ($rows as $row) {
                if (empty($row['bundle_id'])) {
                    continue;
                }

                $bundleId = (int) $row['bundle_id'];

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundleQty = (float) ($bundle->qty_sewing_ok ?? 0);

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }
                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

                if ($qtyOk + $qtyReject > $bundleQty) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQty;

                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQty - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQty);
                $rejectReason = $row['reject_reason'] ?? null;
                $notes = $row['notes'] ?? null;

                $this->upsertBundleQc(
                    stage: QcResult::STAGE_FINISHING,
                    bundle: $bundle,
                    qcDate: $qcDate,
                    qtyOk: $qtyOk,
                    qtyReject: $qtyReject,
                    status: $status,
                    operatorId: $operatorId,
                    notes: $notes,
                    rejectReason: $rejectReason,
                    cuttingJobId: $bundle->cutting_job_id,
                    sewingJobId: null,
                    finishingJobId: $job->id,
                );

                // akumulasi qty OK per item untuk HPP RM-only
                if ($bundle->finished_item_id && $qtyOk > 0) {
                    $itemId = $bundle->finished_item_id;

                    $totalOkByItem[$itemId] =
                        ($totalOkByItem[$itemId] ?? 0) + $qtyOk;

                    $hasAnyMovement = true;
                }
            }

            if (!$hasAnyMovement) {
                return;
            }

            // TODO:
            // - OUT dari WIP-FIN
            // - IN ke FG (OK) + REJ-FIN (reject)
            //   (bisa kamu lengkapi nanti, tidak mengganggu HPP RM-only)

            // 💥 AUTO HPP RM-only per FinishingJob (MULTI-LOT)
            $this->finishingRmHpp->createRmOnlySnapshotsFromFinishing($job, $totalOkByItem);
        });
    }

    /* ============================================================
     * HELPER UMUM QC PER BUNDLE
     * ============================================================
     */
    protected function upsertBundleQc(
        string $stage,
        CuttingJobBundle $bundle,
        string $qcDate,
        float $qtyOk,
        float $qtyReject,
        string $status,
        ?int $operatorId = null,
        ?string $notes = null,
        ?string $rejectReason = null,
        ?int $cuttingJobId = null,
        ?int $sewingJobId = null,
        ?int $finishingJobId = null,
    ): void {
        QcResult::updateOrCreate(
            [
                'stage' => $stage,
                'cutting_job_bundle_id' => $bundle->id,
                'cutting_job_id' => $cuttingJobId,
                'sewing_job_id' => $sewingJobId,
                'finishing_job_id' => $finishingJobId,
            ],
            [
                'qc_date' => $qcDate,
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyReject,
                'reject_reason' => $rejectReason,
                'operator_id' => $operatorId,
                'status' => $status,
                'notes' => $notes,
            ],
        );

        if ($stage === QcResult::STAGE_CUTTING) {
            $bundle->qty_qc_ok = $qtyOk;
            $bundle->qty_qc_reject = $qtyReject;
            $bundle->status = $status;
            $bundle->save();
        }
    }

    protected function resolveBundleStatus(float $qtyOk, float $qtyReject, float $bundleQty): string
    {
        if ($qtyOk <= 0 && $qtyReject <= 0) {
            return 'cut';
        }

        if ($qtyOk > 0 && $qtyReject <= 0) {
            return 'qc_ok';
        }

        if ($qtyOk > 0 && $qtyReject > 0) {
            return 'qc_mixed';
        }

        return 'qc_reject';
    }

    protected function num(float | int | string | null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // format Indonesia 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

}
