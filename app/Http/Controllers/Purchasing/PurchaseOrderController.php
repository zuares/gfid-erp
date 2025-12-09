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
    protected PurchaseOrderService $service;

    public function __construct(PurchaseOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * List PO.
     */
    // app/Http/Controllers/Purchasing/PurchaseOrderController.php

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

        // Data untuk tabel (paginated)
        $orders = $q->paginate(20)->withQueryString();

        // Jika AJAX (infinite scroll): balas JSON (HTML rows + next_page_url)
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
     */
    public function create()
    {
        $order = new PurchaseOrder();
        $order->date = now()->toDateString();
        $order->tax_percent = 11;
        $order->discount = 0;
        $order->shipping_cost = 0;

        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::query()
            ->where('active', 1)
            ->where('type', 'material')
            ->with('category')
            ->orderBy('name')
            ->limit(100)
            ->get();

        // lines kosong untuk form awal
        $lines = collect();

        return view('purchasing.purchase_orders.create', compact('order', 'suppliers', 'items', 'lines'));
    }

    /**
     * Simpan PO baru.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['created_by'] = $request->user()->id;
        $data['status'] = 'draft'; // ⬅ selalu draft saat dibuat

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

    /**
     * Form edit PO.
     */
    public function edit(PurchaseOrder $purchase_order)
    {

        // ⬅ blokir edit kalau status bukan draft
        if ($purchase_order->status !== 'draft') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang sudah di-approve/cancel tidak bisa diedit.');
        }

        // load detail + item
        $purchase_order->load(['lines.item']);

        $suppliers = Supplier::orderBy('name')->get();
        $items = Item::where('active', 1)->orderBy('name')->get();

        // bisa langsung kirim collection-nya, _form akan handle array/collection
        $lines = $purchase_order->lines;

        return view('purchasing.purchase_orders.edit', [
            'order' => $purchase_order,
            'suppliers' => $suppliers,
            'items' => $items,
            'lines' => $lines,
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
        // jaga-jaga: jangan izinkan ganti status lewat update
        $data['status'] = 'draft';

        // kirim ke service, termasuk status
        $order = $this->service->update($purchase_order, $data);

        return redirect()
            ->route('purchasing.purchase_orders.show', $order->id)
            ->with('success', 'Purchase Order berhasil diperbarui.');
    }

    /**
     * Hapus PO (opsional).
     */
    public function destroy(PurchaseOrder $purchase_order)
    {
        // Tergantung rules bisnis, kalau PO sudah dipakai stok jangan dihapus
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
            'shipping_cost' => ['nullable', 'string'],
            'lines' => ['array'],
            'lines.*.item_id' => ['nullable', 'integer'],
            'lines.*.qty' => ['nullable', 'string'],
            'lines.*.unit_price' => ['nullable', 'string'],

            // pesan error custom (opsional)
            'lines.required' => 'Minimal harus ada 1 baris detail.',
            'lines.*.item_id.required' => 'Item harus dipilih.',
            'lines.*.qty.required' => 'Qty wajib diisi.',
            'lines.*.unit_price.required' => 'Harga wajib diisi.',
        ];

        $data = $request->validate($rules);

        $normalize = function ($v) {
            if ($v === null || $v === '') {
                return 0;
            }

            $v = trim($v);
            $v = str_replace(' ', '', $v);
            if (strpos($v, ',') !== false) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
                $v = str_replace('.', '', $v);
            }
            return (float) $v;
        };

        $data['discount'] = $normalize($data['discount'] ?? 0);
        $data['tax_percent'] = $normalize($data['tax_percent'] ?? 0);
        $data['shipping_cost'] = $normalize($data['shipping_cost'] ?? 0);

        foreach ($data['lines'] as &$line) {
            $line['qty'] = $normalize($line['qty']);
            $line['unit_price'] = $normalize($line['unit_price']);
            $line['discount'] = $normalize($line['discount'] ?? 0);
        }

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
        // Double safety di controller: status + GRN
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

}
