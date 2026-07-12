<?php

use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Services\Purchasing\GoodsReceiptService;
use App\Services\Purchasing\ReplacementReceiptService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "--- UAT Multiple Purchase Return & Replacement Receipt V2 ---\n";

DB::beginTransaction();

try {
    $baseGrn = PurchaseReceipt::with(['lines.item', 'order'])->where('status', 'posted')->where('is_replacement', false)->whereHas('lines', function($q) { $q->where('qty_received', '>', 0); })->first();

    if (!$baseGrn) {
        die("No base GRN found.\n");
    }
    echo "1. Found Base GRN: {$baseGrn->code}\n";

    // 2. Create Return
    $l = $baseGrn->lines->where('qty_received', '>', 0)->first();
    $purchaseReturn = new PurchaseReturn();
    $purchaseReturn->code = \App\Helpers\CodeGenerator::make('RET', now()->toDateString());
    $purchaseReturn->purchase_receipt_id = $baseGrn->id;
    $purchaseReturn->purchase_order_id = $baseGrn->purchase_order_id;
    $purchaseReturn->supplier_id = $baseGrn->supplier_id;
    $purchaseReturn->date = now()->toDateString();
    $purchaseReturn->status = 'posted';
    $purchaseReturn->replacement_status = 'pending';
    $purchaseReturn->save();

    $returnLine = new \App\Models\PurchaseReturnLine();
    $returnLine->purchase_return_id = $purchaseReturn->id;
    $returnLine->purchase_receipt_line_id = $l->id;
    $returnLine->item_id = $l->item_id;
    $returnLine->qty = 10;
    $returnLine->replacement_item_id = $l->item_id;
    $returnLine->replacement_qty_expected = 10;
    $returnLine->replacement_qty_received = 0;
    $returnLine->unit_price = $l->unit_price;
    $returnLine->save();

    $purchaseReturn->load('lines');
    
    echo "2. Created Return {$purchaseReturn->code} with expected replacement: {$purchaseReturn->lines->first()->replacement_qty_expected}\n";

    // 3. Draft Replacement GRN
    $replService = app(ReplacementReceiptService::class);
    $draftLines = [
        [
            'id' => $purchaseReturn->lines->first()->id,
            'qty' => 10
        ]
    ];
    
    $draftGrn = $replService->createFromReturn($purchaseReturn, $draftLines, now()->toDateString(), $baseGrn->warehouse_id);
    
    echo "3. Draft Replacement GRN: {$draftGrn->code} (Status: {$draftGrn->status})\n";
    $purchaseReturn->refresh();
    echo "   [DRAFT CHECK] Return Replacement Qty Received: {$purchaseReturn->lines->first()->replacement_qty_received}\n";
    echo "   [DRAFT CHECK] Return Status: {$purchaseReturn->replacement_status}\n";
    
    // 4. Post Replacement GRN
    $grnService = app(GoodsReceiptService::class);
    $postedGrn = $grnService->post($draftGrn);
    
    echo "4. Post Replacement GRN\n";
    echo "   Status after post: {$postedGrn->status}\n";
    $purchaseReturn->refresh();
    echo "   [POST CHECK] Return Replacement Qty Received: {$purchaseReturn->lines->first()->replacement_qty_received}\n";
    echo "   [POST CHECK] Return Status: {$purchaseReturn->replacement_status}\n";
    
    // 5. Unpost
    $unpostedGrn = $grnService->unpost($postedGrn);
    echo "5. Unpost Replacement GRN\n";
    echo "   Status after unpost: {$unpostedGrn->status}\n";
    $purchaseReturn->refresh();
    echo "   [UNPOST CHECK] Return Replacement Qty Received: {$purchaseReturn->lines->first()->replacement_qty_received}\n";
    echo "   [UNPOST CHECK] Return Status: {$purchaseReturn->replacement_status}\n";
    
    // 6. Repost
    $repostedGrn = $grnService->post($unpostedGrn);
    echo "6. Repost Replacement GRN\n";
    echo "   Status after repost: {$repostedGrn->status}\n";
    $purchaseReturn->refresh();
    echo "   [REPOST CHECK] Return Replacement Qty Received: {$purchaseReturn->lines->first()->replacement_qty_received}\n";
    echo "   [REPOST CHECK] Return Status: {$purchaseReturn->replacement_status}\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} finally {
    DB::rollBack();
    echo "Test Finished (Rolled back).\n";
}
