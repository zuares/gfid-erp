<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\Employee;
use App\Models\InventoryStock;
use App\Models\QcResult;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QcController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Form QC untuk satu Cutting Job (stage = cutting)
     */
    public function editCutting(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'bundles.finishedItem',
            'bundles.qcResults',
        ]);

        // QC existing per bundle (1 row per bundle, stage=cutting, karena kita pakai updateOrCreate)
        $existingQc = QcResult::query()
            ->where('stage', 'cutting')
            ->where('cutting_job_id', $cuttingJob->id)
            ->get()
            ->keyBy('cutting_job_bundle_id');

        $rows = [];
        foreach ($cuttingJob->bundles as $bundle) {
            $qc = $existingQc->get($bundle->id);

            $rows[] = [
                'bundle_id' => $bundle->id,
                'bundle_no' => $bundle->bundle_no,
                'bundle_code' => $bundle->bundle_code,
                'item_code' => $bundle->finishedItem?->code,
                'qty_pcs' => $bundle->qty_pcs,
                'status' => $bundle->status, // status bundle (cut / qc_ok / qc_mixed / qc_reject)
                'qty_ok' => $qc?->qty_ok ?? $bundle->qty_pcs, // default semua OK
                'qty_reject' => $qc?->qty_reject ?? 0,
                'notes' => $qc?->notes ?? null,
            ];
        }

        // Operator QC
        // sementara: kalau belum ada role 'qc', tetap pakai role 'cutting'
        $operators = Employee::query()
            ->whereIn('role', ['qc', 'cutting']) // nanti bisa ganti hanya ['qc']
            ->orderBy('code')
            ->get();

        $hasQcCutting = $existingQc->isNotEmpty();

        return view('production.qc.cutting_edit', [
            'job' => $cuttingJob,
            'rows' => $rows,
            'operators' => $operators,
            'hasQcCutting' => $hasQcCutting,
        ]);
    }

    /**
     * Simpan hasil QC Cutting
     */
    public function updateCutting(Request $request, CuttingJob $cuttingJob)
    {
        $validated = $request->validate([
            'qc_date' => ['required', 'date'],
            'operator_id' => ['nullable', 'exists:employees,id'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'results.*.qty_reject' => ['nullable', 'integer', 'min:0'],
            'results.*.notes' => ['nullable', 'string'],
        ]);

        // simpan status lama untuk deteksi "posting pertama kali"
        $oldStatus = $cuttingJob->status;

        DB::transaction(function () use ($validated, $cuttingJob, $oldStatus) {

            $qcDate = $validated['qc_date'];
            $operatorId = $validated['operator_id'] ?? null;

            // mapping bundle
            $bundleMap = $cuttingJob->bundles()->get()->keyBy('id');
            $bundleOkMap = [];

            foreach ($validated['results'] as $row) {

                /** @var \App\Models\CuttingJobBundle|null $bundle */
                $bundle = $bundleMap->get((int) $row['bundle_id']);
                if (!$bundle) {
                    continue;
                }

                $qtyBundle = (int) $bundle->qty_pcs;
                $rej = max(0, (int) ($row['qty_reject'] ?? 0));
                if ($rej > $qtyBundle) {
                    $rej = $qtyBundle;
                }

                $ok = $qtyBundle - $rej;

                // status QC per bundle
                if ($ok > 0 && $rej === 0) {
                    $qcStatus = 'ok';
                    $bundleStatus = 'qc_ok';
                } elseif ($ok === 0 && $rej > 0) {
                    $qcStatus = 'reject';
                    $bundleStatus = 'qc_reject';
                } else {
                    $qcStatus = 'mixed';
                    $bundleStatus = 'qc_mixed';
                }

                // save / update QC
                QcResult::updateOrCreate(
                    [
                        'stage' => 'cutting',
                        'cutting_job_id' => $cuttingJob->id,
                        'cutting_job_bundle_id' => $bundle->id,
                    ],
                    [
                        'qc_date' => $qcDate,
                        'qty_ok' => $ok,
                        'qty_reject' => $rej,
                        'operator_id' => $operatorId,
                        'status' => $qcStatus,
                        'notes' => $row['notes'] ?? null,
                    ]
                );

                // update status bundle
                $bundle->update(['status' => $bundleStatus]);

                // simpan qty OK per bundle untuk stockIn WIP
                $bundleOkMap[$bundle->id] = $ok;
            }

            // STATUS JOB GLOBAL
            $statuses = $cuttingJob->bundles()->pluck('status');

            if ($statuses->every(fn($s) => $s === 'qc_ok')) {
                $cuttingJob->status = 'qc_ok';
            } elseif ($statuses->every(fn($s) => $s === 'qc_reject')) {
                $cuttingJob->status = 'qc_reject';
            } elseif ($statuses->contains('qc_ok') && $statuses->contains('qc_reject')) {
                $cuttingJob->status = 'qc_mixed';
            } else {
                // masih ada yang 'cut' atau kombinasi lain
                $cuttingJob->status = 'cut';
            }

            $cuttingJob->save();

            /**
             * ==========================
             *   INVENTORY MUTATION
             * ==========================
             *
             * Jalan hanya SEKALI:
             * - kalau status lama masih "belum pernah dipost" (cut/null/'')
             * - dan status baru SUDAH bukan 'cut'
             */
            if (
                in_array($oldStatus, ['cut', null, ''], true)
                && $cuttingJob->status !== 'cut'
            ) {
                //---------------------------------------------------
                // 1) STOCK OUT LOT (buang semua saldo LOT di gudang job)
                //---------------------------------------------------
                $cuttingJob->loadMissing('lot');
                $lot = $cuttingJob->lot;

                if ($lot) {
                    // total saldo LOT di gudang job ini
                    $qtyLot = (float) InventoryStock::query()
                        ->where('lot_id', $lot->id)
                        ->where('warehouse_id', $cuttingJob->warehouse_id)
                        ->sum('qty_balance');

                    if ($qtyLot > 0) {
                        $this->inventory->stockOut(
                            $cuttingJob->warehouse_id,
                            $lot->item_id,
                            $qtyLot,
                            $qcDate,
                            'qc_cutting_lot',
                            $cuttingJob->id,
                            'Pemakaian LOT untuk Cutting Job ' . $cuttingJob->code,
                            $lot->id,
                        );
                    }
                }

                //---------------------------------------------------
                // 2) STOCK IN WIP CUTTING (per bundle OK)
                //---------------------------------------------------
                $wip = Warehouse::where('code', 'WIP-CUT')->first();

                if ($wip) {
                    foreach ($bundleOkMap as $bundleId => $okQty) {
                        if ($okQty <= 0) {
                            continue;
                        }

                        /** @var \App\Models\CuttingJobBundle|null $bundle */
                        $bundle = $bundleMap->get($bundleId);
                        if (!$bundle) {
                            continue;
                        }

                        $this->inventory->stockIn(
                            $wip->id,
                            $bundle->finished_item_id,
                            $okQty,
                            $qcDate,
                            'qc_cutting_ok',
                            $cuttingJob->id,
                            'Hasil QC Cutting ' . $bundle->bundle_code,
                            null, // WIP tidak pakai LOT
                        );
                    }
                }
            }
        });

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'QC Cutting berhasil disimpan.');
    }

}
