<?php

namespace App\Services\Inventory;

use App\Models\InventoryMutation;
use App\Models\InventoryStock;
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
        float | int | string | null $unitCost = null, // harga per unit (untuk moving average)
    ): ?InventoryMutation {
        $qty = $this->num($qty);
        if ($qty <= 0) {
            return null;
        }

        $date = $this->normalizeDate($date);

        /** @var InventoryStock $stock */
        $stock = InventoryStock::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
            ],
            [
                'qty' => 0,
            ]
        );

        $stock->qty = $this->num($stock->qty) + $qty;
        $stock->save();

        // cost
        $unitCostValue = $unitCost !== null ? $this->num($unitCost) : null;
        $totalCost = $unitCostValue !== null ? $unitCostValue * $qty : null;

        // catat mutasi (IN: qty_change +, total_cost +)
        $mutation = InventoryMutation::create([
            'date' => $date,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => $qty,
            'direction' => 'in',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
            'lot_id' => $lotId,
            'unit_cost' => $unitCostValue,
            'total_cost' => $totalCost,
        ]);

        // update moving average LOT kalau ada lotId + unitCost
        if ($lotId && $unitCostValue !== null) {
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
            throw new \RuntimeException("Stok tidak mencukupi untuk item {$itemId} di gudang {$warehouseId}. Stok: {$current}, mau keluar: {$qty}");
        }

        if (!$stock) {
            $stock = InventoryStock::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'qty' => 0,
            ]);
        }

        $stock->qty = $this->num($stock->qty) - $qty;
        $stock->save();

        // kalau ada LOT → ambil avg cost
        $avgCost = null;
        $totalCost = null;

        if ($lotId) {
            $avgCost = $this->lotCost->getAvgCost($lotId);
            // OUT kita simpan sebagai nilai NEGATIF, biar bisa langsung dijumlah
            $totalCost = -($avgCost * $qty);
        }

        // catat mutasi (OUT: qty_change -, total_cost -)
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

        if ($lotId) {
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
            // keluar dulu dari gudang asal
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
            );

            // ambil avg cost LOT untuk masuk ke gudang tujuan
            $unitCost = $lotId
            ? $this->lotCost->getAvgCost($lotId)
            : null;

            // masuk ke gudang tujuan
            $mutations['in'] = $this->stockIn(
                warehouseId: $toWarehouseId,
                itemId: $itemId,
                qty: $qty,
                date: $date,
                sourceType: $sourceType ?? 'transfer_in',
                sourceId: $sourceId,
                notes: $notes,
                lotId: $lotId,
                unitCost: $unitCost
            );
        });

        return $mutations;
    }

    /**
     * Sesuaikan stok supaya sama dengan nilai baru (stock opname).
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
            return null; // tidak ada perubahan
        }

        if ($diff > 0) {
            // stok kurang → masuk
            return $this->stockIn(
                warehouseId: $warehouseId,
                itemId: $itemId,
                qty: $diff,
                date: $date,
                sourceType: $sourceType,
                sourceId: $sourceId,
                notes: $notes,
                lotId: $lotId,
            );
        }

        // stok kelebihan → keluar
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
        );
    }

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
            ->with(['lot.item', 'warehouse']); // pastikan relasi ini ada di model

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
            $value = str_replace('.', '', $value); // buang titik ribuan
            $value = str_replace(',', '.', $value); // koma jadi titik
            return (float) $value;
        }

        // Kalau tidak ada koma → biarkan floatval yang baca
        // "25.000" => 25, "1234.5" => 1234.5
        return (float) $value;
    }
}
