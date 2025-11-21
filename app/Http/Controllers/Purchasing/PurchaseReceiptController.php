<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Purchasing\GoodsReceiptService;
use Illuminate\Http\Request;

class PurchaseReceiptController extends Controller
{
    protected GoodsReceiptService $service;

    public function __construct(GoodsReceiptService $service)
    {
        $this->service = $service;
    }

    /**
     * List GRN.
     */
    // public function index(Request $request)
    // {
    //     $q = PurchaseReceipt::with(['supplier', 'warehouse'])
    //         ->orderByDesc('date')
    //         ->orderByDesc('id');

    //     if ($request->filled('supplier_id')) {
    //         $q->where('supplier_id', $request->supplier_id);
    //     }

    //     if ($request->filled('warehouse_id')) {
    //         $q->where('warehouse_id', $request->warehouse_id);
    //     }

    //     if ($request->filled('status')) {
    //         $q->where('status', $request->status);
    //     }

    //     if ($request->filled('from_date')) {
    //         $q->whereDate('date', '>=', $request->from_date);
    //     }

    //     if ($request->filled('to_date')) {
    //         $q->whereDate('date', '<=', $request->to_date);
    //     }

    //     $receipts = $q->paginate(15)->withQueryString();

    //     $suppliers = Supplier::orderBy('name')->get();
    //     $warehouses = Warehouse::orderBy('name')->get();

    //     return view('purchasing.purchase_receipts.index', compact(
    //         'receipts',
    //         'suppliers',
    //         'warehouses'
    //     ));
    // }

    public function index(Request $request)
    {
        $q = PurchaseReceipt::with(['supplier', 'warehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('warehouse_id')) {
            $q->where('warehouse_id', $request->warehouse_id);
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

        $receipts = $q->paginate(15)->withQueryString();

        // === SUMMARY untuk mini dashboard ===
        $summary = (clone $q)
            ->selectRaw('COUNT(*) as total_receipts')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count")
            ->selectRaw('MAX(date) as last_date')
            ->first();

        // Data pendukung filter
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        // === Infinite scroll AJAX response ===
        if ($request->ajax()) {
            $html = view('purchasing.purchase_receipts._rows', [
                'receipts' => $receipts,
                'startIndex' => method_exists($receipts, 'firstItem')
                ? $receipts->firstItem()
                : 1,
            ])->render();

            return response()->json([
                'html' => $html,
                'next_page_url' => $receipts->nextPageUrl(),
            ]);
        }

        return view('purchasing.purchase_receipts.index', [
            'receipts' => $receipts,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'summary' => $summary,
        ]);
    }

    /**
     * Form create GRN.
     */
    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        $order = null; // default: tidak dari PO tertentu

        $selectedSupplierId = $request->input('supplier_id');
        $lines = collect();

        if ($selectedSupplierId) {
            // ambil semua baris PO status draft / belum diterima untuk supplier tsb
            $lines = PurchaseOrderLine::with(['item', 'purchaseOrder'])
                ->whereHas('purchaseOrder', function ($q) use ($selectedSupplierId) {
                    $q->where('supplier_id', $selectedSupplierId)
                        ->where('status', 'draft'); // silakan sesuaikan
                })
                ->get();
        }

        return view('purchasing.purchase_receipts.create', compact(
            'suppliers',
            'warehouses',
            'order',
            'lines',
            'selectedSupplierId'
        ));
    }

    public function createFromOrder(PurchaseOrder $purchase_order)
    {
        $purchase_order->load(['supplier', 'lines.item']);

        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $items = Item::where('active', 1)->orderBy('name')->get();

        return view('purchasing.purchase_receipts.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'items' => $items,
            'order' => $purchase_order, // <-- penting
        ]);
    }

    /**
     * Simpan GRN (draft).
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // mapping dari array paralel (item_id[], qty_received[], dst.) ke lines[]
        $lines = [];
        $itemIds = $request->input('item_id', []);
        $qtyReceiveds = $request->input('qty_received', []);
        $qtyRejects = $request->input('qty_reject', []);
        $unitPrices = $request->input('unit_price', []);
        $lineNotes = $request->input('line_notes', []);
        $units = $request->input('unit', []);

        foreach ($itemIds as $i => $itemId) {
            if (!$itemId) {
                continue;
            }

            $lines[] = [
                'item_id' => $itemId,
                'qty_received' => $qtyReceiveds[$i] ?? 0,
                'qty_reject' => $qtyRejects[$i] ?? 0,
                'unit_price' => $unitPrices[$i] ?? 0,
                'unit' => $units[$i] ?? null,
                'notes' => $lineNotes[$i] ?? null,
            ];
        }

        $data['lines'] = $lines;
        $data['created_by'] = $request->user()->id;

        $receipt = $this->service->create($data);

        return redirect()
            ->route('purchasing.purchase_receipts.show', $receipt->id)
            ->with('success', 'Goods Receipt berhasil dibuat sebagai draft.');
    }

    /**
     * Detail GRN.
     */
    public function show(PurchaseReceipt $purchase_receipt)
    {
        $purchase_receipt->load(['supplier', 'warehouse', 'lines.item']);

        return view('purchasing.purchase_receipts.show', [
            'receipt' => $purchase_receipt,
        ]);
    }

    /**
     * Form edit GRN (hanya draft).
     */
    public function edit(PurchaseReceipt $purchase_receipt)
    {
        if ($purchase_receipt->status !== 'draft') {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'Hanya GRN draft yang bisa diedit.');
        }

        $purchase_receipt->load(['lines.item']);

        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $items = Item::where('active', 1)->orderBy('name')->get();

        return view('purchasing.purchase_receipts.edit', [
            'receipt' => $purchase_receipt,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'items' => $items,
        ]);
    }

    /**
     * Update GRN draft.
     */
    public function update(Request $request, PurchaseReceipt $purchase_receipt)
    {
        if ($purchase_receipt->status !== 'draft') {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'Hanya GRN draft yang bisa diupdate.');
        }

        $data = $this->validateData($request);

        $lines = [];
        $itemIds = $request->input('item_id', []);
        $qtyReceiveds = $request->input('qty_received', []);
        $qtyRejects = $request->input('qty_reject', []);
        $unitPrices = $request->input('unit_price', []);
        $lineNotes = $request->input('line_notes', []);
        $units = $request->input('unit', []);

        foreach ($itemIds as $i => $itemId) {
            if (!$itemId) {
                continue;
            }

            $lines[] = [
                'item_id' => $itemId,
                'qty_received' => $qtyReceiveds[$i] ?? 0,
                'qty_reject' => $qtyRejects[$i] ?? 0,
                'unit_price' => $unitPrices[$i] ?? 0,
                'unit' => $units[$i] ?? null,
                'notes' => $lineNotes[$i] ?? null,
            ];
        }

        $data['lines'] = $lines;

        $receipt = $this->service->update($purchase_receipt, $data);

        return redirect()
            ->route('purchasing.purchase_receipts.show', $receipt->id)
            ->with('success', 'Goods Receipt berhasil diperbarui.');
    }

    /**
     * POST / Confirm GRN → masuk stok.
     */
    public function post(PurchaseReceipt $purchase_receipt)
    {
        try {
            $receipt = $this->service->post($purchase_receipt);

            return redirect()
                ->route('purchasing.purchase_receipts.show', $receipt->id)
                ->with('success', 'Goods Receipt berhasil diposting. Stok gudang sudah bertambah.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Validasi basic GRN header.
     */

    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],

            // detail item
            'po_line_id' => ['required', 'array'],
            'po_line_id.*' => ['required', 'exists:purchase_order_lines,id'],

            'item_id' => ['required', 'array'],
            'item_id.*' => ['required', 'exists:items,id'],

            'qty_received' => ['required', 'array'],
            'qty_received.*' => ['nullable', 'numeric', 'min:0'],

            'qty_reject' => ['required', 'array'],
            'qty_reject.*' => ['nullable', 'numeric', 'min:0'],

            'selected' => ['nullable', 'array'], // bisa null kalau user belum centang
            'selected.*' => ['nullable'],

            'unit_price' => ['required', 'array'],
            'unit_price.*' => ['nullable', 'numeric'],

            'unit' => ['required', 'array'],
            'unit.*' => ['nullable', 'string'],
        ], [
            // Pesan error custom
            'qty_received.*.numeric' => 'Qty diterima harus angka.',
            'qty_reject.*.numeric' => 'Qty reject harus angka.',
            'qty_received.*.min' => 'Qty diterima tidak boleh minus.',
            'qty_reject.*.min' => 'Qty reject tidak boleh minus.',
        ]);

        // ================================================================
        //  VALIDASI LOGIKA: qty_received + qty_reject ≤ qty_po
        // ================================================================
        $errors = [];
        $anySelected = false;

        foreach ($validated['po_line_id'] as $i => $poLineId) {

            $selected = $request->input("selected.$i");
            $qtyRec = (float) ($validated['qty_received'][$i] ?? 0);
            $qtyRej = (float) ($validated['qty_reject'][$i] ?? 0);

            if ($selected) {
                $anySelected = true;

                $poQty = \App\Models\PurchaseOrderLine::find($poLineId)?->qty ?? 0;

                // Gabungan lebih besar dari qty PO → ERROR
                if ($qtyRec + $qtyRej > $poQty) {
                    $errors["qty_received.$i"] = "Qty diterima + qty reject tidak boleh melebihi Qty PO ($poQty).";
                    $errors["qty_reject.$i"] = "Qty diterima + qty reject tidak boleh melebihi Qty PO ($poQty).";
                }

                // Qty diterima tidak boleh > PO
                if ($qtyRec > $poQty) {
                    $errors["qty_received.$i"] = "Qty diterima tidak boleh lebih dari Qty PO ($poQty).";
                }

                // Qty reject tidak boleh > PO
                if ($qtyRej > $poQty) {
                    $errors["qty_reject.$i"] = "Qty reject tidak boleh lebih dari Qty PO ($poQty).";
                }
            }
        }

        if (!$anySelected) {
            $errors["selected"] = "Tidak ada item yang dipilih. Centang minimal satu item.";
        }

        // Jika ada error → throw kembali ke form
        if (!empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return $validated;
    }

}
