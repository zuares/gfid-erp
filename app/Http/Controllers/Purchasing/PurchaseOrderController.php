<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceiptLine;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\WhatsAppMessage;
use App\Services\Accounting\JournalService;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\WhatsApp\WhatsAppMessageService;
use App\Services\WhatsApp\PurchaseOrderWhatsAppMessageBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $service,
        protected JournalService $journalService
    ) {}

    /**
     * List PO.
     */
    public function index(Request $request)
    {
        $sortCol = in_array($request->sort, ['date', 'code', 'grand_total', 'status', 'supplier_id'], true)
            ? $request->sort : 'date';
        $sortDir = $request->dir === 'asc' ? 'asc' : 'desc';

        $q = PurchaseOrder::query()
            ->with([
                'supplier',
                'approvedBy',
                'paymentMethod',
                'purchaseReceipts',
                'lines.item:id,code,name,unit,stock_unit,purchase_unit,purchase_conversion_factor',
            ]);

        if ($sortCol === 'supplier_id') {
            $q->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
              ->orderBy('suppliers.name', $sortDir)
              ->select('purchase_orders.*');
        } else {
            $q->orderBy($sortCol, $sortDir);
            if ($sortCol !== 'id') $q->orderByDesc('purchase_orders.id');
        }

        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', (int) $request->supplier_id);
        }

        if ($request->filled('supplier_search')) {
            $term = (string) $request->supplier_search;
            $q->whereHas('supplier', function ($s) use ($term) {
                $s->where('name', 'like', '%' . $term . '%')
                    ->orWhere('code', 'like', '%' . $term . '%');
            });
        }

        if ($request->filled('status')) {
            $q->where('status', (string) $request->status);
        }

        if ($request->filled('pay_status')) {
            $q->where('payment_status', (string) $request->pay_status);
        }

        if ($request->filled('order_type')) {
            $q->where('order_type', $this->normalizeOrderType($request->order_type));
        }

        if ($request->filled('from_date')) {
            $q->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $q->whereDate('date', '<=', $request->to_date);
        }

        if ($request->filled('q')) {
            $term = (string) $request->q;
            $q->where(function ($sub) use ($term) {
                $sub->where('code', 'like', '%' . $term . '%');
            });
        }

        $summaryQuery = clone $q;
        
        $grandTotalQuery = clone $summaryQuery;
        if ($request->status !== 'cancelled') {
            $grandTotalQuery->where('status', '!=', 'cancelled');
        }

        $summary = (object) [
            'total_orders' => (clone $summaryQuery)->count(),
            'total_grand_total' => $grandTotalQuery->sum('grand_total'),
            'draft_count' => (clone $summaryQuery)->where('status', 'draft')->count(),
            'approved_count' => (clone $summaryQuery)->where('status', 'approved')->count(),
            'cancelled_count' => (clone $summaryQuery)->where('status', 'cancelled')->count(),
            'closed_count' => (clone $summaryQuery)->where('status', 'closed')->count(),
            'last_date' => optional((clone $summaryQuery)->orderByDesc('date')->first())->date,
        ];

        $orders = $q->paginate(15)->withQueryString();



        $suppliers = Supplier::orderBy('name')->get();

        return view('purchasing.purchase_orders.index', compact('orders', 'suppliers', 'summary', 'sortCol', 'sortDir'));
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

        $orderType = $this->normalizeOrderType($request->input('order_type', 'material'));
        $order->order_type = $orderType;

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', 1)
            ->orderByRaw("CASE WHEN code='CASH' THEN 0 ELSE 1 END")
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::query()
            ->where('type', 'supplier')
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'po_types']);

        $itemQuery = Item::query()
            ->select([
                'id', 'code', 'name', 'unit', 'stock_unit', 'purchase_unit', 'purchase_conversion_factor', 'item_category_id', 'type', 'active',
                'default_allocation', 'default_expense_account_id',
            ])
            ->where('active', 1)
            ->with(['category:id,code,name'])
            ->orderByRaw("
                CASE
                    WHEN item_category_id = (SELECT id FROM item_categories WHERE code='PACK' LIMIT 1) THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('name')
            ->limit(300);
        $items = $itemQuery->get();

        $cashAccounts = Account::query()
            ->where('type', 'asset')
            ->where('is_active', 1)
            ->where('is_cash', 1)
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::query()
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $lines  = collect();
        $fromPr = null; // PR-D: null by default

        // PR-D: Pre-fill dari Purchase Request jika from_pr dikirim
        if ($request->filled('from_pr') && \Illuminate\Support\Facades\Schema::hasTable('purchase_requests')) {
            $pr = \App\Models\PurchaseRequest::with('lines.item')->find((int) $request->from_pr);

            if ($pr && $pr->isApproved() && is_null($pr->converted_to_po_id)) {
                $fromPr = $pr;

                // Pre-fill supplier
                if ($pr->supplier_id && !$request->filled('supplier_id')) {
                    $order->supplier_id = $pr->supplier_id;
                }

                // Build lines dari PR (format compatible dengan _form.blade.php)
                $prLinesMapped = $pr->lines->map(function ($prLine) {
                    $item = $prLine->item;
                    return [
                        'item_id'             => $prLine->item_id,
                        'item'                => $item ? [
                            'id'                         => $item->id,
                            'code'                       => $item->code,
                            'name'                       => $item->name,
                            'unit'                       => $item->unit,
                            'stock_unit'                 => $item->stockUnit(),
                            'purchase_unit'              => $item->purchaseUnit(),
                            'purchase_conversion_factor'=> $item->purchaseConversionFactor(),
                            'default_allocation'         => $item->default_allocation ?? 'hpp',
                            'default_expense_account_id' => $item->default_expense_account_id ?? null,
                        ] : null,
                        'qty'                 => $prLine->qty,
                        'unit_price'          => $prLine->unit_price ?? 0,
                        'discount'            => 0,
                        'allocation'          => $item->default_allocation ?? 'hpp',
                        'expense_account_id'  => $item->default_expense_account_id ?? '',
                    ];
                });

                if ($prLinesMapped->isNotEmpty()) {
                    $lines = $prLinesMapped;
                }

                // PR notes sebagai referensi di PO notes
                if (!empty($pr->notes)) {
                    $order->notes = '[PR: ' . $pr->code . '] ' . $pr->notes;
                } else {
                    $order->notes = '[PR: ' . $pr->code . ']';
                }
            }
        }

        return view('purchasing.purchase_orders.create', [
            'order'          => $order,
            'suppliers'      => $suppliers,
            'paymentMethods' => $paymentMethods,
            'items'          => $items,
            'lines'          => $lines,
            'cashAccounts'   => $cashAccounts,
            'expenseAccounts'=> $expenseAccounts,
            'orderType'      => $orderType,
            'fromPr'         => $fromPr, // PR-D: null atau PurchaseRequest model
        ]);
    }

    /**
     * Simpan PO baru.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // ==========================================
        // IDEMPOTENCY CHECK
        // ==========================================
        if (!$request->input('ignore_duplicate')) {
            $inputLines = collect($data['lines'] ?? [])->map(fn($l) => [
                'item_id' => (int) ($l['item_id'] ?? 0),
                'qty' => (float) ($l['qty'] ?? 0),
                'unit_price' => (float) ($l['unit_price'] ?? 0),
            ])->sortBy('item_id')->values()->toArray();

            $idempotencyHash = md5(json_encode([
                'supplier_id' => $data['supplier_id'],
                'discount' => (float)($data['discount'] ?? 0),
                'tax_percent' => (float)($data['tax_percent'] ?? 0),
                'shipping_cost' => (float)($data['shipping_cost'] ?? 0),
                'lines' => $inputLines,
            ]));

            $lockKey = 'po_store_' . $request->user()->id . '_' . $idempotencyHash;
            $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);

            if (!$lock->get()) {
                return back()->with('error', 'Sistem sedang memproses data PO yang sama. Harap tunggu sebentar lalu coba lagi (double-click terdeteksi).');
            }

            $recentPOs = \App\Models\PurchaseOrder::with('lines')
                ->where('created_by', $request->user()->id)
                ->where('supplier_id', $data['supplier_id'])
                ->where('created_at', '>=', now()->subMinutes(15))
                ->get();
            
            $inputLines = collect($data['lines'] ?? [])->map(fn($l) => [
                'item_id' => (int) ($l['item_id'] ?? 0),
                'qty' => (float) ($l['qty'] ?? 0),
                'unit_price' => (float) ($l['unit_price'] ?? 0),
            ])->sortBy('item_id')->values()->toArray();

            $isDuplicate = $recentPOs->contains(function ($po) use ($inputLines, $data) {
                if ((float)$po->discount !== (float)($data['discount'] ?? 0)) return false;
                if ((float)$po->tax_percent !== (float)($data['tax_percent'] ?? 0)) return false;
                if ((float)$po->shipping_cost !== (float)($data['shipping_cost'] ?? 0)) return false;

                $poLines = $po->lines->map(fn($l) => [
                    'item_id' => (int) $l->item_id,
                    'qty' => (float) $l->qty,
                    'unit_price' => (float) $l->unit_price,
                ])->sortBy('item_id')->values()->toArray();

                return $poLines == $inputLines;
            });

            if ($isDuplicate) {
                return back()
                    ->withInput()
                    ->with('duplicate_warning', 'Anda baru saja membuat Purchase Order yang sama persis (supplier, item, qty, harga) dalam 15 menit terakhir. Lanjutkan simpan data ganda?');
            }
        }

        $data['created_by'] = (int) $request->user()->id;
        $data['status'] = 'draft';

        // Status pembayaran harus mengikuti nominal pembayaran yang benar-benar
        // tercatat, bukan hanya mode metode pembayaran yang dipilih.
        $data['payment_status'] = 'unpaid';

        $order = $this->service->create($data);

        // auto-create payment from form (pay_now)
        $this->maybeCreatePayNowPayment($request, $order, allowIfHasExistingPayments: true);
        $this->recalcPaymentStatus($order);

        // PR-D: Jika PO dibuat dari Purchase Request, simpan relasi + update status PR
        $successMsg = 'Purchase Order berhasil dibuat.';
        $prId = (int) $request->input('purchase_request_id', 0);
        if ($prId > 0 && \Illuminate\Support\Facades\Schema::hasTable('purchase_requests')) {
            $pr = \App\Models\PurchaseRequest::find($prId);
            if ($pr && $pr->isApproved() && is_null($pr->converted_to_po_id)) {
                // Simpan reference di PO
                $order->purchase_request_id = $prId;
                $order->save();

                // Update status PR → converted
                $pr->update([
                    'status'           => 'converted',
                    'converted_to_po_id' => $order->id,
                    'converted_at'     => now(),
                ]);

                $successMsg = "PO {$order->code} berhasil dibuat dari PR {$pr->code}.";
            }
        }

        return redirect()
            ->route('purchasing.purchase_orders.show', $order->id)
            ->with('success', $successMsg);
    }

    /**
     * Detail PO.
     */
    public function show(PurchaseOrder $purchase_order)
    {
        $purchase_order->load([
            'supplier',
            'paymentMethod',
            'lines.item',
            'lines.expenseAccount',
            'createdBy',
            'approvedBy',
            'cancelledBy',
            'purchaseReceipts.warehouse',
            'purchaseReceipts',
            'purchaseRequest', // PR-D: relasi ke PR asal (nullable)
            'payments' => function ($q) {
                $q->with(['paymentMethod', 'cashAccount'])
                    ->orderByDesc('date')
                    ->orderByDesc('id');
            },
        ]);

        // Repair lock stale saat detail dibuka. Lock hanya dilepas jika tidak
        // ada GRN, payment, atau retur yang tersisa sebagai jejak transaksi.
        if ($purchase_order->isLocked()) {
            $this->service->maybeUnlock($purchase_order);
        }

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', 1)
            ->orderByRaw("
                CASE
                    WHEN mode = 'cash' THEN 0
                    WHEN code = 'CASH' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $cashAccounts = Account::query()
            ->where('is_active', 1)
            ->where('is_cash', 1)
            ->whereIn('code', ['1101', '1111', '1112', '1113', '1114'])
            ->orderByRaw("
                CASE code
                    WHEN '1101' THEN 0
                    WHEN '1111' THEN 1
                    WHEN '1112' THEN 2
                    WHEN '1113' THEN 3
                    WHEN '1114' THEN 4
                    ELSE 99
                END
            ")
            ->get();

        // =========================================================
        // METRICS hutang berbasis GRN posted (tetap dulu, kamu bilang nanti)
        // =========================================================
        $grnPostedTotal = (float) $purchase_order->purchaseReceipts
            ->where('status', 'posted')
            ->sum('grand_total');

        $returnPostedTotal = (float) PurchaseReturn::query()
            ->where('purchase_order_id', $purchase_order->id)
            ->where('status', 'posted')
            ->whereNull('voided_at')
            ->sum('total');

        $paidPaymentTotal = (float) $purchase_order->payments
            ->whereNull('voided_at')
            ->where('type', 'payment')
            ->sum('amount');

        $dpTotal = (float) $purchase_order->payments
            ->whereNull('voided_at')
            ->where('type', 'dp')
            ->sum('amount');

        $dpAppliedTotal = (float) $purchase_order->payments
            ->whereNull('voided_at')
            ->where('type', 'dp_apply')
            ->sum('amount');

        $dpAvailable = max(0, round($dpTotal - $dpAppliedTotal, 2));

        $settled = $paidPaymentTotal + $dpAppliedTotal;
        $apDebt = max(0, round($grnPostedTotal - $returnPostedTotal, 2));
        $apOutstanding = max(0, round($apDebt - $settled, 2));

        // Cek apakah semua PO line sudah fully received (dari GRN posted)
        // → dipakai untuk disable tombol "+ GRN baru"
        $poLines = $purchase_order->lines;
        $canCreateGrn = true; // default: boleh
        $receivedByLine = collect();
        $returnedByLine = collect();
        if ($poLines->isNotEmpty()) {
            $poLineIds = $poLines->pluck('id');
            $receivedByLine = PurchaseReceiptLine::query()
                ->whereIn('purchase_order_line_id', $poLineIds)
                ->whereHas('receipt', fn($q) => $q->where('status', 'posted'))
                ->selectRaw('purchase_order_line_id, SUM(qty_received) as total_received')
                ->groupBy('purchase_order_line_id')
                ->pluck('total_received', 'purchase_order_line_id');

            $returnedByLine = \App\Models\PurchaseReturnLine::query()
                ->join('purchase_returns as pr', 'pr.id', '=', 'purchase_return_lines.purchase_return_id')
                ->join('purchase_receipt_lines as grnl', 'grnl.id', '=', 'purchase_return_lines.purchase_receipt_line_id')
                ->whereIn('grnl.purchase_order_line_id', $poLineIds)
                ->where('pr.status', 'posted')
                ->whereNull('pr.voided_at')
                ->selectRaw('grnl.purchase_order_line_id, SUM(purchase_return_lines.qty) as total_returned')
                ->groupBy('grnl.purchase_order_line_id')
                ->pluck('total_returned', 'purchase_order_line_id');

            // Masih bisa GRN kalau ada minimal 1 line yang belum lunas terima
            $canCreateGrn = $poLines->contains(function ($line) use ($receivedByLine) {
                return (float) ($receivedByLine[$line->id] ?? 0) < (float) $line->qty;
            });
        }

        // Tahap 4: Supplier Invoices untuk dropdown payment + ringkasan
        $poInvoices = \App\Models\SupplierInvoice::query()
            ->where('purchase_order_id', $purchase_order->id)
            ->whereNotIn('status', ['void'])
            ->orderByDesc('invoice_date')
            ->get();

        // Invoice yang belum lunas (untuk dropdown di form payment)
        $unpaidInvoices = $poInvoices->whereNotIn('status', ['paid', 'void']);

        // Ringkasan invoice untuk UI
        $invoiceTotalAmount = (float) $poInvoices->whereNotIn('status', ['void'])->sum('total_amount');
        $invoiceTotalPaid   = (float) $poInvoices->whereNotIn('status', ['void'])->sum('paid_amount');
        $invoiceOutstanding = max(0, round($invoiceTotalAmount - $invoiceTotalPaid, 2));

        // Cek syarat Close PO
        $closeBlockers = $this->calcCloseBlockers($purchase_order, $poInvoices);
        $canClose = empty($closeBlockers) && !$purchase_order->isClosed();

        $lastWhatsappMessage = WhatsAppMessage::query()
            ->where('module', 'purchasing')
            ->where('reference_type', PurchaseOrder::class)
            ->where('reference_id', $purchase_order->id)
            ->where('status', 'sent')
            ->latest('sent_at')
            ->latest('id')
            ->first();

        return view('purchasing.purchase_orders.show', [
            'order' => $purchase_order,
            'paymentMethods' => $paymentMethods,
            'cashAccounts' => $cashAccounts,
            'grnPostedTotal' => $grnPostedTotal,
            'returnPostedTotal' => $returnPostedTotal,
            'apDebt' => $apDebt,
            'paidPaymentTotal' => $paidPaymentTotal,
            'dpTotal' => $dpTotal,
            'dpAppliedTotal' => $dpAppliedTotal,
            'dpAvailable' => $dpAvailable,
            'apOutstanding' => $apOutstanding,
            'canCreateGrn' => $canCreateGrn,
            'receivedByLine' => $receivedByLine,
            'returnedByLine' => $returnedByLine,
            // Tahap 4
            'poInvoices' => $poInvoices,
            'unpaidInvoices' => $unpaidInvoices,
            'invoiceTotalAmount' => $invoiceTotalAmount,
            'invoiceTotalPaid' => $invoiceTotalPaid,
            'invoiceOutstanding' => $invoiceOutstanding,
            'closeBlockers' => $closeBlockers,
            'canClose' => $canClose,
            'lastWhatsappMessage' => $lastWhatsappMessage,
        ]);
    }

    /**
     * Form edit PO.
     */
    public function edit(Request $request, PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status !== 'draft') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang sudah di-approve/cancel tidak bisa diedit.');
        }

        // ✅ RECEIVING LOCK: admin gudang (tanpa hak harga) tidak boleh mengedit PO
        // yang sudah dirujuk GRN. Owner masih boleh (proteksi granular di Service).
        if ($purchase_order->isLocked() && !($request->user()?->canSeePurchasePrices())) {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO terkunci karena sudah ada GRN. Tidak dapat diedit.');
        }

        $purchase_order->load(['lines.item', 'paymentMethod']);

        $suppliers = Supplier::query()
            ->where('type', 'supplier')
            ->where(function ($q) use ($purchase_order) {
                $q->where('active', 1)
                    // Tetap tampilkan supplier PO walau status master-nya
                    // sudah nonaktif. Builder project ini tidak mendukung
                    // helper Eloquent `orWhereKey()`.
                    ->orWhere('id', $purchase_order->supplier_id);
            })
            ->orderBy('name')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $expenseAccounts = Account::query()
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $orderType = $this->normalizeOrderType(
            (string) ($purchase_order->getAttribute('order_type') ?: $request->input('order_type', 'material'))
        );

        $itemsBaseQuery = Item::query()
            ->where('active', 1)
            ->with('category')
            ->orderByRaw("
                CASE
                    WHEN item_category_id = (SELECT id FROM item_categories WHERE code='PACK' LIMIT 1) THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('name')
            ->limit(300);
        $itemsBase = $itemsBaseQuery->get();

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

        $items = $itemsBase
            ->concat($itemsLine)
            ->unique('id')
            ->sortBy('name')
            ->values();

        $lines = $purchase_order->lines;

        return view('purchasing.purchase_orders.edit', [
            'order' => $purchase_order,
            'suppliers' => $suppliers,
            'paymentMethods' => $paymentMethods,
            'items' => $items,
            'lines' => $lines,
            'expenseAccounts' => $expenseAccounts,
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

        // ✅ RECEIVING LOCK: user tanpa hak harga tidak boleh update PO terkunci.
        // Owner boleh — Service memproteksi line yang sudah dirujuk GRN.
        if ($purchase_order->isLocked() && !($request->user()?->canSeePurchasePrices())) {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO terkunci karena sudah ada GRN. Perubahan ditolak.');
        }

        $data = $this->validateData($request, $purchase_order);
        $data['status'] = 'draft';

        // Jangan menandai lunas hanya karena metode cash/transfer dipilih.
        // Status akan dihitung ulang dari payment aktif setelah PO disimpan.
        $data['payment_status'] = $purchase_order->payment_status;

        $order = $this->service->update($purchase_order, $data);

        // hanya buat payment dari form kalau belum ada payment aktif
        $this->maybeCreatePayNowPayment($request, $order, allowIfHasExistingPayments: false);
        $this->recalcPaymentStatus($order);

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

        // ✅ RECEIVING LOCK: PO yang sudah dirujuk GRN tidak boleh dihapus
        // (menjaga integritas referensi purchase_order_line_id pada GRN).
        if ($purchase_order->isLocked()) {
            return back()->with('error', 'PO terkunci karena sudah ada GRN. Tidak dapat dihapus.');
        }

        $purchase_order->lines()->delete();
        $purchase_order->delete();

        return redirect()
            ->route('purchasing.purchase_orders.index')
            ->with('success', 'Purchase Order berhasil dihapus.');
    }

    // ======================================================================
    // APPROVE / CANCEL
    // ======================================================================

    public function approve(PurchaseOrder $purchase_order)
    {
        abort_unless(
            in_array(auth()->user()?->role, ['owner', 'admin'], true) || auth()->user()?->isDeveloper(),
            403, 'Hanya owner atau admin yang bisa approve PO.'
        );

        if ($purchase_order->status !== 'draft') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang bukan draft tidak bisa di-approve.');
        }

        $this->service->approve($purchase_order, (int) auth()->id());

        return redirect()
            ->route('purchasing.purchase_orders.show', $purchase_order->id)
            ->with('success', 'PO berhasil di-approve.');
    }

    public function unapprove(PurchaseOrder $purchase_order)
    {
        abort_unless(
            in_array(auth()->user()?->role, ['owner', 'admin'], true) || auth()->user()?->isDeveloper(),
            403, 'Hanya owner atau admin yang bisa unapprove PO.'
        );

        if ($purchase_order->status !== 'approved') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'Hanya PO yang berstatus approved yang bisa di-unapprove.');
        }

        if ($purchase_order->purchaseReceipts()->exists()) {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang sudah memiliki GRN tidak bisa di-unapprove. Hapus GRN terlebih dahulu.');
        }

        if ($purchase_order->activePayments()->exists()) {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'PO yang sudah memiliki pembayaran aktif tidak bisa dikembalikan ke Draft. Void pembayaran terlebih dahulu.');
        }

        // Bersihkan lock stale setelah seluruh GRN Draft dihapus.
        if ($purchase_order->isLocked() && !$this->service->maybeUnlock($purchase_order)) {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order->id)
                ->with('error', 'Lock PO belum bisa dilepas karena masih ada jejak GRN, pembayaran, atau retur.');
        }

        $this->service->unapprove($purchase_order);

        return redirect()
            ->route('purchasing.purchase_orders.show', $purchase_order->id)
            ->with('success', 'PO berhasil dikembalikan ke status Draft.');
    }

    /**
     * Kirim ringkasan PO ke supplier melalui WhatsApp.
     */
    public function sendWhatsapp(
        PurchaseOrder $purchase_order,
        WhatsAppMessageService $whatsapp,
        PurchaseOrderWhatsAppMessageBuilder $builder,
    )
    {
        abort_unless(
            in_array(auth()->user()?->role, ['owner', 'admin'], true) || auth()->user()?->isDeveloper(),
            403,
            'Hanya owner atau admin yang bisa mengirim PO melalui WhatsApp.'
        );

        $draft = $builder->build($purchase_order);
        $phone = $draft['phone'];

        if ($phone === '') {
            return back()->with('error', 'Nomor WhatsApp supplier belum diisi.');
        }

        if (! filled(config('services.fonnte.token'))) {
            return back()->with('error', 'FONNTE_TOKEN belum dikonfigurasi.');
        }

        $messageLog = $whatsapp->sendText(
            $phone,
            $draft['message'],
            [
                'module' => $draft['module'],
                'reference_type' => $draft['reference_type'],
                'reference_id' => $draft['reference_id'],
                'reference_label' => $draft['reference_label'],
            ],
            $draft['recipient_name'],
            $draft['template_key'],
        );

        return back()->with(
            $messageLog->isSent() ? 'success' : 'error',
            $messageLog->isSent()
                ? "Ringkasan PO {$purchase_order->code} berhasil dikirim ke supplier via WhatsApp."
                : 'Pesan WhatsApp gagal dikirim. Periksa koneksi device Fonnte dan log aplikasi.'
        );
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

        $this->service->cancel($purchase_order, (int) Auth::id());

        return redirect()
            ->route('purchasing.purchase_orders.show', $purchase_order->id)
            ->with('success', 'PO berhasil dibatalkan.');
    }
    public function printDotMatrix(PurchaseOrder $purchase_order)
    {
        // Dokumen PO memuat harga → hanya untuk role berhak harga.
        abort_unless($this->canSeeMoney(request()), 403, 'Anda tidak memiliki akses harga untuk mencetak PO.');

        return view('purchasing.purchase_orders.print_dot_matrix', [
            'order' => $purchase_order
        ]);
    }

    public function printRaw(PurchaseOrder $purchase_order)
    {
        abort_unless($this->canSeeMoney(request()), 403, 'Anda tidak memiliki akses harga untuk mencetak PO.');

        $purchase_order->load(['supplier', 'lines.item', 'paymentMethod']);

        $width = 45; // lebar approx untuk 12cm
        $lines = [];

        // Header
        $companyName = "G R E A T F I T . I D";
        $lines[] = str_pad($companyName, $width, " ", STR_PAD_BOTH);
        $lines[] = str_pad("PURCHASE ORDER", $width, " ", STR_PAD_BOTH);
        $lines[] = str_repeat("=", $width);
        
        // Info PO (kiri-kanan)
        $noPoStr = "No : " . $purchase_order->code;
        $tglStr = "Tgl: " . date('d/m/Y', strtotime($purchase_order->date));
        $lines[] = $noPoStr . str_repeat(" ", max(0, $width - strlen($noPoStr) - strlen($tglStr))) . $tglStr;
        
        $lines[] = "Kpd: " . substr(optional($purchase_order->supplier)->name ?? '-', 0, $width - 5);
        $lines[] = str_repeat("-", $width);

        // Header Tabel
        // Barang (19) | Qty (5) | Harga (8) | Tot (9)
        $lines[] = str_pad("Deskripsi Barang", 19) . " " . str_pad("Qty", 5, " ", STR_PAD_LEFT) . " " . str_pad("Harga", 8, " ", STR_PAD_LEFT) . " " . str_pad("Total", 9, " ", STR_PAD_LEFT);
        $lines[] = str_repeat("-", $width);

        $totalQty = 0;
        $canSeeMoney = $this->canSeeMoney(request());

        foreach ($purchase_order->lines as $line) {
            $itemName = substr($line->item->name ?? 'Item', 0, 19);
            $qty = rtrim(rtrim(number_format($line->qty, 2, ',', '.'), '0'), ',');
            if ($qty === '') $qty = '0';
            
            $priceStr = $canSeeMoney ? number_format($line->unit_price, 0, ',', '.') : '***';
            $subtotalStr = $canSeeMoney
                ? number_format($line->calculatedLineTotal(), 0, ',', '.')
                : '***';
            
            $strItem = str_pad($itemName, 19);
            $strQty = str_pad($qty, 5, " ", STR_PAD_LEFT);
            $strPrice = str_pad(substr($priceStr, -8), 8, " ", STR_PAD_LEFT);
            $strSub = str_pad(substr($subtotalStr, -9), 9, " ", STR_PAD_LEFT);
            
            $lines[] = "$strItem $strQty $strPrice $strSub";
            $totalQty += $line->qty;
        }
        
        $lines[] = str_repeat("-", $width);
        
        $totalQtyStr = rtrim(rtrim(number_format($totalQty, 2, ',', '.'), '0'), ',');
        if ($totalQtyStr === '') $totalQtyStr = '0';
        $grandTotalStr = $canSeeMoney ? number_format($purchase_order->grand_total, 0, ',', '.') : '***';
        
        $lines[] = str_pad("Total Item", 19) . " " . str_pad($totalQtyStr, 5, " ", STR_PAD_LEFT);
        $lines[] = str_pad("GRAND TOTAL", 34) . " " . str_pad(substr($grandTotalStr, -9), 9, " ", STR_PAD_LEFT);
        $lines[] = str_repeat("=", $width);
        $lines[] = "";
        
        // Tanda Tangan (kiri-kanan rapi)
        $lines[] = str_pad("Disetujui Oleh,", 22) . str_pad("Diterima Oleh,", 23, " ", STR_PAD_LEFT);
        $lines[] = "";
        $lines[] = "";
        $lines[] = str_pad("(_______________)", 22) . str_pad("(_______________)", 23, " ", STR_PAD_LEFT);

        // Pad to exactly 33 lines (14cm at 6 lpi = ~33 lines)
        $totalLines = count($lines);
        $maxLines = 33; // 14cm page height
        $padLines = $maxLines - $totalLines;
        if ($padLines > 0) {
            for ($i = 0; $i < $padLines; $i++) {
                $lines[] = "";
            }
        }
        
        // Return raw string with CRLF
        $rawText = implode("\r\n", $lines) . "\r\n";
        
        return response()->json([
            'raw_text' => $rawText
        ]);
    }

    // ======================================================================
    // API kecil: last price supplier-item
    // ======================================================================

    public function getSupplierLastPrice(Request $request)
    {
        if (!$this->canSeeMoney($request)) {
            return response()->json(['last_price' => null]);
        }

        $supplierId = (int) $request->query('supplier_id');
        $itemId = (int) $request->query('item_id');

        if ($itemId <= 0) {
            return response()->json(['last_price' => null]);
        }

        if ($supplierId > 0) {
            // 1) supplier_prices — diupdate setiap PO approve/save, paling akurat
            if (Schema::hasTable('supplier_prices')) {
                $last = DB::table('supplier_prices')
                    ->where('supplier_id', $supplierId)
                    ->where('item_id', $itemId)
                    ->value('last_price');

                if ($last !== null && (float) $last > 0) {
                    return response()->json(['last_price' => (float) $last]);
                }
            }

            // 2) supplier_items — harga master yang diset manual
            if (Schema::hasTable('supplier_items')) {
                $last = DB::table('supplier_items')
                    ->where('supplier_id', $supplierId)
                    ->where('item_id', $itemId)
                    ->value('last_price');

                if ($last !== null && (float) $last > 0) {
                    return response()->json(['last_price' => (float) $last]);
                }
            }
        }

        // 3) fallback global: item.last_purchase_price (tidak spesifik supplier)
        // 4) fallback terakhir: item.hpp (berguna untuk finished goods atau master price)
        $fallback = Item::query()
            ->whereKey($itemId)
            ->select(['last_purchase_price', 'hpp'])
            ->first();

        $n = 0;
        if ($fallback) {
            $n = (float) $fallback->last_purchase_price;
            if ($n <= 0) {
                $n = (float) $fallback->hpp;
            }
        }
        return response()->json(['last_price' => $n > 0 ? $n : null]);
    }

    // ======================================================================
    // VALIDASI + NORMALISASI
    // ======================================================================

    protected function validateData(Request $request, ?PurchaseOrder $existingOrder = null): array
    {
        $rules = [
            'date' => ['required', 'date'],
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q
                    ->where('type', 'supplier')
                    ->where('active', 1)),
            ],
            // Legacy field: tidak lagi diisi user, tetapi tetap diterima agar
            // PO lama dan integrasi existing tetap kompatibel.
            'order_type' => ['nullable', 'in:material,finished_good,packing,asset,service,jasa,lainnya'],

            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],

            'tax_percent' => ['nullable', 'string'],
            'discount' => ['nullable', 'string'],
            'shipping_cost' => ['nullable', 'string'],

            'lines' => ['array'],
            'lines.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['nullable', 'string'],
            'lines.*.purchase_unit' => ['nullable', 'string', 'max:20'],
            'lines.*.stock_unit' => ['nullable', 'string', 'max:20'],
            'lines.*.conversion_factor' => ['nullable', 'numeric', 'gt:0', 'max:1000000'],
            'lines.*.unit_price' => ['nullable', 'string'],
            'lines.*.discount' => ['nullable', 'string'],

            'pay_now' => ['nullable', 'string'],
            'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ];

        $data = $request->validate($rules);

        $data['supplier_id'] = (int) $data['supplier_id'];
        $data['order_type'] = $this->normalizeOrderType($data['order_type'] ?? 'material');

        $data['discount'] = $this->toNumber($data['discount'] ?? 0);
        $data['tax_percent'] = $this->toNumber($data['tax_percent'] ?? 0);
        $data['shipping_cost'] = $this->toNumber($data['shipping_cost'] ?? 0);
        $data['pay_now'] = $this->toNumber($data['pay_now'] ?? 0);

        $data['lines'] = $data['lines'] ?? [];
        foreach ($data['lines'] as &$line) {
            $line['qty'] = $this->toNumber($line['qty'] ?? 0);
            $line['unit_price'] = $this->toNumber($line['unit_price'] ?? 0);
            $line['discount'] = $this->toNumber($line['discount'] ?? 0);
        }
        unset($line);

        // PO boleh berisi campuran bahan baku, support/ATK, packaging,
        // service, dan barang jadi. Accounting tetap ditentukan per baris
        // melalui default_allocation + akun biaya master item.

        if (!$this->canSeeMoney($request)) {
            $this->stripMoneyFromNonOwnerPayload($data, $existingOrder);
        }

        $data['payment_method_id'] = (int) $data['payment_method_id'];

        return $data;
    }

    // ======================================================================
    // PAYMENT dari form (pay_now)
    // ======================================================================

    protected function maybeCreatePayNowPayment(Request $request, PurchaseOrder $order, bool $allowIfHasExistingPayments = true): void
    {
        if (!$this->canSeeMoney($request)) {
            return;
        }

        $payNow = $this->toNumber($request->input('pay_now'));
        if ($payNow <= 0.0001) {
            return;
        }

        if (!$allowIfHasExistingPayments && method_exists($order, 'activePayments')) {
            if ($order->activePayments()->exists()) {
                return;
            }
        }

        /** @var PaymentMethod|null $pm */
        $pm = PaymentMethod::query()->find($order->payment_method_id);
        $mode = $this->detectPaymentMode($pm);

        if ($mode === 'credit') {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Metode CREDIT/TEMPO tidak boleh bayar langsung dari form. Pembayaran dilakukan sebagai pelunasan hutang.',
            ]);
        }

        $cashAccountId = $request->input('cash_account_id');

        if ($mode === 'transfer' && empty($cashAccountId)) {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Pilih akun bank untuk transfer.',
            ]);
        }

        if ($mode === 'cash' && empty($cashAccountId)) {
            $cash = Account::query()
                ->where('code', '1101')
                ->where('is_active', 1)
                ->first();

            $cashAccountId = $cash?->id;
        }

        $grand = round((float) $order->grand_total, 2);
        $payNow = round($payNow, 2);

        $eps = 0.01;
        // Nominal di atas total PO tetap dicatat sebagai DP agar selisihnya
        // menjadi piutang supplier, bukan pelunasan AP yang kelebihan.
        $type = ($payNow <= $grand + $eps && abs($payNow - $grand) < $eps) ? 'payment' : 'dp';

        DB::transaction(function () use ($request, $order, $cashAccountId, $payNow, $type) {
            $payment = PurchasePayment::create([
                'purchase_order_id' => $order->id,
                'date' => $order->date,
                'payment_method_id' => $order->payment_method_id,
                'cash_account_id' => $cashAccountId ? (int) $cashAccountId : null,
                'type' => $type,
                'amount' => round($payNow, 2),
                'ref_no' => null,
                'notes' => $type === 'dp' ? 'DP saat buat/ubah PO' : 'Lunas saat buat/ubah PO',
                'created_by' => (int) $request->user()->id,
            ]);

            $this->journalService->postPurchasePayment(
                $payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod'])
            );

            $this->recalcPaymentStatus($order);
        });
    }

    protected function recalcPaymentStatus(PurchaseOrder $order): void
    {
        $paid = 0.0;
        if (method_exists($order, 'activePayments')) {
            $paid = (float) $order->activePayments()
                ->whereIn('type', ['dp', 'payment'])
                ->sum('amount');
        }

        $paid = round($paid, 2);
        $grand = round((float) $order->grand_total, 2);
        $eps = 0.01;

        $status = 'unpaid';
        if ($paid > $eps && $paid > $grand + $eps) {
            $status = 'overpaid';
        } elseif ($paid > $eps && $paid + $eps < $grand) {
            $status = 'partial';
        } elseif ($paid + $eps >= $grand && $grand > 0) {
            $status = 'paid';
        }

        // Pembayaran otomatis mengonfirmasi PO. Gunakan status approved yang
        // sudah menjadi lifecycle non-draft pada modul purchase order ini.
        if ($order->status === 'draft' && in_array($status, ['partial', 'paid', 'overpaid'], true)) {
            $order->status = 'approved';
            $order->approved_by = auth()->id();
            $order->approved_at = now();
        }

        $order->paid_amount = round($paid, 2);
        $order->payment_status = $status;
        $order->save();
    }

    protected function detectPaymentMode(?PaymentMethod $pm): string
    {
        if (!$pm) {
            return 'unknown';
        }

        $mode = strtolower((string) ($pm->mode ?? ''));
        if (in_array($mode, ['cash', 'transfer', 'credit'], true)) {
            return $mode;
        }

        $code = strtoupper((string) ($pm->code ?? ''));
        if (str_contains($code, 'CASH')) {
            return 'cash';
        }
        if (str_contains($code, 'TRF') || str_contains($code, 'TRANSFER')) {
            return 'transfer';
        }
        if (str_contains($code, 'TEMPO') || str_contains($code, 'CREDIT')) {
            return 'credit';
        }

        return 'unknown';
    }

    protected function normalizeOrderType(?string $value): string
    {
        $v = strtolower(trim((string) $value));
        $allowed = ['material', 'finished_good', 'packing', 'asset', 'service', 'jasa', 'lainnya'];
        return in_array($v, $allowed, true) ? $v : 'material';
    }

    protected function applyPoItemFilter($query, string $orderType): void
    {
        // Retained for backwards compatibility with extensions that call this
        // helper. PO item selection is intentionally unfiltered by type or
        // category; only the active-item scope is applied by the caller.
    }

    protected function canSeeMoney(?Request $request = null): bool
    {
        $user = $request?->user() ?: auth()->user();
        return $user && method_exists($user, 'canSeePurchasePrices') && $user->canSeePurchasePrices();
    }

    protected function stripMoneyFromNonOwnerPayload(array &$data, ?PurchaseOrder $existingOrder = null): void
    {
        $data['discount']      = $existingOrder ? (float) ($existingOrder->discount ?? 0) : 0.0;
        $data['tax_percent']   = $existingOrder ? (float) ($existingOrder->tax_percent ?? 0) : 0.0;
        $data['shipping_cost'] = $existingOrder ? (float) ($existingOrder->shipping_cost ?? 0) : 0.0;
        $data['pay_now']       = 0.0;

        // Harga existing PO (untuk edit — pertahankan harga yang sudah diisi owner)
        $moneyByItem = [];
        if ($existingOrder) {
            $existingOrder->loadMissing('lines');
            foreach ($existingOrder->lines as $line) {
                $itemId = (int) ($line->item_id ?? 0);
                if ($itemId <= 0) continue;
                $moneyByItem[$itemId] ??= [];
                $moneyByItem[$itemId][] = [
                    'unit_price' => (float) ($line->unit_price ?? 0),
                    'discount'   => (float) ($line->discount ?? 0),
                ];
            }
        }

        // Preload supplier_prices untuk supplier ini (dipakai sebagai fallback harga)
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $itemIds    = array_filter(array_map(fn($l) => (int) ($l['item_id'] ?? 0), $data['lines']));
        $supplierPriceMap = [];
        $itemLastPriceMap = [];
        if ($supplierId > 0 && !empty($itemIds)) {
            $supplierPriceMap = DB::table('supplier_prices')
                ->where('supplier_id', $supplierId)
                ->whereIn('item_id', $itemIds)
                ->pluck('last_price', 'item_id')
                ->toArray();

            $itemLastPriceMap = DB::table('items')
                ->whereIn('id', $itemIds)
                ->pluck('last_purchase_price', 'id')
                ->toArray();
        }

        foreach ($data['lines'] as &$line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $money  = null;
            if ($itemId > 0 && !empty($moneyByItem[$itemId])) {
                $money = array_shift($moneyByItem[$itemId]);
            }

            $existingPrice = (float) ($money['unit_price'] ?? 0);

            if ($existingPrice > 0) {
                // Edit PO: pertahankan harga owner
                $line['unit_price'] = $existingPrice;
                $line['discount']   = (float) ($money['discount'] ?? 0);
            } else {
                // PO baru atau harga belum diisi: ambil dari supplier_prices / item
                $fallback = (float) ($supplierPriceMap[$itemId] ?? 0);
                if ($fallback <= 0) {
                    $fallback = (float) ($itemLastPriceMap[$itemId] ?? 0);
                }
                $line['unit_price'] = $fallback;
                $line['discount']   = 0.0;
            }
        }
        unset($line);
    }

    protected function toNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // format indo: 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // ribuan: 1.234.567
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        return (float) $value;
    }

    // ======================================================================
    // TAHAP 4 — Close PO
    // ======================================================================

    /**
     * Close PO — hanya owner, hanya jika semua syarat terpenuhi.
     */
    public function close(Request $request, PurchaseOrder $purchase_order)
    {
        abort_unless($request->user()?->isOwner(), 403, 'Hanya owner yang boleh menutup PO.');

        if ($purchase_order->isClosed()) {
            return back()->with('error', 'PO sudah di-close.');
        }

        // Load invoice untuk cek blockers
        $poInvoices = \App\Models\SupplierInvoice::query()
            ->where('purchase_order_id', $purchase_order->id)
            ->whereNotIn('status', ['void'])
            ->get();

        $blockers = $this->calcCloseBlockers($purchase_order, $poInvoices);

        if (!empty($blockers)) {
            return back()->with('error', 'PO belum bisa di-close: ' . implode('; ', $blockers));
        }

        $purchase_order->status = 'closed';
        $purchase_order->closed_at = now();
        $purchase_order->closed_by = (int) $request->user()->id;
        $purchase_order->save();

        return back()->with('success', 'PO berhasil di-close.');
    }

    /**
     * Hitung daftar alasan PO belum bisa di-close.
     * Return array kosong = bisa close.
     *
     * @param \Illuminate\Support\Collection $poInvoices
     */
    protected function calcCloseBlockers(PurchaseOrder $order, $poInvoices): array
    {
        $blockers = [];

        if ($order->status !== 'approved') {
            $blockers[] = 'PO ' . strtoupper($order->status ?? 'draft') . ', belum approved';
        }

        $rcvStatus = $order->received_status ?? 'not_received';
        if ($rcvStatus === 'not_received') {
            $blockers[] = 'Barang belum diterima sama sekali';
        }

        $payStatus = $order->payment_status ?? 'unpaid';
        if ($payStatus !== 'paid') {
            $blockers[] = match ($payStatus) {
                'partial' => 'Pembayaran baru sebagian',
                'overpaid' => 'Pembayaran melebihi nilai PO (piutang supplier)',
                default   => 'Belum ada pembayaran',
            };
        }

        $outstandingInvoices = $poInvoices->whereIn('status', ['posted', 'partial_paid', 'draft']);
        if ($outstandingInvoices->isNotEmpty()) {
            $blockers[] = $outstandingInvoices->count() . ' faktur belum lunas';
        }

        return $blockers;
    }
}
