<?php

namespace App\Services\Inventory;

use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Services\Accounting\JournalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    public function __construct(
        protected InventoryService $inventory,
        protected JournalService $journal,
    ) {}

    /**
     * Generate lines dari stok sistem gudang (periodic):
     * - SUM(qty_change) dari inventory_mutations per item
     * - Hanya item dengan stok != 0 kalau $onlyWithStock = true
     * - SO Periodik: HPP awal diisi dari master item (kolom items.hpp)
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

        // Cek tipe: opening / periodic
        $isOpening = method_exists($opname, 'isOpening')
        ? $opname->isOpening()
        : ($opname->type === StockOpname::TYPE_OPENING);

        // Ambil master item sekali saja
        $itemIds = $stocks->pluck('item_id')->filter()->unique()->all();
        $items = Item::whereIn('id', $itemIds)->get()->keyBy('id');

        $now = now();
        $rows = [];

        foreach ($stocks as $row) {
            $itemId = (int) $row->item_id;
            $item = $items->get($itemId);

            // DEFAULT: null
            $unitCost = null;

            // ✅ Hanya untuk SO PERIODIK:
            //    kalau ada HPP di master (kolom items.hpp), pakai itu
            if (!$isOpening && $item && (float) $item->hpp > 0) {
                $unitCost = (float) $item->hpp;
            }

            $rows[] = [
                'stock_opname_id' => $opname->id,
                'item_id' => $itemId,
                'system_qty' => (float) $row->qty,
                'physical_qty' => null,
                'difference_qty' => 0,
                'is_counted' => false,
                'notes' => null,
                'unit_cost' => $unitCost, // ← sudah isi dari master HPP kalau Periodik
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            StockOpnameLine::insert($rows);
        }
    }

    /**
     * Generate lines KHUSUS bahan baku RM (periodic):
     * - system_qty = SUM(lots.qty_onhand WHERE status='open') per item
     * - hanya item dengan item_role IN ('raw_material','production_supply') dan active=1
     * - semua item bahan baku masuk (termasuk yang saldo 0 agar tetap bisa dicek fisik)
     *
     * Berbeda dari generateLinesFromWarehouse() yang pakai SUM(mutations).
     * Untuk bahan baku kain/RIB, lots adalah sumber kebenaran saldo.
     */
    public function generateRawMaterialLines(StockOpname $opname, int $warehouseId): void
    {
        // 1) Item bahan baku aktif
        $eligibleItems = Item::query()
            ->whereIn('item_role', ['raw_material', 'production_supply'])
            ->where('active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'hpp']);

        if ($eligibleItems->isEmpty()) {
            return;
        }

        $itemIds = $eligibleItems->pluck('id')->all();

        // 2) system_qty dari SUM(lots.qty_onhand) — sumber kebenaran untuk bahan baku
        $lotQtys = \Illuminate\Support\Facades\DB::table('lots')
            ->whereIn('item_id', $itemIds)
            ->where('status', 'open')
            ->groupBy('item_id')
            ->selectRaw('item_id, SUM(qty_onhand) as total_qty')
            ->get()
            ->keyBy('item_id');

        $now  = now();
        $rows = [];

        foreach ($eligibleItems as $item) {
            $lotRow    = $lotQtys->get($item->id);
            $systemQty = $lotRow ? (float) $lotRow->total_qty : 0.0;
            $unitCost  = ((float) ($item->hpp ?? 0)) > 0 ? (float) $item->hpp : null;

            $rows[] = [
                'stock_opname_id' => $opname->id,
                'item_id'         => $item->id,
                'system_qty'      => $systemQty,
                'physical_qty'    => null,
                'difference_qty'  => 0,
                'is_counted'      => false,
                'notes'           => null,
                'unit_cost'       => $unitCost,
                'created_at'      => $now,
                'updated_at'      => $now,
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

        $adjustment = DB::transaction(function () use ($opname, $reason, $notes) {
            $user = Auth::user();
            $isOwner = $user && (($user->role ?? null) === 'owner');

            $opname->loadMissing(['warehouse', 'lines.item']);

            $date = $opname->date ?? now()->toDateString();
            $warehouseId = (int) $opname->warehouse_id;

            // ==========================================================
            // OPENING
            // ==========================================================
            if ($opname->type === StockOpname::TYPE_OPENING) {

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

                $adjustment = new InventoryAdjustment();
                $adjustment->code = $this->generateAdjustmentCodeForDate($date);
                $adjustment->date = $date;
                $adjustment->warehouse_id = $warehouseId;
                $adjustment->source_type = StockOpname::class;
                $adjustment->source_id = $opname->id;
                $adjustment->reason = $reason ?: ('Saldo awal dari stock opname opening ' . $opname->code);
                $adjustment->notes = $notes;
                $adjustment->status = $isOwner ? InventoryAdjustment::STATUS_APPROVED : InventoryAdjustment::STATUS_PENDING;
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

                    // realtime qty_before
                    $qtyBefore = (float) $this->inventory->getOnHandQty(
                        warehouseId: $warehouseId,
                        itemId: $itemId
                    );

                    $difference = $physicalQty - $qtyBefore; // SIGNED

                    if (abs($difference) < 0.0000001) {
                        // optional: kalau mau tetap audit even 0, kamu boleh create line 0 di sini
                        continue;
                    }

                    $direction = $difference >= 0 ? 'in' : 'out';

                    InventoryAdjustmentLine::create([
                        'inventory_adjustment_id' => $adjustment->id,
                        'item_id' => $itemId,
                        'qty_before' => $qtyBefore,
                        'qty_after' => $physicalQty,
                        'qty_change' => $difference,
                        'direction' => $direction,
                        'notes' => $line->notes,
                        'lot_id' => null,
                    ]);

                    if (!$isOwner) {
                        continue;
                    }

                    $unitCost = $resolvedCosts[$line->id] ?? null;

                    $this->inventory->adjustByDifference(
                        warehouseId: $warehouseId,
                        itemId: $itemId,
                        qtyChange: $difference,
                        date: $date,
                        sourceType: InventoryAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $adjustment->reason,
                        lotId: null,
                        allowNegative: false,
                        unitCostOverride: $unitCost,
                        affectLotCost: false,
                    );

                    // snapshot opening + update base_unit_cost
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

                        if ($line->item) {
                            $line->item->base_unit_cost = (float) $unitCost;
                            $line->item->save();
                        }
                    }
                }

                $opname->status = StockOpname::STATUS_FINALIZED;
                $opname->finalized_by = $user?->id;
                $opname->finalized_at = now();
                $opname->save();

                return $adjustment;
            }

            // ==========================================================
            // PERIODIC (✅ realtime qty_before, bukan system_qty di line)
            // ==========================================================
            $adjustment = new InventoryAdjustment();
            $adjustment->code = $this->generateAdjustmentCodeForDate($date);
            $adjustment->date = $date;
            $adjustment->warehouse_id = $warehouseId;
            $adjustment->source_type = StockOpname::class;
            $adjustment->source_id = $opname->id;
            $adjustment->reason = $reason ?: ('Penyesuaian stok dari stock opname ' . $opname->code);
            $adjustment->notes = $notes;
            $adjustment->status = $isOwner ? InventoryAdjustment::STATUS_APPROVED : InventoryAdjustment::STATUS_PENDING;
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

                // ✅ qty_before realtime (lebih benar)
                $qtyBefore = (float) $this->inventory->getOnHandQty(
                    warehouseId: $warehouseId,
                    itemId: $itemId
                );

                $difference = $physicalQty - $qtyBefore; // SIGNED

                // resolve cost periodic (konsisten dengan generator kamu)
                $unitCost = null;
                if ($line->unit_cost !== null && (float) $line->unit_cost > 0) {
                    $unitCost = (float) $line->unit_cost;
                } elseif ($line->item && (float) ($line->item->hpp ?? 0) > 0) {
                    $unitCost = (float) $line->item->hpp;
                }

                $direction    = $difference >= 0 ? 'in' : 'out';
                $hasDiff      = abs($difference) >= 0.0000001;
                $postedLotId  = null;

                // ── Non-owner: catat untuk audit saja, stok belum berubah ──
                if (!$isOwner) {
                    InventoryAdjustmentLine::create([
                        'inventory_adjustment_id' => $adjustment->id,
                        'item_id'   => $itemId,
                        'qty_before' => $qtyBefore,
                        'qty_after'  => $physicalQty,
                        'qty_change' => $hasDiff ? $difference : 0,
                        'direction'  => $direction,
                        'notes'      => $line->notes,
                        'lot_id'     => null,
                    ]);
                    continue;
                }

                // ── Owner: posting ke lots ────────────────────────────────
                if ($hasDiff) {

                    if ($difference > 0) {
                        // SURPLUS → tambah ke LOT adjustment khusus ADJ-{ITEM_CODE}
                        $adjLotCode = 'ADJ-' . ($line->item?->code ?? "item{$itemId}");

                        $adjLot = \App\Models\Lot::firstOrCreate(
                            ['code' => $adjLotCode, 'item_id' => $itemId],
                            [
                                'initial_qty'  => 0,
                                'initial_cost' => 0,
                                'qty_onhand'   => 0,
                                'avg_cost'     => 0,
                                'total_cost'   => 0,
                                'status'       => 'open',
                            ]
                        );

                        $postedLotId = $adjLot->id;

                        $this->inventory->stockIn(
                            warehouseId:   $warehouseId,
                            itemId:        $itemId,
                            qty:           $difference,
                            date:          $date,
                            sourceType:    'stock_opname_adjustment',
                            sourceId:      $adjustment->id,
                            notes:         "Surplus opname {$opname->code}",
                            lotId:         $adjLot->id,
                            unitCost:      $unitCost,
                            affectLotCost: $unitCost !== null && $unitCost > 0,
                        );

                    } else {
                        // SHORTAGE → kurangi dari lot open, dimulai dari yang terbesar
                        $remaining = abs($difference);

                        $openLots = \App\Models\Lot::where('item_id', $itemId)
                            ->where('status', 'open')
                            ->where('qty_onhand', '>', 0)
                            ->orderByDesc('qty_onhand')
                            ->get();

                        foreach ($openLots as $openLot) {
                            if ($remaining <= 0.0000001) {
                                break;
                            }

                            $deduct = min($remaining, (float) $openLot->qty_onhand);

                            if ($postedLotId === null) {
                                $postedLotId = $openLot->id; // lot utama untuk audit line
                            }

                            $this->inventory->stockOut(
                                warehouseId:    $warehouseId,
                                itemId:         $itemId,
                                qty:            $deduct,
                                date:           $date,
                                sourceType:     'stock_opname_adjustment',
                                sourceId:       $adjustment->id,
                                notes:          "Shortage opname {$opname->code}",
                                allowNegative:  false,
                                lotId:          $openLot->id,
                                affectLotCost:  true,
                            );

                            $remaining -= $deduct;
                        }

                        // Jika masih sisa setelah semua lot habis (atau item tidak punya lot,
                        // mis. finished goods) → tetap posting pengurangan stok gudang TANPA lot,
                        // supaya saldo gudang benar-benar turun ke qty fisik hasil hitung.
                        // Sebelumnya sisa ini hanya di-log, akibatnya stok sistem tidak pernah
                        // dikoreksi dan tidak match dengan qty_after di adjustment line.
                        if ($remaining > 0.0000001) {
                            $this->inventory->stockOut(
                                warehouseId:      $warehouseId,
                                itemId:           $itemId,
                                qty:              $remaining,
                                date:             $date,
                                sourceType:       'stock_opname_adjustment',
                                sourceId:         $adjustment->id,
                                notes:            "Shortage opname {$opname->code} (tanpa lot)",
                                allowNegative:    false,
                                lotId:            null,
                                unitCostOverride: $unitCost,
                                affectLotCost:    false,
                            );

                            \Illuminate\Support\Facades\Log::warning(
                                "[StockOpname] {$opname->code}: shortage item #{$itemId} sebesar {$remaining} "
                                . "diposting tanpa lot (lot open tidak mencukupi / item tidak ber-lot). "
                                . "Ledger lot tidak berubah untuk porsi ini."
                            );
                        }
                    }
                }

                // ── Simpan adjustment line (dengan lot_id jika ada) ───────
                InventoryAdjustmentLine::create([
                    'inventory_adjustment_id' => $adjustment->id,
                    'item_id'    => $itemId,
                    'qty_before' => $qtyBefore,
                    'qty_after'  => $physicalQty,
                    'qty_change' => $hasDiff ? $difference : 0,
                    'direction'  => $direction,
                    'notes'      => $line->notes,
                    'lot_id'     => $postedLotId,
                ]);

                // ── Snapshot HPP periodic ─────────────────────────────────
                if ($hasDiff) {
                    $this->snapshotPeriodicCost(
                        itemId:       $itemId,
                        warehouseId:  $warehouseId,
                        snapshotDate: $date,
                        qtyBasis:     $physicalQty,
                        opname:       $opname,
                        notes:        $notes
                    );
                }
            }

            $opname->status = StockOpname::STATUS_FINALIZED;
            $opname->finalized_by = $user?->id;
            $opname->finalized_at = now();
            $opname->save();

            return $adjustment;
        });

        if ($adjustment?->status === InventoryAdjustment::STATUS_APPROVED) {
            $this->journal->postInventoryAdjustment($adjustment);
        }

        return $adjustment;
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

            // Fallback terakhir: HPP master item (items.hpp) — konsisten dengan
            // jalur periodic & pesan error yang menyuruh melengkapi HPP di master.
            if ($unitCost <= 0 && $line->item && (float) ($line->item->hpp ?? 0) > 0) {
                $unitCost = (float) $line->item->hpp;
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

    /**
     * Snapshot HPP untuk SO Periodik:
     * - Prioritas 1: HPP di baris SO (unit_cost)
     * - Prioritas 2: HPP di master item (kolom items.hpp)
     */
    protected function snapshotPeriodicCost(
        int $itemId,
        int $warehouseId,
        string $snapshotDate,
        float $qtyBasis,
        StockOpname $opname,
        ?string $notes
    ): void {
        $unitCost = 0.0;

        // Cari line SO untuk item ini
        /** @var \App\Models\StockOpnameLine|null $line */
        $line = $opname->lines->firstWhere('item_id', $itemId);

        // 1) Kalau di SO Periodik sudah ada HPP (unit_cost) → pakai itu dulu
        if ($line && $line->unit_cost !== null && (float) $line->unit_cost > 0) {
            $unitCost = (float) $line->unit_cost;
        } else {
            // 2) Kalau belum ada HPP di line → ambil dari master item (kolom items.hpp)
            $item = $line?->item ?: Item::find($itemId);

            if ($item && (float) $item->hpp > 0) {
                $unitCost = (float) $item->hpp;
            }
        }

        // Kalau tetap tidak ada HPP yang valid → jangan bikin snapshot
        if ($unitCost <= 0) {
            return;
        }

        // Matikan snapshot aktif sebelumnya
        $this->deactivateActiveSnapshots($itemId, $warehouseId);

        // Buat snapshot periodic baru
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
