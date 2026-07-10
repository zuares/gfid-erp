<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditInventoryAllocated extends Command
{
    protected $signature = 'inventory:audit-allocated';
    protected $description = 'Audit inventory allocations and fulfillment sync after integration';

    public function handle()
    {
        $this->info("Memulai audit inventory...");
        $now = now();
        $integrationDate = '2026-07-09 00:00:00'; // threshold for historical data

        // Kumpulkan ID temuan aktif sebelum audit untuk di-resolve nanti jika tidak ditemukan lagi
        $activeFindings = \App\Models\InventoryAuditFinding::whereNull('resolved_at')->pluck('id', 'id')->toArray();
        $foundIds = [];

        // Helper to record finding
        $recordFinding = function ($category, $severity, $title, $message, $item_id, $shipment_id, $fulfillment_id, $payload) use (&$foundIds, $now) {
            $finding = \App\Models\InventoryAuditFinding::updateOrCreate(
                [
                    'category' => $category,
                    'item_id' => $item_id,
                    'shipment_id' => $shipment_id,
                    'fulfillment_id' => $fulfillment_id,
                ],
                [
                    'severity' => $severity,
                    'title' => $title,
                    'message' => $message,
                    'payload' => $payload,
                    'detected_at' => $now,
                    'resolved_at' => null, // reopen if resolved previously
                ]
            );
            $foundIds[$finding->id] = $finding->id;
        };

        // 1. Phantom Stock & 2. Mismatch Stock
        $this->info("1 & 2. Cek Mismatch Allocated Qty & Phantom Stock...");
        
        $stockTotals = \Illuminate\Support\Facades\DB::table('inventory_stocks')
            ->select('item_id', \Illuminate\Support\Facades\DB::raw('SUM(allocated_qty) as stock_allocated'))
            ->groupBy('item_id')
            ->pluck('stock_allocated', 'item_id');

        $lineTotals = \Illuminate\Support\Facades\DB::table('shipment_lines as sl')
            ->join('shipments as s', 's.id', '=', 'sl.shipment_id')
            ->whereIn('s.status', ['draft', 'submitted'])
            ->select('sl.item_id', \Illuminate\Support\Facades\DB::raw('SUM(sl.allocated_qty) as lines_allocated'))
            ->groupBy('sl.item_id')
            ->pluck('lines_allocated', 'item_id');

        $allItemIds = collect($stockTotals->keys())->merge($lineTotals->keys())->unique();

        foreach ($allItemIds as $itemId) {
            $stockAlloc = (int) ($stockTotals[$itemId] ?? 0);
            $linesAlloc = (int) ($lineTotals[$itemId] ?? 0);

            if ($stockAlloc !== $linesAlloc) {
                $category = $linesAlloc === 0 ? 'phantom_stock' : 'mismatch_stock';
                $title = $linesAlloc === 0 ? "Stok Alokasi Menggantung" : "Selisih Stok Alokasi";
                $msg = "Item {$itemId} memiliki inventory allocated_qty ({$stockAlloc}) berbeda dengan total di shipment ({$linesAlloc}).";
                $recordFinding($category, 'high', $title, $msg, $itemId, null, null, [
                    'stock_allocated' => $stockAlloc,
                    'lines_allocated' => $linesAlloc,
                ]);
            }
        }

        // 3. Stale Drafts
        $this->info("3. Cek Stale Drafts...");
        $staleDrafts = \App\Models\Shipment::whereIn('status', ['draft', 'submitted'])
            ->where('created_at', '<', $now->copy()->subHours(24))
            ->whereHas('lines', function($q) {
                $q->where('allocated_qty', '>', 0);
            })->get();

        foreach ($staleDrafts as $shipment) {
            $typeLabel = ucfirst($shipment->shipment_type ?? 'manual');
            $recordFinding('stale_draft', 'medium', "Shipment {$typeLabel} Draft Lama", 
                "Shipment {$typeLabel} {$shipment->code} berumur > 24 jam masih menahan alokasi stok.",
                null, $shipment->id, null, [
                    'shipment_type' => $shipment->shipment_type,
                    'created_at' => $shipment->created_at->toDateTimeString(),
                    'age_hours' => $now->diffInHours($shipment->created_at),
                ]
            );
        }

        // 4. Unmatched Order
        $this->info("4. Cek Unmatched Orders...");
        $unmatchedScans = \App\Models\ShipmentOrderScan::whereNull('fulfillment_id')
            ->whereHas('shipment', function($q) {
                $q->where('shipment_type', \App\Models\Shipment::TYPE_MARKETPLACE);
            })
            ->where('created_at', '>=', $integrationDate)
            ->get();

        foreach ($unmatchedScans as $scan) {
            $recordFinding('unmatched_order', 'medium', "Order Tidak Dikenal di Shipment", 
                "Order {$scan->order_no} masuk ke shipment marketplace tapi tidak terkait ke Fulfillment mana pun.",
                null, $scan->shipment_id, null, [
                    'order_no' => $scan->order_no,
                    'scan_id' => $scan->id,
                ]
            );
        }

        // 5. Sync Failure (Shipment Posted, Fulfillment Belum Confirmed)
        $this->info("5. Cek Sync Failures...");
        $missedScans = \App\Models\ShipmentOrderScan::whereHas('shipment', function($q) {
                $q->where('status', 'posted')
                  ->where('shipment_type', \App\Models\Shipment::TYPE_MARKETPLACE);
            })
            ->whereHas('fulfillment', function($q) {
                $q->whereNotIn('status', ['confirmed', 'cancelled']);
            })
            ->where('created_at', '>=', $integrationDate)
            ->with(['shipment:id,code', 'fulfillment:id,status,order_no'])
            ->get();

        foreach ($missedScans as $scan) {
            $recordFinding('sync_failure', 'high', "Status Fulfillment Gagal Sync", 
                "Shipment {$scan->shipment->code} sudah posted tapi order {$scan->fulfillment->order_no} masih {$scan->fulfillment->status}.",
                null, $scan->shipment_id, $scan->fulfillment_id, [
                    'shipment_status' => 'posted',
                    'fulfillment_status' => $scan->fulfillment->status,
                ]
            );
        }

        // 6. Ghost Fulfillment (Confirmed tapi tidak ada Shipment Scan Posted)
        $this->info("6. Cek Ghost Fulfillments...");
        $ghostFulfillments = \App\Models\OrderFulfillment::where('status', 'confirmed')
            ->where('created_at', '>=', $integrationDate)
            ->whereDoesntHave('shipmentScans', function($q) {
                $q->whereHas('shipment', fn($s) => $s->where('status', 'posted'));
            })->get();

        foreach ($ghostFulfillments as $fulfillment) {
            $recordFinding('ghost_fulfillment', 'high', "Ghost Fulfillment", 
                "Order {$fulfillment->order_no} berstatus Confirmed tapi belum memiliki Shipment Posted.",
                null, null, $fulfillment->id, [
                    'order_no' => $fulfillment->order_no,
                    'confirmed_at' => $fulfillment->confirmed_at?->toDateTimeString(),
                ]
            );
        }

        // Auto Resolve
        $this->info("Meresolve anomali yang sudah diperbaiki...");
        $resolvedIds = array_diff($activeFindings, $foundIds);
        if (!empty($resolvedIds)) {
            \App\Models\InventoryAuditFinding::whereIn('id', $resolvedIds)->update(['resolved_at' => $now]);
            $this->info("Berhasil meresolve " . count($resolvedIds) . " temuan lawas.");
        }

        $this->info("Audit Selesai. Total temuan aktif saat ini: " . count($foundIds));
    }
}
