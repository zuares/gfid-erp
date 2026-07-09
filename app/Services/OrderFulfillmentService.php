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
                    'qty_fulfilled'             => (int) $orderItem->qty, // Stok boleh minus
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
                $data['qty_fulfilled'] = $line->qty_ordered; // Stok boleh minus
            }
        }

        $line->update($data);
        return $line->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PICKING WORKFLOW
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mulai picking: pending_review → picking.
     * Bisa batch (array of fulfillments).
     *
     * @param  OrderFulfillment[]  $fulfillments
     */
    public function startPicking(array $fulfillments): void
    {
        foreach ($fulfillments as $f) {
            if (! $f->canStartPicking()) continue;
            $f->update(['status' => OrderFulfillment::STATUS_PICKING]);
        }
    }

    /**
     * Tandai fulfillment siap dikemas: picking → packed.
     * Dipanggil otomatis setelah semua line di-pick, atau manual oleh user.
     */
    public function markPacked(OrderFulfillment $fulfillment): OrderFulfillment
    {
        if (! $fulfillment->canPack()) {
            throw new \RuntimeException("Fulfillment tidak dalam status picking.");
        }
        $fulfillment->update(['status' => OrderFulfillment::STATUS_PACKED]);
        return $fulfillment->fresh();
    }

    /**
     * Undo packed → picking (misalnya ada item yang perlu dicek ulang).
     */
    public function unpack(OrderFulfillment $fulfillment): OrderFulfillment
    {
        if (! $fulfillment->canUnpack()) {
            throw new \RuntimeException("Fulfillment tidak dalam status packed.");
        }
        $fulfillment->update(['status' => OrderFulfillment::STATUS_PICKING]);
        return $fulfillment->fresh();
    }

    /**
     * Toggle picked_at pada satu line.
     * Jika sudah dipick → un-pick. Jika belum → tandai picked sekarang.
     * Mengembalikan true jika sekarang picked, false jika un-picked.
     * Setelah toggle, cek apakah semua line sudah picked → auto markPacked.
     */
    public function toggleLinePicked(OrderFulfillmentLine $line): bool
    {
        if ($line->isPicked()) {
            $line->update(['picked_at' => null]);
            // Kalau fulfillment sudah packed, kembalikan ke picking
            $fulfillment = $line->fulfillment;
            if ($fulfillment->isPacked()) {
                $fulfillment->update(['status' => OrderFulfillment::STATUS_PICKING]);
            }
            return false;
        }

        // Clear problem jika ada saat pick
        $line->update(['picked_at' => now(), 'pick_problem' => null]);

        // Cek apakah semua line sudah picked → auto markPacked
        $fulfillment = $line->fulfillment()->with('lines')->first();
        $allPicked = $fulfillment->lines->every(fn ($l) => $l->isPicked());
        if ($allPicked && $fulfillment->isPicking()) {
            $fulfillment->update(['status' => OrderFulfillment::STATUS_PACKED]);
        }
        return true;
    }

    /**
     * Tandai baris sebagai problem (tidak bisa dipick).
     * Meng-clear picked_at jika sebelumnya sudah dipick.
     */
    public function flagLineProblem(OrderFulfillmentLine $line, string $reason): void
    {
        $line->update([
            'pick_problem' => $reason,
            'picked_at'    => null,
        ]);
        // Pastikan fulfillment kembali ke picking kalau sempat packed
        $fulfillment = $line->fulfillment;
        if ($fulfillment->isPacked()) {
            $fulfillment->update(['status' => OrderFulfillment::STATUS_PICKING]);
        }
    }

    /**
     * Selesaikan problem: ganti item + qty, lalu clear pick_problem.
     */
    public function resolveLineProblem(OrderFulfillmentLine $line, array $data): OrderFulfillmentLine
    {
        // Pakai updateLine yang sudah ada untuk handle item swap + stok
        $line = $this->updateLine($line, $data);
        $line->update(['pick_problem' => null]);
        return $line;
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

        // Simpan order_id sebelum masuk closure (hindari lazy-load di dalam transaction)
        $orderId = $fulfillment->marketplace_order_id;

        DB::transaction(function () use ($fulfillment, $confirmedBy) {
            foreach ($fulfillment->lines as $line) {
                if (! $line->item_id) {
                    continue; // Skip line yang belum ter-mapping
                }

                // Potong stok — hanya jika warehouse terset
                if ($fulfillment->warehouse_id) {
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

                    InventoryStock::where('warehouse_id', $fulfillment->warehouse_id)
                        ->where('item_id', $line->item_id)
                        ->decrement('qty', $line->qty_fulfilled);
                }
            }

            // Update status fulfillment → picking (stok sudah dipotong, picker mulai ambil barang)
            $fulfillment->update([
                'status'       => OrderFulfillment::STATUS_PICKING,
                'confirmed_by' => $confirmedBy->id,
                'confirmed_at' => now(),
            ]);
        });

        // Update order_status DI LUAR transaction agar SQLite flush dengan benar
        DB::table('marketplace_orders')
            ->where('id', $orderId)
            ->update(['order_status' => 'fulfilled', 'updated_at' => now()]);

        return $fulfillment->fresh();
    }

    /**
     * Ganti item pada satu line SELAMA picking berlangsung.
     *
     * Alur stok:
     *   1. Balikkan potongan stok item lama  (mutation: in,  source = order_fulfillment_substitution)
     *   2. Potong stok item pengganti        (mutation: out, source = order_fulfillment_substitution)
     *   3. Update line → item baru, qty baru, substituted = true, auto-pick
     *
     * @throws \RuntimeException jika fulfillment bukan dalam status picking/packed
     */
    public function substituteItem(
        OrderFulfillmentLine $line,
        int  $newItemId,
        int  $newQty,
    ): OrderFulfillmentLine {
        $fulfillment = $line->fulfillment()->with('order')->first();

        if (! in_array($fulfillment->status, [
            OrderFulfillment::STATUS_PICKING,
            OrderFulfillment::STATUS_PACKED,
        ])) {
            throw new \RuntimeException('Penggantian item hanya bisa dilakukan saat status picking.');
        }

        $warehouseId = $fulfillment->warehouse_id;
        $orderNo     = $fulfillment->order?->channel_order_id ?? "#{$fulfillment->id}";

        DB::transaction(function () use ($line, $newItemId, $newQty, $warehouseId, $fulfillment, $orderNo) {
            // 1. Balikkan stok item lama (jika sudah terpetakan & warehouse ada)
            if ($line->item_id && $warehouseId) {
                InventoryMutation::create([
                    'date'        => now()->toDateString(),
                    'warehouse_id'=> $warehouseId,
                    'item_id'     => $line->item_id,
                    'lot_id'      => $line->lot_id,
                    'qty_change'  => $line->qty_fulfilled,
                    'direction'   => 'in',
                    'source_type' => 'order_fulfillment_substitution',
                    'source_id'   => $fulfillment->id,
                    'notes'       => "Pembatalan picking — ganti item order {$orderNo}",
                    'unit_cost'   => 0,
                    'total_cost'  => 0,
                ]);

                InventoryStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $line->item_id)
                    ->increment('qty', $line->qty_fulfilled);
            }

            // 2. Potong stok item pengganti
            if ($warehouseId) {
                InventoryMutation::create([
                    'date'        => now()->toDateString(),
                    'warehouse_id'=> $warehouseId,
                    'item_id'     => $newItemId,
                    'lot_id'      => null,
                    'qty_change'  => $newQty,
                    'direction'   => 'out',
                    'source_type' => 'order_fulfillment_substitution',
                    'source_id'   => $fulfillment->id,
                    'notes'       => "Penggantian item picking order {$orderNo}",
                    'unit_cost'   => 0,
                    'total_cost'  => 0,
                ]);

                InventoryStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $newItemId)
                    ->decrement('qty', $newQty);
            }

            // 3. Hitung stok tersisa item baru
            $stockRow = $warehouseId
                ? InventoryStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $newItemId)
                    ->first()
                : null;

            // 4. Update line
            $line->update([
                'item_id'         => $newItemId,
                'lot_id'          => $stockRow?->lot_id ?? null,
                'qty_fulfilled'   => $newQty,
                'stock_available' => (int) ($stockRow?->qty ?? 0),
                'substituted'     => true,
                'picked_at'       => now(),   // otomatis dipick setelah substitusi
                'pick_problem'    => null,
            ]);

            // 5. Cek apakah semua line sekarang sudah picked → auto packed
            $fulfillment->load('lines');
            $allPicked = $fulfillment->lines->every(fn ($l) => $l->isPicked());
            if ($allPicked && $fulfillment->isPicking()) {
                $fulfillment->update(['status' => OrderFulfillment::STATUS_PACKED]);
            }
        });

        return $line->fresh(['item', 'lot']);
    }

    /**
     * Selesaikan picking: picking / packed → confirmed.
     * Dipanggil setelah semua item selesai dipick di halaman fulfillment.
     */
    public function completePicking(OrderFulfillment $fulfillment): OrderFulfillment
    {
        if (! in_array($fulfillment->status, [
            OrderFulfillment::STATUS_PICKING,
            OrderFulfillment::STATUS_PACKED,
        ])) {
            throw new \RuntimeException("Fulfillment tidak dalam status picking/packed.");
        }

        DB::table('marketplace_orders')
            ->where('id', $fulfillment->marketplace_order_id)
            ->update(['order_status' => 'fulfilled', 'updated_at' => now()]);

        $fulfillment->update(['status' => OrderFulfillment::STATUS_CONFIRMED]);
        return $fulfillment->fresh();
    }

    /**
     * Konfirmasi batch: potong stok + mark semua lines picked + status → confirmed atomically.
     *
     * Dipakai dari batch mode — user sudah scan fisik semua item,
     * sehingga picking phase di-skip dan langsung selesai.
     *
     * @throws \RuntimeException
     */
    public function batchConfirm(OrderFulfillment $fulfillment, User $confirmedBy): OrderFulfillment
    {
        if (! in_array($fulfillment->status, [
            OrderFulfillment::STATUS_DRAFT,
            OrderFulfillment::STATUS_PENDING_REVIEW,
        ])) {
            throw new \RuntimeException("Fulfillment sudah {$fulfillment->status}, tidak bisa dikonfirmasi via batch.");
        }

        $fulfillment->loadMissing(['lines', 'order']);

        // Validasi: semua active lines harus punya item_id
        $unresolved = $fulfillment->lines
            ->where('is_split_parent', false)
            ->whereNull('item_id');

        if ($unresolved->isNotEmpty()) {
            $skus = $unresolved->pluck('marketplace_sku')->filter()->join(', ');
            throw new \RuntimeException("Ada item yang belum dipetakan: {$skus}.");
        }

        $orderId = $fulfillment->marketplace_order_id;

        DB::transaction(function () use ($fulfillment, $confirmedBy) {
            $activeLines = $fulfillment->lines->where('is_split_parent', false);

            foreach ($activeLines as $line) {
                if (! $line->item_id) continue;

                // Potong stok
                if ($fulfillment->warehouse_id) {
                    InventoryMutation::create([
                        'date'        => now()->toDateString(),
                        'warehouse_id'=> $fulfillment->warehouse_id,
                        'item_id'     => $line->item_id,
                        'lot_id'      => $line->lot_id,
                        'qty_change'  => $line->qty_fulfilled,
                        'direction'   => 'out',
                        'source_type' => 'order_fulfillment',
                        'source_id'   => $fulfillment->id,
                        'notes'       => "Batch fulfillment order #{$fulfillment->order?->channel_order_id}",
                        'unit_cost'   => 0,
                        'total_cost'  => 0,
                    ]);

                    InventoryStock::where('warehouse_id', $fulfillment->warehouse_id)
                        ->where('item_id', $line->item_id)
                        ->decrement('qty', $line->qty_fulfilled);
                }

                // Mark picked (batch = semua sudah fisik di tangan)
                $line->update(['picked_at' => now(), 'pick_problem' => null]);
            }

            // Langsung confirmed — skip picking phase
            $fulfillment->update([
                'status'       => OrderFulfillment::STATUS_CONFIRMED,
                'confirmed_by' => $confirmedBy->id,
                'confirmed_at' => now(),
            ]);
        });

        // Update order_status di luar transaction (SQLite flush)
        DB::table('marketplace_orders')
            ->where('id', $orderId)
            ->update(['order_status' => 'fulfilled', 'updated_at' => now()]);

        return $fulfillment->fresh();
    }

    /**
     * Proses packing (single mode) — update qty_fulfilled + set packed, TANPA potong stok.
     *
     * @param  array  $scannedItems  [['item_id' => int, 'qty' => int], ...]
     */
    public function packOrder(OrderFulfillment $fulfillment, array $scannedItems): OrderFulfillment
    {
        if (! in_array($fulfillment->status, [
            OrderFulfillment::STATUS_DRAFT,
            OrderFulfillment::STATUS_PENDING_REVIEW,
            OrderFulfillment::STATUS_PACKED,
        ])) {
            throw new \RuntimeException("Fulfillment sudah {$fulfillment->status}, tidak bisa diproses.");
        }

        $fulfillment->loadMissing('lines');

        // Validasi: semua active lines harus punya item_id
        $unresolved = $fulfillment->lines
            ->where('is_split_parent', false)
            ->whereNull('item_id');

        if ($unresolved->isNotEmpty()) {
            $skus = $unresolved->pluck('marketplace_sku')->filter()->join(', ');
            throw new \RuntimeException("Ada item yang belum dipetakan: {$skus}.");
        }

        // Build lookup: item_id → qty terscan
        $scannedMap      = collect($scannedItems)->keyBy('item_id');
        $hasScannedItems = count($scannedItems) > 0;

        // Validasi ketat scan items:
        if ($hasScannedItems) {
            $scannedTotals = collect($scannedItems)->groupBy('item_id')->map->sum('qty');
            $activeItemIds = $fulfillment->lines->where('is_split_parent', false)->pluck('item_id')->toArray();
            
            foreach ($scannedTotals as $itemId => $qty) {
                if (!in_array($itemId, $activeItemIds)) {
                    throw new \RuntimeException("SKU tidak sesuai dengan pesanan.");
                }
            }

            foreach ($fulfillment->lines->where('is_split_parent', false) as $line) {
                $scannedQty = $scannedTotals->get($line->item_id) ?? 0;
                if ($scannedQty < $line->qty_ordered) {
                    throw new \RuntimeException("Barang belum lengkap, cek kembali hasil scan.");
                }
                if ($scannedQty > $line->qty_ordered) {
                    throw new \RuntimeException("Jumlah scan melebihi pesanan.");
                }
            }
        } else {
            foreach ($fulfillment->lines->where('is_split_parent', false) as $line) {
                if ($line->qty_fulfilled < $line->qty_ordered) {
                    throw new \RuntimeException("Barang belum lengkap, cek kembali hasil scan.");
                }
                if ($line->qty_fulfilled > $line->qty_ordered) {
                    throw new \RuntimeException("Jumlah scan melebihi pesanan.");
                }
            }
        }

        // Build scan_log: raw scan list + enriched item metadata
        $scanLog = null;
        if ($hasScannedItems) {
            $fulfillment->loadMissing('lines.item');
            $itemMeta = $fulfillment->lines
                ->where('is_split_parent', false)
                ->whereNotNull('item_id')
                ->keyBy('item_id');

            $scanLog = collect($scannedItems)->map(function ($s) use ($itemMeta) {
                $line = $itemMeta->get($s['item_id']);
                return [
                    'item_id' => (int) $s['item_id'],
                    'qty'     => (int) $s['qty'],
                    'code'    => $line?->item?->code ?? null,
                    'name'    => $line?->item?->name ?? null,
                ];
            })->values()->toArray();
        }

        DB::transaction(function () use ($fulfillment, $scannedMap, $hasScannedItems, $scanLog) {
            $activeLines = $fulfillment->lines->where('is_split_parent', false);

            foreach ($activeLines as $line) {
                if (! $line->item_id) continue;

                $scanned = $scannedMap->get($line->item_id);

                // Jika ada scan list: item tidak terscan = 0 (tidak dipacking)
                // Jika tidak ada scan list: pertahankan qty_fulfilled lama
                $newQty = $hasScannedItems
                    ? ($scanned ? (int) $scanned['qty'] : 0)
                    : $line->qty_fulfilled;

                $line->update([
                    'qty_fulfilled' => $newQty,
                    'picked_at'     => now(),
                    'pick_problem'  => null,
                ]);
            }

            $updateData = ['status' => OrderFulfillment::STATUS_PACKED];

            // Simpan scan_log jika kolom sudah ada (setelah migration dijalankan)
            if ($scanLog !== null) {
                try {
                    $cols = \Illuminate\Support\Facades\Schema::getColumnListing('order_fulfillments');
                    if (in_array('scan_log', $cols)) {
                        $updateData['scan_log'] = json_encode($scanLog);
                    }
                } catch (\Throwable) {
                    // ignore — kolom belum ada
                }
            }

            $fulfillment->update($updateData);
        });

        return $fulfillment->fresh();
    }

    /**
     * Konfirmasi dari status packed — potong stok + set confirmed.
     * Dipakai di tab Review fulfillment page setelah packOrder().
     *
     * Jika scan_log tersedia: potong stok berdasarkan item yang di-scan (bukan qty_ordered).
     * Fallback: potong stok berdasarkan qty_fulfilled > 0 per line.
     */
    public function confirmPacked(OrderFulfillment $fulfillment, User $confirmedBy): OrderFulfillment
    {
        if ($fulfillment->status !== OrderFulfillment::STATUS_PACKED) {
            throw new \RuntimeException("Fulfillment harus dalam status packed untuk dikonfirmasi.");
        }

        $fulfillment->loadMissing(['lines.item', 'order']);

        // Validasi ketat sebelum potong stok
        foreach ($fulfillment->lines->where('is_split_parent', false) as $line) {
            if ($line->qty_fulfilled < $line->qty_ordered) {
                throw new \RuntimeException("Order belum siap dikonfirmasi.");
            }
        }

        $orderId = $fulfillment->marketplace_order_id;

        // lot_id lookup: item_id → lot_id dari order lines (untuk mutation)
        $lotByItemId = $fulfillment->lines
            ->where('is_split_parent', false)
            ->whereNotNull('item_id')
            ->keyBy('item_id')
            ->map(fn ($l) => $l->lot_id);

        // Tentukan item yang akan dipotong stoknya
        // Priority: scan_log (raw scan) → qty_fulfilled > 0 per line
        $scanLogRaw = null;
        try {
            $hasScanLogCol = in_array(
                'scan_log',
                \Illuminate\Support\Facades\Schema::getColumnListing('order_fulfillments')
            );
            if ($hasScanLogCol && $fulfillment->scan_log) {
                $scanLogRaw = json_decode($fulfillment->scan_log, true);
            }
        } catch (\Throwable) {}

        if ($scanLogRaw && count($scanLogRaw) > 0) {
            // Gunakan scan_log: hanya potong item yang benar-benar di-scan
            // Group by item_id (jaga-jaga duplikat), sum qty
            $grouped = collect($scanLogRaw)->groupBy('item_id')->map(fn ($g, $itemId) => [
                'item_id' => (int) $itemId,
                'qty'     => $g->sum('qty'),
                'lot_id'  => $lotByItemId->get((int) $itemId),
            ]);
            $itemsToCut = $grouped->values();
        } else {
            // Fallback: gunakan qty_fulfilled per line
            $itemsToCut = $fulfillment->lines
                ->where('is_split_parent', false)
                ->filter(fn ($l) => $l->item_id && $l->qty_fulfilled > 0)
                ->map(fn ($l) => [
                    'item_id' => $l->item_id,
                    'qty'     => $l->qty_fulfilled,
                    'lot_id'  => $l->lot_id,
                ]);
        }

        DB::transaction(function () use ($fulfillment, $confirmedBy, $itemsToCut) {
            foreach ($itemsToCut as $item) {
                $itemId = $item['item_id'] ?? null;
                $qty    = (int) ($item['qty'] ?? 0);
                if (! $itemId || $qty <= 0) continue;

                if ($fulfillment->warehouse_id) {
                    // TODO: Ambil nilai cost dari HPP item untuk unit_cost dan total_cost (Jangan hardcode 0)
                    InventoryMutation::create([
                        'date'         => now()->toDateString(),
                        'warehouse_id' => $fulfillment->warehouse_id,
                        'item_id'      => $itemId,
                        'lot_id'       => $item['lot_id'] ?? null,
                        'qty_change'   => $qty,
                        'direction'    => 'out',
                        'source_type'  => 'order_fulfillment',
                        'source_id'    => $fulfillment->id,
                        'notes'        => "Konfirmasi order #{$fulfillment->order?->channel_order_id}",
                        'unit_cost'    => 0,
                        'total_cost'   => 0,
                    ]);

                    InventoryStock::where('warehouse_id', $fulfillment->warehouse_id)
                        ->where('item_id', $itemId)
                        ->decrement('qty', $qty);
                }
            }

            $fulfillment->update([
                'status'       => OrderFulfillment::STATUS_CONFIRMED,
                'confirmed_by' => $confirmedBy->id,
                'confirmed_at' => now(),
            ]);
        });

        DB::table('marketplace_orders')
            ->where('id', $orderId)
            ->update(['order_status' => 'fulfilled', 'updated_at' => now()]);

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
                'qty_fulfilled'   => $line->qty_ordered, // Stok boleh minus
            ]);
            $resolved++;
        }

        return $resolved;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SPLIT LINE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pecah satu line menjadi N baris baru dengan item/qty berbeda.
     *
     * @param  array<int, array{item_id: int, qty: int}>  $splits
     * @return OrderFulfillmentLine[]  Baris-baris baru (split children)
     *
     * @throws \RuntimeException  Jika total qty splits ≠ qty_ordered, atau line sudah di-split
     */
    public function splitLine(OrderFulfillmentLine $line, array $splits): array
    {
        if ($line->is_split_parent) {
            throw new \RuntimeException('Line ini sudah pernah di-split sebelumnya.');
        }

        $totalQty = array_sum(array_column($splits, 'qty'));
        if ($totalQty !== (int) $line->qty_ordered) {
            throw new \RuntimeException(
                "Total qty split ({$totalQty}) harus sama persis dengan qty ordered ({$line->qty_ordered})."
            );
        }

        if (count($splits) < 2) {
            throw new \RuntimeException('Split harus menghasilkan minimal 2 baris.');
        }

        $fulfillment = $line->fulfillment()->with('order')->first();
        $warehouseId = $fulfillment->warehouse_id;
        $orderNo     = $fulfillment->order?->channel_order_id ?? "#{$fulfillment->id}";

        // Apakah stok sudah dipotong? (post-confirm = picking/packed/confirmed)
        $stockAlreadyCut = in_array($fulfillment->status, [
            OrderFulfillment::STATUS_PICKING,
            OrderFulfillment::STATUS_PACKED,
            OrderFulfillment::STATUS_CONFIRMED,
        ]);

        $newLines = [];

        DB::transaction(function () use (
            $line, $splits, $warehouseId, $orderNo, $stockAlreadyCut, $fulfillment, &$newLines
        ) {
            // 1. Jika stok sudah dipotong → reverse item asli
            if ($stockAlreadyCut && $line->item_id && $warehouseId) {
                InventoryMutation::create([
                    'date'        => now()->toDateString(),
                    'warehouse_id'=> $warehouseId,
                    'item_id'     => $line->item_id,
                    'lot_id'      => $line->lot_id,
                    'qty_change'  => $line->qty_fulfilled,
                    'direction'   => 'in',
                    'source_type' => 'order_fulfillment_split',
                    'source_id'   => $line->id,
                    'notes'       => "Reverse split — line #{$line->id} order {$orderNo}",
                    'unit_cost'   => 0, 'total_cost' => 0,
                ]);
                InventoryStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $line->item_id)
                    ->increment('qty', $line->qty_fulfilled);
            }

            // 2. Soft-archive parent
            $line->update(['is_split_parent' => true]);

            // 3. Buat split children + potong stok masing-masing (jika post-confirm)
            foreach ($splits as $s) {
                $stockRow = ($warehouseId && $s['item_id'])
                    ? InventoryStock::where('warehouse_id', $warehouseId)
                        ->where('item_id', $s['item_id'])->first()
                    : null;

                $newLine = OrderFulfillmentLine::create([
                    'fulfillment_id'           => $line->fulfillment_id,
                    'marketplace_order_item_id' => $line->marketplace_order_item_id,
                    'marketplace_sku'           => $line->marketplace_sku,
                    'marketplace_item_name'     => $line->marketplace_item_name,
                    'item_id'                   => $s['item_id'],
                    'lot_id'                    => $stockRow?->lot_id,
                    'qty_ordered'               => $s['qty'],
                    'qty_fulfilled'             => $s['qty'],
                    'substituted'               => $s['item_id'] !== $line->item_id,
                    'split_parent_id'           => $line->id,
                    'stock_available'           => (int) ($stockRow?->qty ?? 0),
                ]);

                if ($stockAlreadyCut && $warehouseId) {
                    InventoryMutation::create([
                        'date'        => now()->toDateString(),
                        'warehouse_id'=> $warehouseId,
                        'item_id'     => $s['item_id'],
                        'lot_id'      => $stockRow?->lot_id,
                        'qty_change'  => $s['qty'],
                        'direction'   => 'out',
                        'source_type' => 'order_fulfillment_split',
                        'source_id'   => $newLine->id,
                        'notes'       => "Split picking — line #{$newLine->id} order {$orderNo}",
                        'unit_cost'   => 0, 'total_cost' => 0,
                    ]);
                    InventoryStock::where('warehouse_id', $warehouseId)
                        ->where('item_id', $s['item_id'])
                        ->decrement('qty', $s['qty']);
                }

                $newLines[] = $newLine->fresh(['item']);
            }
        });

        return $newLines;
    }

    /**
     * Restore split: kembalikan parent line ke kondisi semula, hapus split children.
     *
     * @param  OrderFulfillmentLine  $parentLine  Line dengan is_split_parent = true
     * @return OrderFulfillmentLine  Parent yang sudah di-restore
     *
     * @throws \RuntimeException
     */
    public function restoreSplitLine(OrderFulfillmentLine $parentLine): OrderFulfillmentLine
    {
        if (! $parentLine->is_split_parent) {
            throw new \RuntimeException('Line ini bukan split parent.');
        }

        $children = OrderFulfillmentLine::where('split_parent_id', $parentLine->id)->get();
        if ($children->isEmpty()) {
            // Anomali: tandai saja sebagai bukan parent lagi
            $parentLine->update(['is_split_parent' => false]);
            return $parentLine->fresh(['item']);
        }

        $fulfillment = $parentLine->fulfillment()->with('order')->first();
        $warehouseId = $fulfillment->warehouse_id;
        $orderNo     = $fulfillment->order?->channel_order_id ?? "#{$fulfillment->id}";

        // Cek apakah ada mutations untuk split children
        $childIds        = $children->pluck('id')->toArray();
        $hasMutations    = InventoryMutation::where('source_type', 'order_fulfillment_split')
            ->whereIn('source_id', $childIds)->exists();

        DB::transaction(function () use (
            $parentLine, $children, $childIds, $warehouseId, $orderNo, $hasMutations, $fulfillment
        ) {
            if ($hasMutations && $warehouseId) {
                // Reverse tiap split child mutation (balik stok child ke gudang)
                foreach ($children as $child) {
                    if (! $child->item_id) continue;
                    InventoryMutation::create([
                        'date'        => now()->toDateString(),
                        'warehouse_id'=> $warehouseId,
                        'item_id'     => $child->item_id,
                        'lot_id'      => $child->lot_id,
                        'qty_change'  => $child->qty_fulfilled,
                        'direction'   => 'in',
                        'source_type' => 'order_fulfillment_split_restore',
                        'source_id'   => $child->id,
                        'notes'       => "Restore split — child #{$child->id} order {$orderNo}",
                        'unit_cost'   => 0, 'total_cost' => 0,
                    ]);
                    InventoryStock::where('warehouse_id', $warehouseId)
                        ->where('item_id', $child->item_id)
                        ->increment('qty', $child->qty_fulfilled);
                }

                // Re-cut stok item original (parent)
                if ($parentLine->item_id) {
                    InventoryMutation::create([
                        'date'        => now()->toDateString(),
                        'warehouse_id'=> $warehouseId,
                        'item_id'     => $parentLine->item_id,
                        'lot_id'      => $parentLine->lot_id,
                        'qty_change'  => $parentLine->qty_fulfilled,
                        'direction'   => 'out',
                        'source_type' => 'order_fulfillment_split_restore',
                        'source_id'   => $parentLine->id,
                        'notes'       => "Re-cut restore split — line #{$parentLine->id} order {$orderNo}",
                        'unit_cost'   => 0, 'total_cost' => 0,
                    ]);
                    InventoryStock::where('warehouse_id', $warehouseId)
                        ->where('item_id', $parentLine->item_id)
                        ->decrement('qty', $parentLine->qty_fulfilled);
                }
            }

            // Hapus split children
            OrderFulfillmentLine::whereIn('id', $childIds)->delete();

            // Restore parent
            $parentLine->update([
                'is_split_parent' => false,
                'picked_at'       => null,  // reset picked status
                'pick_problem'    => null,
            ]);
        });

        return $parentLine->fresh(['item']);
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
                'qty_fulfilled'   => $line->qty_ordered, // Stok boleh minus
            ]);
        }
    }
}
