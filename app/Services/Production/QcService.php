<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\FinishingJob;
use App\Models\QcResult;
use App\Models\SewingJob; // atau SewingReturn kalau nama model kamu beda
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class QcService
{
    public function __construct(
        protected InventoryService $inventory,
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
            $rows = $payload['results'];

            // Gudang RM (LOT kain) & WIP-CUT
            $rmWarehouseId = $job->warehouse_id;
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            if (!$rmWarehouseId || !$wipCutWarehouseId) {
                throw new \RuntimeException('Warehouse RM atau WIP-CUT belum dikonfigurasi.');
            }

            // Map bundle di job ini
            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            $totalOkByFinishedItem = [];
            $hasAnyOk = false;

            // 1) Loop hasil QC per bundle
            foreach ($rows as $row) {
                $bundleId = (int) $row['bundle_id'];

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundleQty = (float) $bundle->qty_pcs;
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                // Clamp supaya tidak melebihi qty bundle
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

                // 🔁 Helper umum untuk simpan qc_results + update bundle
                $this->upsertBundleQc(
                    stage: QcResult::STAGE_CUTTING,
                    bundle: $bundle,
                    qcDate: $qcDate,
                    qtyOk: $qtyOk,
                    qtyReject: $qtyReject,
                    status: $status,
                    operatorId: $operatorId,
                    notes: $notes,
                    rejectReason: $rejectReason,
                    cuttingJobId: $job->id,
                    sewingJobId: null,
                    finishingJobId: null,
                );

                // Akumulasi qty_ok per finished_item untuk WIP-CUT
                if ($bundle->finished_item_id && $qtyOk > 0) {
                    $totalOkByFinishedItem[$bundle->finished_item_id] =
                        ($totalOkByFinishedItem[$bundle->finished_item_id] ?? 0) + $qtyOk;

                    $hasAnyOk = true;
                }
            }

            if (!$hasAnyOk) {
                return;
            }

            // 2) INVENTORY MOVEMENT: RM → WIP-CUT
            $lot = $job->lot;

            if (!$lot) {
                throw new \RuntimeException("CuttingJob {$job->id} tidak memiliki LOT terkait.");
            }

            // Ambil saldo LOT aktual
            $lotQty = $this->inventory->getLotBalance(
                warehouseId: $rmWarehouseId,
                itemId: $lot->item_id,
                lotId: $lot->id,
            );

            if ($lotQty <= 0) {
                return;
            }

            // 2.a STOCK OUT: habiskan saldo LOT di gudang RM
            $this->inventory->stockOut(
                warehouseId: $rmWarehouseId,
                itemId: $lot->item_id,
                qty: $lotQty,
                date: $qcDate,
                sourceType: 'cutting_qc_out',
                sourceId: $job->id,
                notes: "QC Cutting OUT full saldo LOT {$lotQty} untuk job {$job->code}",
                allowNegative: false,
                lotId: $lot->id,
            );

            // 2.b STOCK IN: WIP-CUT per finished_item
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
                    lotId: $lot->id,
                    unitCost: null,
                );
            }
        });
    }

    /* ============================================================
     * 2) QC SEWING (KERANGKA)
     * ============================================================
     */
    public function saveSewingQc(SewingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'];

            // TODO: sesuaikan:
            // - sumber stok: WIP-CUT
            // - tujuan stok OK: WIP-SEW atau WIP-FIN (sesuai desain kamu)
            // - bundle tetap anchor-nya sama (bundle dari Cutting)

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id'); // sesuaikan relasi di SewingJob

            foreach ($rows as $row) {
                $bundleId = (int) $row['bundle_id'];

                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundleQty = (float) $bundle->qty_qc_ok; // atau field lain yang jadi basis di sewing
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

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

                // TODO: tambah logic inventory:
                // - OUT: WIP-CUT (qty in sewing)
                // - IN : WIP-FIN atau WIP-SEW (tergantung desain final kamu)
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
            $rows = $payload['results'];

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id'); // sesuaikan relasi FinishingJob → bundles

            foreach ($rows as $row) {
                $bundleId = (int) $row['bundle_id'];

                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundleQty = (float) $bundle->qty_sewing_ok ?? 0; // sesuaikan
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

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

                // TODO: inventory:
                // - OUT: WIP-FIN
                // - IN : FG (Finished Goods)
            }
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
                'bundle_id' => $bundle->id,
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

        // Update field QC di bundle (bisa kamu refine per stage kalau mau beda)
        $bundle->qty_qc_ok = $qtyOk;
        $bundle->qty_qc_reject = $qtyReject;
        $bundle->status = $status;
        $bundle->save();
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

        return 'qc_reject'; // qtyOk = 0, qtyReject > 0
    }
}
