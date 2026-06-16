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
use Illuminate\Support\Facades\DB;
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
        // ✅ biar tahu ada return (tanpa N+1)
            ->withCount(['returns as return_count'])
            ->withSum('returns as return_total_sum', 'total') // ✅ kolomnya total (bukan amount)
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', (int) $request->supplier_id);
        }

        if ($request->filled('warehouse_id')) {
            $q->where('warehouse_id', (int) $request->warehouse_id);
        }

        // default: posted (kalau param status tidak dikirim sama sekali)
        $status = $request->input('status');
        if ($request->has('status')) {
            if ($status !== null && $status !== '') {
                $q->where('status', (string) $status);
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
            ->reorder()
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
                $q->where('status', 'approved')
                  ->where('order_type', '!=', 'packing'); // packing skip GRN

                if (!empty($selectedSupplierId)) {
                    $q->where('supplier_id', (int) $selectedSupplierId);
                }

                if (!empty($selectedOrderType)) {
                    $q->where('order_type', $selectedOrderType);
                }
            })
            ->orderByDesc(
                PurchaseOrder::query()
                    ->select('date')
                    ->whereColumn('purchase_orders.id', 'purchase_order_lines.purchase_order_id')
                    ->limit(1)
            )
            ->orderByDesc('purchase_order_id')
            ->orderByDesc('id')
            ->get();

        $lines->each(function (PurchaseOrderLine $line) {
            $line->has_draft_grn = ((int) ($line->draft_receipt_lines_count ?? 0)) > 0;
        });

        // ✅ Default warehouse (controller side)
        // finished_good -> WH-RTS ; material/packing -> RM
        $defaultWhCode = ($selectedOrderType === 'finished_good') ? 'WH-RTS' : 'RM';
        $defaultWarehouse = $warehouses->firstWhere('code', $defaultWhCode) ?: $warehouses->firstWhere('code', 'RM') ?: $warehouses->first();

        $selectedWarehouseId = (int) old('warehouse_id', $defaultWarehouse?->id);

        return view('purchasing.purchase_receipts.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'order' => $order,
            'lines' => $lines,
            'selectedSupplierId' => $selectedSupplierId,
            'selectedOrderType' => $selectedOrderType,
            'defaultWhCode' => $defaultWarehouse?->code,
            'defaultWarehouse' => $defaultWarehouse,
            'selectedWarehouseId' => $selectedWarehouseId,
        ]);
    }

    /**
     * Form create GRN dari satu PO tertentu.
     */
    public function createFromOrder(PurchaseOrder $purchase_order)
    {
        if ($purchase_order->order_type === 'packing') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('info', 'PO Packing tidak memerlukan GRN.');
        }

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
            $line->has_draft_grn = ((int) ($line->draft_receipt_lines_count ?? 0)) > 0;
        });

        $selectedSupplierId = $purchase_order->supplier_id;
        $selectedOrderType = $this->normalizeOrderType($purchase_order->order_type) ?: 'material';

        $defaultWhCode = ($selectedOrderType === 'finished_good') ? 'WH-RTS' : 'RM';
        $defaultWarehouse = $warehouses->firstWhere('code', $defaultWhCode) ?: $warehouses->firstWhere('code', 'RM') ?: $warehouses->first();

        $selectedWarehouseId = (int) old('warehouse_id', $defaultWarehouse?->id);

        return view('purchasing.purchase_receipts.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'order' => $purchase_order,
            'lines' => $lines,
            'selectedSupplierId' => $selectedSupplierId,
            'selectedOrderType' => $selectedOrderType,
            'defaultWhCode' => $defaultWarehouse?->code,
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

        // optional: kalau create dari single PO, pastikan supplier match PO
        $this->validateOptionalPurchaseOrderRelation($data);

        $data['lines'] = $lines;
        $data['created_by'] = (int) $request->user()->id;

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
            'order',
            'qc.checkedBy',
            'qc.purchaseReturn',
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

        $data = $this->validateHeader($request, $purchase_receipt);

        // edit: tidak wajib checkbox selected (anggap baris qty>0 dianggap masuk)
        $lines = $this->buildLinesFromRequest($request, requireSelected: false, existingReceipt: $purchase_receipt);

        if (empty($lines)) {
            return back()
                ->withInput()
                ->withErrors(['lines' => 'Tidak ada line dengan Qty Diterima / Reject > 0.']);
        }

        $this->validateOptionalPurchaseOrderRelation($data);

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
    protected function validateHeader(Request $request, ?PurchaseReceipt $existingReceipt = null): array
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],

            'discount' => ['nullable', 'string'],
            'tax_percent' => ['nullable', 'string'],
            'shipping_cost' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'surat_jalan_no' => ['nullable', 'string', 'max:100'],
        ]);

        // normalize numerics (Indonesia-friendly)
        $data['discount'] = $this->num($data['discount'] ?? 0);
        $data['tax_percent'] = $this->num($data['tax_percent'] ?? 0);
        $data['shipping_cost'] = $this->num($data['shipping_cost'] ?? 0);

        if (!$this->canSeeMoney($request)) {
            $data['discount'] = $existingReceipt ? (float) ($existingReceipt->discount ?? 0) : 0.0;
            $data['tax_percent'] = $existingReceipt ? (float) ($existingReceipt->tax_percent ?? 0) : 0.0;
            $data['shipping_cost'] = $existingReceipt ? (float) ($existingReceipt->shipping_cost ?? 0) : 0.0;
        }

        $data['supplier_id'] = (int) $data['supplier_id'];
        $data['warehouse_id'] = (int) $data['warehouse_id'];
        $data['purchase_order_id'] = !empty($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null;

        return $data;
    }

    /**
     * Build lines dari request tabel.
     * - create: requireSelected=true (pakai checkbox selected)
     * - edit: requireSelected=false (ambil semua baris yang qty>0)
     *
     * Input yang dipakai:
     * - po_line_id[], item_id[], qty_received[], qty_reject[], unit_price[], unit[], line_notes[], selected[index]
     */
    protected function buildLinesFromRequest(Request $request, bool $requireSelected, ?PurchaseReceipt $existingReceipt = null): array
    {
        $poLineIds = $request->input('po_line_id', []);
        $itemIds = $request->input('item_id', []);
        $qtyReceived = $request->input('qty_received', []);
        $qtyReject = $request->input('qty_reject', []);
        $unitPrices = $request->input('unit_price', []);
        $units = $request->input('unit', []);
        $lineNotes = $request->input('line_notes', []);
        $selected = $request->input('selected', []);

        if (!is_array($itemIds) || count($itemIds) === 0) {
            throw ValidationException::withMessages(['lines' => 'Detail item tidak ditemukan.']);
        }

        // Preload PO qty + harga untuk validasi (hindari N+1)
        $poQtyMap = [];
        $poPriceMap = [];
        $cumulativeReceivedMap = []; // total qty sudah diterima dari GRN lain (cumulative)

        if (is_array($poLineIds) && count($poLineIds) > 0) {
            $ids = collect($poLineIds)->filter()->map(fn($v) => (int) $v)->unique()->values();
            if ($ids->count()) {
                $poLines = PurchaseOrderLine::whereIn('id', $ids)
                    ->get(['id', 'qty', 'unit_price']);
                $poQtyMap   = $poLines->pluck('qty', 'id')->toArray();
                $poPriceMap = $poLines->pluck('unit_price', 'id')->toArray();

                // ✅ Cumulative: total qty_received + qty_reject dari GRN lain untuk po_line_id ini
                $cumulativeQuery = \DB::table('purchase_receipt_lines as prl')
                    ->join('purchase_receipts as pr', 'pr.id', '=', 'prl.purchase_receipt_id')
                    ->whereIn('prl.purchase_order_line_id', $ids->all())
                    ->whereIn('pr.status', ['draft', 'posted']);

                // Kalau edit: kecualikan GRN yang sedang diedit
                if ($existingReceipt) {
                    $cumulativeQuery->where('pr.id', '!=', (int) $existingReceipt->id);
                }

                $cumulativeReceivedMap = $cumulativeQuery
                    ->groupBy('prl.purchase_order_line_id')
                    ->selectRaw('prl.purchase_order_line_id, SUM(prl.qty_received + prl.qty_reject) as total_received')
                    ->pluck('total_received', 'purchase_order_line_id')
                    ->map(fn($v) => (float) $v)
                    ->toArray();
            }
        }

        $existingPriceByItem = [];
        if ($existingReceipt) {
            $existingReceipt->loadMissing('lines');
            foreach ($existingReceipt->lines as $line) {
                $itemId = (int) ($line->item_id ?? 0);
                if ($itemId <= 0) {
                    continue;
                }
                $existingPriceByItem[$itemId] ??= [];
                $existingPriceByItem[$itemId][] = (float) ($line->unit_price ?? 0);
            }
        }

        $lines = [];
        $errors = [];
        $anySelected = false;

        foreach ($itemIds as $i => $itemId) {
            $itemId = ($itemId === null || $itemId === '') ? 0 : (int) $itemId;
            if ($itemId <= 0) {
                continue;
            }

            $isChecked = is_array($selected) && array_key_exists($i, $selected);

            if ($requireSelected) {
                if (!$isChecked) {
                    continue;
                }
                $anySelected = true;
            }

            $qtyRec = $this->num($qtyReceived[$i] ?? 0);
            $qtyRej = $this->num($qtyReject[$i] ?? 0);

            if ($qtyRec <= 0 && $qtyRej <= 0) {
                continue;
            }

            $poLineId = $poLineIds[$i] ?? null;
            $poLineId = ($poLineId === null || $poLineId === '') ? null : (int) $poLineId;

            $unitPrice = $this->num($unitPrices[$i] ?? 0);
            if (!$this->canSeeMoney($request)) {
                if ($poLineId && array_key_exists($poLineId, $poPriceMap)) {
                    $unitPrice = (float) $poPriceMap[$poLineId];
                } elseif (!empty($existingPriceByItem[$itemId])) {
                    $unitPrice = (float) array_shift($existingPriceByItem[$itemId]);
                } else {
                    $unitPrice = 0.0;
                }
            }

            if ($poLineId) {
                $poQty           = (float) ($poQtyMap[$poLineId] ?? 0);
                $alreadyReceived = (float) ($cumulativeReceivedMap[$poLineId] ?? 0);
                $remaining       = max(0, round($poQty - $alreadyReceived, 4));

                if ($poQty > 0 && ($qtyRec + $qtyRej) > $remaining + 0.0001) {
                    $msg = $alreadyReceived > 0
                        ? "Qty melebihi sisa PO. PO: {$poQty}, sudah diterima: {$alreadyReceived}, sisa: {$remaining}."
                        : "Qty diterima + reject tidak boleh melebihi Qty PO ({$poQty}).";
                    $errors["qty_received.$i"] = $msg;
                    $errors["qty_reject.$i"]   = $msg;
                }
            }

            $lines[] = [
                'purchase_order_line_id' => $poLineId,
                'item_id' => $itemId,
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
     * Validasi tambahan: kalau purchase_order_id diisi, supplier harus match PO.
     * (biar "jujur": GRN dari PO A gak boleh supplier B)
     */
    protected function validateOptionalPurchaseOrderRelation(array $header): void
    {
        $poId = $header['purchase_order_id'] ?? null;
        if (!$poId) {
            return;
        }

        /** @var PurchaseOrder|null $po */
        $po = PurchaseOrder::query()
            ->select(['id', 'supplier_id', 'status'])
            ->find($poId);

        if (!$po) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'PO tidak ditemukan.',
            ]);
        }

        if (!in_array((string) $po->status, ['approved', 'closed'], true)) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'GRN hanya boleh mengacu ke PO yang statusnya approved/closed.',
            ]);
        }

        if ((int) $po->supplier_id !== (int) ($header['supplier_id'] ?? 0)) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Supplier GRN harus sama dengan supplier pada PO.',
            ]);
        }
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

        // kalau ada koma, anggap format indo: 1.234,56
        if (str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
            return (float) $v;
        }

        // format ribuan: 1.234.567
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
        return in_array($v, ['material', 'finished_good', 'packing'], true) ? $v : null;
    }

    protected function canSeeMoney(?Request $request = null): bool
    {
        $user = $request?->user() ?: auth()->user();
        return $user && method_exists($user, 'isOwner') && $user->isOwner();
    }
}
