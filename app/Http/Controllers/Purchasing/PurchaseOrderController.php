<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\SupplierPrice;
use App\Services\Purchasing\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
    public function index(Request $request)
    {
        $q = PurchaseOrder::query()
            ->with([
                'supplier',
                'approvedBy',
                'paymentMethod', // ✅ kalau index nanti mau tampilkan metode bayar
                'purchaseReceipts', // ✅ buat badge/indikator GRN
            ])
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
     */
    public function create()
    {
        $order = new PurchaseOrder();
        $order->date = now()->toDateString();
        $order->tax_percent = 11;
        $order->discount = 0;
        $order->shipping_cost = 0;

        // ✅ Payment methods (aktif)
        $paymentMethods = PaymentMethod::query()
            ->where('is_active', 1)
            ->orderByRaw("CASE WHEN code='CASH' THEN 0 ELSE 1 END") // ✅ default CASH
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // ✅ default payment method = CASH (kalau ada), kalau tidak ambil first aktif
        $order->payment_method_id = $paymentMethods->first()?->id;

        // ✅ suppliers
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get();

        // ✅ items material (limit untuk performa, ambil field yang dipakai suggest)
        $items = Item::query()
            ->select(['id', 'code', 'name', 'category_id', 'type', 'active'])
            ->where('active', 1)
            ->where('type', 'material')
            ->with(['category:id,code,name'])
            ->orderBy('name')
            ->limit(150)
            ->get();

        // ✅ NEW: akun kas/bank dari COA (untuk CASH/TRANSFER)
        $cashAccounts = Account::query()
            ->where('type', 'asset')
            ->where('is_active', 1)
            ->where('is_cash', 1) // ✅ dari data kamu: kas/bank punya flag 1
            ->orderBy('code')
            ->get();

        $lines = collect();

        return view('purchasing.purchase_orders.create', compact(
            'order',
            'suppliers',
            'paymentMethods',
            'items',
            'lines',
            'cashAccounts' // ✅ kirim ke _blade.php
        ));
    }

    /**
     * Simpan PO baru.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['created_by'] = $request->user()->id;
        $data['status'] = 'draft';

        $order = $this->service->create($data);

        // ✅ NEW: auto-create payment from form (pay_now)
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
            'purchaseReceipts', // biar bisa hitung total posted tanpa query tambahan (opsional)

            'payments' => function ($q) {
                $q->with(['paymentMethod', 'cashAccount'])
                    ->orderByDesc('date')
                    ->orderByDesc('id');
            },
        ]);

        // daftar metode bayar aktif untuk modal
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

        // cash/bank yang dimunculkan saja (urut: kas dulu, lalu 1111->1114)
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

        // ==========================
        // OPTIONAL: metrics hutang real berbasis GRN posted
        // ==========================
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

        $apOutstanding = max(0, round($grnPostedTotal - $paidPaymentTotal, 2));

        return view('purchasing.purchase_orders.show', [
            'order' => $purchase_order,
            'paymentMethods' => $paymentMethods,
            'cashAccounts' => $cashAccounts,

            // optional (kalau mau dipakai di blade)
            'grnPostedTotal' => $grnPostedTotal,
            'paidPaymentTotal' => $paidPaymentTotal,
            'dpTotal' => $dpTotal,
            'apOutstanding' => $apOutstanding,
        ]);
    }

    /**
     * Form edit PO.
     */
    public function edit(PurchaseOrder $purchase_order)
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

        $items = Item::query()
            ->where('active', 1)
            ->where('type', 'material')
            ->with('category')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $lines = $purchase_order->lines;

        return view('purchasing.purchase_orders.edit', [
            'order' => $purchase_order,
            'suppliers' => $suppliers,
            'paymentMethods' => $paymentMethods,
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

        $data['status'] = 'draft';

        $order = $this->service->update($purchase_order, $data);

        // ✅ NEW (SAFE): hanya buat payment dari form kalau belum ada payment aktif
        // biar tidak dobel saat user edit PO berkali-kali.
        $this->maybeCreatePayNowPayment($request, $order, allowIfHasExistingPayments: false);

        return redirect()
            ->route('purchasing.purchase_orders.show', $order->id)
            ->with('success', 'Purchase Order berhasil diperbarui.');
    }

    /**
     * Hapus PO (opsional).
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
    // VALIDASI + NORMALISASI
    // ======================================================================

    protected function validateData(Request $request): array
    {
        $rules = [
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],

            // ✅ NEW: sinkron dengan view payment select
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],

            // optional header angka (kalau field ini ada di form/DB)
            'tax_percent' => ['nullable', 'string'],
            'discount' => ['nullable', 'string'],

            'shipping_cost' => ['nullable', 'string'],

            'lines' => ['array'],
            'lines.*.item_id' => ['nullable', 'integer'],
            'lines.*.qty' => ['nullable', 'string'],
            'lines.*.unit_price' => ['nullable', 'string'],
            'lines.*.discount' => ['nullable', 'string'],
            'pay_now' => ['nullable', 'string'],
            'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ];

        $data = $request->validate($rules);

        $normalize = function ($v) {
            if ($v === null || $v === '') {
                return 0;
            }

            $v = trim((string) $v);
            $v = str_replace(' ', '', $v);

            // format indo: 1.234,56
            if (strpos($v, ',') !== false) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
                // ribuan: 1.234.567
                $v = str_replace('.', '', $v);
            }

            return (float) $v;
        };

        // ✅ aman walaupun field tidak ada di form
        $data['discount'] = $normalize($data['discount'] ?? 0);
        $data['tax_percent'] = $normalize($data['tax_percent'] ?? 0);
        $data['shipping_cost'] = $normalize($data['shipping_cost'] ?? 0);
        $data['pay_now'] = $normalize($data['pay_now'] ?? 0);

        // ✅ pastikan lines selalu array agar foreach aman
        $data['lines'] = $data['lines'] ?? [];

        foreach ($data['lines'] as &$line) {
            $line['qty'] = $normalize($line['qty'] ?? 0);
            $line['unit_price'] = $normalize($line['unit_price'] ?? 0);
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

    public function getSupplierLastPrice(Request $request)
    {
        $supplierId = (int) $request->query('supplier_id');
        $itemId = (int) $request->query('item_id');

        if ($supplierId <= 0 || $itemId <= 0) {
            return response()->json(['last_price' => null]);
        }

        $row = SupplierPrice::query()
            ->where('supplier_id', $supplierId)
            ->where('item_id', $itemId)
            ->first();

        return response()->json([
            'last_price' => $row ? (float) $row->last_price : null,
        ]);
    }

    protected function maybeCreatePayNowPayment(Request $request, \App\Models\PurchaseOrder $order, bool $allowIfHasExistingPayments = true): void
    {
        $payNow = $this->toNumber($request->input('pay_now'));
        if ($payNow <= 0) {
            return;
        }

        // Safety: jangan dobel saat edit berulang
        if (!$allowIfHasExistingPayments) {
            $hasAny = $order->activePayments()->exists();
            if ($hasAny) {
                return;
            }
        }

        /** @var PaymentMethod $pm */
        $pm = PaymentMethod::query()->find($order->payment_method_id);
        $mode = $this->detectPaymentMode($pm);

        // CREDIT/TEMPO -> tidak boleh bikin purchase_payments dari pay_now (harus lewat hutang)
        if ($mode === 'credit') {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Metode CREDIT/TEMPO tidak boleh bayar langsung dari form. Pembayaran dilakukan sebagai pelunasan hutang.',
            ]);
        }

        $cashAccountId = $request->input('cash_account_id');

        // TRANSFER wajib pilih akun
        if ($mode === 'transfer' && empty($cashAccountId)) {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Pilih akun bank untuk transfer.',
            ]);
        }

        // CASH boleh auto 1101 kalau kosong
        if ($mode === 'cash' && empty($cashAccountId)) {
            $cash = Account::query()
                ->where('code', '1101')
                ->where('is_active', 1)
                ->first();

            $cashAccountId = $cash?->id;
        }

        // cap payNow supaya tidak overpayment (opsional tapi aman)
        $grand = (float) $order->grand_total;
        $payNow = min($payNow, max(0, $grand));

        // tentukan type: jika bayar full → payment, kalau sebagian → dp
        $type = (abs($payNow - $grand) < 0.01) ? 'payment' : 'dp';

        DB::transaction(function () use ($request, $order, $pm, $cashAccountId, $payNow, $type) {
            PurchasePayment::create([
                'purchase_order_id' => $order->id,
                'date' => $order->date,
                'payment_method_id' => $order->payment_method_id,
                'cash_account_id' => $cashAccountId ? (int) $cashAccountId : null,
                'type' => $type,
                'amount' => $payNow,
                'ref_no' => null,
                'notes' => $type === 'dp' ? 'DP saat buat/ubah PO' : 'Lunas saat buat/ubah PO',
                'created_by' => $request->user()->id,
            ]);

            $this->recalcPaymentStatus($order);
        });
    }

    protected function recalcPaymentStatus(\App\Models\PurchaseOrder $order): void
    {
        $paid = (float) $order->activePayments()->sum('amount');
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

        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        return (float) $value;
    }

}
