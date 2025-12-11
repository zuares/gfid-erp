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
        float | int | string | null $unitCost = null, // harga per unit (untuk moving average / nilai mutasi)
        bool $affectLotCost = true, // apakah mutasi ini ikut update LotCost (hanya untuk kain mentah)
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

        // UPDATE SALDO QTY
        $stock->qty = $this->num($stock->qty) + $qty;
        $stock->save();

        // COST
        $unitCostValue = $unitCost !== null ? $this->num($unitCost) : null;
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
            throw new \RuntimeException("Stok tidak mencukupi untuk item {$itemId} di gudang {$warehouseId}. Stok: {$current}, mau keluar: {$qty}");
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
            // Kasus WIP: pakai unit_cost yang dikirim caller (misal avg WIP-CUT)
            $avgCost = $this->num($unitCostOverride);
            $totalCost = -($avgCost * $qty);

        } elseif ($lotId && $affectLotCost) {
            // Kasus kain mentah: pakai LotCost (avg cost per lot untuk RM)
            $avgCost = $this->lotCost->getAvgCost($lotId);
            $totalCost = -($avgCost * $qty);

        } else {
            // 🔥 Kasus FG / item tanpa LOT:
            // pakai moving average cost berdasarkan mutasi IN di gudang ini
            $avgCost = $this->getItemIncomingUnitCost($warehouseId, $itemId);

            if ($avgCost !== 0.0) {
                $totalCost = -($avgCost * $qty);
            } else {
                // kalau benar-benar belum ada cost IN, biarkan null
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
                // Kalau TIDAK pakai LOT (FG / WIP) → pakai avg cost incoming di gudang asal
                $unitCostForTransfer = $this->getItemIncomingUnitCost($fromWarehouseId, $itemId);
            }

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
                unitCostOverride: $unitCostForTransfer, // ⬅️ pastikan total_cost OUT ikut nilai ini
                affectLotCost: (bool) $lotId, // ⬅️ LotCost hanya diubah kalau pakai LOT
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
                unitCost: $unitCostForTransfer ?: null, // kalau null/0, biarin kosong
                affectLotCost: (bool) $lotId, // hanya pengaruhi LotCost kalau ada LOT
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

    /**
     * Ambil daftar LOT yang masih memiliki saldo (qty_balance > 0),
     * bisa difilter per gudang dan per item.
     *
     * Return: Collection berisi baris (per lot_id + warehouse_id + item_id)
     *         dengan tambahan relasi lot, item, warehouse.
     */
    public function getAvailableLots(
        ?int $warehouseId = null, // filter opsional: pilih gudang tertentu
        ?int $itemId = null, // filter opsional: pilih item tertentu
    ): Collection {

        // 1️⃣ Mulai query dari inventory_mutations
        //    Kita hitung total qty_change per group (lot, warehouse, item).
        $q = InventoryMutation::query()
            ->selectRaw('
                lot_id,
                warehouse_id,
                item_id,
                SUM(qty_change) as qty_balance
            ')
        // 2️⃣ Pastikan ini benar-benar data LOT
            ->whereNotNull('lot_id')
        // 3️⃣ Group untuk hitung saldo per:
        //    - lot_id (LOT siapa)
        //    - warehouse_id (lokasi mana)
        //    - item_id (jenis kain apa)
            ->groupBy('lot_id', 'warehouse_id', 'item_id')
        // 4️⃣ Hanya LOT yang MASIH ada sisa (qty_balance > 0)
            ->having('qty_balance', '>', 0)
        // 5️⃣ Preload relasi supaya Blade tidak N+1 query
            ->with([
                'lot.item', // akses: $row->lot->item->name
                'warehouse', // akses: $row->warehouse->name
            ])
        // 6️⃣ Urutkan biar rapi saat ditampilkan di dropdown / tabel
            ->orderBy('lot_id')
            ->orderBy('warehouse_id');

        // 7️⃣ Filter opsional jika user pilih gudang tertentu
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }

        // 8️⃣ Filter opsional jika user ingin LOT untuk item tertentu
        if ($itemId) {
            $q->where('item_id', $itemId);
        }

        // 9️⃣ Kembalikan hasil akhir
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

    public function getLotBalance(
        int $warehouseId,
        int $itemId,
        int $lotId,
    ): float {
        return (float) InventoryMutation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('lot_id', $lotId)
            ->sum('qty_change'); // IN positif, OUT negatif → saldo
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

    public function getBalance(int $warehouseId, int $itemId): float
    {
        return (float) InventoryMutation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
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
        $row = \App\Models\InventoryMutation::query()
            ->selectRaw('
                COALESCE(SUM(CASE WHEN direction = "in"  THEN total_cost ELSE 0 END), 0) AS total_in_cost,
                COALESCE(SUM(CASE WHEN direction = "in"  THEN qty_change ELSE 0 END), 0) AS total_in_qty
            ')
            ->where('warehouse_id', $fgWarehouseId)
            ->where('item_id', $itemId)
            ->first();

        if (!$row || $row->total_in_qty <= 0) {
            return 0.0;
        }

        return (float) ($row->total_in_cost / $row->total_in_qty); // rp/pcs
    }

    public function getLotMovingAverageUnitCost(int $warehouseId, int $itemId, int $lotId): ?float
    {
        // Ambil mutasi terakhir untuk kombinasi warehouse + item + lot ini.
        $mutation = \DB::table('inventory_mutations')
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

    /**
     * Stok available di satu gudang untuk satu item.
     * Untuk saat ini = on hand, karena belum ada konsep reserved.
     */
    public function getAvailableStock(int $warehouseId, int $itemId): float
    {
        // Kalau nanti kamu punya reserved, tinggal ubah logika di sini saja.
        return $this->getOnHandQty($warehouseId, $itemId);
    }

    /**
     * Ringkasan stok per gudang untuk 1 item.
     */
    public function getStockSummaryForItem(int $itemId): array
    {
        /** @var \Illuminate\Support\Collection $rows */
        $rows = InventoryStock::query()
            ->with('warehouse') // pastikan di model InventoryStock ada belongsTo warehouse()
            ->where('item_id', $itemId)
            ->where('qty', '!=', 0)
            ->get();

        return $rows->map(function (InventoryStock $row) {
            $onHand = $this->num($row->qty ?? 0);
            $reserved = 0.0; // nanti kalau kamu punya konsep reserved, tinggal ganti di sini
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
     *
     * Urutan parameter disesuaikan dengan pemakaian di controller:
     * move(itemId, fromWarehouseId, toWarehouseId, qty, referenceType, referenceId, notes, date, allowNegative, lotId)
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
     * (Baru, opsional) Adjust berbasis SELISIH qty.
     *
     * - qtyChange > 0 → stok IN
     * - qtyChange < 0 → stok OUT
     * - qtyChange = 0 → return null
     *
     * Cocok buat manual adjustment "by difference" kalau nanti dibutuhkan.
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

        return $snapshot?->unit_cost ?? 0.0;
    }
}
