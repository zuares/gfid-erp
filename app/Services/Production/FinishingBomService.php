<?php

namespace App\Services\Production;

use App\Models\FinishingJob;
use App\Models\InventoryStock;
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

        // ✅ VALIDASI STOK RM: agregasi kebutuhan semua line yang belum di-apply,
        // lalu tolak jika stok RM tidak mencukupi sebelum mutasi apapun dilakukan.
        $totalNeedByMaterial = []; // material_item_id → ['code' => ..., 'need' => float]
        foreach ($job->lines as $line) {
            if (!empty($line->bom_applied_at)) continue;
            $fgItemId = (int) ($line->item_id ?? 0);
            $qtyOk    = (float) ($line->qty_ok ?? 0);
            if ($fgItemId <= 0 || $qtyOk <= 0) continue;

            $bom = ItemBom::where('item_id', $fgItemId)->where('active', true)->first();
            if (!$bom) continue;

            $excludeFLC = (int) ($line->bundle?->cuttingJob?->fabric_item_id ?? 0);
            $bomLines   = ItemBomLine::where('item_bom_id', $bom->id)
                ->where('usage_stage', ItemBomLine::STAGE_PACKING_SUPPLY)
                ->where('is_optional', false)
                ->get();

            foreach ($bomLines as $bl) {
                if ($excludeFLC > 0 && (int) $bl->material_item_id === $excludeFLC) continue;
                $bomQty = (float) ($bl->qty ?? 0);
                if ($bomQty <= 0) continue;
                $need   = $qtyOk * $bomQty * (1 + ((float) ($bl->scrap_pct ?? 0) / 100.0));
                $matId  = (int) $bl->material_item_id;
                if (!isset($totalNeedByMaterial[$matId])) {
                    $totalNeedByMaterial[$matId] = ['code' => (string) ($bl->material?->code ?? "material #{$matId}"), 'need' => 0.0];
                }
                $totalNeedByMaterial[$matId]['need'] += $need;
            }
        }

        if (!empty($totalNeedByMaterial)) {
            $matIds    = array_keys($totalNeedByMaterial);
            $rmStocks  = InventoryStock::where('warehouse_id', $rmWarehouseId)
                ->whereIn('item_id', $matIds)
                ->pluck('qty', 'item_id')
                ->map(fn($v) => (float) $v)
                ->toArray();

            $shortages = [];
            foreach ($totalNeedByMaterial as $matId => $row) {
                $have  = (float) ($rmStocks[$matId] ?? 0);
                $need  = $row['need'];
                if ($have + 0.000001 < $need) {
                    $code  = $row['code'];
                    $n     = number_format($need, 2, ',', '.');
                    $h     = number_format($have, 2, ',', '.');
                    $short = number_format($need - $have, 2, ',', '.');
                    $shortages[] = "• {$code}: butuh {$n}, stok RM {$h} (kurang {$short})";
                }
            }

            if (!empty($shortages)) {
                throw ValidationException::withMessages([
                    'supplies' => "Stok RM tidak cukup untuk kelengkapan finishing. Posting ditolak.\n\n"
                        . implode("\n", $shortages),
                ]);
            }
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
                ->where('usage_stage', ItemBomLine::STAGE_PACKING_SUPPLY)
                ->where('is_optional', false)
                ->orderBy('sort_order')
                ->get();

            if ($bomLines->isEmpty()) {
                $line->bom_applied_at = now();
                $line->save();
                continue;
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

                $unitCost = (float) $this->inventory->getItemIncomingUnitCost(
                    $rmWarehouseId,
                    (int) $bl->material_item_id,
                );
                if ($unitCost <= 0) {
                    // Skip BOM line ini — material belum di-GRN atau cost belum tersedia.
                    // Produksi tetap jalan; tandai line bom_has_gaps=true agar muncul di dashboard rekonsiliasi.
                    \Illuminate\Support\Facades\Log::warning(
                        "FIN {$job->code} line {$line->id}: skip BOM material #{$bl->material_item_id} ({$bl->code}) — unitCost=0, belum ada cost di RM."
                    );
                    $line->bom_has_gaps = true;
                    continue;
                }

                $this->inventory->stockOut(
                    warehouseId: $rmWarehouseId,
                    itemId: (int) $bl->material_item_id,
                    qty: (float) $needStr,
                    date: $movementDate,
                    sourceType: 'finishing_bom',
                    sourceId: (int) $line->id,
                    notes: "FIN {$job->code} • BOM SUP • line {$line->id}",
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: $unitCost,
                    affectLotCost: false,
                );
            }

            // ✅ mark applied (bom_has_gaps tetap tersimpan jika di-set true di atas)
            $line->bom_applied_at = now();
            $line->save();
        }
    }
}
