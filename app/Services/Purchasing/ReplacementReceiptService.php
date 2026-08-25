<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Account;
use App\Models\Item;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplacementReceiptService
{
    /**
     * Create a new Purchase Receipt (Replacement) from a Purchase Return.
     * The new receipt will be returned in a 'draft' state, unless you immediately post it.
     */
    public function createFromReturn(PurchaseReturn $lockedReturn, array $linesData, string $receivedAt, int $warehouseId, ?string $notes = null, ?string $suratJalanNo = null): PurchaseReceipt
    {
        if (!in_array($lockedReturn->replacement_status, ['pending', 'partial'], true)) {
            throw ValidationException::withMessages([
                'return' => 'Status replacement sudah berubah.',
            ]);
        }

        // BLOCK: Opsi A - Satu Draft Aktif per Purchase Return
        $hasActiveDraft = PurchaseReceipt::query()
            ->where('purchase_return_id', $lockedReturn->id)
            ->where('is_replacement', true)
            ->where('status', 'draft')
            ->exists();

        if ($hasActiveDraft) {
            throw ValidationException::withMessages([
                'return' => 'Masih terdapat draft penerimaan barang pengganti yang belum diselesaikan. Silakan lanjutkan atau batalkan draft tersebut terlebih dahulu.',
            ]);
        }

        $hasReceipt = false;
        $totalAmtReceived = 0.0;

        $receipt = new PurchaseReceipt();
        $receipt->code = CodeGenerator::make('GRN-R', $receivedAt);
        $receipt->date = $receivedAt;
        $receipt->purchase_order_id = $lockedReturn->purchase_order_id;
        $receipt->supplier_id = $lockedReturn->supplier_id;
        $receipt->warehouse_id = $warehouseId;
        $receipt->status = 'draft';
        $receipt->notes = trim("Replacement IN {$lockedReturn->code} | {$notes}");
        $receipt->surat_jalan_no = $suratJalanNo;
        $receipt->is_replacement = true;
        $receipt->purchase_return_id = $lockedReturn->id;
        $receipt->created_by = auth()->id();
        
        $receipt->save();

        foreach ($linesData as $lineData) {
            $qty = (float) $lineData['qty'];
            if ($qty <= 0) {
                continue;
            }

            $returnLine = $lockedReturn->lines()->where('id', $lineData['id'])->lockForUpdate()->first();
            if (!$returnLine) {
                continue;
            }

            $outstanding = (float) $returnLine->replacement_qty_expected - (float) $returnLine->replacement_qty_received;
            if ($qty > $outstanding + 0.0001) {
                throw ValidationException::withMessages([
                    "lines.{$returnLine->id}.qty" => "Qty diterima ({$qty}) melebihi outstanding ({$outstanding}).",
                ]);
            }

            $receiptLine = new PurchaseReceiptLine();
            $receiptLine->purchase_receipt_id = $receipt->id;
            $receiptLine->purchase_order_line_id = $returnLine->grnLine->purchase_order_line_id ?? null;
            $receiptLine->purchase_return_line_id = $returnLine->id;
            
            $receiptLine->item_id = $returnLine->replacement_item_id;
            $receiptLine->lot_id = null; // MVP no lot override
            $receiptLine->qty_received = $qty;
            $receiptLine->qty_reject = 0;
            $replacementItem = Item::query()
                ->select(['id', 'unit', 'stock_unit', 'purchase_unit', 'purchase_conversion_factor'])
                ->find((int) $returnLine->replacement_item_id);
            $sameItem = (int) $returnLine->replacement_item_id === (int) $returnLine->item_id;
            $receiptLine->purchase_unit = $sameItem
                ? $returnLine->effectivePurchaseUnit()
                : ($replacementItem?->purchaseUnit() ?? 'pcs');
            $receiptLine->stock_unit = $sameItem
                ? $returnLine->effectiveStockUnit()
                : ($replacementItem?->stockUnit() ?? 'pcs');
            $receiptLine->conversion_factor = $sameItem
                ? $returnLine->effectiveConversionFactor()
                : ($replacementItem?->purchaseConversionFactor() ?? 1);
            $receiptLine->stock_qty_received = round($qty * (float) $receiptLine->conversion_factor, 6);
            $receiptLine->stock_qty_reject = 0;
            $receiptLine->unit = $receiptLine->purchase_unit;
            $receiptLine->unit_price = $returnLine->unit_price;
            $stockUnitPrice = (float) $returnLine->unit_price / max(0.000001, (float) $receiptLine->conversion_factor);
            $receiptLine->line_total = round((float) $receiptLine->stock_qty_received * $stockUnitPrice, 2);
            $receiptLine->save();

            $totalAmtReceived += $receiptLine->line_total;
            $hasReceipt = true;
        }

        if (!$hasReceipt) {
            // Rollback if no valid lines
            throw ValidationException::withMessages([
                'return' => 'Tidak ada qty valid yang diterima.',
            ]);
        }

        $receipt->subtotal = $totalAmtReceived;
        $receipt->grand_total = $totalAmtReceived;
        $receipt->save();

        return $receipt;
    }
}
