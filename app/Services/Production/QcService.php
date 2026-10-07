<?php

namespace App\Services\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\CuttingQcCancellation;
use App\Models\FinishingJob;
use App\Models\InventoryMutation;
use App\Models\ProductionLog;
use App\Models\QcResult;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Costing\FinishingRmHppService;
use App\Services\Inventory\InventoryService;
use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Support\Facades\DB;

class QcService
{
    public function __construct(
        protected InventoryService $inventory,
        protected FinishingRmHppService $finishingRmHpp,
        protected JournalService $journal,
    ) {}

    private function resolveRejectItem(int $originalItemId): Item
    {
        $original = Item::with('category')->find($originalItemId);
        if (!$original) {
            throw new \RuntimeException("Item ID {$originalItemId} tidak ditemukan.");
        }

        $category = $original->category;
        if (!$category) {
            $category = ItemCategory::firstOrCreate(['code' => 'UNCAT'], ['name' => 'Uncategorized']);
        }

        return Item::firstOrCreate([
            'code' => 'REJ-' . $category->code,
        ], [
            'name' => 'Reject ' . $category->name,
            'item_category_id' => $category->id,
            'unit_id' => $original->unit_id ?? null,
            'is_stockable' => true,
            'is_active' => true,
        ]);
    }

    /* ============================================================
     * 1) QC CUTTING
     * ============================================================
     */

    public function saveCuttingQc(CuttingJob $job, array $payload): void
    {
        DB::transaction(function () use ($job, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $qcByUserId = $payload['qc_by_user_id'] ?? null;
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
                        'qc_by_user_id' => $qcByUserId,
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
    public function saveSewingQc(SewingReturn $sewingReturn, array $payload): void
    {
        DB::transaction(function () use ($sewingReturn, $payload) {

            $qcDate = $payload['qc_date'];
            $operatorId = $payload['operator_id'] ?? null;
            $qcByUserId = $payload['qc_by_user_id'] ?? null;
            $rows = $payload['results'] ?? [];

            // ===========================
            // 0) WAREHOUSE SETUP
            // ===========================
            $wipSewWarehouseId = Warehouse::where('code', 'WIP-SEW')->value('id');
            $destinationWarehouse = Warehouse::query()
                ->whereIn('code', ['WH-RTS', 'WH-PRD'])
                ->whereKey((int) ($payload['destination_warehouse_id'] ?? $sewingReturn->destination_warehouse_id))
                ->first();
            $whPrdWarehouseId  = Warehouse::where('code', 'WH-PRD')->value('id');
            $rejSewWarehouseId = Warehouse::where('code', 'REJ-SEW')->value('id');

            if (!$wipSewWarehouseId || !$destinationWarehouse || !$whPrdWarehouseId || !$rejSewWarehouseId) {
                throw new \RuntimeException('Warehouse WIP-SEW / WH-PRD / WH-RTS / REJ-SEW belum dikonfigurasi.');
            }

            // Simpan tujuan yang dipilih saat QC agar detail return dan proses
            // berikutnya memakai gudang yang sama.
            $sewingReturn->destination_warehouse_id = (int) $destinationWarehouse->id;
            $sewingReturn->save();

            $returnLines = $sewingReturn->lines()
                ->with('pickupLine')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Load bundles via return lines -> pickup lines.
            $bundleIds = $returnLines
                ->pluck('pickupLine.cutting_job_bundle_id')
                ->filter()
                ->unique();

            /** @var \Illuminate\Support\Collection<int, CuttingJobBundle> $bundleMap */
            $bundleMap = CuttingJobBundle::whereIn('id', $bundleIds)->get()->keyBy('id');

            $totalProcessedByItem = []; // [item_id => total (OK+Reject)]
            $totalOkByItem = []; // [item_id => total OK]
            $totalRejectByItem = []; // [item_id => total Reject]
            // FASE 1: pecah per bundle agar setiap mutasi WIP-SEW/WIP-FIN/REJ-SEW
            // membawa cutting_job_bundle_id (readiness ledger butuh atribusi per bundle).
            $processedByBundleItem = []; // [bundle_id => [item_id => qty (OK+Reject)]]
            $okByBundleItem = []; // [bundle_id => [item_id => qty OK]]
            $rejectJahitByBundleItem = []; // [bundle_id => [item_id => qty Reject Jahit]]
            $rejectBahanByBundleItem = []; // [bundle_id => [item_id => qty Reject Bahan]]
            $hasAnyMovement = false;
            $touchedPickupIds = [];

            // ===========================
            // 1) LOOP HASIL QC PER BUNDLE
            // ===========================
            foreach ($rows as $row) {

                if (empty($row['bundle_id'])) {
                    continue;
                }

                $bundleId = (int) $row['bundle_id'];
                $returnLineId = (int) ($row['sewing_return_line_id'] ?? 0);
                $returnLine = $returnLineId > 0 ? $returnLines->get($returnLineId) : null;

                /** @var CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get($bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundleQtyBase = $returnLine
                    ? (float) ((float) ($returnLine->qty_ok ?? 0) + (float) ($returnLine->qty_reject ?? 0))
                    : (float) ($bundle->qty_qc_ok ?? $bundle->qty_pcs ?? 0);

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyRejectJahit = (float) ($row['qty_reject_jahit'] ?? 0);
                $qtyRejectBahan = (float) ($row['qty_reject_bahan'] ?? 0);
                $qtyReject = $qtyRejectJahit + $qtyRejectBahan;

                if ($qtyOk < 0) { $qtyOk = 0; }
                if ($qtyRejectJahit < 0) { $qtyRejectJahit = 0; }
                if ($qtyRejectBahan < 0) { $qtyRejectBahan = 0; }
                $qtyReject = $qtyRejectJahit + $qtyRejectBahan;

                if ($qtyOk + $qtyReject > $bundleQtyBase) {
                    $diff = ($qtyOk + $qtyReject) - $bundleQtyBase;

                    if ($qtyReject >= $diff) {
                        // kurangi dari salah satu reject (prioritas kurang dari jahit)
                        if ($qtyRejectJahit >= $diff) {
                            $qtyRejectJahit -= $diff;
                        } else {
                            $qtyRejectBahan -= ($diff - $qtyRejectJahit);
                            $qtyRejectJahit = 0;
                        }
                        $qtyReject = $qtyRejectJahit + $qtyRejectBahan;
                    } else {
                        $qtyOk = max(0, $bundleQtyBase - $qtyReject);
                    }
                }

                $status = $this->resolveBundleStatus($qtyOk, $qtyReject, $bundleQtyBase);
                
                $rejectReason = null;
                if ($qtyRejectJahit > 0 && $qtyRejectBahan > 0) {
                    $rejectReason = 'Reject Jahit & Bahan';
                } elseif ($qtyRejectJahit > 0) {
                    $rejectReason = 'Reject Jahit';
                } elseif ($qtyRejectBahan > 0) {
                    $rejectReason = 'Reject Bahan';
                }

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
                    qcByUserId: $qcByUserId,
                    notes: $notes,
                    rejectReason: $rejectReason,
                    cuttingJobId: $bundle->cutting_job_id,
                    sewingJobId: $sewingReturn->id,
                    finishingJobId: null,
                );

                if ($returnLine) {
                    $oldOk = (float) ($returnLine->qty_ok ?? 0);
                    $oldReject = (float) ($returnLine->qty_reject ?? 0);

                    $returnLine->qty_ok = $qtyOk;
                    $returnLine->qty_reject = $qtyReject;
                    $returnLine->save();

                    $pickupLine = $returnLine->pickupLine;
                    if ($pickupLine) {
                        // Reject hasil QC masuk REJ-SEW dan ditutup dari pickup normal.
                        // Setor ulangnya memakai flow reject rework, bukan membuka WIP-SEW lagi.
                        $pickupLine->qty_returned_ok = max(
                            (float) ($pickupLine->qty_returned_ok ?? 0) - $oldOk + $qtyOk,
                            0
                        );
                        $pickupLine->qty_returned_reject = max(
                            (float) ($pickupLine->qty_returned_reject ?? 0) - $oldReject + $qtyReject,
                            0
                        );
                        $pickupLine->save();

                        if ($pickupLine->sewing_pickup_id) {
                            $touchedPickupIds[(int) $pickupLine->sewing_pickup_id] = true;
                        }
                    }
                }

                // 1.b Akumulasi untuk mutasi stok
                if ($bundle->finished_item_id) {
                    $itemId = $bundle->finished_item_id;
                    $processedQty = $qtyOk + $qtyReject;

                    if ($processedQty > 0) {
                        $totalProcessedByItem[$itemId] =
                            ($totalProcessedByItem[$itemId] ?? 0) + $processedQty;
                        $processedByBundleItem[$bundleId][$itemId] =
                            ($processedByBundleItem[$bundleId][$itemId] ?? 0) + $processedQty;
                        $hasAnyMovement = true;
                    }

                    if ($qtyOk > 0) {
                        $totalOkByItem[$itemId] =
                            ($totalOkByItem[$itemId] ?? 0) + $qtyOk;
                        $okByBundleItem[$bundleId][$itemId] =
                            ($okByBundleItem[$bundleId][$itemId] ?? 0) + $qtyOk;
                    }

                    if ($qtyReject > 0) {
                        $totalRejectByItem[$itemId] =
                            ($totalRejectByItem[$itemId] ?? 0) + $qtyReject;
                        if ($qtyRejectBahan > 0) {
                            $rejectBahanByBundleItem[$bundleId][$itemId] =
                                ($rejectBahanByBundleItem[$bundleId][$itemId] ?? 0) + $qtyRejectBahan;
                        }
                        if ($qtyRejectJahit > 0) {
                            $rejectJahitByBundleItem[$bundleId][$itemId] =
                                ($rejectJahitByBundleItem[$bundleId][$itemId] ?? 0) + $qtyRejectJahit;
                        }
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
            // - IN : tujuan QC (WH-PRD / WH-RTS) (OK) pakai cost yang sama
            // - IN : REJ-SEW (Reject) pakai cost yang sama

            // siapkan map unit_cost di WIP-SEW (atau REJ-SEW) per item
            $unitCostWipSewPerItem = [];
            foreach (array_keys($totalProcessedByItem) as $itemId) {
                $unit = $this->inventory->getItemIncomingUnitCost($sewingReturn->warehouse_id, $itemId);
                $unitCostWipSewPerItem[$itemId] = $unit > 0 ? $unit : null;
            }

            // 2.a OUT dari WIP-SEW/REJ-SEW (per bundle agar mutasi ber-tag)
            foreach ($processedByBundleItem as $bundleId => $byItem) {
                foreach ($byItem as $itemId => $qtyProcessed) {
                    if ($qtyProcessed <= 0) {
                        continue;
                    }

                    $this->inventory->stockOut(
                        warehouseId: $sewingReturn->warehouse_id,
                        itemId: $itemId,
                        qty: $qtyProcessed,
                        date: $qcDate,
                        sourceType: 'sewing_qc_out',
                        sourceId: $sewingReturn->id,
                        notes: "QC Sewing OUT {$qtyProcessed} pcs dari warehouse untuk return {$sewingReturn->code} (bundle #{$bundleId})",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: $unitCostWipSewPerItem[$itemId] ?? null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }
            }

            // 2.b IN ke gudang tujuan QC (OK, per bundle)
            foreach ($okByBundleItem as $bundleId => $byItem) {
                foreach ($byItem as $itemId => $qtyOkItem) {
                    if ($qtyOkItem <= 0) {
                        continue;
                    }

                    $this->inventory->stockIn(
                        warehouseId: $destinationWarehouse->id,
                        itemId: $itemId,
                        qty: $qtyOkItem,
                        date: $qcDate,
                        sourceType: 'sewing_qc_in',
                        sourceId: $sewingReturn->id,
                        notes: "QC Sewing IN {$destinationWarehouse->code} {$qtyOkItem} pcs untuk return {$sewingReturn->code} (bundle #{$bundleId})",
                        lotId: null,
                        unitCost: $unitCostWipSewPerItem[$itemId] ?? null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }
            }

            // 2.c IN ke REJ-SEW (Reject Jahit, per bundle)
            foreach ($rejectJahitByBundleItem as $bundleId => $byItem) {
                foreach ($byItem as $itemId => $qtyRejectItem) {
                    if ($qtyRejectItem <= 0) {
                        continue;
                    }

                    $this->inventory->stockIn(
                        warehouseId: $rejSewWarehouseId,
                        itemId: $itemId,
                        qty: $qtyRejectItem,
                        date: $qcDate,
                        sourceType: 'sewing_qc_reject',
                        sourceId: $sewingReturn->id,
                        notes: "QC Sewing REJECT JAHIT {$qtyRejectItem} pcs untuk return {$sewingReturn->code} (bundle #{$bundleId})",
                        lotId: null,
                        unitCost: $unitCostWipSewPerItem[$itemId] ?? null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }
            }

            // 2.d IN ke WH-PRD (Reject Bahan, dikonversi jadi REJ-kategori, per bundle)
            foreach ($rejectBahanByBundleItem as $bundleId => $byItem) {
                foreach ($byItem as $itemId => $qtyRejectItem) {
                    if ($qtyRejectItem <= 0) {
                        continue;
                    }

                    $rejectItem = $this->resolveRejectItem($itemId);

                    $this->inventory->stockIn(
                        warehouseId: $whPrdWarehouseId,
                        itemId: $rejectItem->id,
                        qty: $qtyRejectItem,
                        date: $qcDate,
                        sourceType: 'sewing_qc_reject',
                        sourceId: $sewingReturn->id,
                        notes: "QC Sewing REJECT BAHAN {$qtyRejectItem} pcs dikonversi ke {$rejectItem->code} untuk return {$sewingReturn->code} (bundle #{$bundleId})",
                        lotId: null,
                        unitCost: $unitCostWipSewPerItem[$itemId] ?? null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }
            }

            foreach ($okByBundleItem as $bundleId => $byItem) {
                $qtyOkBundle = array_sum($byItem);
                if ($qtyOkBundle <= 0) {
                    continue;
                }

                $bundle = $bundleMap->get((int) $bundleId);
                if (!$bundle) {
                    continue;
                }

                $bundle->wip_warehouse_id = (int) $destinationWarehouse->id;
                $bundle->wip_qty = (float) ($bundle->wip_qty ?? 0) + (float) $qtyOkBundle;
                $bundle->save();
            }

            if (!empty($touchedPickupIds)) {
                foreach (array_keys($touchedPickupIds) as $pickupId) {
                    $pickup = \App\Models\SewingPickup::with('lines')
                        ->lockForUpdate()
                        ->find($pickupId);

                    if ($pickup && $pickup->isFillable('status')) {
                        $pickup->status = $pickup->recalcStatus();
                        $pickup->save();
                    }
                }
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
            $qcByUserId = $payload['qc_by_user_id'] ?? null;
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
            // FASE 1: pecah per bundle agar OUT WIP-FIN membawa cutting_job_bundle_id
            // (saldo ledger per bundle balik ke 0 saat finishing dikonsumsi).
            $processedByBundleItem = []; // [bundle_id => [item_id => qty (OK+Reject)]]
            $okByBundleItem = []; // [bundle_id => [item_id => qty OK]]
            $rejectByBundleItem = []; // [bundle_id => [item_id => qty Reject]]
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
                    qcByUserId: $qcByUserId,
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
                        $processedByBundleItem[$bundleId][$itemId] =
                            ($processedByBundleItem[$bundleId][$itemId] ?? 0) + $processedQty;
                        $hasAnyMovement = true;
                    }

                    if ($qtyOk > 0) {
                        $totalOkByItem[$itemId] =
                            ($totalOkByItem[$itemId] ?? 0) + $qtyOk;
                        $okByBundleItem[$bundleId][$itemId] =
                            ($okByBundleItem[$bundleId][$itemId] ?? 0) + $qtyOk;
                    }

                    if ($qtyReject > 0) {
                        $totalRejectByItem[$itemId] =
                            ($totalRejectByItem[$itemId] ?? 0) + $qtyReject;
                        $rejectByBundleItem[$bundleId][$itemId] =
                            ($rejectByBundleItem[$bundleId][$itemId] ?? 0) + $qtyReject;
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

            // 1.a OUT dari WIP-FIN (OK + Reject, per bundle agar saldo ledger balik 0)
            foreach ($processedByBundleItem as $bundleId => $byItem) {
                foreach ($byItem as $itemId => $qtyProcessed) {
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
                        notes: "QC Finishing OUT {$qtyProcessed} pcs dari WIP-FIN untuk job {$job->code} (bundle #{$bundleId})",
                        allowNegative: false,
                        lotId: null, // WIP tidak pakai LOT
                        unitCostOverride: $unitCostWipFinPerItem[$itemId] ?? null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }
            }

            // 1.b IN ke FG (OK, per bundle)
            foreach ($okByBundleItem as $bundleId => $byItem) {
                foreach ($byItem as $itemId => $qtyOkItem) {
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
                        notes: "QC Finishing IN FG {$qtyOkItem} pcs dari job {$job->code} (bundle #{$bundleId})",
                        lotId: null,
                        unitCost: $unitCostWipFinPerItem[$itemId] ?? null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }
            }

            // 1.c IN ke REJ-FIN (Reject finishing, per bundle)
            foreach ($rejectByBundleItem as $bundleId => $byItem) {
                foreach ($byItem as $itemId => $qtyRejectItem) {
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
                        notes: "QC Finishing REJECT {$qtyRejectItem} pcs dari job {$job->code} (bundle #{$bundleId})",
                        lotId: null,
                        unitCost: $unitCostWipFinPerItem[$itemId] ?? null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                }
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
        ?int $qcByUserId = null,
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
                'qc_by_user_id' => $qcByUserId,
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

            // Partial cancel membuat reversal mutation/journal tersendiri.
            // Saat seluruh job dibatalkan, jejak partial tersebut juga harus
            // dibalik agar total ledger kembali benar-benar nol.
            $partialCancellations = CuttingQcCancellation::query()
                ->where('cutting_job_id', $job->id)
                ->lockForUpdate()
                ->get();

            foreach ($partialCancellations as $partial) {
                $this->inventory->reverseBySource(
                    originalSourceTypes: ['cutting_qc_bundle_void'],
                    originalSourceId: (int) $partial->id,
                    voidSourceType: 'cutting_qc_full_void',
                    voidSourceId: (int) $job->id,
                    notesPrefix: "VOID partial QC {$job->code} / {$partial->id}",
                    date: now(),
                );

                $this->journal->voidBySource(
                    'cutting_qc_bundle_void',
                    (int) $partial->id,
                    "VOID partial QC {$job->code} / {$partial->id}",
                );
                $this->journal->voidBySource(
                    'cutting_qc_bundle_repost',
                    (int) $partial->id,
                    "VOID re-QC partial {$job->code} / {$partial->id}",
                );

                $partial->update([
                    'metadata' => array_merge($partial->metadata ?? [], [
                        'closed_by_full_cancel' => true,
                        'closed_at' => now()->toISOString(),
                    ]),
                ]);
            }

            /**
             * 1) Reverse mutasi hasil QC
             * Original dibuat oleh createWipFromCuttingQc():
             * - cutting_wip
             * - cutting_reject
             *
             * PLUS (opsional tapi recommended):
             * - cutting_qc_adjust_in / cutting_qc_adjust_out (kalau kamu pakai adjustment QC)
             *
             * reverseBySource akan membuat mutasi lawan arah dan akan gagal
             * kalau stok sudah kepakai (misal sudah di-pickup sewing).
             */
            $this->inventory->reverseBySource(
                originalSourceTypes: [
                    'cutting_wip',
                    'cutting_reject',

                    // ✅ kalau kamu pakai QC Adjustment, ini bikin cancel lebih bersih
                    'cutting_qc_adjust_in',
                    'cutting_qc_adjust_out',
                ],
                originalSourceId: $job->id,
                voidSourceType: 'cutting_qc_void',
                voidSourceId: $job->id,
                notesPrefix: "VOID QC CUTTING {$job->code}",
                date: now(), // atau pakai tanggal cancel
            );

            // 1b) Void jurnal hasil QC cutting supaya akuntansi ikut bersih.
            //     (Sebelumnya hanya stok yang dibalik; jurnal cutting_wip tertinggal aktif.)
            //     voidBySource idempotent → aman diulang; postCuttingWip akan bikin
            //     jurnal baru saat QC ulang.
            $this->journal->voidBySource(JournalService::SRC_CUTTING_WIP, (int) $job->id, "VOID QC Cutting {$job->code}");

            // 2) Reset bundle QC fields supaya bisa QC ulang
            $job->loadMissing(['bundles']);

            foreach ($job->bundles as $bundle) {
                $bundle->qty_qc_ok = 0;
                $bundle->qty_qc_reject = 0;
                $bundle->status = 'cut';

                // ✅ konsistensi baru:
                // wip_qty selalu = qty_ok, jadi reset ke 0
                $bundle->wip_qty = 0;

                // reset info WIP posting
                $bundle->wip_warehouse_id = null;
                $bundle->wip_posted_at = null; // ✅ ini yang baru & wajib

                // reset kolom cutting-WIP juga (otoritatif untuk Ambil Jahit)
                $bundle->cut_wip_qty = 0;
                $bundle->cut_wip_warehouse_id = null;

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

    /**
     * Batalkan QC hanya untuk satu bundle yang belum pernah diambil jahit.
     *
     * Reversal stok ditag dengan id audit partial-cancel, bukan id job,
     * sehingga bundle lain dalam Cutting Job yang sama tidak ikut tersentuh.
     */
    public function cancelCuttingBundleQc(
        CuttingJobBundle $bundle,
        string $reason,
        ?int $actorId = null,
    ): void {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException('Alasan pembatalan QC wajib diisi.');
        }

        DB::transaction(function () use ($bundle, $reason, $actorId) {
            $bundle = CuttingJobBundle::query()
                ->whereKey($bundle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $job = CuttingJob::query()
                ->whereKey($bundle->cutting_job_id)
                ->lockForUpdate()
                ->firstOrFail();

            $activePickedQty = (float) SewingPickupLine::query()
                ->where('cutting_job_bundle_id', $bundle->id)
                ->where('status', '!=', 'void')
                ->sum('qty_bundle');

            if ($activePickedQty > 0.000001 || (float) ($bundle->sewing_picked_qty ?? 0) > 0.000001) {
                throw new \RuntimeException(
                    "Bundle {$bundle->bundle_code} tidak bisa dibatalkan: sudah ada qty yang diambil jahit."
                );
            }

            $qc = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $job->id)
                ->where('cutting_job_bundle_id', $bundle->id)
                ->lockForUpdate()
                ->first();

            if (!$qc) {
                throw new \RuntimeException("Bundle {$bundle->bundle_code} belum memiliki QC Cutting aktif.");
            }

            $qtyOkBefore = (float) $qc->qty_ok;
            $qtyRejectBefore = (float) $qc->qty_reject;

            $qcMutations = InventoryMutation::query()
                ->whereIn('source_type', [
                    'cutting_wip',
                    'cutting_reject',
                    'cutting_qc_adjust_in',
                    'cutting_qc_adjust_out',
                ])
                ->where('source_id', $job->id)
                ->where('cutting_job_bundle_id', $bundle->id)
                ->lockForUpdate()
                ->get();

            if ($qcMutations->contains(fn (InventoryMutation $mutation) => str_starts_with(
                (string) $mutation->source_type,
                'cutting_qc_adjust_'
            ))) {
                throw new \RuntimeException(
                    "Bundle {$bundle->bundle_code} sudah pernah di-Adjust QC. "
                    . 'Batalkan adjustment tersebut terlebih dahulu atau gunakan Cancel QC penuh.'
                );
            }

            $okCost = round((float) $qcMutations
                ->where('source_type', 'cutting_wip')
                ->where('qty_change', '>', 0)
                ->sum('total_cost'), 2);
            $rejectCost = round((float) $qcMutations
                ->where('source_type', 'cutting_reject')
                ->where('qty_change', '>', 0)
                ->sum('total_cost'), 2);
            $selectedOutputCost = round($okCost + $rejectCost, 2);

            $allOutputCost = round((float) InventoryMutation::query()
                ->whereIn('source_type', ['cutting_wip', 'cutting_reject'])
                ->where('source_id', $job->id)
                ->where('qty_change', '>', 0)
                ->sum('total_cost'), 2);
            $rawCost = round(abs((float) InventoryMutation::query()
                ->where('source_type', 'cutting_job')
                ->where('source_id', $job->id)
                ->where('qty_change', '<', 0)
                ->sum('total_cost')), 2);

            // Ikuti normalisasi yang dipakai JournalService::postCuttingWip.
            $journalRawCost = $rawCost;
            $journalLabor = round($allOutputCost - $journalRawCost, 2);
            if ($journalLabor <= 0.01) {
                $journalRawCost = $allOutputCost;
                $journalLabor = 0.0;
            }

            $share = $allOutputCost > 0.000001
                ? min(max($selectedOutputCost / $allOutputCost, 0), 1)
                : 0.0;
            $rawCostShare = round(min($journalRawCost * $share, $selectedOutputCost), 2);
            // Paksa debit reversal tetap sama persis dengan credit output
            // setelah pembulatan 2 desimal, supaya jurnal tidak selisih 0.01.
            $laborCostShare = round(max($selectedOutputCost - $rawCostShare, 0), 2);

            $audit = CuttingQcCancellation::create([
                'cutting_job_id' => $job->id,
                'cutting_job_bundle_id' => $bundle->id,
                'qc_result_id' => $qc->id,
                'item_id' => $bundle->finished_item_id,
                'qty_ok' => $qtyOkBefore,
                'qty_reject' => $qtyRejectBefore,
                'ok_cost' => $okCost,
                'reject_cost' => $rejectCost,
                'raw_cost_share' => $rawCostShare,
                'labor_cost_share' => $laborCostShare,
                'reason' => $reason,
                'cancelled_by' => $actorId ?: auth()->id(),
                'cancelled_at' => now(),
            ]);

            $reversalMutationIds = [];
            foreach ($qcMutations as $mutation) {
                $reversal = $this->inventory->adjustByDifference(
                    warehouseId: (int) $mutation->warehouse_id,
                    itemId: (int) $mutation->item_id,
                    qtyChange: -((float) $mutation->qty_change),
                    date: now(),
                    sourceType: 'cutting_qc_bundle_void',
                    sourceId: (int) $audit->id,
                    notes: "Partial VOID QC {$job->code} / {$bundle->bundle_code} | reverse mut#{$mutation->id}",
                    lotId: $mutation->lot_id ? (int) $mutation->lot_id : null,
                    allowNegative: false,
                    unitCostOverride: $mutation->unit_cost !== null ? (float) $mutation->unit_cost : null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );

                if ($reversal?->id) {
                    $reversalMutationIds[] = (int) $reversal->id;
                }
            }

            $audit->update([
                'reversal_cutoff_mutation_id' => !empty($reversalMutationIds)
                    ? max($reversalMutationIds)
                    : (int) InventoryMutation::max('id'),
                'metadata' => [
                    'original_mutation_ids' => $qcMutations->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                    'reversal_mutation_ids' => $reversalMutationIds,
                    'active_picked_qty_at_cancel' => $activePickedQty,
                ],
            ]);

            if ($selectedOutputCost > 0.01) {
                $journal = $this->journal->post(
                    date: now()->toDateString(),
                    sourceType: 'cutting_qc_bundle_void',
                    sourceId: (int) $audit->id,
                    description: "Partial VOID QC {$job->code} — {$bundle->bundle_code}",
                    lines: $this->buildBundleCancelJournalLines(
                        okCost: $okCost,
                        rejectCost: $rejectCost,
                        rawCostShare: $rawCostShare,
                        laborCostShare: $laborCostShare,
                    ),
                    meta: [
                        'reference_no' => $bundle->bundle_code,
                        'notes' => $reason,
                        'created_by' => $actorId ?: auth()->id(),
                    ],
                );
                $audit->update(['reversal_journal_id' => $journal->id]);
            }

            $bundle->qty_qc_ok = 0;
            $bundle->qty_qc_reject = 0;
            $bundle->status = 'cut';
            $bundle->wip_qty = 0;
            $bundle->wip_warehouse_id = null;
            $bundle->wip_posted_at = null;
            $bundle->cut_wip_qty = 0;
            $bundle->cut_wip_warehouse_id = null;
            $bundle->save();

            $qc->delete();

            $totalBundles = $job->bundles()->count();
            $doneCount = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $job->id)
                ->distinct('cutting_job_bundle_id')
                ->count('cutting_job_bundle_id');

            $job->update([
                'status' => $doneCount >= $totalBundles ? 'qc_done' : 'sent_to_qc',
                'updated_by' => $actorId ?: auth()->id(),
            ]);

            ProductionLog::record(
                event: 'qc_bundle_cancelled',
                summary: "Partial VOID QC {$job->code} — {$bundle->bundle_code}",
                meta: [
                    'cutting_job_id' => (int) $job->id,
                    'bundle_id' => (int) $bundle->id,
                    'item_id' => (int) $bundle->finished_item_id,
                    'qty_ok' => $qtyOkBefore,
                    'qty_reject' => $qtyRejectBefore,
                    'reason' => $reason,
                    'audit_id' => (int) $audit->id,
                ],
                sourceType: CuttingQcCancellation::class,
                sourceId: (int) $audit->id,
                reference: $bundle->bundle_code,
            );
        });
    }

    /**
     * Post jurnal tambahan ketika bundle yang pernah partial-cancel di-QC ulang.
     * Journal QC job lama tetap aktif karena bundle lain masih dipakai sewing.
     */
    public function postReopenedCuttingQcJournals(CuttingJob $job, string $qcDate): void
    {
        $cancellations = CuttingQcCancellation::query()
            ->where('cutting_job_id', $job->id)
            ->whereNull('reprocessed_at')
            ->lockForUpdate()
            ->get();

        foreach ($cancellations as $audit) {
            if (($audit->metadata['closed_by_full_cancel'] ?? false) === true) {
                continue;
            }

            $qc = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $job->id)
                ->where('cutting_job_bundle_id', $audit->cutting_job_bundle_id)
                ->latest('id')
                ->first();

            if (!$qc) {
                continue;
            }

            $newMutations = InventoryMutation::query()
                ->whereIn('source_type', ['cutting_wip', 'cutting_reject'])
                ->where('source_id', $job->id)
                ->where('cutting_job_bundle_id', $audit->cutting_job_bundle_id)
                ->where('id', '>', (int) ($audit->reversal_cutoff_mutation_id ?? 0))
                ->where('qty_change', '>', 0)
                ->lockForUpdate()
                ->get();

            $okCost = round((float) $newMutations
                ->where('source_type', 'cutting_wip')
                ->sum('total_cost'), 2);
            $rejectCost = round((float) $newMutations
                ->where('source_type', 'cutting_reject')
                ->sum('total_cost'), 2);
            $outputCost = round($okCost + $rejectCost, 2);

            if ($outputCost <= 0.01) {
                continue;
            }

            // Kalau item pengganti punya cost lebih rendah, gunakan output
            // aktual sebagai batas credit raw agar jurnal tetap balance.
            $rawCredit = min(max((float) $audit->raw_cost_share, 0), $outputCost);
            $laborCredit = round($outputCost - $rawCredit, 2);

            $journal = $this->journal->post(
                date: $qcDate,
                sourceType: 'cutting_qc_bundle_repost',
                sourceId: (int) $audit->id,
                description: "Re-QC partial {$job->code} — {$audit->bundle?->bundle_code}",
                lines: $this->buildBundleRepostJournalLines(
                    okCost: $okCost,
                    rejectCost: $rejectCost,
                    rawCredit: $rawCredit,
                    laborCredit: $laborCredit,
                ),
                meta: [
                    'reference_no' => $audit->bundle?->bundle_code,
                    'notes' => 'Re-QC setelah partial cancel QC',
                    'created_by' => auth()->id(),
                ],
            );

            $audit->update([
                'repost_journal_id' => $journal->id,
                'reprocessed_qc_result_id' => $qc->id,
                'reprocessed_at' => now(),
            ]);
        }
    }

    /**
     * Ganti item finished good pada bundle yang sudah di-partial-cancel.
     * Tidak mengubah qty atau konsumsi kain; bundle akan memakai item baru
     * saat QC ulang dan posting WIP berikutnya.
     */
    public function updateCuttingBundleItem(
        CuttingJobBundle $bundle,
        string $itemCode,
        ?int $actorId = null,
    ): void {
        $itemCode = strtoupper(trim($itemCode));
        if ($itemCode === '') {
            throw new \RuntimeException('Kode item baru wajib diisi.');
        }

        DB::transaction(function () use ($bundle, $itemCode, $actorId) {
            $bundle = CuttingJobBundle::query()
                ->whereKey($bundle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $job = CuttingJob::query()
                ->whereKey($bundle->cutting_job_id)
                ->lockForUpdate()
                ->firstOrFail();

            $activePickedQty = (float) SewingPickupLine::query()
                ->where('cutting_job_bundle_id', $bundle->id)
                ->where('status', '!=', 'void')
                ->sum('qty_bundle');

            if ($activePickedQty > 0.000001 || (float) ($bundle->sewing_picked_qty ?? 0) > 0.000001) {
                throw new \RuntimeException(
                    "Bundle {$bundle->bundle_code} tidak bisa diubah: sudah ada qty yang diambil jahit."
                );
            }

            $cancellation = CuttingQcCancellation::query()
                ->where('cutting_job_id', $job->id)
                ->where('cutting_job_bundle_id', $bundle->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$cancellation) {
                throw new \RuntimeException(
                    "Bundle {$bundle->bundle_code} harus di-Partial Cancel QC terlebih dahulu."
                );
            }

            $activeQc = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $job->id)
                ->where('cutting_job_bundle_id', $bundle->id)
                ->exists();

            if ($activeQc || (float) ($bundle->cut_wip_qty ?? 0) > 0.000001) {
                throw new \RuntimeException(
                    "Bundle {$bundle->bundle_code} harus belum memiliki QC/WIP aktif saat item diubah."
                );
            }

            $item = Item::query()
                ->whereRaw('UPPER(code) = ?', [$itemCode])
                ->where('type', 'finished_good')
                ->canBeMade()
                ->first();

            if (!$item) {
                throw new \RuntimeException(
                    "Item {$itemCode} tidak ditemukan atau belum dikonfigurasi sebagai item produksi."
                );
            }

            if (!DB::table('item_boms')
                ->where('item_id', $item->id)
                ->where('active', true)
                ->exists()) {
                throw new \RuntimeException("Item {$itemCode} belum memiliki BOM aktif.");
            }

            $oldItem = Item::query()->find($bundle->finished_item_id);
            $oldItemId = (int) ($bundle->finished_item_id ?? 0);
            if ($oldItemId === (int) $item->id) {
                throw new \RuntimeException("Bundle {$bundle->bundle_code} sudah menggunakan item {$itemCode}.");
            }

            $metadata = $cancellation->metadata ?? [];
            $itemChanges = $metadata['item_changes'] ?? [];
            $itemChanges[] = [
                'from_item_id' => $oldItemId ?: null,
                'from_item_code' => $oldItem?->code,
                'to_item_id' => (int) $item->id,
                'to_item_code' => $item->code,
                'changed_by' => $actorId ?: auth()->id(),
                'changed_at' => now()->toISOString(),
            ];

            $cancellation->update([
                'metadata' => array_merge($metadata, [
                    'item_changes' => $itemChanges,
                    'latest_item_id' => (int) $item->id,
                ]),
            ]);

            $bundle->update([
                'finished_item_id' => $item->id,
                'item_category_id' => $item->item_category_id,
                'status' => 'cut',
                'qty_qc_ok' => 0,
                'qty_qc_reject' => 0,
            ]);

            ProductionLog::record(
                event: 'qc_bundle_item_changed',
                summary: "Item bundle {$bundle->bundle_code}: " . ($oldItem?->code ?? '-') . " → {$item->code}",
                meta: [
                    'cutting_job_id' => (int) $job->id,
                    'bundle_id' => (int) $bundle->id,
                    'partial_cancel_id' => (int) $cancellation->id,
                    'from_item_id' => $oldItemId ?: null,
                    'from_item_code' => $oldItem?->code,
                    'to_item_id' => (int) $item->id,
                    'to_item_code' => $item->code,
                ],
                sourceType: CuttingJobBundle::class,
                sourceId: (int) $bundle->id,
                reference: $bundle->bundle_code,
            );
        });
    }

    private function journalAccountId(string $code): int
    {
        return (int) DB::table('accounts')->where('code', $code)->value('id');
    }

    private function buildBundleCancelJournalLines(
        float $okCost,
        float $rejectCost,
        float $rawCostShare,
        float $laborCostShare,
    ): array {
        $lines = [];
        if ($rawCostShare > 0.01) {
            $lines[] = ['account_id' => $this->journalAccountId(JournalService::CODE_INV_WIP), 'debit' => $rawCostShare, 'credit' => 0];
        }
        if ($laborCostShare > 0.01) {
            $lines[] = ['account_id' => $this->journalAccountId(JournalService::CODE_PAYROLL_PAYABLE), 'debit' => $laborCostShare, 'credit' => 0];
        }
        if ($okCost > 0.01) {
            $lines[] = ['account_id' => $this->journalAccountId(JournalService::CODE_INV_WIP), 'debit' => 0, 'credit' => $okCost];
        }
        if ($rejectCost > 0.01) {
            $lines[] = ['account_id' => $this->journalAccountId(JournalService::CODE_INV_DEFECT), 'debit' => 0, 'credit' => $rejectCost];
        }

        return $lines;
    }

    private function buildBundleRepostJournalLines(
        float $okCost,
        float $rejectCost,
        float $rawCredit,
        float $laborCredit,
    ): array {
        $lines = [];
        if ($okCost > 0.01) {
            $lines[] = ['account_id' => $this->journalAccountId(JournalService::CODE_INV_WIP), 'debit' => $okCost, 'credit' => 0];
        }
        if ($rejectCost > 0.01) {
            $lines[] = ['account_id' => $this->journalAccountId(JournalService::CODE_INV_DEFECT), 'debit' => $rejectCost, 'credit' => 0];
        }
        if ($rawCredit > 0.01) {
            $lines[] = ['account_id' => $this->journalAccountId(JournalService::CODE_INV_WIP), 'debit' => 0, 'credit' => $rawCredit];
        }
        if ($laborCredit > 0.01) {
            $lines[] = ['account_id' => $this->journalAccountId(JournalService::CODE_PAYROLL_PAYABLE), 'debit' => 0, 'credit' => $laborCredit];
        }

        return $lines;
    }

    public function adjustCuttingBundleQc(
        CuttingJobBundle $bundle,
        string $qcDate,
        float $newOk,
        float $newReject = 0,
        ?int $operatorId = null,
        ?int $qcByUserId = null,
        ?string $rejectReason = null,
        ?string $notes = null,
    ): void {
        DB::transaction(function () use ($bundle, $qcDate, $newOk, $newReject, $operatorId, $qcByUserId, $rejectReason, $notes) {

            // lock bundle + job supaya aman dari race condition
            $bundle = CuttingJobBundle::query()
                ->whereKey($bundle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $job = CuttingJob::query()
                ->whereKey($bundle->cutting_job_id)
                ->lockForUpdate()
                ->firstOrFail();

            // ✅ adjust hanya boleh jika sudah pernah posting WIP dari QC
            if (empty($bundle->wip_posted_at)) {
                throw new \RuntimeException('Bundle belum pernah posting WIP (wip_posted_at kosong). Lakukan QC normal dulu.');
            }

            // normalisasi
            $newOk = max(0, (float) $newOk);
            $newReject = max(0, (float) $newReject);

            $bundleQty = (float) ($bundle->qty_pcs ?? 0);

            // jaga-jaga: OK + Reject tidak boleh melebihi qty_pcs
            if ($newOk + $newReject > $bundleQty) {
                throw new \RuntimeException("QC tidak valid: OK({$newOk}) + Reject({$newReject}) > qty_pcs({$bundleQty}).");
            }

            // ambil QC record existing (kalau ada)
            $qc = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $job->id)
                ->where('cutting_job_bundle_id', $bundle->id)
                ->lockForUpdate()
                ->first();

            $oldOk = (float) ($qc?->qty_ok ?? $bundle->qty_qc_ok ?? 0);
            $oldReject = (float) ($qc?->qty_reject ?? $bundle->qty_qc_reject ?? 0);

            $deltaOk = $newOk - $oldOk; // (+) tambah WIP, (-) kurangi WIP
            $deltaReject = $newReject - $oldReject; // (+) tambah REJ, (-) kurangi REJ

            // warehouses
            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
            $rejCutWarehouseId = Warehouse::where('code', 'REJ-CUT')->value('id');

            if (!$wipCutWarehouseId || !$rejCutWarehouseId) {
                throw new \RuntimeException('Warehouse WIP-CUT / REJ-CUT belum dikonfigurasi.');
            }

            $itemId = (int) $bundle->finished_item_id;
            if ($itemId <= 0) {
                throw new \RuntimeException('finished_item_id bundle kosong.');
            }

            // gunakan wip_warehouse_id bundle jika ada
            $bundleWipWarehouseId = $bundle->wip_warehouse_id ?: $wipCutWarehouseId;

            // =========================
            // 1) ADJUST STOCK WIP-CUT (OK)
            // =========================
            if ($deltaOk > 0) {
                $this->inventory->stockIn(
                    warehouseId: $bundleWipWarehouseId,
                    itemId: $itemId,
                    qty: $deltaOk,
                    date: $qcDate,
                    sourceType: 'cutting_qc_adjust_in',
                    sourceId: $job->id,
                    notes: "QC Adjust +OK {$deltaOk} pcs (bundle {$bundle->bundle_code}, job {$job->code})",
                    lotId: null,
                    unitCost: null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );
            } elseif ($deltaOk < 0) {
                // kalau sudah kepakai sewing, stok WIP-CUT bisa tidak cukup → InventoryService akan throw
                $this->inventory->stockOut(
                    warehouseId: $bundleWipWarehouseId,
                    itemId: $itemId,
                    qty: abs($deltaOk),
                    date: $qcDate,
                    sourceType: 'cutting_qc_adjust_out',
                    sourceId: $job->id,
                    notes: "QC Adjust -OK " . abs($deltaOk) . " pcs (bundle {$bundle->bundle_code}, job {$job->code})",
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );
            }

            // =========================
            // 2) ADJUST STOCK REJ-CUT (Reject)
            // =========================
            if ($deltaReject > 0) {
                $this->inventory->stockIn(
                    warehouseId: $rejCutWarehouseId,
                    itemId: $itemId,
                    qty: $deltaReject,
                    date: $qcDate,
                    sourceType: 'cutting_qc_adjust_in',
                    sourceId: $job->id,
                    notes: "QC Adjust +REJ {$deltaReject} pcs (bundle {$bundle->bundle_code}, job {$job->code})",
                    lotId: null,
                    unitCost: null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );
            } elseif ($deltaReject < 0) {
                $this->inventory->stockOut(
                    warehouseId: $rejCutWarehouseId,
                    itemId: $itemId,
                    qty: abs($deltaReject),
                    date: $qcDate,
                    sourceType: 'cutting_qc_adjust_out',
                    sourceId: $job->id,
                    notes: "QC Adjust -REJ " . abs($deltaReject) . " pcs (bundle {$bundle->bundle_code}, job {$job->code})",
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );
            }

            // =========================
            // 3) UPDATE QC RESULT + BUNDLE
            // =========================
            $status = $this->resolveBundleStatus($newOk, $newReject, $bundleQty);

            QcResult::updateOrCreate(
                [
                    'stage' => QcResult::STAGE_CUTTING,
                    'cutting_job_id' => $job->id,
                    'cutting_job_bundle_id' => $bundle->id,
                ],
                [
                    'qc_date' => $qcDate,
                    'qty_ok' => $newOk,
                    'qty_reject' => $newReject,
                    'reject_reason' => $rejectReason,
                    'operator_id' => $operatorId,
                    'qc_by_user_id' => $qcByUserId,
                    'status' => $status,
                    'notes' => $notes,
                ],
            );

            // ✅ aturan baru: wip_qty selalu sama dengan qty_ok
            $bundle->qty_qc_ok = $newOk;
            $bundle->qty_qc_reject = $newReject;
            $bundle->status = $status;
            $bundle->wip_qty = $newOk;

            // Sinkronkan kolom cutting-WIP (otoritatif untuk Ambil Jahit).
            // cut WIP selalu di WIP-CUT (invarian dijaga model guard).
            $bundle->cut_wip_qty = $newOk;
            if (empty($bundle->cut_wip_warehouse_id)) {
                $bundle->cut_wip_warehouse_id = $wipCutWarehouseId;
            }
            $bundle->save();

            // header status tetap qc_done
            if ($job->status !== 'qc_done') {
                $job->update(['status' => 'qc_done']);
            }

            // =========================
            // 4) SYNC PAYROLL (opsional)
            // =========================
            // Kalau payroll kamu sudah punya service sync, panggil di sini.
            // Contoh:
            // app(\App\Services\Payroll\PieceworkPayrollService::class)->syncCuttingFromQc($job, $qcDate);
        });
    }

}
