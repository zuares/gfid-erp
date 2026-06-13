<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\FulfillmentAuditLog;
use App\Models\MarketplaceOrder;
use App\Models\OrderFulfillment;
use App\Models\OrderFulfillmentLine;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FulfillmentController extends Controller
{
    public function __construct(protected OrderFulfillmentService $service) {}

    /** Daftar fulfillment pending (draft / pending_review). */
    public function index(): JsonResponse
    {
        $fulfillments = OrderFulfillment::with([
            'order.store.channel',
            'lines.item',
            'warehouse',
        ])
            ->whereIn('status', [
                OrderFulfillment::STATUS_DRAFT,
                OrderFulfillment::STATUS_PENDING_REVIEW,
                OrderFulfillment::STATUS_PICKING,
                OrderFulfillment::STATUS_PACKED,
            ])
            ->latest()
            ->get()
            ->map(fn ($f) => $this->formatFulfillment($f));

        return response()->json($fulfillments);
    }

    /** Detail satu fulfillment. */
    public function show(OrderFulfillment $fulfillment): JsonResponse
    {
        $fulfillment->load(['order.store.channel', 'lines.item', 'lines.lot', 'warehouse', 'confirmedBy']);
        return response()->json($this->formatFulfillment($fulfillment, detail: true));
    }

    /** Buat draft manual untuk order yang belum punya fulfillment. */
    public function createDraft(Request $request): JsonResponse
    {
        $request->validate(['marketplace_order_id' => ['required', 'integer', 'exists:marketplace_orders,id']]);

        $order = MarketplaceOrder::findOrFail($request->marketplace_order_id);
        $fulfillment = $this->service->createDraft($order);

        FulfillmentAuditLog::record($fulfillment->id, 'create_draft', [
            'marketplace_order_id' => $order->id,
            'channel_order_id'     => $order->channel_order_id,
        ]);

        return response()->json($this->formatFulfillment($fulfillment->fresh(['order.store.channel', 'lines.item', 'warehouse']), detail: true));
    }

    /**
     * Scan nomor order → cari atau buat fulfillment draft, lalu kembalikan detail-nya.
     * Dipakai oleh scan box di halaman fulfillment.
     */
    public function scanOrder(Request $request): JsonResponse
    {
        $request->validate(['order_no' => ['required', 'string', 'max:100']]);

        $orderNo = trim($request->order_no);

        $order = MarketplaceOrder::where('channel_order_id', $orderNo)->first();
        if (! $order) {
            return response()->json(['message' => "Order \"{$orderNo}\" tidak ditemukan."], 404);
        }

        // Cek apakah sudah ada fulfillment (status apapun — termasuk confirmed)
        $existing = OrderFulfillment::where('marketplace_order_id', $order->id)
            ->latest()
            ->first();

        if ($existing && $existing->isConfirmed()) {
            // Sudah dikonfirmasi — return dengan flag
            $existing->load(['order.store.channel', 'lines.item', 'lines.lot', 'warehouse', 'confirmedBy']);
            return response()->json([
                'already_confirmed' => true,
                'fulfillment'       => $this->formatFulfillment($existing, detail: true),
            ]);
        }

        // Buat atau ambil draft yang ada
        $fulfillment = $this->service->createDraft($order);
        $fulfillment->load(['order.store.channel', 'lines.item', 'lines.lot', 'warehouse']);

        FulfillmentAuditLog::record($fulfillment->id, 'scan_order', [
            'order_no' => $orderNo,
        ]);

        return response()->json([
            'already_confirmed' => false,
            'fulfillment'       => $this->formatFulfillment($fulfillment, detail: true),
        ]);
    }

    /** Update satu line (ganti item / lot / qty / notes). */
    public function updateLine(Request $request, OrderFulfillment $fulfillment, OrderFulfillmentLine $line): JsonResponse
    {
        $data = $request->validate([
            'item_id'       => ['nullable', 'integer', 'exists:items,id'],
            'lot_id'        => ['nullable', 'integer', 'exists:lots,id'],
            'qty_fulfilled' => ['nullable', 'integer', 'min:0'],
            'notes'         => ['nullable', 'string', 'max:255'],
        ]);

        $line = $this->service->updateLine($line, array_filter($data, fn ($v) => $v !== null));

        return response()->json(['line' => $this->formatLine($line->load(['item', 'lot']))]);
    }

    /** Konfirmasi fulfillment → potong stok. */
    public function confirm(OrderFulfillment $fulfillment): JsonResponse
    {
        try {
            $result = $this->service->confirm($fulfillment, auth()->user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'confirm', [
            'lines_count' => $result->lines->where('is_split_parent', false)->count(),
        ]);

        return response()->json([
            'message'     => 'Fulfillment dikonfirmasi. Stok sudah dipotong.',
            'fulfillment' => $this->formatFulfillment($result->load(['lines.item', 'confirmedBy'])),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PICKING WORKFLOW
    // ─────────────────────────────────────────────────────────────────────────

    /** Daftar fulfillment untuk halaman picking, dikelompokkan per stage. */
    public function pickingQueue(): JsonResponse
    {
        $fulfillments = OrderFulfillment::with([
            'order.store.channel',
            'lines.item',
            'warehouse',
        ])
            ->whereIn('status', [
                OrderFulfillment::STATUS_PENDING_REVIEW,
                OrderFulfillment::STATUS_PICKING,
                OrderFulfillment::STATUS_PACKED,
            ])
            ->latest()
            ->get();

        return response()->json([
            'ready_to_pick' => $fulfillments
                ->where('status', OrderFulfillment::STATUS_PENDING_REVIEW)
                ->filter(fn ($f) => $f->allLinesResolved())
                ->values()
                ->map(fn ($f) => $this->formatFulfillment($f, detail: true)),
            'picking' => $fulfillments
                ->where('status', OrderFulfillment::STATUS_PICKING)
                ->values()
                ->map(fn ($f) => $this->formatFulfillment($f, detail: true)),
            'problem' => $fulfillments
                ->where('status', OrderFulfillment::STATUS_PICKING)
                ->filter(fn ($f) => $f->lines->contains(fn ($l) => $l->hasProblem()))
                ->values()
                ->map(fn ($f) => $this->formatFulfillment($f, detail: true)),
            'packed' => $fulfillments
                ->where('status', OrderFulfillment::STATUS_PACKED)
                ->values()
                ->map(fn ($f) => $this->formatFulfillment($f, detail: true)),
        ]);
    }

    /** Mulai picking untuk batch fulfillment (array of IDs). */
    public function startPicking(Request $request): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        $fulfillments = OrderFulfillment::whereIn('id', $request->ids)->get()->all();
        $this->service->startPicking($fulfillments);

        foreach ($fulfillments as $f) {
            FulfillmentAuditLog::record($f->id, 'start_picking');
        }

        return response()->json(['message' => count($fulfillments) . ' fulfillment dimulai picking.']);
    }

    /** Tandai fulfillment siap dikemas (picking → packed). */
    public function markPacked(OrderFulfillment $fulfillment): JsonResponse
    {
        try {
            $result = $this->service->markPacked($fulfillment);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'mark_packed');

        return response()->json(['message' => 'Siap dikemas.', 'fulfillment' => $this->formatFulfillment($result->load('lines.item'))]);
    }

    /** Undo packed → picking. */
    public function unpack(OrderFulfillment $fulfillment): JsonResponse
    {
        try {
            $result = $this->service->unpack($fulfillment);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'unpack');

        return response()->json(['message' => 'Dikembalikan ke picking.', 'fulfillment' => $this->formatFulfillment($result->load('lines.item'))]);
    }

    /** Toggle picked_at pada satu line. */
    public function toggleLinePicked(OrderFulfillment $fulfillment, OrderFulfillmentLine $line): JsonResponse
    {
        $picked = $this->service->toggleLinePicked($line);
        $line->load('item');

        FulfillmentAuditLog::record($fulfillment->id, 'toggle_picked', [
            'picked'    => $picked,
            'item_code' => $line->item?->code,
            'item_name' => $line->item?->name,
            'qty'       => $line->qty_fulfilled,
        ], $line->id);

        return response()->json(['picked' => $picked, 'line' => $this->formatLine($line)]);
    }

    /** Tandai baris sebagai problem. */
    public function flagLineProblem(Request $request, OrderFulfillment $fulfillment, OrderFulfillmentLine $line): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->service->flagLineProblem($line, $request->reason);
        $line->load('item');

        FulfillmentAuditLog::record($fulfillment->id, 'flag_problem', [
            'item_code' => $line->item?->code,
            'reason'    => $request->reason,
        ], $line->id);

        return response()->json(['line' => $this->formatLine($line)]);
    }

    /** Ganti item pada satu picking line + sinkronkan stok (reverse lama, potong baru). */
    public function substituteItem(Request $request, OrderFulfillment $fulfillment, OrderFulfillmentLine $line): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'qty'     => ['required', 'integer', 'min:1'],
        ]);

        $fromItemCode = $line->item?->code;
        $fromItemName = $line->item?->name;

        try {
            $result = $this->service->substituteItem($line, $data['item_id'], $data['qty']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'substitute', [
            'from_item_code' => $fromItemCode,
            'from_item_name' => $fromItemName,
            'to_item_code'   => $result->item?->code,
            'to_item_name'   => $result->item?->name,
            'qty'            => $data['qty'],
        ], $line->id);

        // Reload fulfillment untuk response lengkap
        $fulfillment->load(['order.store.channel', 'lines.item', 'lines.lot', 'warehouse', 'confirmedBy']);
        return response()->json([
            'message'     => 'Item berhasil diganti. Stok sudah disinkronkan.',
            'line'        => $this->formatLine($result),
            'fulfillment' => $this->formatFulfillment($fulfillment, detail: true),
        ]);
    }

    /** Selesaikan problem: ganti item + qty lalu clear problem. */
    public function resolveLineProblem(Request $request, OrderFulfillment $fulfillment, OrderFulfillmentLine $line): JsonResponse
    {
        $data = $request->validate([
            'item_id'       => ['nullable', 'integer', 'exists:items,id'],
            'qty_fulfilled' => ['nullable', 'integer', 'min:0'],
        ]);
        $line = $this->service->resolveLineProblem($line, array_filter($data, fn ($v) => $v !== null));
        $line->load('item');

        FulfillmentAuditLog::record($fulfillment->id, 'resolve_problem', [
            'item_code' => $line->item?->code,
        ], $line->id);

        return response()->json(['line' => $this->formatLine($line)]);
    }

    /** Selesaikan picking: picking/packed → confirmed. */
    public function completePicking(OrderFulfillment $fulfillment): JsonResponse
    {
        try {
            $result = $this->service->completePicking($fulfillment);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'complete_picking');

        return response()->json([
            'message'     => 'Picking selesai. Order dikonfirmasi.',
            'fulfillment' => $this->formatFulfillment($result),
        ]);
    }

    /**
     * Pecah satu line menjadi N baris baru.
     * Body: { splits: [{item_id, qty}, ...] }
     */
    public function splitLine(Request $request, OrderFulfillment $fulfillment, OrderFulfillmentLine $line): JsonResponse
    {
        $data = $request->validate([
            'splits'          => ['required', 'array', 'min:2'],
            'splits.*.item_id'=> ['required', 'integer', 'exists:items,id'],
            'splits.*.qty'    => ['required', 'integer', 'min:1'],
        ]);

        $originalItem = $line->item;

        try {
            $newLines = $this->service->splitLine($line, $data['splits']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'split', [
            'original_item_code' => $originalItem?->code,
            'original_qty'       => $line->qty_ordered,
            'splits'             => array_map(fn ($l) => [
                'item_code' => $l->item?->code,
                'qty'       => $l->qty_ordered,
            ], $newLines),
        ], $line->id);

        $fulfillment->load(['order.store.channel', 'lines.item', 'lines.lot', 'warehouse', 'confirmedBy']);
        return response()->json([
            'message'     => 'Line berhasil di-split.',
            'new_lines'   => array_map(fn ($l) => $this->formatLine($l), $newLines),
            'fulfillment' => $this->formatFulfillment($fulfillment, detail: true),
        ]);
    }

    /**
     * Restore split: kembalikan ke line asli, hapus split children.
     * Query param: ?force=1 untuk lanjut meski ada yang sudah dipick.
     */
    public function restoreSplitLine(Request $request, OrderFulfillment $fulfillment, OrderFulfillmentLine $line): JsonResponse
    {
        // Cek apakah ada split children yang sudah dipick
        $pickedChildren = \App\Models\OrderFulfillmentLine::where('split_parent_id', $line->id)
            ->whereNotNull('picked_at')
            ->count();

        if ($pickedChildren > 0 && ! $request->boolean('force')) {
            return response()->json([
                'needs_confirm' => true,
                'picked_count'  => $pickedChildren,
                'message'       => "Ada {$pickedChildren} item split yang sudah dipick. Yakin restore?",
            ], 409);
        }

        try {
            $restored = $this->service->restoreSplitLine($line);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'restore_split', [
            'item_code'    => $restored->item?->code,
            'qty_restored' => $restored->qty_ordered,
            'force'        => $request->boolean('force'),
        ], $line->id);

        $fulfillment->load(['order.store.channel', 'lines.item', 'lines.lot', 'warehouse', 'confirmedBy']);
        return response()->json([
            'message'     => 'Split berhasil di-restore ke line asli.',
            'line'        => $this->formatLine($restored),
            'fulfillment' => $this->formatFulfillment($fulfillment, detail: true),
        ]);
    }

    /** Statistik untuk batch worker dashboard. */
    public function batchStats(): JsonResponse
    {
        $today = now()->toDateString();

        // Fulfillment confirmed hari ini
        $doneToday = OrderFulfillment::whereDate('confirmed_at', $today)
            ->where('status', OrderFulfillment::STATUS_CONFIRMED)
            ->count();

        // Total lines (item) dari fulfillment confirmed hari ini
        $itemsToday = OrderFulfillmentLine::whereHas('fulfillment', function ($q) use ($today) {
            $q->whereDate('confirmed_at', $today)
              ->where('status', OrderFulfillment::STATUS_CONFIRMED);
        })->where('is_split_parent', false)->sum('qty_fulfilled');

        // Order menunggu diproses (draft / pending_review)
        $waiting = OrderFulfillment::whereIn('status', [
            OrderFulfillment::STATUS_DRAFT,
            OrderFulfillment::STATUS_PENDING_REVIEW,
        ])->count();

        // Dari yang menunggu, berapa yang masih ada unresolved SKU
        $unmapped = OrderFulfillment::whereIn('status', [
            OrderFulfillment::STATUS_DRAFT,
            OrderFulfillment::STATUS_PENDING_REVIEW,
        ])->whereHas('lines', fn ($q) => $q->whereNull('item_id'))->count();

        return response()->json([
            'selesai_hari_ini'     => $doneToday,
            'item_diproses_hari_ini' => (int) $itemsToday,
            'menunggu_diproses'    => $waiting,
            'belum_mapping'        => $unmapped,
        ]);
    }

    /**
     * Batch confirm: potong stok + mark semua lines picked + langsung confirmed.
     * Dipakai dari batch scan mode — skip picking phase.
     */
    public function batchConfirm(OrderFulfillment $fulfillment): JsonResponse
    {
        try {
            $result = $this->service->batchConfirm($fulfillment, auth()->user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'batch_confirm', [
            'lines_count' => $result->lines->where('is_split_parent', false)->count(),
        ]);

        return response()->json([
            'message'     => 'Batch dikonfirmasi. Stok dipotong, semua item otomatis picked.',
            'fulfillment' => $this->formatFulfillment($result->load(['lines.item', 'confirmedBy'])),
        ]);
    }

    /**
     * Single mode: proses packing tanpa potong stok → status packed.
     * Body: { items: [{item_id, qty}, ...] }
     */
    public function packOrder(Request $request, OrderFulfillment $fulfillment): JsonResponse
    {
        $items = $request->input('items', []);

        try {
            $result = $this->service->packOrder($fulfillment, $items);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'pack_order', [
            'items_scanned' => count($items),
        ]);

        return response()->json([
            'message'     => 'Order diproses. Stok belum dipotong.',
            'fulfillment' => $this->formatFulfillment($result->load(['lines.item'])),
        ]);
    }

    /**
     * Review: konfirmasi packed order → potong stok + status confirmed.
     */
    public function confirmPacked(OrderFulfillment $fulfillment): JsonResponse
    {
        try {
            $result = $this->service->confirmPacked($fulfillment, auth()->user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        FulfillmentAuditLog::record($fulfillment->id, 'confirm_packed', [
            'lines_count' => $result->lines->where('is_split_parent', false)->count(),
        ]);

        return response()->json([
            'message'     => 'Stok dipotong. Order selesai.',
            'fulfillment' => $this->formatFulfillment($result->load(['lines.item', 'confirmedBy'])),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUDIT LOG
    // ─────────────────────────────────────────────────────────────────────────

    /** Halaman history audit log untuk satu fulfillment. */
    public function history(OrderFulfillment $fulfillment)
    {
        $fulfillment->load('order.store.channel');
        return view('marketplace.fulfillment-history', compact('fulfillment'));
    }

    /** API: daftar audit log untuk satu fulfillment. */
    public function auditLogs(OrderFulfillment $fulfillment): JsonResponse
    {
        $logs = FulfillmentAuditLog::where('order_fulfillment_id', $fulfillment->id)
            ->with(['user:id,name', 'line:id,marketplace_sku,marketplace_item_name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($log) => [
                'id'         => $log->id,
                'action'     => $log->action,
                'label'      => FulfillmentAuditLog::actionLabel($log->action),
                'meta'       => $log->meta,
                'user'       => $log->user?->name ?? 'System',
                'line_sku'   => $log->line?->marketplace_sku,
                'created_at' => $log->created_at->toISOString(),
            ]);

        return response()->json($logs);
    }

    /** Re-resolve semua lines unmapped di seluruh fulfillment pending. */
    public function remapAll(): JsonResponse
    {
        $resolved = $this->service->remapAllPending();
        return response()->json(['resolved' => $resolved, 'message' => "Berhasil resolve {$resolved} item dari mapping."]);
    }

    /** Refresh snapshot stok semua lines. */
    public function refreshStock(OrderFulfillment $fulfillment): JsonResponse
    {
        $this->service->refreshStock($fulfillment);
        $fulfillment->load(['lines.item', 'lines.lot']);

        return response()->json($this->formatFulfillment($fulfillment, detail: true));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Formatters
    // ─────────────────────────────────────────────────────────────────────────

    private function formatFulfillment(OrderFulfillment $f, bool $detail = false): array
    {
        $data = [
            'id'             => $f->id,
            'status'         => $f->status,
            'notes'          => $f->notes,
            'confirmed_at'   => $f->confirmed_at?->toISOString(),
            'confirmed_by'   => $f->confirmedBy?->name,
            'warehouse'      => $f->warehouse ? ['id' => $f->warehouse->id, 'name' => $f->warehouse->name] : null,
            'order'          => $f->order ? [
                'id'               => $f->order->id,
                'channel_order_id' => $f->order->channel_order_id,
                'order_status'     => $f->order->order_status,
                'total_amount'     => $f->order->total_amount,
                'ordered_at'       => $f->order->ordered_at?->toISOString(),
                'store'            => $f->order->store ? [
                    'name'    => $f->order->store->name,
                    'channel' => $f->order->store->channel?->name,
                ] : null,
            ] : null,
            // Summary untuk list view — exclude split parents (sudah diarsipkan)
            'lines_count'    => $f->lines->where('is_split_parent', false)->count(),
            'lines_resolved' => $f->lines->where('is_split_parent', false)->filter->isResolved()->count(),
            'lines_picked'   => $f->lines->where('is_split_parent', false)->filter->isPicked()->count(),
            'lines_problem'  => $f->lines->where('is_split_parent', false)->filter->hasProblem()->count(),
            'lines_packed'   => $f->lines->where('is_split_parent', false)->filter(fn ($l) => $l->qty_fulfilled > 0)->count(),
            'lines_zero'     => $f->lines->where('is_split_parent', false)->filter(fn ($l) => $l->isResolved() && $l->qty_fulfilled === 0)->count(),
            'has_shortage'   => $f->lines->where('is_split_parent', false)->contains(fn ($l) => $l->hasShortage()),
            'all_resolved'   => $f->lines->where('is_split_parent', false)->every(fn ($l) => $l->isResolved()),
            'all_picked'     => $f->lines->where('is_split_parent', false)->every(fn ($l) => $l->isPicked()),
        ];

        if ($detail) {
            $data['lines']    = $f->lines->map(fn ($l) => $this->formatLine($l))->values();
            $data['scan_log'] = $f->scan_log ? json_decode($f->scan_log, true) : null;
        }

        return $data;
    }

    private function formatLine(OrderFulfillmentLine $l): array
    {
        return [
            'id'                     => $l->id,
            'marketplace_sku'        => $l->marketplace_sku,
            'marketplace_item_name'  => $l->marketplace_item_name,
            'qty_ordered'            => $l->qty_ordered,
            'qty_fulfilled'          => $l->qty_fulfilled,
            'stock_available'        => $l->stock_available,
            'stock_status'           => $l->stockStatus(),
            'substituted'            => $l->substituted,
            'is_split_parent'        => (bool) $l->is_split_parent,
            'split_parent_id'        => $l->split_parent_id,
            'notes'                  => $l->notes,
            'picked_at'    => $l->picked_at?->toISOString(),
            'pick_problem' => $l->pick_problem,
            'is_picked'    => $l->isPicked(),
            'has_problem'  => $l->hasProblem(),
            'item'  => $l->item  ? ['id' => $l->item->id,  'code' => $l->item->code,  'name' => $l->item->name]  : null,
            'lot'   => $l->lot   ? ['id' => $l->lot->id,   'code' => $l->lot->code]   : null,
        ];
    }
}
