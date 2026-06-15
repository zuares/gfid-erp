<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\InventoryMutation;
use App\Models\Lot;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Temporary direct stock-in after PO approval; GRN module kept for future workflow.
 *
 * Logika:
 *  - Dipanggil sekali setelah PO bahan baku (order_type=material) di-approve.
 *  - Setiap PO line dengan allocation=hpp dan qty>0 akan dibuatkan Lot baru + inventory_mutation (IN).
 *  - Idempotent: jika sudah pernah diposting (cek mutation source_type='purchase_order_approval',
 *    source_id=po_id), proses dilewati dan tidak ada data ganda.
 *  - GRN (purchase_receipts / PurchaseReceipt) tidak disentuh sama sekali.
 */
class PurchaseOrderInventoryService
{
    public const SOURCE_TYPE = 'purchase_order_approval';

    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Post semua HPP lines PO yang approved ke gudang RM sebagai stock-in.
     *
     * @return array{posted: int, lots: int[], skipped: bool, reason: string|null}
     */
    public function postApprovedPurchaseOrderToInventory(PurchaseOrder $po): array
    {
        // ── 1. Hanya material atau finished_good PO (packing skip inventory) ──
        $orderType = $po->order_type ?? 'material';
        if (!in_array($orderType, ['material', 'finished_good'], true)) {
            return ['posted' => 0, 'lots' => [], 'skipped' => true, 'reason' => 'unsupported_order_type'];
        }

        // ── 2. Anti-double-posting: cek mutation sudah ada ──────────────
        $alreadyPosted = InventoryMutation::query()
            ->where('source_type', self::SOURCE_TYPE)
            ->where('source_id', $po->id)
            ->exists();

        if ($alreadyPosted) {
            Log::info("[PO→Inventory] PO #{$po->id} ({$po->code}) sudah diposting sebelumnya. Dilewati.");
            return ['posted' => 0, 'lots' => [], 'skipped' => true, 'reason' => 'already_posted'];
        }

        // ── 3. Pilih gudang tujuan berdasarkan order_type ───────────────
        //  material      → RM (Bahan Baku)
        //  finished_good → WH-RTS (Barang Jadi Siap Kirim)
        $warehouseCode = $orderType === 'finished_good' ? 'WH-RTS' : 'RM';
        $warehouse = Warehouse::where('code', $warehouseCode)->where('active', 1)->first();

        if (!$warehouse) {
            throw ValidationException::withMessages([
                'warehouse' => "Gudang {$warehouseCode} tidak ditemukan atau tidak aktif. "
                    . "Pastikan warehouse dengan code={$warehouseCode} sudah terdaftar.",
            ]);
        }

        // ── 4. Ambil HPP lines dengan qty > 0 ───────────────────────────
        $lines = $po->lines()
            ->with('item:id,code,name,unit')
            ->where('allocation', 'hpp')
            ->where('qty', '>', 0)
            ->get();

        if ($lines->isEmpty()) {
            Log::info("[PO→Inventory] PO #{$po->id} ({$po->code}) tidak ada HPP lines. Skip.");
            return ['posted' => 0, 'lots' => [], 'skipped' => true, 'reason' => 'no_hpp_lines'];
        }

        // ── 5. Proses setiap line → Lot baru + stock-in ─────────────────
        $postedCount = 0;
        $lotIds      = [];

        foreach ($lines as $line) {
            $qty       = round((float) $line->qty, 4);
            $unitPrice = round((float) $line->unit_price, 4);
            $itemId    = (int) $line->item_id;
            $itemCode  = $line->item->code ?? "item#{$itemId}";

            if ($qty <= 0) {
                continue;
            }

            // ── Buat Lot baru untuk setiap line PO ──────────────────────
            // Setiap baris PO = satu lot (penelusuran per-gulung / per-batch).
            // qty_onhand/avg_cost/total_cost sengaja dimulai 0 agar addReceipt()
            // (dipanggil oleh stockIn di bawah) yang menghitung nilainya dengan benar.
            // Jika langsung diisi, num() di LotCostService akan salah baca string
            // dari cast decimal:3 (misal "25.000" → 25000).
            $lot = Lot::create([
                'code'         => CodeGenerator::generate('LOT'),
                'item_id'      => $itemId,
                'initial_qty'  => $qty,
                'initial_cost' => round($qty * $unitPrice, 2),
                'qty_onhand'   => 0,
                'avg_cost'     => 0,
                'total_cost'   => 0,
                'status'       => 'open',
            ]);

            $lotIds[] = $lot->id;

            // ── Simpan lot_id ke PO line (opsional, untuk traceability) ─
            $line->lot_id = $lot->id;
            $line->save();

            // ── Stock-in ke gudang tujuan ────────────────────────────────
            $notes = sprintf(
                'Direct stock-in dari approval PO %s → %s — %s (%.3f %s × Rp%s)',
                $po->code,
                $warehouseCode,
                $itemCode,
                $qty,
                $line->item->unit ?? 'pcs',
                number_format($unitPrice, 0, ',', '.'),
            );

            $this->inventory->stockIn(
                warehouseId:          $warehouse->id,
                itemId:               $itemId,
                qty:                  $qty,
                date:                 $po->date,
                sourceType:           self::SOURCE_TYPE,
                sourceId:             $po->id,
                notes:                $notes,
                lotId:                $lot->id,
                unitCost:             $unitPrice > 0 ? $unitPrice : null,
                affectLotCost:        $unitPrice > 0,
            );

            $postedCount++;

            Log::info("[PO→Inventory] Posted line PO #{$po->id} → Lot #{$lot->id} ({$lot->code}) "
                . "{$itemCode} qty={$qty} cost={$unitPrice}");
        }

        Log::info("[PO→Inventory] PO #{$po->id} ({$po->code}) selesai: "
            . "{$postedCount} lines diposting, lots=" . implode(',', $lotIds));

        return [
            'posted'  => $postedCount,
            'lots'    => $lotIds,
            'skipped' => false,
            'reason'  => null,
        ];
    }

    /**
     * Cek apakah PO sudah pernah diposting ke inventory.
     */
    public function isPosted(PurchaseOrder $po): bool
    {
        return InventoryMutation::query()
            ->where('source_type', self::SOURCE_TYPE)
            ->where('source_id', $po->id)
            ->exists();
    }

    /**
     * Rollback posting PO (void mutation + hapus lot yang dibuat saat approval).
     * Hanya boleh dipakai jika PO belum punya GRN dan stock belum dikonsumsi.
     *
     * @throws ValidationException jika ada konsumsi stok sudah terjadi
     */
    public function rollbackPosting(PurchaseOrder $po): array
    {
        // Cek ada mutation yang referensikan lot ini (consumption)
        $lotIds = InventoryMutation::query()
            ->where('source_type', self::SOURCE_TYPE)
            ->where('source_id', $po->id)
            ->pluck('lot_id')
            ->filter()
            ->unique()
            ->values();

        // Pastikan tidak ada out-mutation pada lot-lot ini
        $hasConsumption = InventoryMutation::query()
            ->whereIn('lot_id', $lotIds)
            ->where('direction', 'out')
            ->exists();

        if ($hasConsumption) {
            throw ValidationException::withMessages([
                'rollback' => 'Tidak bisa rollback: ada lot dari PO ini yang sudah dikonsumsi (cutting/dll).',
            ]);
        }

        $removed = 0;

        // Hapus mutation in yang terkait
        InventoryMutation::query()
            ->where('source_type', self::SOURCE_TYPE)
            ->where('source_id', $po->id)
            ->delete();

        // Hapus lot yang dibuat saat posting
        if ($lotIds->isNotEmpty()) {
            // Update PO lines: hapus lot_id referensi
            $po->lines()->whereIn('lot_id', $lotIds)->update(['lot_id' => null]);

            $removed = Lot::whereIn('id', $lotIds)->delete();
        }

        Log::warning("[PO→Inventory] ROLLBACK posting PO #{$po->id} ({$po->code}): "
            . "{$removed} lots dihapus.");

        return ['rolled_back_lots' => $removed];
    }
}
