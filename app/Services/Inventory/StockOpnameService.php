<?php

namespace App\Services\Inventory;

use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\InventoryMutation;
use App\Models\ItemCostSnapshot;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Generate lines dari stok sistem gudang (periodic):
     * - SUM(qty_change) dari inventory_mutations per item
     * - Hanya item dengan stok != 0 kalau $onlyWithStock = true
     */
    public function generateLinesFromWarehouse(
        StockOpname $opname,
        int $warehouseId,
        bool $onlyWithStock = true
    ): void {
        $query = InventoryMutation::selectRaw('item_id, SUM(qty_change) as qty')
            ->where('warehouse_id', $warehouseId)
            ->groupBy('item_id');

        if ($onlyWithStock) {
            $query->having('qty', '!=', 0);
        }

        $stocks = $query->get();

        $now = now();
        $rows = [];

        foreach ($stocks as $row) {
            $rows[] = [
                'stock_opname_id' => $opname->id,
                'item_id' => (int) $row->item_id,
                'system_qty' => (float) $row->qty,
                'physical_qty' => null,
                'difference_qty' => 0,
                'is_counted' => false,
                'notes' => null,
                'unit_cost' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            StockOpnameLine::insert($rows);
        }
    }

    /**
     * Finalize Stock Opname:
     * - opening: SEKARANG juga bikin InventoryAdjustment (biar muncul di menu adjustment)
     * - periodic: tetap bikin InventoryAdjustment
     *
     * Return:
     * - opening  -> return InventoryAdjustment (baru)
     * - periodic -> return InventoryAdjustment
     */
    public function finalize(StockOpname $opname, ?string $reason = null, ?string $notes = null): ?InventoryAdjustment
    {
        if ($opname->status === StockOpname::STATUS_FINALIZED) {
            throw new \RuntimeException('Stock Opname sudah difinalkan.');
        }

        return DB::transaction(function () use ($opname, $reason, $notes) {
            $user = Auth::user();
            $isOwner = $user && (($user->role ?? null) === 'owner');

            $opname->loadMissing(['warehouse', 'lines.item']);

            $date = $opname->date ?? now()->toDateString();
            $warehouseId = (int) $opname->warehouse_id;

            // ==========================================================
            // OPENING: bikin adjustment juga
            // ==========================================================
            if ($opname->type === StockOpname::TYPE_OPENING) {

                // Owner: wajib resolve cost (karena akan bikin snapshot & update base cost)
                // Non-owner pending: boleh lolos tanpa cost lengkap (akan dipaksa saat approve adjustment)
                $resolvedCosts = [];
                if ($isOwner) {
                    [$resolvedCosts, $missingCostLines] = $this->resolveOpeningUnitCosts($opname);

                    if (!empty($missingCostLines)) {
                        $codes = collect($missingCostLines)
                            ->map(fn(StockOpnameLine $line) => $line->item?->code ?? ('#' . $line->id))
                            ->take(8)
                            ->implode(', ');

                        throw new \RuntimeException(
                            'Masih ada item opening dengan Qty fisik > 0 tetapi HPP (unit_cost) tidak ditemukan: '
                            . $codes
                            . '. Lengkapi HPP di item master / snapshot / input manual pada dokumen opening.'
                        );
                    }
                }

                // Header InventoryAdjustment utk opening
                $adjustment = new InventoryAdjustment();
                $adjustment->code = $this->generateAdjustmentCodeForDate($date);
                $adjustment->date = $date;
                $adjustment->warehouse_id = $warehouseId;
                $adjustment->source_type = StockOpname::class;
                $adjustment->source_id = $opname->id;
                $adjustment->reason = $reason ?: ('Saldo awal dari stock opname opening ' . $opname->code);
                $adjustment->notes = $notes;
                $adjustment->status = $isOwner
                ? InventoryAdjustment::STATUS_APPROVED
                : InventoryAdjustment::STATUS_PENDING;
                $adjustment->created_by = $opname->created_by ?? $user?->id;

                if ($isOwner) {
                    $adjustment->approved_by = $user?->id;
                    $adjustment->approved_at = now();
                }

                $adjustment->save();

                foreach ($opname->lines as $line) {
                    if ($line->physical_qty === null) {
                        continue;
                    }

                    $itemId = (int) $line->item_id;
                    $physicalQty = (float) $line->physical_qty;

                    // qty_before = stok real saat finalize (lebih akurat utk opening)
                    $qtyBefore = (float) $this->inventory->getOnHandQty(
                        warehouseId: $warehouseId,
                        itemId: $itemId
                    );

                    $difference = $physicalQty - $qtyBefore; // SIGNED

                    if (abs($difference) < 0.0000001) {
                        continue;
                    }

                    $direction = $difference >= 0 ? 'in' : 'out';

                    InventoryAdjustmentLine::create([
                        'inventory_adjustment_id' => $adjustment->id,
                        'item_id' => $itemId,
                        'qty_before' => $qtyBefore,
                        'qty_after' => $physicalQty,
                        'qty_change' => $difference, // ✅ SIGNED
                        'direction' => $direction,
                        'notes' => $line->notes,
                        'lot_id' => null,
                    ]);

                    // Non-owner -> pending: stop (stok belum berubah)
                    if (!$isOwner) {
                        continue;
                    }

                    // Owner: set stok real jadi qty fisik (DENGAN COST)
                    $unitCost = $resolvedCosts[$line->id] ?? null; // hasil resolveOpeningUnitCosts()

                    $this->inventory->adjustByDifference(
                        warehouseId: $warehouseId,
                        itemId: $itemId,
                        qtyChange: $difference, // SIGNED (physical - before)
                        date: $date,
                        sourceType: InventoryAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $adjustment->reason,
                        lotId: null,
                        allowNegative: false,
                        unitCostOverride: $unitCost, // ✅ kunci agar mutasi punya unit_cost & total_cost
                        affectLotCost: false,
                    );

                    // Snapshot opening hanya jika qty > 0 + cost > 0
                    $unitCost = $resolvedCosts[$line->id] ?? null;
                    if ($physicalQty > 0 && $unitCost && (float) $unitCost > 0) {
                        $this->deactivateActiveSnapshots($itemId, $warehouseId);

                        ItemCostSnapshot::create([
                            'item_id' => $itemId,
                            'warehouse_id' => $warehouseId,
                            'snapshot_date' => $date,
                            'reference_type' => 'stock_opname_opening',
                            'reference_id' => $opname->id,
                            'qty_basis' => $physicalQty,
                            'rm_unit_cost' => (float) $unitCost,
                            'cutting_unit_cost' => 0,
                            'sewing_unit_cost' => 0,
                            'finishing_unit_cost' => 0,
                            'packaging_unit_cost' => 0,
                            'overhead_unit_cost' => 0,
                            'unit_cost' => (float) $unitCost,
                            'notes' => $notes ?: ('Opening balance ' . $opname->code),
                            'is_active' => true,
                            'created_by' => $opname->created_by ?? Auth::id(),
                        ]);

                        // update base_unit_cost (last opening wins)
                        if ($line->item) {
                            $line->item->base_unit_cost = (float) $unitCost;
                            $line->item->save();
                        }
                    }
                }

                // finalize opname (dikunci)
                $opname->status = StockOpname::STATUS_FINALIZED;
                $opname->finalized_by = $user?->id;
                $opname->finalized_at = now();
                $opname->save();

                return $adjustment;
            }

            // ==========================================================
            // PERIODIC: tetap seperti biasa
            // ==========================================================

            $adjustment = new InventoryAdjustment();
            $adjustment->code = $this->generateAdjustmentCodeForDate($date);
            $adjustment->date = $date;
            $adjustment->warehouse_id = $warehouseId;
            $adjustment->source_type = StockOpname::class;
            $adjustment->source_id = $opname->id;
            $adjustment->reason = $reason ?: ('Penyesuaian stok dari stock opname ' . $opname->code);
            $adjustment->notes = $notes;
            $adjustment->status = $isOwner
            ? InventoryAdjustment::STATUS_APPROVED
            : InventoryAdjustment::STATUS_PENDING;
            $adjustment->created_by = $opname->created_by ?? $user?->id;

            if ($isOwner) {
                $adjustment->approved_by = $user?->id;
                $adjustment->approved_at = now();
            }

            $adjustment->save();

            foreach ($opname->lines as $line) {
                if ($line->physical_qty === null) {
                    continue;
                }

                $systemQty = (float) $line->system_qty;
                $physicalQty = (float) $line->physical_qty;
                $difference = $physicalQty - $systemQty; // SIGNED

                if (abs($difference) < 0.0000001) {
                    continue;
                }

                $direction = $difference >= 0 ? 'in' : 'out';

                InventoryAdjustmentLine::create([
                    'inventory_adjustment_id' => $adjustment->id,
                    'item_id' => (int) $line->item_id,
                    'qty_before' => $systemQty,
                    'qty_after' => $physicalQty,
                    'qty_change' => $difference, // ✅ SIGNED
                    'direction' => $direction,
                    'notes' => $line->notes,
                    'lot_id' => null,
                ]);

                if (!$isOwner) {
                    continue;
                }

                $this->inventory->adjustTo(
                    warehouseId: $warehouseId,
                    itemId: (int) $line->item_id,
                    newQty: $physicalQty,
                    date: $date,
                    sourceType: InventoryAdjustment::class,
                    sourceId: $adjustment->id,
                    notes: $adjustment->reason,
                    lotId: null,
                );

                $this->snapshotPeriodicCost(
                    itemId: (int) $line->item_id,
                    warehouseId: $warehouseId,
                    snapshotDate: $date,
                    qtyBasis: $physicalQty,
                    opname: $opname,
                    notes: $notes
                );
            }

            $opname->status = StockOpname::STATUS_FINALIZED;
            $opname->finalized_by = $user?->id;
            $opname->finalized_at = now();
            $opname->save();

            return $adjustment;
        });
    }

    // ==========================================================
    // OPENING HELPERS
    // ==========================================================

    /**
     * Resolve HPP untuk opening (dipakai saat owner auto-approve):
     * - line.unit_cost > 0 → pakai itu
     * - fallback:
     *   1) snapshot aktif (item+gudang)
     *   2) item.base_unit_cost
     */
    protected function resolveOpeningUnitCosts(StockOpname $opname): array
    {
        $resolvedCosts = [];
        $missing = [];

        $warehouseId = (int) $opname->warehouse_id;

        foreach ($opname->lines as $line) {
            $qty = (float) ($line->physical_qty ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $unitCost = $line->unit_cost !== null ? (float) $line->unit_cost : 0.0;

            if ($unitCost <= 0) {
                $snap = ItemCostSnapshot::getActiveForItem((int) $line->item_id, $warehouseId);
                if ($snap && (float) $snap->unit_cost > 0) {
                    $unitCost = (float) $snap->unit_cost;
                }
            }

            if ($unitCost <= 0 && $line->item && (float) $line->item->base_unit_cost > 0) {
                $unitCost = (float) $line->item->base_unit_cost;
            }

            if ($unitCost <= 0) {
                $missing[] = $line;
                continue;
            }

            $resolvedCosts[$line->id] = $unitCost;
        }

        return [$resolvedCosts, $missing];
    }

    // ==========================================================
    // PERIODIC HELPERS
    // ==========================================================

    protected function snapshotPeriodicCost(
        int $itemId,
        int $warehouseId,
        string $snapshotDate,
        float $qtyBasis,
        StockOpname $opname,
        ?string $notes
    ): void {
        $active = ItemCostSnapshot::getActiveForItem($itemId, $warehouseId);

        $unitCost = 0.0;

        if ($active && (float) $active->unit_cost > 0) {
            $unitCost = (float) $active->unit_cost;
        } else {
            $item = $opname->lines->firstWhere('item_id', $itemId)?->item;
            if ($item && (float) $item->base_unit_cost > 0) {
                $unitCost = (float) $item->base_unit_cost;
            }
        }

        if ($unitCost <= 0) {
            return;
        }

        $this->deactivateActiveSnapshots($itemId, $warehouseId);

        ItemCostSnapshot::create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'snapshot_date' => $snapshotDate,
            'reference_type' => 'stock_opname_periodic',
            'reference_id' => $opname->id,
            'qty_basis' => $qtyBasis,
            'rm_unit_cost' => $unitCost,
            'cutting_unit_cost' => 0,
            'sewing_unit_cost' => 0,
            'finishing_unit_cost' => 0,
            'packaging_unit_cost' => 0,
            'overhead_unit_cost' => 0,
            'unit_cost' => $unitCost,
            'notes' => $notes ?: ('SO periodic ' . $opname->code),
            'is_active' => true,
            'created_by' => $opname->created_by ?? Auth::id(),
        ]);
    }

    protected function deactivateActiveSnapshots(int $itemId, int $warehouseId): void
    {
        ItemCostSnapshot::query()
            ->where('item_id', $itemId)
            ->forWarehouseOrGlobal($warehouseId)
            ->active()
            ->update(['is_active' => false]);
    }

    /**
     * Generate kode ADJ-YYYYMMDD-###
     */
    protected function generateAdjustmentCodeForDate(string $date): string
    {
        $d = Carbon::parse($date);
        $dateStr = $d->format('Ymd');
        $prefix = 'ADJ-' . $dateStr . '-';

        $last = InventoryAdjustment::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        $next = 1;
        if ($last) {
            $n = (int) substr($last->code, strlen($prefix));
            $next = $n + 1;
        }

        return sprintf('%s%03d', $prefix, $next);
    }
}
