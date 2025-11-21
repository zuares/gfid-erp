<?php

namespace App\Services\Inventory;

use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Tambah stok (IN) ke suatu gudang/item.
     *
     * @param  int                             $warehouseId
     * @param  int                             $itemId
     * @param  float|int|string                $qty
     * @param  string|\DateTimeInterface|null  $date
     * @param  string|null                     $sourceType  contoh: 'purchase_receipt', 'cutting_receive'
     * @param  int|null                        $sourceId    id dokumen sumber
     * @param  string|null                     $notes
     * @return InventoryMutation|null
     */
    public function stockIn(
        int $warehouseId,
        int $itemId,
        float | int | string $qty,
        string | \DateTimeInterface  | null $date = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $notes = null,
    ): ?InventoryMutation {
        $qty = $this->num($qty);
        if ($qty <= 0) {
            return null;
        }

        $date = $this->normalizeDate($date);

        // update / buat stok
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

        // catat mutasi
        return InventoryMutation::create([
            'date' => $date,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => $qty,
            'direction' => 'in',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
        ]);
    }

    /**
     * Kurangi stok (OUT) dari suatu gudang/item.
     *
     * @return InventoryMutation|null
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

        // catat mutasi
        return InventoryMutation::create([
            'date' => $date,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => -$qty,
            'direction' => 'out',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
        ]);
    }

    /**
     * Transfer stok antar gudang.
     * Menghasilkan 2 mutasi: out dari gudang asal, in ke gudang tujuan.
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
            );

            // masuk ke gudang tujuan
            $mutations['in'] = $this->stockIn(
                warehouseId: $toWarehouseId,
                itemId: $itemId,
                qty: $qty,
                date: $date,
                sourceType: $sourceType ?? 'transfer_in',
                sourceId: $sourceId,
                notes: $notes,
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
            return $this->stockIn(
                warehouseId: $warehouseId,
                itemId: $itemId,
                qty: $diff,
                date: $date,
                sourceType: $sourceType,
                sourceId: $sourceId,
                notes: $notes,
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
        );
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

        // dd($value);
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Kalau sudah numeric (hasil validasi / cast Laravel), langsung saja
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        // Pastikan string
        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // Kalau ada koma → anggap format Indonesia: "1.234,56" / "24,00"
        if (strpos($value, ',') !== false) {
            // Hilangkan titik ribuan
            $value = str_replace('.', '', $value);
            // Ganti koma jadi titik desimal
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // Kalau tidak ada koma, tapi pola ribuan: "1.234" atau "1.234.567"
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        // Default: biarkan Laravel terjemahkan (mis. "1234.56")
        return (float) $value;
    }
}
