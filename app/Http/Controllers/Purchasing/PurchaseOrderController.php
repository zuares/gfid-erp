<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Purchasing\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $service
    ) {}

    /**
     * List PO.
     */
    public function index(Request $request)
    {
        $q = PurchaseOrder::with(['supplier', 'approvedBy', 'purchaseReceipts'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        // (optional) filter order_type kalau kolomnya ada & request ada
        if ($request->filled('order_type')) {
            $q->where('order_type', $request->order_type);
        }

        if ($request->filled('from_date')) {
            $q->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $q->whereDate('date', '<=', $request->to_date);
        }

        // Summary untuk mini dashboard (hasil filter penuh, bukan per halaman)
        $summaryQuery = clone $q;
        $summary = (object) [
            'total_orders' => (clone $summaryQuery)->count(),
            'total_grand_total' => (clone $summaryQuery)->sum('grand_total'),
            'draft_count' => (clone $summaryQuery)->where('status', 'draft')->count(),
            'approved_count' => (clone $summaryQuery)->where('status', 'approved')->count(),
            'closed_count' => (clone $summaryQuery)->where('status', 'closed')->count(),
            'last_date' => optional((clone $summaryQuery)->orderByDesc('date')->first())->date,
        ];

        $orders = $q->paginate(20)->withQueryString();

        if ($request->ajax()) {
            $html = view('purchasing.purchase_orders._table_rows', [
                'orders' => $orders,
            ])->render();

            return response()->json([
                'html' => $html,
                'next_page_url' => $orders->nextPageUrl(),
            ]);
        }

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchasing.purchase_orders.index', compact('orders', 'suppliers', 'summary'));
    }

    /**
     * Form create PO.
     * Support: jenis pembelian material / finished_good.
     */
    public function create(Request $request)
    {
        $order = new PurchaseOrder();
        $order->date = now()->toDateString();
        $order->tax_percent = 11;
        $order->discount = 0;
        $order->shipping_cost = 0;

        // =========================
        // Determine order type
        // =========================
        $orderType = $this->normalizeOrderType($request->input('order_type', 'material'));

        // kalau tabel punya kolom order_type, set buat tampilan (optional)
        if ($this->poHasOrderTypeColumn($order)) {
            $order->order_type = $orderType;
        }

        $suppliers = Supplier::orderBy('name')->get();

        // items sesuai jenis
        $items = Item::query()
            ->where('active', 1)
            ->where('type', $orderType)
            ->with('category')
            ->orderBy('name')
            ->limit(300)
            ->get();

        $lines = collect();

        return view('purchasing.purchase_orders.create', [
            'order' => $order,
            'suppliers' => $suppliers,
            'items' => $items,
            'lines' => $lines,
            'orderType' => $orderType,
        ]);
    }

    /**
     * Simpan PO baru.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['created_by'] = $request->user()->id;
        $data['status'] = 'draft';

        // order_type optional kalau kolom ada (atau kamu mau simpan di payload)
        $data['order_type'] = $this->normalizeOrderType($request->input('order_type', $data['order_type'] ?? 'material'));

        $order = $this->service->create($data);

        return redirect()
            ->route('purchasing.purchase_orders.show', $order->id)
            ->with('success', 'Purchase Order berhasil dibuat.');
    }

    /**
     * Detail PO.
     */
    public function show(PurchaseOrder $purchase_order)
    {
        $purchase_order->load([
            'supplier',
            'lines.item',
            'createdBy',
            'approvedBy',
            'cancelledBy',
            'purchaseReceipts',
        ]);

        return view('purchasing.purchase_orders.show', [
            'order' => $purchase_order,
        ]);
    }

    /**
     * Form edit PO.
     */
    public function edit(Request $request, PurchaseOrder $purchase_order)
    {
        // blokir edit kalau status bukan draft
        if ($purchase_order->status !== 'draft') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang sudah di-approve/cancel tidak bisa diedit.');
        }

        // load detail + item
        $purchase_order->load(['lines.item']);

        $suppliers = Supplier::orderBy('name')->get();

        // =========================
        // Determine order type
        // Priority:
        // 1) DB order_type (kalau ada)
        // 2) query ?order_type=...
        // 3) default material
        // =========================
        $orderType = $this->normalizeOrderType(
            (string) ($purchase_order->getAttribute('order_type')
                ?: $request->input('order_type', 'material'))
        );

        // =========================
        // Items list:
        // - ambil items sesuai orderType (rapi)
        // - tapi juga gabungkan items yang sudah dipakai di lines,
        //   supaya dropdown tidak blank walau orderType berubah via query.
        // =========================
        $itemsBase = Item::query()
            ->where('active', 1)
            ->where('type', $orderType)
            ->with('category')
            ->orderBy('name')
            ->get();

        $lineItemIds = $purchase_order->lines
            ->pluck('item_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $itemsLine = collect();
        if (!empty($lineItemIds)) {
            $itemsLine = Item::query()
                ->whereIn('id', $lineItemIds)
                ->with('category')
                ->get();
        }

        // merge + unique by id
        $items = $itemsBase
            ->concat($itemsLine)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $lines = $purchase_order->lines;

        return view('purchasing.purchase_orders.edit', [
            'order' => $purchase_order,
            'suppliers' => $suppliers,
            'items' => $items,
            'lines' => $lines,
            'orderType' => $orderType,
        ]);
    }

    /**
     * Update PO.
     */
    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status !== 'draft') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang sudah di-approve/cancel tidak bisa diubah.');
        }

        $data = $this->validateData($request);
        $data['status'] = 'draft';

        $data['order_type'] = $this->normalizeOrderType(
            $request->input('order_type', $purchase_order->getAttribute('order_type') ?: 'material')
        );

        $order = $this->service->update($purchase_order, $data);

        return redirect()
            ->route('purchasing.purchase_orders.show', $order->id)
            ->with('success', 'Purchase Order berhasil diperbarui.');
    }

    /**
     * Hapus PO.
     */
    public function destroy(PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status !== 'draft') {
            return back()->with('error', 'PO non-draft tidak boleh dihapus.');
        }

        $purchase_order->lines()->delete();
        $purchase_order->delete();

        return redirect()
            ->route('purchasing.purchase_orders.index')
            ->with('success', 'Purchase Order berhasil dihapus.');
    }

    // ======================================================================
    // VALIDASI
    // ======================================================================

    protected function validateData(Request $request): array
    {
        $rules = [
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],

            // NEW: jenis PO (kalau kolom belum ada pun tetap dipakai untuk filtering item)
            'order_type' => ['required', 'in:material,finished_good'],

            'shipping_cost' => ['nullable', 'string'],
            'discount' => ['nullable', 'string'],
            'tax_percent' => ['nullable', 'string'],

            'lines' => ['array'],
            'lines.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['nullable', 'string'],
            'lines.*.unit_price' => ['nullable', 'string'],
            'lines.*.discount' => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        $normalize = function ($v) {
            if ($v === null || $v === '') {
                return 0;
            }

            $v = trim((string) $v);
            $v = str_replace(' ', '', $v);
            if (strpos($v, ',') !== false) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
                $v = str_replace('.', '', $v);
            }
            return (float) $v;
        };

        // normalisasi header
        $data['discount'] = $normalize($data['discount'] ?? 0);
        $data['tax_percent'] = $normalize($data['tax_percent'] ?? 0);
        $data['shipping_cost'] = $normalize($data['shipping_cost'] ?? 0);

        $data['order_type'] = $this->normalizeOrderType($data['order_type'] ?? $request->input('order_type', 'material'));

        // normalisasi lines
        $lines = $data['lines'] ?? [];
        foreach ($lines as &$line) {
            $line['qty'] = $normalize($line['qty'] ?? 0);
            $line['unit_price'] = $normalize($line['unit_price'] ?? 0);
            $line['discount'] = $normalize($line['discount'] ?? 0);
        }
        $data['lines'] = $lines;

        return $data;
    }

    public function approve(PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status !== 'draft') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang bukan draft tidak bisa di-approve.');
        }

        $this->service->approve($purchase_order, auth()->id());

        return redirect()
            ->route('purchasing.purchase_orders.show', $purchase_order->id)
            ->with('success', 'PO berhasil di-approve.');
    }

    public function cancel(PurchaseOrder $purchase_order)
    {
        if (!in_array($purchase_order->status, ['draft', 'approved'], true)) {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO ini sudah tidak bisa dibatalkan.');
        }

        if ($purchase_order->purchaseReceipts()->exists()) {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang sudah punya GRN tidak boleh dibatalkan.');
        }

        $this->service->cancel($purchase_order, Auth::id());

        return redirect()
            ->route('purchasing.purchase_orders.show', $purchase_order->id)
            ->with('success', 'PO berhasil dibatalkan.');
    }

    // ======================================================================
    // HELPERS
    // ======================================================================

    protected function normalizeOrderType(?string $value): string
    {
        $v = strtolower(trim((string) $value));
        return in_array($v, ['material', 'finished_good'], true) ? $v : 'material';
    }

    protected function poHasOrderTypeColumn(PurchaseOrder $order): bool
    {
        // Aman: kalau kolom belum ada, attribute akan null terus; tapi ini sekadar indikator.
        // Kamu bisa hapus fungsi ini kalau kamu sudah yakin kolomnya ada.
        try {
            $order->getAttribute('order_type');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
