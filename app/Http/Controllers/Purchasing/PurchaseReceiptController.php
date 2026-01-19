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
use Illuminate\Validation\ValidationException;

class PurchaseReceiptController extends Controller
{
    public function __construct(
        protected GoodsReceiptService $service
    ) {}

    /**
     * Index GRN (Goods Receipt).
     */
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

        // STATUS default posted kalau param status tidak ada sama sekali
        $status = $request->input('status', null);
        if ($request->has('status')) {
            if ($status !== null && $status !== '') {
                $q->where('status', $status);
            }
        } else {
            $q->where('status', 'posted');
            $status = 'posted';
        }

        if ($request->filled('from_date')) {
            $q->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $q->whereDate('date', '<=', $request->to_date);
        }

        $receipts = $q->paginate(15)->withQueryString();

        $summary = (clone $q)
            ->selectRaw('COUNT(*) as total_receipts')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
            ->selectRaw("SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count")
            ->selectRaw('MAX(date) as last_date')
            ->first();

        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

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

        return view('purchasing.purchase_receipts.index', compact(
            'receipts',
            'suppliers',
            'warehouses',
            'summary',
            'status'
        ));
    }

    /**
     * Form create GRN dari semua PO approved (optional filter supplier + order_type).
     */
    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        $order = null;

        $selectedSupplierId = $request->input('supplier_id');

        $selectedOrderType = $this->normalizeOrderType($request->input('order_type')); // null/material/finished_good

        $lines = PurchaseOrderLine::with(['item', 'purchaseOrder.supplier'])
            ->withCount('draftReceiptLines')
            ->whereHas('purchaseOrder', function ($q) use ($selectedSupplierId, $selectedOrderType) {
                $q->where('status', 'approved');

                if ($selectedSupplierId) {
                    $q->where('supplier_id', $selectedSupplierId);
                }

                if ($selectedOrderType) {
                    $q->where('order_type', $selectedOrderType);
                }
            })
            ->orderBy('purchase_order_id')
            ->orderBy('id')
            ->get();

        $lines->each(function (PurchaseOrderLine $line) {
            $line->has_draft_grn = ($line->draft_receipt_lines_count ?? 0) > 0;
        });

        // ✅ Default warehouse (controller side)
        $defaultWhCode = ($selectedOrderType === 'finished_good') ? 'WH-RTS' : 'RM';
        $defaultWarehouse = $warehouses->firstWhere('code', $defaultWhCode);

        // fallback kalau tidak ketemu
        if (!$defaultWarehouse) {
            $defaultWhCode = 'RM';
            $defaultWarehouse = $warehouses->firstWhere('code', 'RM');
        }

        $selectedWarehouseId = (int) old('warehouse_id', $defaultWarehouse?->id);

        return view('purchasing.purchase_receipts.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'order' => $order,
            'lines' => $lines,
            'selectedSupplierId' => $selectedSupplierId,
            'selectedOrderType' => $selectedOrderType,
            'defaultWhCode' => $defaultWhCode,
            'defaultWarehouse' => $defaultWarehouse,
            'selectedWarehouseId' => $selectedWarehouseId,
        ]);
    }

    /**
     * Form create GRN dari satu PO tertentu.
     */
    public function createFromOrder(PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status !== 'approved') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'GRN hanya bisa dibuat dari PO yang sudah di-approve.');
        }

        $purchase_order->load([
            'supplier',
            'lines' => function ($q) {
                $q->with('item')->withCount('draftReceiptLines');
            },
        ]);

        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        $lines = $purchase_order->lines;
        $lines->each(function (PurchaseOrderLine $line) {
            $line->has_draft_grn = ($line->draft_receipt_lines_count ?? 0) > 0;
        });

        $selectedSupplierId = $purchase_order->supplier_id;
        $selectedOrderType = $this->normalizeOrderType($purchase_order->order_type) ?: 'material';

        // ✅ Default warehouse (controller side)
        $defaultWhCode = ($selectedOrderType === 'finished_good') ? 'WH-RTS' : 'RM';
        $defaultWarehouse = $warehouses->firstWhere('code', $defaultWhCode);

        if (!$defaultWarehouse) {
            $defaultWhCode = 'RM';
            $defaultWarehouse = $warehouses->firstWhere('code', 'RM');
        }

        $selectedWarehouseId = (int) old('warehouse_id', $defaultWarehouse?->id);

        return view('purchasing.purchase_receipts.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'order' => $purchase_order,
            'lines' => $lines,
            'selectedSupplierId' => $selectedSupplierId,
            'selectedOrderType' => $selectedOrderType,
            'defaultWhCode' => $defaultWhCode,
            'defaultWarehouse' => $defaultWarehouse,
            'selectedWarehouseId' => $selectedWarehouseId,
        ]);
    }

    /**
     * Simpan GRN (draft).
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $poLineIds = $request->input('po_line_id', []);
        $itemIds = $request->input('item_id', []);
        $qtyReceiveds = $request->input('qty_received', []);
        $qtyRejects = $request->input('qty_reject', []);
        $unitPrices = $request->input('unit_price', []);
        $lineNotes = $request->input('line_notes', []);
        $units = $request->input('unit', []);

        $selected = $request->input('selected', []); // key = index baris

        $lines = [];

        foreach ($itemIds as $i => $itemId) {
            if (!$itemId) {
                continue;
            }

            if (!array_key_exists($i, $selected)) {
                continue;
            }

            $qtyRec = (float) str_replace(',', '.', (string) ($qtyReceiveds[$i] ?? 0));
            $qtyRej = (float) str_replace(',', '.', (string) ($qtyRejects[$i] ?? 0));

            if ($qtyRec <= 0 && $qtyRej <= 0) {
                continue;
            }

            $unitPrice = (float) str_replace(',', '.', (string) ($unitPrices[$i] ?? 0));

            $lines[] = [
                'purchase_order_line_id' => $poLineIds[$i] ?? null,
                'item_id' => (int) $itemId,
                'qty_received' => $qtyRec,
                'qty_reject' => $qtyRej,
                'unit_price' => $unitPrice,
                'unit' => $units[$i] ?? null,
                'notes' => $lineNotes[$i] ?? null,
            ];
        }

        if (empty($lines)) {
            return back()
                ->withInput()
                ->withErrors(['lines' => 'Tidak ada item yang dipilih, atau Qty Diterima / Reject masih 0.']);
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

        return view('purchasing.purchase_receipts.edit', compact(
            'purchase_receipt',
            'suppliers',
            'warehouses',
            'items'
        ));
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

        $poLineIds = $request->input('po_line_id', []); // ✅ penting
        $itemIds = $request->input('item_id', []);
        $qtyReceiveds = $request->input('qty_received', []);
        $qtyRejects = $request->input('qty_reject', []);
        $unitPrices = $request->input('unit_price', []);
        $lineNotes = $request->input('line_notes', []);
        $units = $request->input('unit', []);

        $lines = [];

        foreach ($itemIds as $i => $itemId) {
            if (!$itemId) {
                continue;
            }

            $qtyRec = (float) str_replace(',', '.', (string) ($qtyReceiveds[$i] ?? 0));
            $qtyRej = (float) str_replace(',', '.', (string) ($qtyRejects[$i] ?? 0));
            $unitPrice = (float) str_replace(',', '.', (string) ($unitPrices[$i] ?? 0));

            // ✅ simpan juga purchase_order_line_id agar tidak hilang saat edit
            $lines[] = [
                'purchase_order_line_id' => $poLineIds[$i] ?? null,
                'item_id' => (int) $itemId,
                'qty_received' => $qtyRec,
                'qty_reject' => $qtyRej,
                'unit_price' => $unitPrice,
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
     * Validasi basic GRN header + struktur array detail.
     */
    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],

            'po_line_id' => ['required', 'array'],
            'po_line_id.*' => ['required', 'exists:purchase_order_lines,id'],

            'item_id' => ['required', 'array'],
            'item_id.*' => ['required', 'exists:items,id'],

            'qty_received' => ['required', 'array'],
            'qty_received.*' => ['nullable', 'numeric', 'min:0'],

            'qty_reject' => ['required', 'array'],
            'qty_reject.*' => ['nullable', 'numeric', 'min:0'],

            'selected' => ['nullable', 'array'],
            'selected.*' => ['nullable'],

            'unit_price' => ['required', 'array'],
            'unit_price.*' => ['nullable', 'numeric'],

            'unit' => ['required', 'array'],
            'unit.*' => ['nullable', 'string'],
        ], [
            'qty_received.*.numeric' => 'Qty diterima harus angka.',
            'qty_reject.*.numeric' => 'Qty reject harus angka.',
            'qty_received.*.min' => 'Qty diterima tidak boleh minus.',
            'qty_reject.*.min' => 'Qty reject tidak boleh minus.',
        ]);

        // Validasi logika qty <= qty_po, minimal 1 selected
        $errors = [];
        $anySelected = false;

        foreach ($validated['po_line_id'] as $i => $poLineId) {
            $selected = $request->input("selected.$i");
            $qtyRec = (float) ($validated['qty_received'][$i] ?? 0);
            $qtyRej = (float) ($validated['qty_reject'][$i] ?? 0);

            if ($selected) {
                $anySelected = true;

                $poQty = (float) (PurchaseOrderLine::find($poLineId)?->qty ?? 0);

                if ($qtyRec + $qtyRej > $poQty) {
                    $errors["qty_received.$i"] = "Qty diterima + qty reject tidak boleh melebihi Qty PO ($poQty).";
                    $errors["qty_reject.$i"] = "Qty diterima + qty reject tidak boleh melebihi Qty PO ($poQty).";
                }

                if ($qtyRec > $poQty) {
                    $errors["qty_received.$i"] = "Qty diterima tidak boleh lebih dari Qty PO ($poQty).";
                }

                if ($qtyRej > $poQty) {
                    $errors["qty_reject.$i"] = "Qty reject tidak boleh lebih dari Qty PO ($poQty).";
                }
            }
        }

        if (!$anySelected) {
            $errors['selected'] = 'Tidak ada item yang dipilih. Centang minimal satu item.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    /**
     * normalize order_type untuk filter (null artinya "semua").
     */
    protected function normalizeOrderType($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $v = strtolower(trim((string) $value));
        if (in_array($v, ['material', 'finished_good'], true)) {
            return $v;
        }
        return null;
    }
}
