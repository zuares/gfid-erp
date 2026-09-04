<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Purchasing\GoodsReceiptService;
use App\Services\Purchasing\PurchaseOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReceiptController extends Controller
{
    public function __construct(
        protected GoodsReceiptService $service,
        protected PurchaseOrderService $purchaseOrderService
    ) {}

    /**
     * Index GRN (Goods Receipt).
     */
    public function index(Request $request)
    {
        $q = PurchaseReceipt::query()->select('purchase_receipts.*')
            ->with(['supplier', 'warehouse', 'order'])
        // ✅ biar tahu ada return (tanpa N+1)
            ->withCount(['returns as return_count'])
            ->withSum('returns as return_total_sum', 'total') // ✅ kolomnya total (bukan amount)
            ->addSelect([
                'total_stock_qty' => \App\Models\PurchaseReceiptLine::selectRaw('SUM(COALESCE(stock_qty_received, qty_received * COALESCE(conversion_factor, 1)))')
                    ->whereColumn('purchase_receipt_id', 'purchase_receipts.id'),
                'total_stock_reject' => \App\Models\PurchaseReceiptLine::selectRaw('SUM(COALESCE(stock_qty_reject, qty_reject * COALESCE(conversion_factor, 1)))')
                    ->whereColumn('purchase_receipt_id', 'purchase_receipts.id'),
                'total_reject_rp' => \App\Models\PurchaseReceiptLine::selectRaw('SUM(qty_reject * unit_price)')
                    ->whereColumn('purchase_receipt_id', 'purchase_receipts.id')
            ])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', (int) $request->supplier_id);
        }

        if ($request->filled('q')) {
            $searchTerm = '%' . $request->q . '%';
            $q->where(function($query) use ($searchTerm) {
                $query->where('code', 'like', $searchTerm)
                      ->orWhere('surat_jalan_no', 'like', $searchTerm)
                      ->orWhere('notes', 'like', $searchTerm)
                      ->orWhereHas('order', function ($orderQuery) use ($searchTerm) {
                          $orderQuery->where('code', 'like', $searchTerm);
                      })
                      ->orWhereHas('lines.item', function ($itemQuery) use ($searchTerm) {
                          $itemQuery->where('code', 'like', $searchTerm)
                              ->orWhere('name', 'like', $searchTerm);
                      });
            });
        }

        if ($request->filled('supplier_search')) {
            $q->whereHas('supplier', fn($s) => $s->where('name', 'like', '%' . $request->supplier_search . '%'));
        }

        if ($request->filled('warehouse_id')) {
            $q->where('warehouse_id', (int) $request->warehouse_id);
        }

        $status = $request->input('status');
        if ($status !== null && $status !== '') {
            $q->where('status', (string) $status);
        }

        if ($request->filled('from_date')) {
            $q->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $q->whereDate('date', '<=', $request->to_date);
        }

        // Clone query sebelum pagination agar limit/offset tidak terbawa ke aggregate query
        $summaryQuery = clone $q;
        
        $receipts = $q->paginate(15)->withQueryString();

        // Summary (clone query biar aman)
        $summary = $summaryQuery
            ->reorder()
            ->selectRaw('COUNT(*) as total_receipts')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
            ->selectRaw("SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count")
            ->selectRaw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count")
            ->selectRaw('MAX(date) as last_date')
            ->selectRaw('SUM(grand_total) as grand_total_sum')
            ->first();

        // Calculate total qty across all filtered receipts
        if ($summary) {
            $summary->total_qty_sum = \App\Models\PurchaseReceiptLine::whereIn(
                'purchase_receipt_id',
                (clone $summaryQuery)->select('purchase_receipts.id')
            )->selectRaw('SUM(COALESCE(stock_qty_received, qty_received * COALESCE(conversion_factor, 1))) as sum')->value('sum');
            
            $summary->total_reject_sum = \App\Models\PurchaseReceiptLine::whereIn(
                'purchase_receipt_id',
                (clone $summaryQuery)->select('purchase_receipts.id')
            )->selectRaw('SUM(COALESCE(stock_qty_reject, qty_reject * COALESCE(conversion_factor, 1))) as sum')->value('sum');
            
            $summary->total_reject_rp_sum = \App\Models\PurchaseReceiptLine::whereIn(
                'purchase_receipt_id',
                (clone $summaryQuery)->select('purchase_receipts.id')
            )->selectRaw('SUM(qty_reject * unit_price) as sum')->value('sum');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('purchasing.purchase_receipts.index', compact(
            'receipts',
            'suppliers',
            'warehouses',
            'summary',
            'status'
        ));
    }

    /**
     * Export detail GRN dalam PDF sesuai filter yang sedang dipakai di daftar GRN.
     */
    public function export(Request $request)
    {
        $query = PurchaseReceiptLine::query()
            ->with(['receipt.supplier', 'receipt.warehouse', 'receipt.order', 'item', 'purchaseOrderLine'])
            ->whereHas('receipt', function ($receiptQuery) use ($request) {
                if ($request->filled('supplier_id')) {
                    $receiptQuery->where('supplier_id', (int) $request->supplier_id);
                }

                if ($request->filled('warehouse_id')) {
                    $receiptQuery->where('warehouse_id', (int) $request->warehouse_id);
                }

                if ($request->filled('status')) {
                    $receiptQuery->where('status', (string) $request->status);
                }

                if ($request->filled('from_date')) {
                    $receiptQuery->whereDate('date', '>=', $request->from_date);
                }

                if ($request->filled('to_date')) {
                    $receiptQuery->whereDate('date', '<=', $request->to_date);
                }

                if ($request->filled('q')) {
                    $searchTerm = '%' . $request->q . '%';
                    $receiptQuery->where(function ($searchQuery) use ($searchTerm) {
                        $searchQuery->where('code', 'like', $searchTerm)
                            ->orWhere('surat_jalan_no', 'like', $searchTerm)
                            ->orWhere('notes', 'like', $searchTerm)
                            ->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('code', 'like', $searchTerm))
                            ->orWhereHas('lines.item', function ($itemQuery) use ($searchTerm) {
                                $itemQuery->where('code', 'like', $searchTerm)
                                    ->orWhere('name', 'like', $searchTerm);
                            });
                    });
                }
            })
            ->orderByDesc(
                PurchaseReceipt::query()
                    ->select('date')
                    ->whereColumn('purchase_receipts.id', 'purchase_receipt_lines.purchase_receipt_id')
                    ->limit(1)
            )
            ->orderByDesc('purchase_receipt_id')
            ->orderBy('id');

        $filename = 'grn-penerimaan-' . now()->format('Ymd-His') . '.pdf';

        $rows = $query->limit(2000)->get();
        $supplier = $request->filled('supplier_id')
            ? Supplier::find((int) $request->supplier_id)
            : null;
        $warehouse = $request->filled('warehouse_id')
            ? Warehouse::find((int) $request->warehouse_id)
            : null;

        $pdf = Pdf::loadView('purchasing.purchase_receipts.export', [
            'rows' => $rows,
            'supplier' => $supplier,
            'warehouse' => $warehouse,
            'filters' => [
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'q' => $request->input('q'),
                'warehouse_id' => $request->input('warehouse_id'),
                'status' => $request->input('status'),
            ],
        ])->setPaper('a4', 'landscape');

        $pdfContent = $pdf->output();
        if ($request->boolean('preview')) {
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        return response()->streamDownload(
            fn () => print($pdfContent),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Form create GRN dari semua PO yang masih dapat diterima (filter supplier opsional).
     */
    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::query()->where('active', 1)->orderBy('name')->get();

        $order = null;

        $selectedSupplierId = $request->input('supplier_id');
        // PO tidak lagi dibatasi jenis. Semua PO yang masih dapat diterima
        // ditampilkan agar satu supplier bisa diproses lintas kategori item.

        $lines = PurchaseOrderLine::query()
            ->with(['item', 'expenseAccount', 'purchaseOrder.supplier'])
            ->withCount('draftReceiptLines')
            ->withSum(['receiptLines as qty_received_posted' => function ($q) {
                $q->whereHas('receipt', function ($r) {
                    $r->where('status', 'posted')
                        ->where(function ($r) {
                            $r->whereNull('is_replacement')
                                ->orWhere('is_replacement', false);
                        });
                });
            }], 'qty_received')
            ->withSum(['receiptLines as qty_rejected_posted' => function ($q) {
                $q->whereHas('receipt', function ($r) {
                    $r->where('status', 'posted')
                        ->where(function ($r) {
                            $r->whereNull('is_replacement')
                                ->orWhere('is_replacement', false);
                        });
                });
            }], 'qty_reject')
            ->whereHas('purchaseOrder', function ($q) use ($selectedSupplierId) {
                // GRN hanya boleh dari PO yang sudah approved.
                $q->where('status', 'approved');

                if (!empty($selectedSupplierId)) {
                    $q->where('supplier_id', (int) $selectedSupplierId);
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
            $line->has_draft_grn    = ((int) ($line->draft_receipt_lines_count ?? 0)) > 0;
            $qtyReceived            = (float) ($line->qty_received_posted ?? 0);
            $qtyRejected            = (float) ($line->qty_rejected_posted ?? 0);
            $qtyAccounted           = $qtyReceived + $qtyRejected;
            $line->qty_rejected_posted = $qtyRejected;
            $line->qty_remaining    = max(0.0, (float) $line->qty - $qtyAccounted);
            $line->fully_received   = $qtyAccounted >= (float) $line->qty;
            $line->partially_received = !$line->fully_received && $qtyAccounted > 0;
        });

        // Default hanya sebagai usulan; user memilih warehouse di form GRN.
        $defaultWhCode = 'RM';
        $defaultWarehouse = $warehouses->firstWhere('code', $defaultWhCode) ?: $warehouses->firstWhere('code', 'RM') ?: $warehouses->first();

        $selectedWarehouseId = (int) old('warehouse_id', $defaultWarehouse?->id);

        return view('purchasing.purchase_receipts.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'order' => $order,
            'lines' => $lines,
            'selectedSupplierId' => $selectedSupplierId,
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
        // GRN hanya boleh dari PO approved. Draft harus di-approve terlebih dahulu.
        if (!$purchase_order->isReceivableForGrn() || $purchase_order->status === 'cancelled') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'GRN hanya bisa dibuat dari PO berstatus Approved. Approve PO terlebih dahulu.');
        }

        $purchase_order->load([
            'supplier',
            'lines' => function ($q) {
                $q->with(['item', 'expenseAccount'])
                  ->withCount('draftReceiptLines')
                  ->withSum(['receiptLines as qty_received_posted' => function ($q) {
                      $q->whereHas('receipt', function ($r) {
                          $r->where('status', 'posted')
                              ->where(function ($r) {
                                  $r->whereNull('is_replacement')
                                      ->orWhere('is_replacement', false);
                              });
                          });
                  }], 'qty_received')
                  ->withSum(['receiptLines as qty_rejected_posted' => function ($q) {
                      $q->whereHas('receipt', function ($r) {
                          $r->where('status', 'posted')
                              ->where(function ($r) {
                                  $r->whereNull('is_replacement')
                                      ->orWhere('is_replacement', false);
                              });
                      });
                  }], 'qty_reject');
            },
        ]);

        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::query()->where('active', 1)->orderBy('name')->get();

        $lines = $purchase_order->lines;
        $lines->each(function (PurchaseOrderLine $line) {
            $line->has_draft_grn      = ((int) ($line->draft_receipt_lines_count ?? 0)) > 0;
            $qtyReceived              = (float) ($line->qty_received_posted ?? 0);
            $qtyRejected              = (float) ($line->qty_rejected_posted ?? 0);
            $qtyAccounted             = $qtyReceived + $qtyRejected;
            $line->qty_rejected_posted = $qtyRejected;
            $line->qty_remaining      = max(0.0, (float) $line->qty - $qtyAccounted);
            $line->fully_received     = $qtyAccounted >= (float) $line->qty;
            $line->partially_received = !$line->fully_received && $qtyAccounted > 0;
        });

        $selectedSupplierId = $purchase_order->supplier_id;
        // Legacy order_type hanya dipakai untuk memberi usulan awal gudang
        // pada PO lama; user tetap dapat menggantinya di form GRN.
        $legacyOrderType = $this->normalizeOrderType($purchase_order->order_type) ?: 'material';

        $defaultWhCode = ($legacyOrderType === 'finished_good') ? 'WH-RTS' : 'RM';
        $defaultWarehouse = $warehouses->firstWhere('code', $defaultWhCode) ?: $warehouses->firstWhere('code', 'RM') ?: $warehouses->first();

        $selectedWarehouseId = (int) old('warehouse_id', $defaultWarehouse?->id);

        return view('purchasing.purchase_receipts.create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'order' => $purchase_order,
            'lines' => $lines,
            'selectedSupplierId' => $selectedSupplierId,
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

        // ==========================================
        // IDEMPOTENCY CHECK
        // ==========================================
        if (!$request->input('ignore_duplicate')) {
            $inputLines = collect($data['lines'])->map(fn($l) => [
                'item_id' => (int) ($l['item_id'] ?? 0),
                'qty_received' => (float) ($l['qty_received'] ?? 0),
                'qty_reject' => (float) ($l['qty_reject'] ?? 0),
            ])->sortBy('item_id')->values()->toArray();

            $idempotencyHash = md5(json_encode([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'] ?? 0,
                'purchase_order_id' => $data['purchase_order_id'] ?? 0,
                'lines' => $inputLines,
            ]));

            $lockKey = 'grn_store_' . $request->user()->id . '_' . $idempotencyHash;
            $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);

            if (!$lock->get()) {
                return back()->with('error', 'Sistem sedang memproses data GRN yang sama. Harap tunggu sebentar lalu coba lagi (double-click terdeteksi).');
            }

            $recentGRNs = \App\Models\PurchaseReceipt::with('lines')
                ->where('created_by', $request->user()->id)
                ->where('supplier_id', $data['supplier_id'])
                ->where('created_at', '>=', now()->subMinutes(15))
                ->get();
            
            $inputLines = collect($data['lines'])->map(fn($l) => [
                'item_id' => (int) ($l['item_id'] ?? 0),
                'qty_received' => (float) ($l['qty_received'] ?? 0),
                'qty_reject' => (float) ($l['qty_reject'] ?? 0),
            ])->sortBy('item_id')->values()->toArray();

            $isDuplicate = $recentGRNs->contains(function ($grn) use ($inputLines, $data) {
                if ((int)$grn->warehouse_id !== (int)($data['warehouse_id'] ?? 0)) return false;
                if ((int)$grn->purchase_order_id !== (int)($data['purchase_order_id'] ?? 0)) return false;

                $grnLines = $grn->lines->map(fn($l) => [
                    'item_id' => (int) $l->item_id,
                    'qty_received' => (float) $l->qty_received,
                    'qty_reject' => (float) $l->qty_reject,
                ])->sortBy('item_id')->values()->toArray();

                return $grnLines == $inputLines;
            });

            if ($isDuplicate) {
                return back()
                    ->withInput()
                    ->with('duplicate_warning', 'Anda baru saja membuat Goods Receipt yang sama persis (supplier, gudang, item, qty terima/reject) dalam 15 menit terakhir. Lanjutkan simpan data ganda?');
            }
        }

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
            'returnOrigin',
        ]);

        return view('purchasing.purchase_receipts.show', [
            'receipt' => $purchase_receipt,
        ]);
    }

    /**
     * Halaman cetak barcode GRN.
     * Default jumlah label per item = qty masuk dalam satuan stok, tapi bisa disesuaikan sebelum cetak.
     */
    public function barcode(PurchaseReceipt $purchase_receipt)
    {
        $purchase_receipt->load(['supplier', 'lines.item']);

        $lines = $purchase_receipt->lines
            ->filter(fn($l) => $l->item && $l->item->code)
            ->map(fn($l) => [
                'id'   => $l->item->id,
                'code' => $l->item->code,
                'name' => $l->item->name,
                'qty'  => max(1, (int) round($l->stockQtyReceived())),
            ])
            ->values();

        return view('purchasing.purchase_receipts.barcode', [
            'receipt' => $purchase_receipt,
            'lines'   => $lines,
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

        if ($purchase_receipt->is_replacement) {
            return redirect()
                ->route('purchasing.purchase_receipts.show', $purchase_receipt->id)
                ->with('error', 'Replacement GRN tidak dapat diedit secara manual. Jika salah, batalkan/hapus GRN ini dan ulangi proses terima pengganti dari dokumen Retur.');
        }

        $purchase_receipt->load(['supplier', 'warehouse', 'lines.item', 'lines.expenseAccount', 'lines.purchaseOrderLine', 'order']);

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
     * - po_line_id[], item_id[], stock_qty_received[], stock_qty_reject[],
     *   qty_received[], qty_reject[], unit_price[], unit[], line_notes[], selected[index]
     */
    protected function buildLinesFromRequest(Request $request, bool $requireSelected, ?PurchaseReceipt $existingReceipt = null): array
    {
        $poLineIds = $request->input('po_line_id', []);
        $itemIds = $request->input('item_id', []);
        $qtyReceived = $request->input('qty_received', []);
        $qtyReject = $request->input('qty_reject', []);
        $stockQtyReceived = $request->input('stock_qty_received', []);
        $stockQtyReject = $request->input('stock_qty_reject', []);
        $unitPrices = $request->input('unit_price', []);
        $units = $request->input('unit', []);
        $lineNotes = $request->input('line_notes', []);
        $allocations = $request->input('allocation', []);
        $expenseAccountIds = $request->input('expense_account_id', []);
        $selected = $request->input('selected', []);

        if (!is_array($itemIds) || count($itemIds) === 0) {
            throw ValidationException::withMessages(['lines' => 'Detail item tidak ditemukan.']);
        }

        // Preload PO qty + harga untuk validasi (hindari N+1)
        $poQtyMap = [];
        $poPriceMap = [];
        $poFactorMap = [];
        $cumulativeReceivedMap = []; // total qty sudah diterima dari GRN lain (cumulative)

        if (is_array($poLineIds) && count($poLineIds) > 0) {
            $ids = collect($poLineIds)->filter()->map(fn($v) => (int) $v)->unique()->values();
            if ($ids->count()) {
                $poLines = PurchaseOrderLine::whereIn('id', $ids)
                    ->with('item:id,purchase_conversion_factor')
                    ->get(['id', 'qty', 'item_id', 'unit_price', 'conversion_factor']);
                $poQtyMap   = $poLines->pluck('qty', 'id')->toArray();
                $poPriceMap = $poLines->pluck('unit_price', 'id')->toArray();
                $poFactorMap = $poLines->mapWithKeys(fn ($line) => [
                    (int) $line->id => max(0.000001, (float) ($line->conversion_factor ?: $line->item?->purchase_conversion_factor ?: 1)),
                ])->toArray();

                // ✅ Cumulative: total qty_received + qty_reject dari GRN lain untuk po_line_id ini
                $cumulativeQuery = \DB::table('purchase_receipt_lines as prl')
                    ->join('purchase_receipts as pr', 'pr.id', '=', 'prl.purchase_receipt_id')
                    ->whereIn('prl.purchase_order_line_id', $ids->all())
                    ->whereIn('pr.status', ['draft', 'posted'])
                    ->where(function ($q) {
                        $q->whereNull('pr.is_replacement')
                            ->orWhere('pr.is_replacement', false);
                    });

                // Kalau edit: kecualikan GRN yang sedang diedit
                if ($existingReceipt) {
                    $cumulativeQuery->where('pr.id', '!=', (int) $existingReceipt->id);
                }

                $cumulativeReceivedMap = $cumulativeQuery
                    ->groupBy('prl.purchase_order_line_id')
                    ->selectRaw('prl.purchase_order_line_id, SUM(COALESCE(prl.stock_qty_received, prl.qty_received * COALESCE(prl.conversion_factor, 1)) + COALESCE(prl.stock_qty_reject, prl.qty_reject * COALESCE(prl.conversion_factor, 1))) as total_received_stock')
                    ->pluck('total_received_stock', 'purchase_order_line_id')
                    ->map(fn($v) => (float) $v)
                    ->toArray();
            }
        }

        $itemFactorMap = Item::query()
            ->whereIn('id', collect($itemIds)->filter()->map(fn ($v) => (int) $v)->unique()->values())
            ->get(['id', 'purchase_conversion_factor'])
            ->mapWithKeys(fn ($item) => [
                (int) $item->id => max(0.000001, (float) ($item->purchase_conversion_factor ?: 1)),
            ])->toArray();

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

            $poLineId = $poLineIds[$i] ?? null;
            $poLineId = ($poLineId === null || $poLineId === '') ? null : (int) $poLineId;
            $conversionFactor = $poLineId
                ? ($poFactorMap[$poLineId] ?? 1)
                : ($itemFactorMap[$itemId] ?? 1);

            $stockRecInput = array_key_exists($i, $stockQtyReceived)
                ? max(0, $this->num($stockQtyReceived[$i]))
                : null;
            $stockRejInput = array_key_exists($i, $stockQtyReject)
                ? max(0, $this->num($stockQtyReject[$i]))
                : null;

            if ($stockRecInput !== null || $stockRejInput !== null) {
                $stockRecInput = $stockRecInput ?? 0;
                $stockRejInput = $stockRejInput ?? 0;
                $qtyRec = round($stockRecInput / $conversionFactor, 6);
                $qtyRej = round($stockRejInput / $conversionFactor, 6);
            } else {
                $qtyRec = $this->num($qtyReceived[$i] ?? 0);
                $qtyRej = $this->num($qtyReject[$i] ?? 0);
                $stockRecInput = round($qtyRec * $conversionFactor, 6);
                $stockRejInput = round($qtyRej * $conversionFactor, 6);
            }

            if ($qtyRec <= 0 && $qtyRej <= 0) {
                continue;
            }

            // ✅ HARGA SERVER-SIDE (defense-in-depth level Controller):
            // - Jika baris terkait PO line → harga SELALU dari PO (abaikan request, semua role).
            // - Jika tidak ada PO line → hanya user berhak harga yang boleh input; selain itu 0.
            //   (Service tetap menjadi otoritas final atas harga.)
            if ($poLineId && array_key_exists($poLineId, $poPriceMap)) {
                $unitPrice = (float) $poPriceMap[$poLineId];
            } elseif ($this->canSeeMoney($request)) {
                $unitPrice = $this->num($unitPrices[$i] ?? 0);
            } elseif (!empty($existingPriceByItem[$itemId])) {
                $unitPrice = (float) array_shift($existingPriceByItem[$itemId]);
            } else {
                $unitPrice = 0.0;
            }

            if ($poLineId) {
                $poQty           = (float) ($poQtyMap[$poLineId] ?? 0);
                $poStockQty      = $poQty * $conversionFactor;
                $alreadyReceived = (float) ($cumulativeReceivedMap[$poLineId] ?? 0);
                $remaining       = max(0, round($poStockQty - $alreadyReceived, 6));

                if ($poQty > 0 && ($stockRecInput + $stockRejInput) > $remaining + 0.0001) {
                    $msg = $alreadyReceived > 0
                        ? "Qty melebihi sisa PO. Sisa stok: {$remaining}."
                        : "Qty diterima + reject tidak boleh melebihi Qty PO ({$poStockQty} stok).";
                    $errors["qty_received.$i"] = $msg;
                    $errors["qty_reject.$i"]   = $msg;
                    $errors["stock_qty_received.$i"] = $msg;
                    $errors["stock_qty_reject.$i"]   = $msg;
                }
            }

            $lines[] = [
                'purchase_order_line_id' => $poLineId,
                'item_id' => $itemId,
                'qty_received' => $qtyRec,
                'qty_reject' => $qtyRej,
                'stock_qty_received' => $stockRecInput,
                'stock_qty_reject' => $stockRejInput,
                'conversion_factor' => $conversionFactor,
                'unit_price' => $unitPrice,
                'unit' => $units[$i] ?? null,
                'notes' => $lineNotes[$i] ?? null,
                'allocation' => $allocations[$i] ?? null,
                'expense_account_id' => $expenseAccountIds[$i] ?? null,
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
            ->select(['id', 'supplier_id', 'status', 'closed_at'])
            ->find($poId);

        if (!$po) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'PO tidak ditemukan.',
            ]);
        }

        // GRN hanya boleh dari PO approved; draft harus di-approve terlebih dahulu.
        if ($po->status === 'cancelled' || !$po->isReceivableForGrn()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'GRN hanya boleh mengacu ke PO berstatus Approved. Approve PO terlebih dahulu.',
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
        return $user && method_exists($user, 'canSeePurchasePrices') && $user->canSeePurchasePrices();
    }

    /**
     * Batal/Hapus Draft Replacement GRN.
     */
    public function destroy(Request $request, PurchaseReceipt $purchase_receipt)
    {

        if ($purchase_receipt->status !== 'draft') {
            return back()->with('error', 'Hanya dokumen berstatus Draft yang dapat dihapus.');
        }



        $purchaseOrderId = (int) ($purchase_receipt->purchase_order_id ?? 0);

        \Illuminate\Support\Facades\DB::transaction(function () use ($purchase_receipt, $purchaseOrderId) {
            $purchase_receipt->lines()->delete();
            $purchase_receipt->delete();

            if ($purchaseOrderId > 0) {
                $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
                if ($purchaseOrder) {
                    $this->purchaseOrderService->maybeUnlock($purchaseOrder);
                }
            }
        });

        if ($purchase_receipt->is_replacement && $purchase_receipt->purchase_return_id) {
            return redirect()->route('purchasing.purchase_returns.show', $purchase_receipt->purchase_return_id)
                ->with('success', 'Draft Penerimaan Barang Pengganti telah dibatalkan.');
        }

        return redirect()->route('purchasing.purchase_receipts.index')
            ->with('success', 'Draft Penerimaan Barang telah dihapus.');
    }
}
