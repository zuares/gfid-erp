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
        // Base query + default urutan updated_at desc
        $q = PurchaseReceipt::with(['supplier', 'warehouse'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        // FILTER SUPPLIER
        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', $request->supplier_id);
        }

        // FILTER GUDANG
        if ($request->filled('warehouse_id')) {
            $q->where('warehouse_id', $request->warehouse_id);
        }

        // FILTER STATUS (default: TIDAK difilter apa-apa → tampil semua)
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        // FILTER PERIODE
        if ($request->filled('from_date')) {
            $q->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $q->whereDate('date', '<=', $request->to_date);
        }

        // === SUMMARY mini dashboard (SETELAH filter diterapkan) ===
        // PENTING: clone SEBELUM paginate supaya summary pakai seluruh hasil filter
        $summaryQuery = clone $q;

        $summary = $summaryQuery
            ->selectRaw('COUNT(*) as total_receipts')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
            ->selectRaw("SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count")
            ->selectRaw('MAX(date) as last_date')
            ->first();

        // DATA TABEL (PAKAI PAGINATE)
        $receipts = $q->paginate(15)->withQueryString();

        // Data pendukung filter
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        // AJAX untuk infinite scroll
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
            'status' => $request->input('status'), // optional, kalau mau dipakai di Blade
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

        $lines = PurchaseOrderLine::with(['item', 'purchaseOrder.supplier'])
            ->withCount('draftReceiptLines') // ⬅️ penting
            ->whereHas('purchaseOrder', function ($q) use ($selectedSupplierId) {
                $q->where('status', 'approved');

                if ($selectedSupplierId) {
                    $q->where('supplier_id', $selectedSupplierId);
                }
            })
            ->orderBy('purchase_order_id')
            ->orderBy('id')
            ->get();

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
        if ($purchase_order->status !== 'approved') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'GRN hanya bisa dibuat dari PO yang sudah di-approve.');
        }

        $purchase_order->load(['supplier', 'lines.item']);

        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $items = Item::where('active', 1)->orderBy('name')->get();

        // supaya Blade punya supplier terpilih yang konsisten
        $selectedSupplierId = $purchase_order->supplier_id;

        return view('purchasing.purchase_receipts.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'items' => $items,
            'order' => $purchase_order, // dipakai di Blade sebagai $order
            'lines' => $purchase_order->lines, // optional, fallback tetap pakai $order->lines
            'selectedSupplierId' => $selectedSupplierId,
        ]);
    }

/**
 * Simpan GRN (draft).
 */
    public function store(Request $request)
    {

        // Validasi header (tanggal, supplier_id, warehouse_id, dst.)
        $data = $this->validateData($request);

        // Ambil array paralel dari form
        $poLineIds = $request->input('po_line_id', []); // hidden input di Blade
        $itemIds = $request->input('item_id', []);
        $qtyReceiveds = $request->input('qty_received', []);
        $qtyRejects = $request->input('qty_reject', []);
        $unitPrices = $request->input('unit_price', []);
        $lineNotes = $request->input('line_notes', []);
        $units = $request->input('unit', []);

        // Checkbox selected[index] → hanya baris yang dicentang yang diproses
        $selected = $request->input('selected', []); // key = index baris

        $lines = [];

        foreach ($itemIds as $i => $itemId) {
            if (!$itemId) {
                continue;
            }

            // kalau tidak dicentang, skip
            if (!array_key_exists($i, $selected)) {
                continue;
            }

            // normalisasi angka
            $qtyRec = (float) str_replace(',', '.', (string) ($qtyReceiveds[$i] ?? 0));
            $qtyRej = (float) str_replace(',', '.', (string) ($qtyRejects[$i] ?? 0));

            // kalau dua-duanya 0 atau kosong, abaikan
            if ($qtyRec <= 0 && $qtyRej <= 0) {
                continue;
            }

            $unitPrice = (float) str_replace(',', '.', (string) ($unitPrices[$i] ?? 0));

            $lines[] = [
                // ⬇⬇ PENTING: pakai nama kolom yang ada di tabel purchase_receipt_lines
                'purchase_order_line_id' => $poLineIds[$i] ?? null,

                'item_id' => $itemId,
                'qty_received' => $qtyRec,
                'qty_reject' => $qtyRej,
                'unit_price' => $unitPrice,
                'unit' => $units[$i] ?? null,
                'notes' => $lineNotes[$i] ?? null,
            ];

        }

        // Kalau tidak ada satu pun baris valid
        if (empty($lines)) {
            return back()
                ->withInput()
                ->withErrors([
                    'lines' => 'Tidak ada item yang dipilih, atau Qty Diterima / Reject masih 0.',
                ]);
        }

        // inject ke payload untuk service
        $data['lines'] = $lines;
        $data['created_by'] = $request->user()->id;

        $receipt = $this->service->create($data);
        dd($receipt);

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
        dd($receipt);

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
