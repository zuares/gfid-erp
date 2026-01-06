<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
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

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            $hasAnyOk = false;
            $totalOkByFinishedItem = []; // [finished_item_id => total qty ok] (kalau suatu saat mau dipakai HPP / report)

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

                // Jaga-jaga biar tidak lebih dari qty bundle
                if ($qtyOk + $qtyReject > $bundleQty) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQty;

                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQty - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQty);

                // Simpan ke qc_results
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

                // Update ke bundle
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

            // Di pattern baru:
            // - RM OUT sudah dilakukan di CuttingService::create() (consumeFabricFromLots)
            // - WIP-CUT IN akan dilakukan terpisah (di CuttingService::createWipFromCuttingQc)
            //   setelah QC selesai, dipanggil dari controller.
            // Jadi di sini TIDAK ADA lagi mutasi stok.

            if (!$hasAnyOk) {
                // Tidak ada qty OK → tidak akan dibuat WIP nantinya, tapi tidak error.
                return;
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

            // ===========================
            // 0) WAREHOUSE SETUP
            // ===========================
            $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
            $fgWarehouseId = Warehouse::where('code', 'FG')->value('id'); // gudang FG
            $rejFinWarehouseId = Warehouse::where('code', 'REJ-FIN')->value('id'); // gudang reject finishing

            if (!$wipFinWarehouseId || !$fgWarehouseId || !$rejFinWarehouseId) {
                throw new \RuntimeException(
                    'Warehouse WIP-FIN / FG / REJ-FIN belum dikonfigurasi di master gudang.'
                );
            }

            $totalProcessedByItem = []; // [item_id => (OK + Reject)]
            $totalOkByItem = []; // [item_id => total OK]
            $totalRejectByItem = []; // [item_id => total Reject]
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

                // base qty finishing dari hasil sewing ok
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

                // SIMPAN QC (stage finishing)
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

                // update info finishing di bundle (opsional tapi enak buat reporting)
                $bundle->qty_finishing_ok = $qtyOk;
                $bundle->qty_finishing_reject = $qtyReject;
                $bundle->status = $status;
                $bundle->save();

                // Akumulasi untuk mutasi stok
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
                // tidak ada qty yang bergerak → langsung create HPP RM-only dan selesai
                $this->finishingRmHpp->createRmOnlySnapshotsFromFinishing($job, $totalOkByItem);
                return;
            }

            // ===========================
            // 1) INVENTORY MOVEMENT
            //    OUT: WIP-FIN
            //    IN : FG (OK)
            //    IN : REJ-FIN (Reject finishing)
            // ===========================
            $unitCostWipFinPerItem = [];
            foreach (array_keys($totalProcessedByItem) as $itemId) {
                $unit = $this->inventory->getItemIncomingUnitCost($wipFinWarehouseId, $itemId);
                $unitCostWipFinPerItem[$itemId] = $unit > 0 ? $unit : null;
            }

            $movementDate = $qcDate;

            // 1.a OUT dari WIP-FIN (OK + Reject)
            foreach ($totalProcessedByItem as $itemId => $qtyProcessed) {
                if ($qtyProcessed <= 0) {
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: $wipFinWarehouseId,
                    itemId: $itemId,
                    qty: $qtyProcessed,
                    date: $movementDate,
                    sourceType: 'finishing_qc_out',
                    sourceId: $job->id,
                    notes: "QC Finishing OUT {$qtyProcessed} pcs dari WIP-FIN untuk job {$job->code}",
                    allowNegative: false,
                    lotId: null, // WIP tidak pakai LOT
                    unitCostOverride: $unitCostWipFinPerItem[$itemId] ?? null,
                    affectLotCost: false,
                );
            }

            // 1.b IN ke FG (OK)
            foreach ($totalOkByItem as $itemId => $qtyOkItem) {
                if ($qtyOkItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $fgWarehouseId,
                    itemId: $itemId,
                    qty: $qtyOkItem,
                    date: $movementDate,
                    sourceType: 'finishing_qc_in_fg',
                    sourceId: $job->id,
                    notes: "QC Finishing IN FG {$qtyOkItem} pcs dari job {$job->code}",
                    lotId: null,
                    unitCost: $unitCostWipFinPerItem[$itemId] ?? null,
                    affectLotCost: false,
                );
            }

            // 1.c IN ke REJ-FIN (Reject finishing)
            foreach ($totalRejectByItem as $itemId => $qtyRejectItem) {
                if ($qtyRejectItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $rejFinWarehouseId,
                    itemId: $itemId,
                    qty: $qtyRejectItem,
                    date: $movementDate,
                    sourceType: 'finishing_qc_reject',
                    sourceId: $job->id,
                    notes: "QC Finishing REJECT {$qtyRejectItem} pcs dari job {$job->code}",
                    lotId: null,
                    unitCost: $unitCostWipFinPerItem[$itemId] ?? null,
                    affectLotCost: false,
                );
            }

            // 💥 AUTO HPP RM-only per FinishingJob (MULTI-LOT) — tetap jalan seperti sebelumnya
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

    public function cancelCuttingQc(CuttingJob $job): void
    {
        DB::transaction(function () use ($job) {

            // pastikan memang ada QC cutting
            $hasQc = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $job->id)
                ->exists();

            if (!$hasQc) {
                // tidak ada QC → tidak ada yang dibatalkan
                return;
            }

            /**
             * 1) Reverse mutasi WIP & REJECT hasil QC
             * Mutasi original dibuat oleh createWipFromCuttingQc() dengan:
             * - sourceType: 'cutting_wip'
             * - sourceType: 'cutting_reject'
             * - sourceId  : $job->id
             *
             * reverseBySource akan membuat mutasi lawan arah (qty_change dibalik)
             * dan akan gagal otomatis kalau stok sudah tidak cukup (sudah kepakai sewing/finishing).
             */
            $this->inventory->reverseBySource(
                originalSourceTypes: ['cutting_wip', 'cutting_reject'],
                originalSourceId: $job->id,
                voidSourceType: 'cutting_qc_void',
                voidSourceId: $job->id,
                notesPrefix: "VOID QC CUTTING {$job->code}",
                date: now(), // atau pakai tanggal cancel
            );

            // 2) Reset bundle QC fields supaya bisa QC ulang
            $job->loadMissing(['bundles']);

            foreach ($job->bundles as $bundle) {
                $bundle->qty_qc_ok = 0;
                $bundle->qty_qc_reject = 0;
                $bundle->status = 'cut';

                // penting: supaya createWipFromCuttingQc() tidak skip
                $bundle->wip_qty = 0;
                $bundle->wip_warehouse_id = null;

                $bundle->save();
            }

            // 3) Hapus QC results cutting
            QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $job->id)
                ->delete();

            // 4) Update status header kembali ke antrian QC
            $job->update([
                'status' => 'sent_to_qc',
                'updated_by' => auth()->id(),
            ]);
        });
    }

}
