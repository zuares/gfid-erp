<?php

namespace App\Services\Inventory;

use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Services\Inventory\LotCostService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        protected LotCostService $lotCost, // service moving average per LOT
    ) {}

    /**
     * Tambah stok (IN) ke suatu gudang/item.
     */
    public function stockIn(
        int $warehouseId,
        int $itemId,
        float | int | string $qty,
        string | \DateTimeInterface  | null $date = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $notes = null,
        ?int $lotId = null, // optional LOT
        float | int | string | null $unitCost = null, // harga per unit (untuk moving average / nilai mutasi)
        bool $affectLotCost = true, // apakah mutasi ini ikut update LotCost (hanya untuk kain mentah)
    ): ?InventoryMutation {
        $qty = $this->num($qty);
        if ($qty <= 0) {
            return null;
        }

        $date = $this->normalizeDate($date);

        /** @var InventoryStock $stock */
        $stock = InventoryStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            $stock = InventoryStock::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'qty' => 0,
            ]);
        }

        // UPDATE SALDO QTY
        $stock->qty = $this->num($stock->qty) + $qty;
        $stock->save();

        // COST
        $unitCostValue = $unitCost !== null ? $this->num($unitCost) : null;
        if ($unitCostValue !== null && $unitCostValue <= 0) {
            $unitCostValue = null;
        }

        $totalCost = $unitCostValue !== null ? $unitCostValue * $qty : null;

        // CATAT MUTASI (IN: qty_change +, total_cost +)
        $mutation = InventoryMutation::create([
            'date' => $date,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => $qty,
            'direction' => 'in',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
            'lot_id' => $lotId ?: null,
            'unit_cost' => $unitCostValue,
            'total_cost' => $totalCost,
        ]);

        // UPDATE LOT COST (HANYA JIKA MEMANG MAU PENGARUHI LOT KAIN)
        if ($affectLotCost && $lotId && $unitCostValue !== null) {
            $this->lotCost->addReceipt($lotId, $qty, $unitCostValue);
        }

        return $mutation;
    }

    /**
     * Kurangi stok (OUT) dari suatu gudang/item.
     */
    public function stockOut(
        int $warehouseId,
        int $itemId,
        float | int | string $qty,
        string | \DateTimeInterface  | null $date = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $notes = null,
        bool $allowNegative = false,
        ?int $lotId = null, // optional LOT
        float | int | string | null $unitCostOverride = null, // untuk WIP, bisa pakai unit_cost custom
        bool $affectLotCost = true, // hanya true untuk pemakaian kain mentah
    ): ?InventoryMutation {
        $qty = $this->num($qty);
        if ($qty <= 0) {
            return null;
        }

        $date = $this->normalizeDate($date);

        /** @var InventoryStock|null $stock */
        $stock = InventoryStock::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->first();

        $current = $stock?->qty ?? 0;

        if (!$allowNegative && $current < $qty) {
            throw new \RuntimeException(
                "Stok tidak mencukupi untuk item {$itemId} di gudang {$warehouseId}. Stok: {$current}, mau keluar: {$qty}"
            );
        }

        if (!$stock) {
            $stock = InventoryStock::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'qty' => 0,
            ]);
        }

        // UPDATE SALDO QTY
        $stock->qty = $this->num($stock->qty) - $qty;
        $stock->save();

        // ===== COSTING =====
        $avgCost = null;
        $totalCost = null;

        if ($unitCostOverride !== null) {
            // Kasus WIP/transfer: pakai unit_cost override dari caller
            $avgCost = $this->num($unitCostOverride);
            if ($avgCost > 0.000001) {
                $totalCost = -($avgCost * $qty);
            } else {
                $avgCost = null;
                $totalCost = null;
            }
        } elseif ($lotId && $affectLotCost) {
            // Kasus kain mentah: pakai LotCost (avg cost per lot untuk RM)
            $avgCost = $this->lotCost->getAvgCost($lotId);
            if ($avgCost > 0.000001) {
                $totalCost = -($avgCost * $qty);
            } else {
                $avgCost = null;
                $totalCost = null;
            }
        } else {
            // Kasus FG / item tanpa LOT: MA dari mutasi IN gudang ini
            $avgCost = $this->getItemIncomingUnitCost($warehouseId, $itemId);

            if ($avgCost > 0.000001) {
                $totalCost = -($avgCost * $qty);
            } else {
                $avgCost = null;
                $totalCost = null;
            }
        }

        // CATAT MUTASI (OUT: qty_change -, total_cost -)
        $mutation = InventoryMutation::create([
            'date' => $date,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => -$qty,
            'direction' => 'out',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
            'lot_id' => $lotId,
            'unit_cost' => $avgCost,
            'total_cost' => $totalCost,
        ]);

        // UPDATE LotCost HANYA UNTUK PEMAKAIAN KAIN MENTAH
        if ($lotId && $affectLotCost && $avgCost !== null) {
            $this->lotCost->consume($lotId, $qty);
        }

        return $mutation;
    }

    /**
     * Transfer stok antar gudang. (bisa bawa LOT juga)
     */
    public function transfer(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $itemId,
        float | int | string $qty,
        string | \DateTimeInterface  | null $date = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $notes = null,
        bool $allowNegative = false,
        ?int $lotId = null, // kalau lot-nya ikut pindah gudang
    ): array {
        $qty = $this->num($qty);
        if ($qty <= 0) {
            return [];
        }

        $date = $this->normalizeDate($date);
        $mutations = [];

        DB::transaction(function () use (
            $fromWarehouseId,
            $toWarehouseId,
            $itemId,
            $qty,
            $date,
            $sourceType,
            $sourceId,
            $notes,
            $allowNegative,
            $lotId,
            &$mutations
        ) {
            // 🔹 Tentukan unit cost yang akan dibawa di transfer ini
            if ($lotId) {
                // Kalau ada LOT → pakai moving average per LOT
                $unitCostForTransfer = $this->lotCost->getAvgCost($lotId);
            } else {
                /**
                 * ✅ Non-LOT (FG/WIP) - PRIORITAS:
                 * 1) last known unit_cost dari ledger gudang asal (mutasi terakhir yang punya unit_cost)
                 * 2) snapshot cost (per-warehouse/global)
                 * 3) 0
                 *
                 * Ini penting untuk WIP-FIN → RTS supaya cost yang kebawa nyambung dengan jejak WIP-FIN.
                 */
                $unitCostForTransfer =
                $this->getLastKnownUnitCost($fromWarehouseId, $itemId) ?? $this->resolveUnitCostFromSnapshot($itemId, $fromWarehouseId) ?? 0.0;
            }

            $unitCostOverride = ($unitCostForTransfer > 0.000001) ? $unitCostForTransfer : null;

            // 1️⃣ OUT dari gudang asal
            $mutations['out'] = $this->stockOut(
                warehouseId: $fromWarehouseId,
                itemId: $itemId,
                qty: $qty,
                date: $date,
                sourceType: $sourceType ?? 'transfer_out',
                sourceId: $sourceId,
                notes: $notes,
                allowNegative: $allowNegative,
                lotId: $lotId,
                unitCostOverride: $unitCostOverride, // ✅ total_cost OUT ikut cost ini
                affectLotCost: (bool) $lotId, // LotCost hanya berubah kalau pakai LOT
            );

            // 2️⃣ IN ke gudang tujuan
            $mutations['in'] = $this->stockIn(
                warehouseId: $toWarehouseId,
                itemId: $itemId,
                qty: $qty,
                date: $date,
                sourceType: $sourceType ?? 'transfer_in',
                sourceId: $sourceId,
                notes: $notes,
                lotId: $lotId,
                unitCost: $unitCostOverride, // ✅ cost ikut kebawa
                affectLotCost: (bool) $lotId,
            );
        });

        return $mutations;
    }

    /**
     * Sesuaikan stok supaya sama dengan nilai baru (stock opname / physical adjust).
     * Akan membuat mutasi in/out sesuai selisih.
     */
    public function adjustTo(
        int $warehouseId,
        int $itemId,
        float | int | string $newQty,
        string | \DateTimeInterface  | null $date = null,
        ?string $sourceType = 'adjustment',
        ?int $sourceId = null,
        ?string $notes = null,
        ?int $lotId = null, // bisa juga per LOT
        float | int | string | null $unitCostOverride = null, // untuk kunci unit_cost mutasi (terutama IN)
        bool $affectLotCost = true, // false untuk SO/adjustment biar LotCost aman
    ): ?InventoryMutation {
        $newQty = $this->num($newQty);
        $date = $this->normalizeDate($date);

        /** @var InventoryStock|null $stock */
        $stock = InventoryStock::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->first();

        $current = $stock?->qty ?? 0;
        $diff = $newQty - $current;

        if (abs($diff) < 0.0000001) {
            return null;
        }

        $unitCostValue = $unitCostOverride !== null ? $this->num($unitCostOverride) : null;
        if ($unitCostValue !== null && $unitCostValue <= 0) {
            $unitCostValue = null;
        }

        if ($diff > 0) {
            return $this->stockIn(
                warehouseId: $warehouseId,
                itemId: $itemId,
                qty: $diff,
                date: $date,
                sourceType: $sourceType,
                sourceId: $sourceId,
                notes: $notes,
                lotId: $lotId,
                unitCost: $unitCostValue,
                affectLotCost: $affectLotCost,
            );
        }

        return $this->stockOut(
            warehouseId: $warehouseId,
            itemId: $itemId,
            qty: abs($diff),
            date: $date,
            sourceType: $sourceType,
            sourceId: $sourceId,
            notes: $notes,
            allowNegative: false,
            lotId: $lotId,
            unitCostOverride: $unitCostValue,
            affectLotCost: $affectLotCost,
        );
    }

    /**
     * Ambil daftar LOT yang masih memiliki saldo (qty_balance > 0),
     * bisa difilter per gudang dan per item.
     */
    public function getAvailableLots(
        ?int $warehouseId = null,
        ?int $itemId = null,
    ): Collection {
        $q = InventoryMutation::query()
            ->selectRaw('
                lot_id,
                warehouse_id,
                item_id,
                SUM(qty_change) as qty_balance
            ')
            ->whereNotNull('lot_id')
            ->groupBy('lot_id', 'warehouse_id', 'item_id')
            ->having('qty_balance', '>', 0)
            ->with([
                'lot.item',
                'warehouse',
            ])
            ->orderBy('lot_id')
            ->orderBy('warehouse_id');

        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }

        if ($itemId) {
            $q->where('item_id', $itemId);
        }

        return $q->get();
    }

    // =====================================================================
    // HELPER
    // =====================================================================

    protected function normalizeDate(string | \DateTimeInterface  | null $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->toDateString();
        }

        if (is_string($date) && trim($date) !== '') {
            return Carbon::parse($date)->toDateString();
        }

        return now()->toDateString();
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

        // Kalau ada koma → anggap format Indonesia (1.234,56)
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        return (float) $value;
    }

    public function getBalance(int $warehouseId, int $itemId): float
    {
        return (float) InventoryMutation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->sum('qty_change');
    }

    public function getLotBalance(int $warehouseId, int $itemId, int $lotId): float
    {
        return (float) InventoryMutation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('lot_id', $lotId)
            ->sum('qty_change');
    }

    public function getLotPurchaseUnitCost(int $itemId, int $lotId): float
    {
        $query = InventoryMutation::query()
            ->where('item_id', $itemId)
            ->where('lot_id', $lotId)
            ->where('direction', 'in')
            ->where('source_type', 'purchase_receipt')
            ->whereNotNull('unit_cost');

        $totalQty = (float) $query->sum('qty_change');
        $totalCost = (float) $query->sum(DB::raw('qty_change * unit_cost'));

        if ($totalQty <= 0.000001) {
            return 0.0;
        }

        return $totalCost / $totalQty;
    }

    /**
     * Rata-rata unit_cost berdasarkan seluruh mutasi IN (historis) di gudang tsb.
     * Catatan: ini bukan MA "true perpetual" (tidak memperhitungkan OUT),
     * tapi cukup sebagai fallback untuk item tanpa override.
     */
    public function getItemIncomingUnitCost(int $warehouseId, int $itemId): float
    {
        $query = InventoryMutation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('direction', 'in')
            ->whereNotNull('unit_cost');

        $totalQty = (float) $query->sum('qty_change');
        $totalCost = (float) $query->sum('total_cost');

        if ($totalQty <= 0.000001) {
            return 0.0;
        }

        return $totalCost / $totalQty;
    }

    public function getMovingAverageFgCost(int $fgWarehouseId, int $itemId): float
    {
        $row = InventoryMutation::query()
            ->selectRaw('
                COALESCE(SUM(CASE WHEN direction = "in"  THEN total_cost ELSE 0 END), 0) AS total_in_cost,
                COALESCE(SUM(CASE WHEN direction = "in"  THEN qty_change ELSE 0 END), 0) AS total_in_qty
            ')
            ->where('warehouse_id', $fgWarehouseId)
            ->where('item_id', $itemId)
            ->first();

        if (!$row || (float) $row->total_in_qty <= 0) {
            return 0.0;
        }

        return (float) ($row->total_in_cost / $row->total_in_qty);
    }

    public function getLotMovingAverageUnitCost(int $warehouseId, int $itemId, int $lotId): ?float
    {
        $mutation = DB::table('inventory_mutations')
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('lot_id', $lotId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        if (!$mutation || $mutation->unit_cost === null) {
            return null;
        }

        return (float) $mutation->unit_cost;
    }

    public function getOnHandQty(int $warehouseId, int $itemId): float
    {
        /** @var InventoryStock|null $stock */
        $stock = InventoryStock::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->first();

        return $this->num($stock?->qty ?? 0);
    }

    public function getAvailableStock(int $warehouseId, int $itemId): float
    {
        return $this->getOnHandQty($warehouseId, $itemId);
    }

    public function getStockSummaryForItem(int $itemId): array
    {
        $rows = InventoryStock::query()
            ->with('warehouse')
            ->where('item_id', $itemId)
            ->where('qty', '!=', 0)
            ->get();

        return $rows->map(function (InventoryStock $row) {
            $onHand = $this->num($row->qty ?? 0);
            $reserved = 0.0;
            $available = $onHand - $reserved;

            return [
                'warehouse_id' => $row->warehouse_id,
                'code' => $row->warehouse->code ?? null,
                'name' => $row->warehouse->name ?? null,
                'on_hand' => $onHand,
                'reserved' => $reserved,
                'available' => $available,
            ];
        })->values()->all();
    }

    /**
     * Wrapper sederhana untuk transfer stok antar gudang.
     */
    public function move(
        int $itemId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float | int | string $qty,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        string | \DateTimeInterface  | null $date = null,
        bool $allowNegative = false,
        ?int $lotId = null,
    ): array {
        return $this->transfer(
            fromWarehouseId: $fromWarehouseId,
            toWarehouseId: $toWarehouseId,
            itemId: $itemId,
            qty: $qty,
            date: $date,
            sourceType: $referenceType,
            sourceId: $referenceId,
            notes: $notes,
            allowNegative: $allowNegative,
            lotId: $lotId,
        );
    }

    /**
     * Adjust berbasis SELISIH qty.
     */
    public function adjustByDifference(
        int $warehouseId,
        int $itemId,
        float | int | string $qtyChange,
        string | \DateTimeInterface  | null $date = null,
        ?string $sourceType = 'adjustment',
        ?int $sourceId = null,
        ?string $notes = null,
        ?int $lotId = null,
        bool $allowNegative = false,
        float | int | string | null $unitCostOverride = null,
        bool $affectLotCost = true,
    ): ?InventoryMutation {
        $diff = $this->num($qtyChange);
        if (abs($diff) < 0.0000001) {
            return null;
        }

        $date = $this->normalizeDate($date);

        if ($diff > 0) {
            return $this->stockIn(
                warehouseId: $warehouseId,
                itemId: $itemId,
                qty: $diff,
                date: $date,
                sourceType: $sourceType,
                sourceId: $sourceId,
                notes: $notes,
                lotId: $lotId,
                unitCost: $unitCostOverride,
                affectLotCost: $affectLotCost,
            );
        }

        return $this->stockOut(
            warehouseId: $warehouseId,
            itemId: $itemId,
            qty: abs($diff),
            date: $date,
            sourceType: $sourceType,
            sourceId: $sourceId,
            notes: $notes,
            allowNegative: $allowNegative,
            lotId: $lotId,
            unitCostOverride: $unitCostOverride,
            affectLotCost: $affectLotCost,
        );
    }

    public function getActiveUnitCost(Item $item, ?int $warehouseId = null): float
    {
        $snapshot = ItemCostSnapshot::getActiveForItem($item->id, $warehouseId);
        return (float) ($snapshot?->unit_cost ?? 0.0);
    }

    protected function resolveUnitCostFromSnapshot(int $itemId, ?int $warehouseId = null): ?float
    {
        $item = Item::find($itemId);
        if (!$item) {
            return null;
        }

        // prioritas: snapshot per-warehouse (kalau ada)
        $cost = (float) ($this->getActiveUnitCost($item, $warehouseId) ?? 0.0);
        if ($cost > 0.000001) {
            return $cost;
        }

        // fallback: snapshot global
        $costGlobal = (float) ($this->getActiveUnitCost($item, null) ?? 0.0);
        if ($costGlobal > 0.000001) {
            return $costGlobal;
        }

        return null;
    }

    /**
     * ✅ NEW: Ambil unit_cost terakhir yang "tercatat" untuk item pada gudang tertentu.
     * Dipakai untuk carry-cost transfer non-LOT (WIP/FG), agar nyambung dengan ledger gudang asal.
     */
    public function getLastKnownUnitCost(int $warehouseId, int $itemId): ?float
    {
        $row = InventoryMutation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->whereNotNull('unit_cost')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        if (!$row) {
            return null;
        }

        $cost = (float) $row->unit_cost;
        return ($cost > 0.000001) ? $cost : null;
    }

    public function getStockQty(int $warehouseId, int $itemId): float
    {
        $stock = \App\Models\InventoryStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->first();

        return (float) ($stock?->qty ?? 0);
    }

    public function reverseBySource(
        array $originalSourceTypes,
        int $originalSourceId,
        string $voidSourceType,
        int $voidSourceId,
        ?string $notesPrefix = null,
        string | \DateTimeInterface  | null $date = null,
    ): void {
        $date = $this->normalizeDate($date);

        // Guard: jangan sampai double void
        $already = InventoryMutation::query()
            ->where('source_type', $voidSourceType)
            ->where('source_id', $voidSourceId)
            ->exists();

        if ($already) {
            throw new \RuntimeException("Sudah pernah di-reverse ({$voidSourceType} #{$voidSourceId}).");
        }

        $rows = InventoryMutation::query()
            ->whereIn('source_type', $originalSourceTypes)
            ->where('source_id', $originalSourceId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($rows->isEmpty()) {
            throw new \RuntimeException("Mutasi sumber tidak ditemukan untuk reverse.");
        }

        foreach ($rows as $m) {
            $delta = -$this->num($m->qty_change); // reverse

            $note = trim(
                ($notesPrefix ? $notesPrefix . ' | ' : '')
                . "reverse mut#{$m->id}"
                . ($m->notes ? " | {$m->notes}" : '')
            );

            // ✅ penting: affectLotCost = false supaya void tidak ngacak moving average LOT kain
            // ✅ unitCostOverride pakai unit_cost lama supaya total_cost balik dengan cost yang sama
            $this->adjustByDifference(
                warehouseId: (int) $m->warehouse_id,
                itemId: (int) $m->item_id,
                qtyChange: $delta,
                date: $date,
                sourceType: $voidSourceType,
                sourceId: $voidSourceId,
                notes: $note,
                lotId: $m->lot_id ? (int) $m->lot_id : null,
                allowNegative: false,
                unitCostOverride: $m->unit_cost !== null ? (float) $m->unit_cost : null,
                affectLotCost: false,
            );
        }
    }

}
