<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\FinishingJob;
use App\Models\QcResult;
// Sesuaikan kalau kamu pakai SewingReturn / model lain
use App\Models\SewingJob;
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
     *
     * Desain:
     * - LOT kain (kg) sudah berada di WIP-CUT sebelum QC (dari proses lain:
     *   transfer RM → WIP-CUT atau dari CuttingService::create()).
     * - Di sini:
     *   - OUT:  kain LOT (kg) dari WIP-CUT sampai saldo LOT = 0
     *   - IN :  barang jadi OK (pcs) ke WIP-CUT [tanpa lot]
     *   - IN :  barang reject (pcs) ke REJ-CUT [tanpa lot]
     */
    public function saveCuttingQc(CuttingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            // Hanya butuh gudang WIP-CUT (di sini kain LOT + WIP bundles berada)
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

            if (!$wipCutWarehouseId) {
                throw new \RuntimeException('Warehouse WIP-CUT belum dikonfigurasi.');
            }

            // Map bundle di job ini biar akses cepat
            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id');

            // Akumulasi hasil QC per finished_item
            $totalOkByFinishedItem = []; // [item_id => total qty_ok]
            $totalRejectByFinishedItem = []; // [item_id => total qty_reject]
            $hasAnyOk = false;
            $hasAnyReject = false;

            // 1) Loop hasil QC per bundle
            foreach ($rows as $row) {
                // Form harus kirim cutting_job_bundle_id
                if (empty($row['cutting_job_bundle_id'])) {
                    continue;
                }

                $bundleId = (int) $row['cutting_job_bundle_id'];

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    // bundle bukan milik job ini → skip
                    continue;
                }

                $bundleQty = (float) $bundle->qty_pcs;
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                // Normalisasi supaya tidak negatif
                if ($qtyOk < 0) {
                    $qtyOk = 0;
                }
                if ($qtyReject < 0) {
                    $qtyReject = 0;
                }

                // Clamp: OK + Reject tidak boleh melebihi qty bundle
                if ($qtyOk + $qtyReject > $bundleQty) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQty;

                    // Prioritaskan mengurangi Reject dulu
                    if ($qtyReject >= $diff) {
                        $qtyReject -= $diff;
                    } else {
                        $qtyOk = max(0, $bundleQty - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQty);
                $rejectReason = $row['reject_reason'] ?? null;
                $notes = $row['notes'] ?? null;

                // 🔁 Simpan qc_results + update bundle (stage cutting)
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

                // Akumulasi qty OK per finished_item untuk stok WIP-CUT (barang jadi)
                if ($bundle->finished_item_id && $qtyOk > 0) {
                    $totalOkByFinishedItem[$bundle->finished_item_id] =
                        ($totalOkByFinishedItem[$bundle->finished_item_id] ?? 0) + $qtyOk;

                    $hasAnyOk = true;
                }

                // Akumulasi qty REJECT per finished_item untuk gudang REJ-CUT
                if ($bundle->finished_item_id && $qtyReject > 0) {
                    $totalRejectByFinishedItem[$bundle->finished_item_id] =
                        ($totalRejectByFinishedItem[$bundle->finished_item_id] ?? 0) + $qtyReject;

                    $hasAnyReject = true;
                }
            }

            // Kalau benar-benar tidak ada OK dan tidak ada REJECT → tidak ada pergerakan stok
            if (!$hasAnyOk && !$hasAnyReject) {
                return;
            }

// 2) INVENTORY MOVEMENT:
            $lot = $job->lot;

            if (!$lot) {
                throw new \RuntimeException("CuttingJob {$job->id} tidak memiliki LOT terkait.");
            }

// 🔎 Ambil saldo LOT aktual di WIP-CUT (kain kg)
            $lotQtyInWipCut = $this->inventory->getLotBalance(
                warehouseId: $wipCutWarehouseId,
                itemId: $lot->item_id,
                lotId: $lot->id,
            );

// 🔎 Hitung total pcs (OK + REJECT) & unit_cost per pcs dari LOT
            $totalOkPieces = array_sum($totalOkByFinishedItem);
            $totalRejectPieces = array_sum($totalRejectByFinishedItem);
            $totalPieces = $totalOkPieces + $totalRejectPieces;

// Ambil cost per unit kain dari pembelian
            $lotUnitCost = $this->inventory->getLotPurchaseUnitCost(
                itemId: $lot->item_id,
                lotId: $lot->id,
            );

// Total cost kain yang dihabiskan di QC ini
            $totalFabricCost = $lotQtyInWipCut * $lotUnitCost;

// Cost per pcs (dibagi ke OK + REJECT)
            $unitCostPerPiece = ($totalPieces > 0 && $totalFabricCost > 0)
            ? $totalFabricCost / $totalPieces
            : 0.0;

            if ($lotQtyInWipCut > 0) {
                // 2.a STOCK OUT: habiskan saldo LOT kain di gudang WIP-CUT
                $this->inventory->stockOut(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $lot->item_id,
                    qty: $lotQtyInWipCut,
                    date: $qcDate,
                    sourceType: 'cutting_qc_out',
                    sourceId: $job->id,
                    notes: "QC Cutting OUT full saldo LOT {$lotQtyInWipCut} di WIP-CUT untuk job {$job->code}",
                    allowNegative: false,
                    lotId: $lot->id,
                );
            }

// 2.b STOCK IN: WIP-CUT per finished_item untuk qty OK (TANPA lot kain)
            foreach ($totalOkByFinishedItem as $finishedItemId => $qtyOkItem) {
                if ($qtyOkItem <= 0) {
                    continue;
                }

                $this->inventory->stockIn(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $finishedItemId,
                    qty: $qtyOkItem, // pcs
                    date: $qcDate,
                    sourceType: 'cutting_qc_in',
                    sourceId: $job->id,
                    notes: "QC Cutting IN WIP-CUT {$qtyOkItem} pcs untuk job {$job->code}",
                    lotId: $job->lot_id, // tetap isi, buat grouping LOT di WIP
                    unitCost: $unitCostPerPiece, // rupiah / pcs
                    affectLotCost: false, // ⬅️ JANGAN sentuh LotCost lagi
                );
            }

// 2.c STOCK IN: gudang REJ-CUT untuk qty REJECT (tanpa lot)
            if ($hasAnyReject) {
                $rejectWarehouseId = Warehouse::where('code', 'REJ-CUT')->value('id');

                if (!$rejectWarehouseId) {
                    throw new \RuntimeException('Warehouse REJ-CUT belum dikonfigurasi.');
                }

                foreach ($totalRejectByFinishedItem as $finishedItemId => $qtyRejectItem) {
                    if ($qtyRejectItem <= 0) {
                        continue;
                    }

                    $this->inventory->stockIn(
                        warehouseId: $rejectWarehouseId,
                        itemId: $finishedItemId,
                        qty: $qtyRejectItem,
                        date: $qcDate,
                        sourceType: 'cutting_qc_reject',
                        sourceId: $job->id,
                        notes: "QC Cutting REJECT {$qtyRejectItem} pcs untuk job {$job->code}",
                        lotId: null,
                        unitCost: $unitCostPerPiece > 0 ? $unitCostPerPiece : null,
                    );
                }
            }

        });
    }

    /* ============================================================
     * 2) QC SEWING (KERANGKA – NANTI DISINKRONKAN
     *    DENGAN ALUR WIP-CUT → WIP-SEW → WIP-FIN)
     * ============================================================
     */
    public function saveSewingQc(SewingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $rows = $payload['results'] ?? [];

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = $job->bundles()->get()->keyBy('id'); // pastikan relasi di SewingJob

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

                // Misal pakai qty_qc_ok cutting sebagai dasar
                $bundleQty = (float) ($bundle->qty_qc_ok ?? 0);

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

                // TODO:
                // - OUT dari WIP-SEW
                // - IN ke WIP-FIN (OK) + REJ-SEW (reject)
                // - Barang jadi tetap tanpa lot, konsisten dengan desain cutting
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
            $bundleMap = $job->bundles()->get()->keyBy('id'); // pastikan relasi di FinishingJob

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

                // Misal pakai qty_sewing_ok sebagai dasar
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

                // TODO:
                // - OUT dari WIP-FIN
                // - IN ke FG (OK) + REJ-FIN (reject)
                // - Tetap tanpa lot untuk barang jadi
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

        // ✅ Untuk sekarang, hanya update field QC di bundle saat stage CUTTING
        if ($stage === QcResult::STAGE_CUTTING) {
            $bundle->qty_qc_ok = $qtyOk;
            $bundle->qty_qc_reject = $qtyReject;
            $bundle->status = $status;
            $bundle->save();
        }
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
