<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Services\Purchasing\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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
        $q = PurchaseOrder::query()
            ->with([
                'supplier',
                'approvedBy',
                'paymentMethod',
                'purchaseReceipts',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', (int) $request->supplier_id);
        }

        if ($request->filled('status')) {
            $q->where('status', (string) $request->status);
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

        $orderType = $this->normalizeOrderType($request->input('order_type', 'material'));
        $order->order_type = $orderType;

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', 1)
            ->orderByRaw("CASE WHEN code='CASH' THEN 0 ELSE 1 END")
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $order->payment_method_id = $paymentMethods->first()?->id;

        $suppliers = Supplier::query()->orderBy('name')->get();

        $items = Item::query()
            ->select(['id', 'code', 'name', 'category_id', 'type', 'active'])
            ->where('active', 1)
            ->where('type', $orderType)
            ->with(['category:id,code,name'])
            ->orderBy('name')
            ->limit(200)
            ->get();

        $cashAccounts = Account::query()
            ->where('type', 'asset')
            ->where('is_active', 1)
            ->where('is_cash', 1)
            ->orderBy('code')
            ->get();

        $lines = collect();

        return view('purchasing.purchase_orders.create', [
            'order' => $order,
            'suppliers' => $suppliers,
            'paymentMethods' => $paymentMethods,
            'items' => $items,
            'lines' => $lines,
            'cashAccounts' => $cashAccounts,
            'orderType' => $orderType,
        ]);
    }

    /**
     * Simpan PO baru.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['created_by'] = (int) $request->user()->id;
        $data['status'] = 'draft';

        $order = $this->service->create($data);

        // auto-create payment from form (pay_now)
        $this->maybeCreatePayNowPayment($request, $order, allowIfHasExistingPayments: true);

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
            'paymentMethod',
            'lines.item',
            'createdBy',
            'approvedBy',
            'cancelledBy',
            'purchaseReceipts.warehouse',
            'purchaseReceipts',
            'payments' => function ($q) {
                $q->with(['paymentMethod', 'cashAccount'])
                    ->orderByDesc('date')
                    ->orderByDesc('id');
            },
        ]);

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
        // METRICS hutang berbasis GRN posted
        // =========================================================
        $grnPostedTotal = (float) $purchase_order->purchaseReceipts
            ->where('status', 'posted')
            ->sum('grand_total');

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
        $apOutstanding = max(0, round($grnPostedTotal - $settled, 2));

        return view('purchasing.purchase_orders.show', [
            'order' => $purchase_order,
            'paymentMethods' => $paymentMethods,
            'cashAccounts' => $cashAccounts,
            'grnPostedTotal' => $grnPostedTotal,
            'paidPaymentTotal' => $paidPaymentTotal,
            'dpTotal' => $dpTotal,
            'dpAppliedTotal' => $dpAppliedTotal,
            'dpAvailable' => $dpAvailable,
            'apOutstanding' => $apOutstanding,
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

        $purchase_order->load(['lines.item', 'paymentMethod']);

        $suppliers = Supplier::orderBy('name')->get();

        $paymentMethods = PaymentMethod::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $orderType = $this->normalizeOrderType(
            (string) ($purchase_order->getAttribute('order_type') ?: $request->input('order_type', 'material'))
        );

        $itemsBase = Item::query()
            ->where('active', 1)
            ->where('type', $orderType)
            ->with('category')
            ->orderBy('name')
            ->limit(300)
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

        $order = $this->service->update($purchase_order, $data);

        // hanya buat payment dari form kalau belum ada payment aktif
        $this->maybeCreatePayNowPayment($request, $order, allowIfHasExistingPayments: false);

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
    // APPROVE / CANCEL
    // ======================================================================

    public function approve(PurchaseOrder $purchase_order)
    {
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

    // ======================================================================
    // API kecil: last price supplier-item
    // ======================================================================

    public function getSupplierLastPrice(Request $request)
    {
        $supplierId = (int) $request->query('supplier_id');
        $itemId = (int) $request->query('item_id');

        if ($supplierId <= 0 || $itemId <= 0) {
            return response()->json(['last_price' => null]);
        }

        // 1) sumber utama: supplier_items (kalau tabel ada)
        if (Schema::hasTable('supplier_items')) {
            $last = DB::table('supplier_items')
                ->where('supplier_id', $supplierId)
                ->where('item_id', $itemId)
                ->value('last_price');

            if ($last !== null) {
                $n = (float) $last;
                return response()->json(['last_price' => $n > 0 ? $n : 0.0]);
            }
        }

        // 2) fallback: item.last_purchase_price
        $fallback = Item::query()
            ->whereKey($itemId)
            ->value('last_purchase_price');

        if ($fallback === null) {
            return response()->json(['last_price' => null]);
        }

        $n = (float) $fallback;
        return response()->json(['last_price' => $n > 0 ? $n : 0.0]);
    }

    // ======================================================================
    // VALIDASI + NORMALISASI
    // ======================================================================

    protected function validateData(Request $request): array
    {
        $rules = [
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'order_type' => ['required', 'in:material,finished_good'],

            // optional
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],

            'tax_percent' => ['nullable', 'string'],
            'discount' => ['nullable', 'string'],
            'shipping_cost' => ['nullable', 'string'],

            'lines' => ['array'],
            'lines.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['nullable', 'string'],
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

        // Auto default payment_method_id kalau kosong
        if (empty($data['payment_method_id'])) {
            $transferId = PaymentMethod::query()
                ->where('is_active', 1)
                ->where('mode', 'transfer')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->value('id');

            $data['payment_method_id'] = $transferId ?: PaymentMethod::query()
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->value('id');
        }

        $data['payment_method_id'] = !empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null;

        return $data;
    }

    // ======================================================================
    // PAYMENT dari form (pay_now)
    // ======================================================================

    protected function maybeCreatePayNowPayment(Request $request, PurchaseOrder $order, bool $allowIfHasExistingPayments = true): void
    {
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

        $grand = (float) $order->grand_total;
        $payNow = min($payNow, max(0, $grand));

        $eps = 0.01;
        $type = (abs($payNow - $grand) < $eps) ? 'payment' : 'dp';

        DB::transaction(function () use ($request, $order, $cashAccountId, $payNow, $type) {
            PurchasePayment::create([
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

            $this->recalcPaymentStatus($order);
        });
    }

    protected function recalcPaymentStatus(PurchaseOrder $order): void
    {
        $paid = method_exists($order, 'activePayments')
        ? (float) $order->activePayments()->sum('amount')
        : 0.0;

        $grand = (float) $order->grand_total;
        $eps = 0.01;

        $status = 'unpaid';
        if ($paid > $eps && $paid + $eps < $grand) {
            $status = 'partial';
        } elseif ($paid + $eps >= $grand && $grand > 0) {
            $status = 'paid';
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
        return in_array($v, ['material', 'finished_good'], true) ? $v : 'material';
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
}
