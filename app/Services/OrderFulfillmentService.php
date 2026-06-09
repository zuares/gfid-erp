<?php

namespace App\Services;

use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\MarketplaceOrder;
use App\Models\OrderFulfillment;
use App\Models\OrderFulfillmentLine;
use App\Models\SkuMapping;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
    /**
     * Buat draft fulfillment untuk sebuah MarketplaceOrder.
     * Idempotent — tidak akan dobel jika sudah ada.
     */
    public function createDraft(MarketplaceOrder $order): OrderFulfillment
    {
        // Cek sudah ada belum
        $existing = OrderFulfillment::where('marketplace_order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        // Ambil warehouse default dari store
        $order->load('store.defaultWarehouse');
        $warehouseId = $order->store?->default_warehouse_id;
        $channelCode = $order->store?->channel?->code;

        return DB::transaction(function () use ($order, $warehouseId, $channelCode) {
            $fulfillment = OrderFulfillment::create([
                'marketplace_order_id' => $order->id,
                'warehouse_id'         => $warehouseId,
                'status'               => OrderFulfillment::STATUS_DRAFT,
            ]);

            // Load items jika belum
            $order->loadMissing('items');

            foreach ($order->items as $orderItem) {
                $sku    = $orderItem->model_sku ?? $orderItem->item_sku;
                $itemId = $sku ? SkuMapping::resolve($sku, $channelCode) : null;

                // Hitung stok tersedia
                $stock = 0;
                $lotId = null;
                if ($itemId && $warehouseId) {
                    $stockRow = InventoryStock::where('item_id', $itemId)
                        ->where('warehouse_id', $warehouseId)
                        ->orderByDesc('qty')
                        ->first();
                    $stock = (int) ($stockRow?->qty ?? 0);
                    $lotId = $stockRow?->lot_id;
                }

                OrderFulfillmentLine::create([
                    'fulfillment_id'           => $fulfillment->id,
                    'marketplace_order_item_id' => $orderItem->id,
                    'marketplace_sku'           => $sku,
                    'marketplace_item_name'     => $orderItem->item_name,
                    'item_id'                   => $itemId,
                    'lot_id'                    => $lotId,
                    'qty_ordered'               => $orderItem->qty,
                    'qty_fulfilled'             => min((int) $orderItem->qty, $stock),
                    'stock_available'           => $stock,
                ]);
            }

            // Naikkan status ke pending_review jika sudah bisa direview
            $fulfillment->update(['status' => OrderFulfillment::STATUS_PENDING_REVIEW]);

            return $fulfillment->fresh('lines');
        });
    }

    /**
     * Update satu line: ganti item / lot / qty_fulfilled / notes.
     * Bisa dipakai saat owner edit manual di halaman queue.
     */
    public function updateLine(OrderFulfillmentLine $line, array $data): OrderFulfillmentLine
    {
        $warehouseId = $line->fulfillment->warehouse_id;

        // Jika item diganti, hitung ulang stok
        if (isset($data['item_id']) && $data['item_id'] != $line->item_id) {
            $data['substituted'] = true;
            $stockRow = $warehouseId
                ? InventoryStock::where('item_id', $data['item_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->orderByDesc('qty')
                    ->first()
                : null;
            $data['stock_available'] = (int) ($stockRow?->qty ?? 0);
            $data['lot_id']          = $data['lot_id'] ?? $stockRow?->lot_id;
            // Auto-set qty_fulfilled ke min(ordered, stok baru) kalau tidak di-override
            if (! isset($data['qty_fulfilled'])) {
                $data['qty_fulfilled'] = min($line->qty_ordered, $data['stock_available']);
            }
        }

        $line->update($data);
        return $line->fresh();
    }

    /**
     * Konfirmasi fulfillment: potong stok + ubah status.
     *
     * @throws \RuntimeException jika ada line yang belum resolved
     */
    public function confirm(OrderFulfillment $fulfillment, User $confirmedBy): OrderFulfillment
    {
        if (! $fulfillment->canConfirm()) {
            throw new \RuntimeException("Fulfillment sudah {$fulfillment->status}, tidak bisa dikonfirmasi.");
        }

        $fulfillment->loadMissing(['lines', 'order']);

        // Validasi: semua line harus punya item_id
        $unresolved = $fulfillment->lines->whereNull('item_id');
        if ($unresolved->isNotEmpty()) {
            $skus = $unresolved->pluck('marketplace_sku')->filter()->join(', ');
            throw new \RuntimeException("Ada item yang belum dipetakan: {$skus}. Harap isi dulu sebelum konfirmasi.");
        }

        DB::transaction(function () use ($fulfillment, $confirmedBy) {
            foreach ($fulfillment->lines as $line) {
                if ($line->qty_fulfilled <= 0) {
                    continue; // Skip line yang qty = 0
                }

                // Potong stok
                InventoryMutation::create([
                    'date'         => now()->toDateString(),
                    'warehouse_id' => $fulfillment->warehouse_id,
                    'item_id'      => $line->item_id,
                    'lot_id'       => $line->lot_id,
                    'qty_change'   => $line->qty_fulfilled,
                    'direction'    => 'out',
                    'source_type'  => 'order_fulfillment',
                    'source_id'    => $fulfillment->id,
                    'notes'        => "Fulfillment order #{$fulfillment->order->channel_order_id}",
                    'unit_cost'    => 0,
                    'total_cost'   => 0,
                ]);

                // Update stok di InventoryStock
                if ($fulfillment->warehouse_id && $line->lot_id) {
                    InventoryStock::where('warehouse_id', $fulfillment->warehouse_id)
                        ->where('item_id', $line->item_id)
                        ->where('lot_id', $line->lot_id)
                        ->decrement('qty', $line->qty_fulfilled);
                }
            }

            // Update status fulfillment
            $fulfillment->update([
                'status'       => OrderFulfillment::STATUS_CONFIRMED,
                'confirmed_by' => $confirmedBy->id,
                'confirmed_at' => now(),
            ]);

            // Update status order marketplace
            $fulfillment->order->update(['order_status' => 'fulfilled']);
        });

        return $fulfillment->fresh();
    }

    /**
     * Re-resolve item_id untuk semua lines yang masih null menggunakan SKU mapping terkini.
     * Dipakai saat mapping baru ditambahkan setelah draft dibuat.
     * Mengembalikan jumlah lines yang berhasil di-resolve.
     */
    public function remapAllPending(): int
    {
        $fulfillments = OrderFulfillment::with(['order.store.channel', 'lines'])
            ->whereIn('status', [
                OrderFulfillment::STATUS_DRAFT,
                OrderFulfillment::STATUS_PENDING_REVIEW,
            ])
            ->get();

        $total = 0;
        foreach ($fulfillments as $fulfillment) {
            $total += $this->remapLines($fulfillment);
        }
        return $total;
    }

    /**
     * Re-resolve lines yang masih unmapped pada satu fulfillment.
     */
    public function remapLines(OrderFulfillment $fulfillment): int
    {
        $channelCode = $fulfillment->order?->store?->channel?->code;
        $warehouseId = $fulfillment->warehouse_id;
        $resolved    = 0;

        foreach ($fulfillment->lines as $line) {
            if ($line->item_id) continue; // sudah mapped, skip
            $sku = $line->marketplace_sku;
            if (! $sku) continue;

            $itemId = SkuMapping::resolve($sku, $channelCode);
            if (! $itemId) continue;

            $stockRow = $warehouseId
                ? InventoryStock::where('item_id', $itemId)
                    ->where('warehouse_id', $warehouseId)
                    ->orderByDesc('qty')
                    ->first()
                : null;
            $stock = (int) ($stockRow?->qty ?? 0);

            $line->update([
                'item_id'         => $itemId,
                'lot_id'          => $line->lot_id ?? $stockRow?->lot_id,
                'stock_available' => $stock,
                'qty_fulfilled'   => min($line->qty_ordered, $stock),
            ]);
            $resolved++;
        }

        return $resolved;
    }

    /**
     * Refresh stok snapshot di semua lines (berguna setelah ada perubahan stok).
     */
    public function refreshStock(OrderFulfillment $fulfillment): void
    {
        $warehouseId = $fulfillment->warehouse_id;
        if (! $warehouseId) return;

        foreach ($fulfillment->lines as $line) {
            if (! $line->item_id) continue;

            $stockRow = InventoryStock::where('item_id', $line->item_id)
                ->where('warehouse_id', $warehouseId)
                ->orderByDesc('qty')
                ->first();

            $stock = (int) ($stockRow?->qty ?? 0);
            $line->update([
                'stock_available' => $stock,
                'lot_id'          => $line->lot_id ?? $stockRow?->lot_id,
                'qty_fulfilled'   => min($line->qty_ordered, $stock),
            ]);
        }
    }
}
