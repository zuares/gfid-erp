<?php

namespace App\Services\Production;

use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;

class ProductionOrderStatusService
{
    public function recalc(ProductionOrder $order): void
    {
        // kalau cancelled/done manual, jangan diubah (opsional kebijakan)
        if (in_array($order->status, ['cancelled'], true)) {
            return;
        }

        // total target per FG item
        $targets = $order->lines()
            ->select('item_id', DB::raw('SUM(qty_target) as qty_target'))
            ->groupBy('item_id')
            ->pluck('qty_target', 'item_id');

        // total received (posted) per FG item, berdasarkan mutation IN ke WH-FG
        // tetapi kita lebih aman: pakai source_type production_receipt dan status receipt posted
        $postedReceiptIds = $order->receipts()->where('status', 'posted')->pluck('id');

        $receivedByItem = DB::table('inventory_mutations')
            ->where('source_type', 'production_receipt')
            ->whereIn('source_id', $postedReceiptIds)
            ->where('direction', 'in')
            ->select('item_id', DB::raw('SUM(qty_change) as qty_received'))
            ->groupBy('item_id')
            ->pluck('qty_received', 'item_id');

        // indikator progress (issue posted / receipt posted / activity)
        $hasPostedIssue = $order->issues()->where('status', 'posted')->exists();
        $hasPostedReceipt = $order->receipts()->where('status', 'posted')->exists();
        $hasActivity = $order->activities()->exists();

        // cek done: semua item target terpenuhi
        $isDone = true;
        foreach ($targets as $itemId => $qtyTarget) {
            $qtyReceived = (float) ($receivedByItem[$itemId] ?? 0);
            if ($qtyReceived + 1e-9 < (float) $qtyTarget) { // toleransi float
                $isDone = false;
                break;
            }
        }

        if ($isDone && $targets->count() > 0) {
            $order->update(['status' => 'done']);
            return;
        }

        if ($hasPostedIssue || $hasPostedReceipt || $hasActivity) {
            $order->update(['status' => 'in_progress']);
            return;
        }

        // fallback kalau belum ada apa-apa
        $order->update(['status' => 'draft']);
    }
}
