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
use Illuminate\Support\Str;

class StockOpnameService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {
        //
    }

    /**
     * Generate lines dari stok sistem gudang:
     * - Ambil stok per item (SUM(qty_change)) dari inventory_mutations
     * - Hanya item dengan stok != 0 kalau $onlyWithStock = true
     */
    public function generateLinesFromWarehouse(
        StockOpname $opname,
        int $warehouseId,
        bool $onlyWithStock = true
    ): void {
        // Kalau mau re-generate:
        // $opname->lines()->delete();

        $query = InventoryMutation::selectRaw('item_id, SUM(qty_change) as qty')
            ->where('warehouse_id', $warehouseId)
            ->groupBy('item_id');

        if ($onlyWithStock) {
            $query->having('qty', '!=', 0);
        }

        $stocks = $query->get();

        $now = now();
        $lines = [];

        foreach ($stocks as $row) {
            $lines[] = [
                'stock_opname_id' => $opname->id,
                'item_id' => $row->item_id,
                'system_qty' => $row->qty,
                'physical_qty' => null,
                'difference_qty' => 0,
                'is_counted' => false,
                'notes' => null,
                'unit_cost' => null, // untuk mode opening (boleh kosong)
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($lines)) {
            StockOpnameLine::insert($lines);
        }
    }

    /**
     * Finalize 1 dokumen Stock Opname:
     *
     * - opening:
     *   - Buat saldo awal ke inventory (adjustTo per item)
     *   - HPP boleh diinput manual per line, atau otomatis fallback dari:
     *       1) ItemCostSnapshot aktif, lalu
     *       2) item.base_unit_cost
     *   - Snapshot HPP opening (RM-only) dibuat per item+gudang (wajib untuk item yang punya Qty fisik)
     *   - Update item.base_unit_cost dari HPP final opening
     *
     * - periodic:
     *   - Buat InventoryAdjustment
     *   - Buat InventoryAdjustmentLine per selisih
     *   - Koreksi stok real lewat InventoryService::adjustTo()
     *   - Snapshot HPP sementara (dari snapshot aktif atau base_unit_cost)
     *     per item+gudang (opsional, skip jika HPP 0)
     *   - Update status opname → finalized
     */
    public function finalize(StockOpname $opname, ?string $reason = null, ?string $notes = null): ?InventoryAdjustment
    {
        if ($opname->status === StockOpname::STATUS_FINALIZED) {
            throw new \RuntimeException('Stock Opname sudah difinalkan.');
        }

        return DB::transaction(function () use ($opname, $reason, $notes) {
            $user = Auth::user();

            $opname->loadMissing(['warehouse', 'lines.item']);

            // ============================
            // CABANG: OPENING BALANCE
            // ============================
            if ($opname->type === StockOpname::TYPE_OPENING) {
                [$resolvedCosts, $missingCostLines] = $this->resolveOpeningUnitCosts($opname);

                // Masih ada item dengan Qty fisik > 0 tapi HPP tidak ketemu sama sekali
                if (!empty($missingCostLines)) {
                    $codes = collect($missingCostLines)
                        ->map(fn(StockOpnameLine $line) => $line->item?->code ?? ('#' . $line->id))
                        ->take(5)
                        ->implode(', ');

                    throw new \RuntimeException(
                        'Masih ada item opening dengan Qty fisik tetapi tidak punya HPP (unit cost) di master / snapshot: '
                        . $codes
                        . '. Lengkapi HPP di master item, snapshot, atau isi manual di dokumen opening.'
                    );
                }

                // 1️⃣ Terapkan saldo awal ke inventory + snapshot opening
                $this->applyOpeningMovements(
                    opname: $opname,
                    reason: $reason,
                    notes: $notes,
                    resolvedCosts: $resolvedCosts,
                );

                // 2️⃣ Set HPP global sementara dari unit_cost final ke item master
                $this->applyOpeningBaseUnitCosts($opname, $resolvedCosts);

                // 3️⃣ Update status dokumen
                $opname->status = StockOpname::STATUS_FINALIZED;
                $opname->finalized_by = $user?->id;
                $opname->finalized_at = now();
                $opname->save();

                // Opening tidak menghasilkan dokumen InventoryAdjustment terpisah
                return null;
            }

            // ============================
            // CABANG: PERIODIC
            // ============================

            // Kalau mau dipaksa reviewed dulu:
            // if ($opname->status !== StockOpname::STATUS_REVIEWED) {
            //     throw new \RuntimeException('Stock Opname harus berstatus reviewed sebelum finalize.');
            // }

            // Header Inventory Adjustment
            $adjustment = new InventoryAdjustment();
            $adjustment->code = $this->generateAdjustmentCode();
            $adjustment->date = $opname->date ?? now()->toDateString();
            $adjustment->warehouse_id = $opname->warehouse_id;
            $adjustment->source_type = StockOpname::class;
            $adjustment->source_id = $opname->id;
            $adjustment->reason = $reason ?: 'Penyesuaian stok dari hasil stock opname';
            $adjustment->notes = $notes;
            $adjustment->status = 'approved';
            $adjustment->created_by = $opname->created_by ?? $user?->id;
            $adjustment->approved_by = $user?->id;
            $adjustment->approved_at = now();
            $adjustment->save();

            $warehouseId = $opname->warehouse_id;

            // Detail per item (lines)
            foreach ($opname->lines as $line) {
                // Kalau qty fisik belum diisi → skip
                if ($line->physical_qty === null) {
                    continue;
                }

                $systemQty = (float) $line->system_qty;
                $physicalQty = (float) $line->physical_qty;
                $difference = $physicalQty - $systemQty;

                // Tidak ada selisih → skip
                if (abs($difference) < 0.0000001) {
                    continue;
                }

                // Direction + qty_change berdasarkan selisih (snapshot)
                [$direction, $qtyChange] = $this->normalizeDifference($difference);

                // sebelum/sesudah berdasarkan data SO (snapshot)
                $qtyBefore = $systemQty;
                $qtyAfter = $physicalQty;

                // 1️⃣ Buat baris InventoryAdjustmentLine
                $adjLine = new InventoryAdjustmentLine();
                $adjLine->inventory_adjustment_id = $adjustment->id;
                $adjLine->item_id = $line->item_id;
                $adjLine->qty_before = $qtyBefore;
                $adjLine->qty_after = $qtyAfter;
                $adjLine->qty_change = $qtyChange;
                $adjLine->direction = $direction;
                $adjLine->notes = $line->notes;
                $adjLine->save();

                // 2️⃣ Koreksi stok real lewat InventoryService
                $this->inventory->adjustTo(
                    warehouseId: $warehouseId,
                    itemId: $line->item_id,
                    newQty: $physicalQty,
                    date: $adjustment->date,
                    sourceType: InventoryAdjustment::class,
                    sourceId: $adjustment->id,
                    notes: $adjustment->reason,
                    lotId: null,
                );

                // 3️⃣ Snapshot HPP sementara (opsional) → cocok dengan struktur tabel
                $item = $line->item;
                $activeSnapshot = ItemCostSnapshot::getActiveForItem($line->item_id, $warehouseId);

                $unitCost = 0.0;

                if ($activeSnapshot && $activeSnapshot->unit_cost > 0) {
                    $unitCost = (float) $activeSnapshot->unit_cost;
                } elseif ($item && $item->base_unit_cost > 0) {
                    $unitCost = (float) $item->base_unit_cost;
                }

                // Kalau masih 0 → HPP tidak jelas, snapshot boleh di-skip
                if ($unitCost <= 0) {
                    continue;
                }

                // Nonaktifkan snapshot aktif sebelumnya untuk item + gudang ini
                ItemCostSnapshot::query()
                    ->where('item_id', $line->item_id)
                    ->forWarehouseOrGlobal($warehouseId)
                    ->active()
                    ->update(['is_active' => false]);

                // Insert snapshot baru untuk SO periodik
                ItemCostSnapshot::create([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $warehouseId,
                    'snapshot_date' => $adjustment->date,
                    'reference_type' => 'stock_opname_periodic',
                    'reference_id' => $opname->id,
                    'qty_basis' => $physicalQty,
                    'rm_unit_cost' => $unitCost, // di SO periodic → anggap HPP aktif sebagai RM-only
                    'cutting_unit_cost' => 0,
                    'sewing_unit_cost' => 0,
                    'finishing_unit_cost' => 0,
                    'packaging_unit_cost' => 0,
                    'overhead_unit_cost' => 0,
                    'unit_cost' => $unitCost,
                    'notes' => $notes ?: ('SO periodic ' . $opname->code),
                    'is_active' => true,
                    'created_by' => $opname->created_by ?? $user?->id,
                ]);
            }

            // Update status opname → finalized
            $opname->status = StockOpname::STATUS_FINALIZED;
            $opname->finalized_by = $user?->id;
            $opname->finalized_at = now();
            $opname->save();

            return $adjustment;
        });
    }

    /**
     * Resolve HPP untuk opening:
     * - Kalau line.unit_cost > 0 → pakai itu (user override)
     * - Kalau kosong → fallback:
     *     1) snapshot aktif per item+gudang
     *     2) item.base_unit_cost
     *
     * Return:
     *   [ array $resolvedCosts (line_id => unit_cost final),
     *     array $missingCostLines (StockOpnameLine[] tanpa HPP padahal Qty fisik > 0) ]
     */
    protected function resolveOpeningUnitCosts(StockOpname $opname): array
    {
        $resolvedCosts = [];
        $missingCostLines = [];

        $warehouseId = $opname->warehouse_id;

        foreach ($opname->lines as $line) {
            $physicalQty = (float) ($line->physical_qty ?? 0);

            // Kalau tidak ada Qty fisik atau <= 0 → HPP boleh di-skip
            if ($physicalQty <= 0) {
                continue;
            }

            // 1️⃣ Coba dari unit_cost di dokumen (user input)
            $unitCost = $line->unit_cost !== null ? (float) $line->unit_cost : 0.0;

            // 2️⃣ Kalau tidak ada, fallback ke snapshot aktif
            if ($unitCost <= 0) {
                $snapshot = ItemCostSnapshot::getActiveForItem($line->item_id, $warehouseId);
                if ($snapshot && $snapshot->unit_cost > 0) {
                    $unitCost = (float) $snapshot->unit_cost;
                }
            }

            // 3️⃣ Kalau masih kosong, fallback ke item.base_unit_cost
            if ($unitCost <= 0 && $line->item && $line->item->base_unit_cost > 0) {
                $unitCost = (float) $line->item->base_unit_cost;
            }

            if ($unitCost <= 0) {
                // Tidak bisa resolve HPP untuk item yang punya Qty fisik
                $missingCostLines[] = $line;
                continue;
            }

            $resolvedCosts[$line->id] = $unitCost;
        }

        return [$resolvedCosts, $missingCostLines];
    }

    /**
     * Opening:
     * Terapkan saldo awal ke inventory + buat snapshot HPP opening (RM-only).
     *
     * - SourceType: StockOpname::class
     * - SourceId  : id dokumen SO opening
     * - newQty    : physical_qty per item
     * - unit_cost : diambil dari $resolvedCosts (hasil resolveOpeningUnitCosts)
     */
    protected function applyOpeningMovements(
        StockOpname $opname,
        ?string $reason,
        ?string $notes,
        array $resolvedCosts
    ): void {
        $date = $opname->date ?? now()->toDateString();
        $reasonText = $reason ?: ('Saldo awal dari stock opname opening ' . $opname->code);
        $userId = Auth::id();
        $warehouseId = $opname->warehouse_id;

        foreach ($opname->lines as $line) {
            if ($line->physical_qty === null) {
                continue;
            }

            $physicalQty = (float) $line->physical_qty;

            // 1️⃣ Set saldo awal stok ke qty fisik (boleh 0, artinya stok nol)
            $this->inventory->adjustTo(
                warehouseId: $warehouseId,
                itemId: $line->item_id,
                newQty: $physicalQty,
                date: $date,
                sourceType: StockOpname::class,
                sourceId: $opname->id,
                notes: $reasonText,
                lotId: null,
            );

            // 2️⃣ Snapshot HPP opening hanya untuk Qty fisik > 0 dan HPP ter-resolve
            $unitCost = $resolvedCosts[$line->id] ?? null;

            if ($physicalQty <= 0 || !$unitCost || $unitCost <= 0) {
                continue;
            }

            // Nonaktifkan snapshot aktif sebelumnya untuk item + gudang ini
            ItemCostSnapshot::query()
                ->where('item_id', $line->item_id)
                ->forWarehouseOrGlobal($warehouseId)
                ->active()
                ->update(['is_active' => false]);

            // Insert snapshot baru
            ItemCostSnapshot::create([
                'item_id' => $line->item_id,
                'warehouse_id' => $warehouseId,
                'snapshot_date' => $date,
                'reference_type' => 'stock_opname_opening',
                'reference_id' => $opname->id,
                'qty_basis' => $physicalQty,
                'rm_unit_cost' => $unitCost, // HPP RM-only
                'cutting_unit_cost' => 0,
                'sewing_unit_cost' => 0,
                'finishing_unit_cost' => 0,
                'packaging_unit_cost' => 0,
                'overhead_unit_cost' => 0,
                'unit_cost' => $unitCost, // total unit_cost = RM-only di fase opening
                'notes' => $notes ?: ('Opening balance ' . $opname->code),
                'is_active' => true,
                'created_by' => $opname->created_by ?? $userId,
            ]);
        }
    }

    /**
     * Opening:
     * Set HPP global sementara (items.base_unit_cost) dari unit_cost final (resolvedCosts).
     *
     * Catatan:
     * - Hanya update kalau:
     *   - physical_qty > 0
     *   - unit_cost > 0
     * - "Last opening wins" (kalau ada >1 opening).
     */
    protected function applyOpeningBaseUnitCosts(StockOpname $opname, array $resolvedCosts): void
    {
        foreach ($opname->lines as $line) {
            $qty = (float) ($line->physical_qty ?? 0);
            $unitCost = $resolvedCosts[$line->id] ?? null;

            if ($qty <= 0 || !$unitCost || $unitCost <= 0) {
                continue;
            }

            if (!$line->item) {
                continue;
            }

            $item = $line->item;

            // Versi 1: selalu overwrite (last opening wins)
            $item->base_unit_cost = $unitCost;

            // Versi 2 (opsional): hanya isi kalau belum pernah di-set
            // if (is_null($item->base_unit_cost) || $item->base_unit_cost <= 0) {
            //     $item->base_unit_cost = $unitCost;
            // }

            $item->save();
        }
    }

    /**
     * Normalisasi selisih:
     * > 0  → direction = in,  qtyChange = selisih
     * < 0  → direction = out, qtyChange = |selisih|
     */
    protected function normalizeDifference(float $difference): array
    {
        if ($difference > 0) {
            return ['in', $difference];
        }

        return ['out', abs($difference)];
    }

    /**
     * Generate kode ADJ sederhana: ADJ-YYYYMMDD-XXXX
     */
    protected function generateAdjustmentCode(): string
    {
        $date = Carbon::now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "ADJ-{$date}-{$random}";
    }
}
