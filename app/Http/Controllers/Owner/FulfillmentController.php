<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
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

        return response()->json($this->formatFulfillment($fulfillment->fresh(['order.store.channel', 'lines.item', 'warehouse']), detail: true));
    }

    /** Update satu line (ganti item / lot / qty / notes). */
    public function updateLine(Request $request, OrderFulfillmentLine $line): JsonResponse
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

        return response()->json([
            'message'     => 'Fulfillment dikonfirmasi. Stok sudah dipotong.',
            'fulfillment' => $this->formatFulfillment($result->load(['lines.item', 'confirmedBy'])),
        ]);
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
            // Summary untuk list view
            'lines_count'    => $f->lines->count(),
            'lines_resolved' => $f->lines->filter->isResolved()->count(),
            'has_shortage'   => $f->lines->contains(fn ($l) => $l->hasShortage()),
            'all_resolved'   => $f->lines->every(fn ($l) => $l->isResolved()),
        ];

        if ($detail) {
            $data['lines'] = $f->lines->map(fn ($l) => $this->formatLine($l))->values();
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
            'notes'                  => $l->notes,
            'item'  => $l->item  ? ['id' => $l->item->id,  'code' => $l->item->code,  'name' => $l->item->name]  : null,
            'lot'   => $l->lot   ? ['id' => $l->lot->id,   'code' => $l->lot->code]   : null,
        ];
    }
}
