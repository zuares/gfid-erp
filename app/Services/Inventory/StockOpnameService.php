<?php

namespace App\Services\Inventory;

use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\InventoryMutation;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockOpnameService
{
    public function __construct(
        protected InventoryService $inventory, // ⬅️ pakai InventoryService kamu
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
        // Hapus dulu lines sebelumnya kalau mau di-regenerate
        // $opname->lines()->delete();

        $query = InventoryMutation::selectRaw('item_id, SUM(qty_change) as qty')
            ->where('warehouse_id', $warehouseId)
            ->groupBy('item_id');

        if ($onlyWithStock) {
            $query->having('qty', '!=', 0);
        }

        $stocks = $query->get();

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
                'created_at' => now(),
                'updated_at' => now(),
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
     *   - Set HPP global sementara ke items.base_unit_cost dari unit_cost line
     *   - TIDAK bikin InventoryAdjustment terpisah → return null
     *
     * - periodic:
     *   - Buat InventoryAdjustment
     *   - Buat InventoryAdjustmentLine per selisih
     *   - Koreksi stok real lewat InventoryService::adjustTo()
     *   - Update status opname → finalized
     */
    public function finalize(StockOpname $opname, ?string $reason = null, ?string $notes = null): ?InventoryAdjustment
    {
        if ($opname->status === 'finalized') {
            throw new \RuntimeException('Stock Opname sudah difinalkan.');
        }

        return DB::transaction(function () use ($opname, $reason, $notes) {
            $user = Auth::user();

            $opname->loadMissing(['warehouse', 'lines.item']);

            // ============================
            // CABANG: OPENING BALANCE
            // ============================
            if ($opname->type === 'opening') {
                // 1️⃣ Terapkan saldo awal ke inventory (per item)
                $this->applyOpeningMovements(
                    opname: $opname,
                    reason: $reason,
                    notes: $notes
                );

                // 2️⃣ Set HPP global sementara dari unit_cost di line
                $this->applyOpeningBaseUnitCosts($opname);

                // 3️⃣ Update status dokumen
                $opname->status = 'finalized';
                $opname->finalized_by = $user?->id;
                $opname->finalized_at = now();
                $opname->save();

                // Opening tidak menghasilkan dokumen InventoryAdjustment terpisah
                return null;
            }

            // ============================
            // CABANG: PERIODIC (LOGIC LAMA)
            // ============================

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
                if ($difference === 0.0) {
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
                $adjLine->notes = $line->notes; // catatan per item dari opname
                $adjLine->save();

                // 2️⃣ Koreksi stok real lewat InventoryService (akan:
                //     - update inventory_stocks
                //     - catat inventory_mutations)
                $this->inventory->adjustTo(
                    warehouseId: $opname->warehouse_id,
                    itemId: $line->item_id,
                    newQty: $physicalQty,
                    date: $adjustment->date,
                    sourceType: InventoryAdjustment::class,
                    sourceId: $adjustment->id,
                    notes: $adjustment->reason,
                    lotId: null, // opname level item, bukan per LOT
                );
            }

            // Update status opname → finalized
            $opname->status = 'finalized';
            $opname->finalized_by = $user?->id;
            $opname->finalized_at = now();
            $opname->save();

            return $adjustment;
        });
    }

    /**
     * Opening:
     * Terapkan saldo awal ke inventory.
     * - SourceType: StockOpname::class
     * - SourceId  : id dokumen SO opening
     * - newQty    : physical_qty per item
     */
    protected function applyOpeningMovements(StockOpname $opname, ?string $reason, ?string $notes): void
    {
        $date = $opname->date ?? now()->toDateString();
        $reasonText = $reason ?: ('Saldo awal dari stock opname opening ' . $opname->code);
        $notesText = $notes;

        foreach ($opname->lines as $line) {
            // Kalau belum ada qty fisik → skip (nggak usah dibuat stok)
            if ($line->physical_qty === null) {
                continue;
            }

            $physicalQty = (float) $line->physical_qty;

            // Untuk opening, biasanya kita mau set langsung ke qty fisik tersebut
            $this->inventory->adjustTo(
                warehouseId: $opname->warehouse_id,
                itemId: $line->item_id,
                newQty: $physicalQty,
                date: $date,
                sourceType: StockOpname::class,
                sourceId: $opname->id,
                notes: $reasonText,
                lotId: null,
            );
        }
    }

    /**
     * Opening:
     * Set HPP global sementara (items.base_unit_cost) dari unit_cost di line.
     *
     * Catatan:
     * - Hanya update kalau:
     *   - physical_qty > 0
     *   - unit_cost > 0
     * - Sekarang pakai "last opening wins".
     *   Kalau mau hanya isi kalau belum pernah di-set, tinggal aktifkan versi 2 di bawah.
     */
    protected function applyOpeningBaseUnitCosts(StockOpname $opname): void
    {
        foreach ($opname->lines as $line) {
            $qty = (float) ($line->physical_qty ?? 0);
            $unitCost = (float) ($line->unit_cost ?? 0);

            if ($qty <= 0 || $unitCost <= 0) {
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
