<?php

namespace App\Services\Production;

use App\Models\CuttingJobBundle;
use App\Models\InventoryMutation;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Returns only sewing-supply BOM cost for a pre-sewing reject.
 *
 * The main material/WIP movement is deliberately outside this service. A
 * reject before sewing remains WIP-CUT -> REJ-CUT as before; only the
 * sewing_supply component that was consumed earlier is returned to RM.
 */
class SewingPickupRejectSupplyService
{
    public const SOURCE = 'sewing_pickup_reject_supply';

    public const VOID_SOURCE = self::SOURCE.'_void';

    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Return the supply component for one reject line to RM.
     *
     * The method is idempotent by line id. It allocates the original RM
     * consumption by lot and original unit cost, so the return can be traced
     * back to the QC/old pickup supply mutation without changing it.
     *
     * @return float returned cost
     */
    public function returnForReject(
        SewingPickupLine $line,
        string|\DateTimeInterface|null $date = null,
        ?string $notes = null,
    ): float {
        $qtyReject = (float) ($line->qty_rejected ?? 0);
        if ($qtyReject <= 0.000001) {
            return 0.0;
        }

        $alreadyReturned = $this->activeReturnMutations($line);
        if ($alreadyReturned->isNotEmpty()) {
            return round((float) $alreadyReturned->sum(fn ($row): float => abs((float) ($row->total_cost ?? 0))), 2);
        }

        $bundle = $line->bundle()->with('cuttingJob')->lockForUpdate()->first();
        if (! $bundle instanceof CuttingJobBundle) {
            throw ValidationException::withMessages([
                'lines' => "Bundle untuk pickup line #{$line->id} tidak ditemukan; reversal kelengkapan dihentikan.",
            ]);
        }
        $line->setRelation('bundle', $bundle);

        $rmWarehouseId = (int) Warehouse::query()->where('code', 'RM')->value('id');
        if ($rmWarehouseId <= 0) {
            throw ValidationException::withMessages([
                'supplies' => 'Gudang RM belum dikonfigurasi. Kelengkapan reject tidak dapat dikembalikan.',
            ]);
        }

        $source = $this->sourceContext($line, $bundle);
        $bom = ItemBom::query()
            ->where('item_id', (int) $line->finished_item_id)
            ->where('active', true)
            ->with(['lines.material'])
            ->first();

        if (! $bom) {
            return 0.0;
        }

        $supplyLines = $bom->lines
            ->filter(fn ($bomLine): bool => $this->isSewingSupplyLine($bomLine))
            ->values();
        if ($supplyLines->isEmpty()) {
            return 0.0;
        }

        $totalCost = 0.0;
        foreach ($supplyLines as $bomLine) {
            $materialId = (int) $bomLine->material_item_id;
            $requiredQty = round($qtyReject * (float) $bomLine->qty, 8);
            if ($materialId <= 0 || $requiredQty <= 0.000001) {
                continue;
            }

            $rows = $this->sourceRows(
                sourceType: $source['source_type'],
                sourceId: $source['source_id'],
                warehouseId: $rmWarehouseId,
                itemId: $materialId,
            );

            // No supply mutation means this path did not consume this BOM
            // component (for example the old pickup path with qty good = 0).
            // Keep the old behavior and do not invent a return.
            if ($rows->isEmpty()) {
                continue;
            }

            $returnedByLot = $this->returnedQtyByLot($line, $source, $rmWarehouseId, $materialId);
            $remaining = $requiredQty;

            foreach ($rows as $row) {
                if ($remaining <= 0.000001) {
                    break;
                }

                $lotKey = $row->lot_id !== null ? (string) (int) $row->lot_id : 'none';
                $originalQty = abs((float) $row->qty_change);
                $alreadyReturnedQty = (float) ($returnedByLot[$lotKey] ?? 0);
                $availableQty = max($originalQty - $alreadyReturnedQty, 0.0);
                if ($availableQty <= 0.000001) {
                    continue;
                }

                $unitCost = $this->unitCost($row);
                if ($unitCost <= 0.000001) {
                    $code = $row->item_code ?: "item #{$materialId}";
                    throw ValidationException::withMessages([
                        'supplies' => "HPP kelengkapan {$code} tidak tersedia pada mutasi asal. Reversal reject dihentikan.",
                    ]);
                }

                $qty = min($remaining, $availableQty);
                $this->inventory->stockIn(
                    warehouseId: $rmWarehouseId,
                    itemId: $materialId,
                    qty: $qty,
                    date: $date,
                    sourceType: self::SOURCE,
                    sourceId: (int) $line->id,
                    sourceLineId: (int) $line->id,
                    notes: $notes ?: "Return kelengkapan reject susulan line #{$line->id}",
                    lotId: $row->lot_id !== null ? (int) $row->lot_id : null,
                    unitCost: $unitCost,
                    affectLotCost: $row->lot_id !== null,
                    cuttingJobBundleId: (int) $bundle->id,
                );

                $totalCost += $qty * $unitCost;
                $remaining -= $qty;
                $returnedByLot[$lotKey] = $alreadyReturnedQty + $qty;
            }

            if ($remaining > 0.000001) {
                $code = $rows->first()->item_code ?: "item #{$materialId}";
                throw ValidationException::withMessages([
                    'supplies' => sprintf(
                        'Saldo konsumsi BOM %s tidak cukup untuk Reject Susulan line #%d. Butuh %.6f, tersedia %.6f.',
                        $code,
                        (int) $line->id,
                        $requiredQty,
                        max($requiredQty - $remaining, 0.0),
                    ),
                ]);
            }
        }

        return round($totalCost, 2);
    }

    /** Reverse the return mutations when the reject line is VOID. */
    public function reverseForReject(
        SewingPickupLine $line,
        string|\DateTimeInterface|null $date = null,
        ?string $notes = null,
    ): void {
        if ($this->activeReturnMutations($line)->isEmpty()) {
            return;
        }

        $this->inventory->reverseBySource(
            originalSourceTypes: [self::SOURCE],
            originalSourceId: (int) $line->id,
            voidSourceType: self::VOID_SOURCE,
            voidSourceId: (int) $line->id,
            notesPrefix: $notes ?: "VOID return kelengkapan reject line #{$line->id}",
            date: $date,
        );
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    private function activeReturnMutations(SewingPickupLine $line)
    {
        return InventoryMutation::query()
            ->where('source_type', self::SOURCE)
            ->where('source_id', (int) $line->id)
            ->where('qty_change', '>', 0)
            ->get();
    }

    /** @return array{source_type:string,source_id:int} */
    private function sourceContext(SewingPickupLine $line, CuttingJobBundle $bundle): array
    {
        $autoBomAtQc = (bool) config('accounting.standalone_production_cutting_qc_auto_bom_enabled', false);

        return [
            'source_type' => $autoBomAtQc
                ? JournalService::SRC_CUTTING_QC_SEWING_SUPPLY
                : JournalService::SRC_SEWING_PICKUP_SUPPLY,
            'source_id' => $autoBomAtQc
                ? (int) $bundle->cutting_job_id
                : (int) $line->sewing_pickup_id,
        ];
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    private function sourceRows(string $sourceType, int $sourceId, int $warehouseId, int $itemId)
    {
        $voidType = $sourceType.'_void';
        $lastVoidId = (int) InventoryMutation::query()
            ->where('source_type', $voidType)
            ->where('source_id', $sourceId)
            ->max('id');

        return DB::table('inventory_mutations as im')
            ->leftJoin('items as i', 'i.id', '=', 'im.item_id')
            ->where('im.warehouse_id', $warehouseId)
            ->where('im.item_id', $itemId)
            ->where('im.source_id', $sourceId)
            ->where('im.qty_change', '<', 0)
            ->where('im.id', '>', $lastVoidId)
            ->where(function ($query) use ($sourceType): void {
                $query
                    ->where('im.source_type', $sourceType)
                    ->orWhere('im.source_type', 'like', $sourceType.'_repost_%');
            })
            ->select('im.*', 'i.code as item_code')
            ->orderBy('im.id')
            ->lockForUpdate()
            ->get();
    }

    /** @return array<string, float> */
    private function returnedQtyByLot(
        SewingPickupLine $line,
        array $source,
        int $warehouseId,
        int $itemId,
    ): array {
        $lineIds = SewingPickupLine::query()
            ->when(
                $source['source_type'] === JournalService::SRC_CUTTING_QC_SEWING_SUPPLY,
                fn ($query) => $query->whereHas('bundle', fn ($bundleQuery) => $bundleQuery->where('cutting_job_id', (int) $line->bundle->cutting_job_id)),
                fn ($query) => $query->where('sewing_pickup_id', (int) $line->sewing_pickup_id),
            )
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($lineIds === []) {
            return [];
        }

        $voidType = self::VOID_SOURCE;

        return DB::table('inventory_mutations')
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->whereIn('source_type', [self::SOURCE, $voidType])
            ->whereIn('source_id', $lineIds)
            ->groupBy('lot_id')
            ->selectRaw('lot_id, SUM(qty_change) as qty')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                $row->lot_id !== null ? (string) (int) $row->lot_id : 'none' => max((float) $row->qty, 0.0),
            ])
            ->all();
    }

    private function unitCost(object $row): float
    {
        $unitCost = (float) ($row->unit_cost ?? 0);
        if ($unitCost > 0.000001) {
            return $unitCost;
        }

        $qty = abs((float) ($row->qty_change ?? 0));
        $totalCost = abs((float) ($row->total_cost ?? 0));

        return $qty > 0.000001 ? $totalCost / $qty : 0.0;
    }

    private function isSewingSupplyLine($line): bool
    {
        if (! $line || ! $line->material || (bool) ($line->is_optional ?? false)) {
            return false;
        }

        return (string) ($line->usage_stage ?? '') === ItemBomLine::STAGE_SEWING_SUPPLY;
    }
}
