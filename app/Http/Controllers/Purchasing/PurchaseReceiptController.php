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
        $q = PurchaseReceipt::query()
            ->with(['supplier', 'warehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('warehouse_id')) {
            $q->where('warehouse_id', $request->warehouse_id);
        }

        // default: posted (kalau param status tidak dikirim sama sekali)
        $status = $request->input('status');
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

        // Summary (clone query biar aman)
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
                'startIndex' => method_exists($receipts, 'firstItem') ? ($receipts->firstItem() ?? 1) : 1,
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

        $lines = PurchaseOrderLine::query()
            ->with(['item', 'purchaseOrder.supplier'])
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
        $data = $this->validateHeader($request);

        // build lines dari input tabel (create wajib centang selected)
        $lines = $this->buildLinesFromRequest($request, requireSelected: true);

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
        $purchase_receipt->load([
            'supplier',
            'warehouse',
            'lines.item',
            // order optional; jangan maksa relasi journal di controller (service kamu post by sourceType)
            'order',
        ]);

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

        $purchase_receipt->load(['supplier', 'warehouse', 'lines.item', 'order']);

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

        $data = $this->validateHeader($request);

        // edit: tidak wajib checkbox selected (anggap baris qty>0 dianggap masuk)
        $lines = $this->buildLinesFromRequest($request, requireSelected: false);

        if (empty($lines)) {
            return back()
                ->withInput()
                ->withErrors(['lines' => 'Tidak ada line dengan Qty Diterima / Reject > 0.']);
        }

        $data['lines'] = $lines;

        $receipt = $this->service->update($purchase_receipt, $data);

        return redirect()
            ->route('purchasing.purchase_receipts.show', $receipt->id)
            ->with('success', 'Goods Receipt berhasil diperbarui.');
    }

    /**
     * POST / Confirm GRN → stok masuk + journal tercatat (handled by service).
     */
    public function post(PurchaseReceipt $purchase_receipt)
    {
        try {
            $receipt = $this->service->post($purchase_receipt);

            return redirect()
                ->route('purchasing.purchase_receipts.show', $receipt->id)
                ->with('success', 'Goods Receipt berhasil diposting. Stok gudang sudah bertambah & jurnal tercatat.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * UNPOST GRN.
     */
    public function unpost(PurchaseReceipt $purchase_receipt)
    {
        try {
            $receipt = $this->service->unpost($purchase_receipt);

            return redirect()
                ->route('purchasing.purchase_receipts.show', $receipt->id)
                ->with('success', 'GRN berhasil di-unpost (stok dibalik + jurnal di-void).');
        } catch (\Throwable $e) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', $e->getMessage());
        }
    }

    // ==========================================================
    // VALIDATION + BUILD LINES
    // ==========================================================

    /**
     * Validasi header GRN (tanpa array detail).
     */
    protected function validateHeader(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],

            'discount' => ['nullable', 'string'],
            'tax_percent' => ['nullable', 'string'],
            'shipping_cost' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /**
     * Build lines dari request tabel.
     * - create: requireSelected=true (pakai checkbox selected)
     * - edit: requireSelected=false (ambil semua baris yang qty>0)
     *
     * Input yang dipakai:
     * - po_line_id[], item_id[], qty_received[], qty_reject[], unit_price[], unit[], line_notes[], selected[index]
     */
    protected function buildLinesFromRequest(Request $request, bool $requireSelected): array
    {
        $poLineIds = $request->input('po_line_id', []);
        $itemIds = $request->input('item_id', []);
        $qtyReceiveds = $request->input('qty_received', []);
        $qtyRejects = $request->input('qty_reject', []);
        $unitPrices = $request->input('unit_price', []);
        $units = $request->input('unit', []);
        $lineNotes = $request->input('line_notes', []);
        $selected = $request->input('selected', []);

        if (!is_array($itemIds) || count($itemIds) === 0) {
            throw ValidationException::withMessages(['lines' => 'Detail item tidak ditemukan.']);
        }

        // Preload PO qty untuk validasi (hindari N+1)
        $poQtyMap = [];
        if (is_array($poLineIds) && count($poLineIds) > 0) {
            $ids = collect($poLineIds)->filter()->unique()->values();
            if ($ids->count()) {
                $poQtyMap = PurchaseOrderLine::whereIn('id', $ids)
                    ->pluck('qty', 'id')
                    ->toArray();
            }
        }

        $lines = [];
        $errors = [];
        $anySelected = false;

        foreach ($itemIds as $i => $itemId) {
            if (!$itemId) {
                continue;
            }

            $isChecked = array_key_exists($i, $selected);

            if ($requireSelected) {
                if (!$isChecked) {
                    continue;
                }
                $anySelected = true;
            }

            $qtyRec = $this->num($qtyReceiveds[$i] ?? 0);
            $qtyRej = $this->num($qtyRejects[$i] ?? 0);

            if ($qtyRec <= 0 && $qtyRej <= 0) {
                continue;
            }

            $unitPrice = $this->num($unitPrices[$i] ?? 0);

            $poLineId = $poLineIds[$i] ?? null;
            if ($poLineId) {
                $poQty = (float) ($poQtyMap[$poLineId] ?? 0);

                if ($poQty > 0 && ($qtyRec + $qtyRej) > $poQty) {
                    $errors["qty_received.$i"] = "Qty diterima + reject tidak boleh melebihi Qty PO ($poQty).";
                    $errors["qty_reject.$i"] = "Qty diterima + reject tidak boleh melebihi Qty PO ($poQty).";
                }
            }

            $lines[] = [
                'purchase_order_line_id' => $poLineId,
                'item_id' => (int) $itemId,
                'qty_received' => $qtyRec,
                'qty_reject' => $qtyRej,
                'unit_price' => $unitPrice,
                'unit' => $units[$i] ?? null,
                'notes' => $lineNotes[$i] ?? null,
            ];
        }

        if ($requireSelected && !$anySelected) {
            $errors['selected'] = 'Tidak ada item yang dipilih. Centang minimal satu item.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $lines;
    }

    /**
     * Parse angka format Indonesia / umum.
     */
    protected function num($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $v = trim((string) $value);
        $v = str_replace(' ', '', $v);

        if (str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
            return (float) $v;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
            $v = str_replace('.', '', $v);
            return (float) $v;
        }

        return (float) $v;
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
