<?php

namespace App\Services\Production;

use App\Models\CuttingJob;

class HppService
{
    /**
     * Hitung HPP berdasarkan 1 CuttingJob.
     *
     * - Kombinasi fabric_usage_cost + labour (cutting_job_labours)
     * - Alokasi per finished_item_id (K7BLK, K5BLK, dst.)
     *
     * Return bentuk array:
     * [
     *   'job_id'   => 1,
     *   'job_code' => 'CUT-20251123-001',
     *   'summary'  => [
     *       'total_qty'           => 80,
     *       'total_fabric_cost'   => 300000,
     *       'total_labour_cost'   => 40000,
     *       'total_overhead_cost' => 0,
     *       'total_hpp'           => 340000,
     *       'hpp_per_pcs'         => 4250,
     *   ],
     *   'items' => [
     *       [
     *           'finished_item_id'   => 100,
     *           'finished_item_code' => 'K7BLK',
     *           'finished_item_name' => 'KAOS K7 HITAM',
     *           'size_label'         => '40',
     *           'qty'                => 40,
     *           'fabric_cost'        => 170000,
     *           'labour_cost'        => 20000,
     *           'overhead_cost'      => 0,
     *           'hpp_total'          => 190000,
     *           'hpp_per_pcs'        => 4750,
     *       ],
     *       ...
     *   ],
     * ]
     */
    public function calculateForCuttingJob(CuttingJob $job, float $overheadPerPcs = 0.0): array
    {
        // Pastikan relasi ter-load
        $job->loadMissing([
            'details.finishedItem',
            'bundles',
            'labours',
        ]);

        $totalQty = (float) $job->total_output_pcs;

        // Kalau total qty 0, nggak bisa hitung HPP
        if ($totalQty <= 0) {
            return [
                'job_id' => $job->id,
                'job_code' => $job->code,
                'summary' => [
                    'total_qty' => 0,
                    'total_fabric_cost' => 0,
                    'total_labour_cost' => 0,
                    'total_overhead_cost' => 0,
                    'total_hpp' => 0,
                    'hpp_per_pcs' => 0,
                ],
                'items' => [],
            ];
        }

        // 1. Total biaya kain
        $totalFabricCost = (float) ($job->fabric_usage_cost ?? 0);

        // 2. Total biaya tenaga kerja (jumlah semua labour cost)
        $totalLabourCost = (float) $job->labours->sum('total_cost');

        // 3. Total overhead (pakai rate per pcs kalau diisi)
        $totalOverheadCost = $overheadPerPcs > 0
        ? $overheadPerPcs * $totalQty
        : 0.0;

        // 4. Total HPP
        $totalHpp = $totalFabricCost + $totalLabourCost + $totalOverheadCost;

        // 5. HPP per pcs (global job)
        $hppPerPcs = $totalHpp / $totalQty;

        // 6. Alokasi per item (detail)
        $items = [];
        foreach ($job->details as $detail) {
            $itemQty = (float) $detail->total_pcs;
            if ($itemQty <= 0) {
                continue;
            }

            $share = $itemQty / $totalQty; // proporsi terhadap total job

            $itemFabricCost = $totalFabricCost * $share;
            $itemLabourCost = $totalLabourCost * $share;
            $itemOverheadCost = $totalOverheadCost * $share;
            $itemTotalHpp = $itemFabricCost + $itemLabourCost + $itemOverheadCost;
            $itemHppPerPcs = $itemTotalHpp / $itemQty;

            $items[] = [
                'finished_item_id' => $detail->finished_item_id,
                'finished_item_code' => $detail->finishedItem?->code,
                'finished_item_name' => $detail->finishedItem?->name,
                'size_label' => $detail->size_label,
                'qty' => $itemQty,
                'fabric_cost' => round($itemFabricCost, 2),
                'labour_cost' => round($itemLabourCost, 2),
                'overhead_cost' => round($itemOverheadCost, 2),
                'hpp_total' => round($itemTotalHpp, 2),
                'hpp_per_pcs' => round($itemHppPerPcs, 2),
            ];
        }

        return [
            'job_id' => $job->id,
            'job_code' => $job->code,
            'summary' => [
                'total_qty' => $totalQty,
                'total_fabric_cost' => round($totalFabricCost, 2),
                'total_labour_cost' => round($totalLabourCost, 2),
                'total_overhead_cost' => round($totalOverheadCost, 2),
                'total_hpp' => round($totalHpp, 2),
                'hpp_per_pcs' => round($hppPerPcs, 2),
            ],
            'items' => $items,
        ];
    }
}
