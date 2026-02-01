<?php

namespace App\Services\Production;

use App\Models\FinishingJob;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Validation\ValidationException;

class FinishingBomService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Apply BOM untuk Finishing POST:
     * - Dijalankan di dalam DB::transaction() pada FinishingJobController@post
     * - Idempotent PER LINE pakai finishing_job_lines.bom_applied_at
     * - Potong bahan pendukung dari RM
     * - SKIP kain utama (FLC) = bundle->cuttingJob->fabric_item_id (karena sudah dipotong via LOT di Cutting)
     *
     * sourceType inventory mutation: 'finishing_bom'
     * sourceId: finishing_job_lines.id
     */
    public function applySupOnlyForPostedJob(FinishingJob $job, \DateTimeInterface $movementDate): void
    {
        $job->loadMissing([
            'lines',
            'lines.bundle.cuttingJob', // ✅ untuk fabric_item_id (skip FLC)
        ]);

        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        if (!$rmWarehouseId) {
            throw new \RuntimeException('Warehouse RM belum dikonfigurasi (code = RM).');
        }

        foreach ($job->lines as $line) {
            // ✅ idempotent per line
            if (!empty($line->bom_applied_at)) {
                continue;
            }

            $fgItemId = (int) ($line->item_id ?? 0);
            $qtyOk = (float) ($line->qty_ok ?? 0);

            // kalau qty_ok 0: tandai saja biar tidak diproses ulang
            if ($fgItemId <= 0 || $qtyOk <= 0) {
                $line->bom_applied_at = now();
                $line->save();
                continue;
            }

            // cari BOM aktif untuk SKU FG
            $bom = ItemBom::where('item_id', $fgItemId)
                ->where('active', true)
                ->first();

            if (!$bom) {
                // ⛔ sementara: skip BOM SUP kalau belum ada
                $line->bom_applied_at = now();
                $line->save();

                // optional: logging biar bisa diaudit
                // \Log::warning("FIN {$job->code} line {$line->id}: BOM belum ada untuk item {$fgItemId}");

                continue;
            }

            // ✅ kain utama sudah dipotong di Cutting (LOT)
            $excludeFLC = (int) ($line->bundle?->cuttingJob?->fabric_item_id ?? 0);

            $bomLines = ItemBomLine::where('item_bom_id', $bom->id)
                ->orderBy('sort_order')
                ->get();

            if ($bomLines->isEmpty()) {
                throw ValidationException::withMessages([
                    'bom' => "BOM item_id={$fgItemId} tidak punya lines (FIN {$job->code}, line {$line->id}).",
                ]);
            }

            foreach ($bomLines as $bl) {
                // ✅ skip kain utama (FLC) biar tidak double stockOut
                if ($excludeFLC > 0 && (int) $bl->material_item_id === $excludeFLC) {
                    continue;
                }

                $bomQty = (float) ($bl->qty ?? 0);
                $scrap = (float) ($bl->scrap_pct ?? 0);

                if ($bomQty <= 0) {
                    continue;
                }

                // need = qty_ok * bom_qty * (1 + scrap%)
                $need = $qtyOk * $bomQty * (1 + ($scrap / 100.0));

                // konsisten dengan schema qty 12,4
                $needStr = number_format($need, 4, '.', '');
                if ((float) $needStr <= 0) {
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: $rmWarehouseId,
                    itemId: (int) $bl->material_item_id,
                    qty: (float) $needStr,
                    date: $movementDate,
                    sourceType: 'finishing_bom', // ✅ audit khusus BOM
                    sourceId: (int) $line->id, // ✅ per line
                    notes: "FIN {$job->code} • BOM SUP • line {$line->id}",
                    allowNegative: true,
                    lotId: null,
                    unitCostOverride: null,
                    affectLotCost: false,
                );
            }

            // ✅ mark applied
            $line->bom_applied_at = now();
            $line->save();
        }
    }
}
