<?php
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Services\Purchasing\GoodsReceiptService;
use App\Services\Purchasing\ReplacementReceiptService;
use App\Models\InventoryStock;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    echo "--- UAT Multiple Purchase Return & Replacement Receipt ---\n";

    // 1. Setup Data - Find an existing posted GRN
    $grn = PurchaseReceipt::where('status', 'posted')->where('is_replacement', false)->has('lines')->latest('id')->first();
    if (!$grn) {
        throw new \Exception("No posted GRN found for testing.");
    }
    echo "1. Found Base GRN: {$grn->code}\n";
    $grnLine = $grn->lines->first();
    $itemId = $grnLine->item_id;

    // 2. Create Purchase Return
    echo "2. Creating Purchase Return...\n";
    $return = new PurchaseReturn();
    $return->code = 'UAT-RET-001';
    $return->date = now();
    $return->purchase_receipt_id = $grn->id;
    $return->purchase_order_id = $grn->purchase_order_id;
    $return->supplier_id = $grn->supplier_id;
    $return->status = 'posted';
    $return->resolution_type = 'replacement';
    $return->replacement_status = 'pending';
    $return->total = 10 * $grnLine->unit_price;
    $return->save();

    $returnLine = new PurchaseReturnLine();
    $returnLine->purchase_return_id = $return->id;
    $returnLine->purchase_receipt_line_id = $grnLine->id;
    $returnLine->item_id = $itemId;
    $returnLine->replacement_item_id = $itemId;
    $returnLine->qty = 10;
    $returnLine->replacement_qty_expected = 10;
    $returnLine->replacement_qty_received = 0;
    $returnLine->unit_price = $grnLine->unit_price;
    $returnLine->line_total = 10 * $grnLine->unit_price;
    $returnLine->save();
    
    echo "   Return {$return->code} created with expected replacement: 10\n";

    // 3. Test Replacement GRN Draft Lifecycle
    echo "3. Creating Replacement GRN (Draft)...\n";
    $repService = app(ReplacementReceiptService::class);
    
    $replacementGrn = $repService->createFromReturn($return, [
        ['id' => $returnLine->id, 'qty' => 6]
    ], now()->toDateString(), $grn->warehouse_id);

    echo "   Created Replacement GRN: {$replacementGrn->code} (Status: {$replacementGrn->status})\n";

    // Check Return State during DRAFT
    $return->refresh();
    $returnLine->refresh();
    echo "   [DRAFT CHECK] Return Replacement Qty Received: {$returnLine->replacement_qty_received}\n";
    echo "   [DRAFT CHECK] Return Status: {$return->replacement_status}\n";

    if ($returnLine->replacement_qty_received == 6) {
        echo "   [!] CRITICAL FINDING: replacement_qty_received is incremented during DRAFT phase!\n";
    }

    // 4. Test Post Replacement GRN
    echo "4. Posting Replacement GRN...\n";
    $grnService = app(GoodsReceiptService::class);
    $grnService->post($replacementGrn);
    $replacementGrn->refresh();
    echo "   Replacement GRN Status after post: {$replacementGrn->status}\n";

    // Check Inventory Mutation
    $mutations = DB::table('inventory_mutations')
        ->where('source_type', 'purchase_receipt')
        ->where('source_id', $replacementGrn->id)
        ->get();
    echo "   Inventory Mutations for Replacement GRN: " . $mutations->count() . "\n";

    // Check Journal
    $journals = DB::table('journals')
        ->where('source_id', $replacementGrn->id)
        ->get();
    echo "   Journals for Replacement GRN: " . $journals->count() . "\n";
    
    foreach ($journals as $j) {
        echo "     Journal ID {$j->id} | Ref: {$j->ref_no} | Amt: {$j->amount}\n";
        $lines = DB::table('journal_lines')->where('journal_id', $j->id)->get();
        foreach ($lines as $jl) {
            $account = DB::table('accounts')->where('id', $jl->account_id)->first();
            echo "       " . ($jl->debit > 0 ? "Dr" : "Cr") . " " . $account->code . " - " . $account->name . " | " . max($jl->debit, $jl->credit) . "\n";
        }
    }

    // 5. Test Unpost Replacement GRN
    echo "5. Unposting Replacement GRN...\n";
    $grnService->unpost($replacementGrn);
    $replacementGrn->refresh();
    echo "   Replacement GRN Status after unpost: {$replacementGrn->status}\n";

    $return->refresh();
    $returnLine->refresh();
    echo "   [UNPOST CHECK] Return Replacement Qty Received: {$returnLine->replacement_qty_received}\n";
    echo "   [UNPOST CHECK] Return Status: {$return->replacement_status}\n";

    // 6. Test Supplier Invoice
    echo "6. Testing Supplier Invoice Filter...\n";
    $invoicesWithReplacement = DB::table('supplier_invoices')
        ->where('purchase_order_id', $grn->purchase_order_id)
        ->get();
    
    echo "Done.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
} finally {
    DB::rollBack();
}
