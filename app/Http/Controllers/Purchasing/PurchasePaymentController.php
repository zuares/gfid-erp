<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierLoan;
use App\Services\Accounting\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasePaymentController extends Controller
{
    // Bank/ewallet yang boleh untuk TRANSFER
    private const TRANSFER_BANK_CODES = ['1111', '1112', '1113', '1114'];

    // ======================================================================
    // STANDALONE INDEX & CREATE
    // ======================================================================

    public function index(Request $request)
    {
        $this->ensureOwner($request);

        $q = PurchasePayment::query()
            ->with(['purchaseOrder.supplier', 'purchaseReceipt', 'paymentMethod', 'cashAccount'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $q->whereHas('purchaseOrder', fn ($s) => $s->where('supplier_id', $request->integer('supplier_id')));
        }

        if ($request->filled('from')) {
            $q->whereDate('date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $q->whereDate('date', '<=', $request->date('to'));
        }

        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }

        if ($request->filled('voided')) {
            $request->string('voided') === 'yes'
                ? $q->whereNotNull('voided_at')
                : $q->whereNull('voided_at');
        } else {
            $q->whereNull('voided_at'); // default: hanya aktif
        }

        $summaryRows = (clone $q)->withoutEagerLoads()
            ->selectRaw('type, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total')
            ->groupBy('type')
            ->get()->keyBy('type');

        $summary = [
            'total_payment' => (float) ($summaryRows->get('payment')?->total ?? 0),
            'total_dp' => (float) ($summaryRows->get('dp')?->total ?? 0),
            'count' => (int) $summaryRows->sum('cnt'),
        ];

        $payments = $q->paginate(30)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $cashAccounts = Account::where('is_cash', true)->where('is_active', true)->orderBy('code')->get();

        // POs with outstanding debt for create form
        $openPos = PurchaseOrder::query()
            ->with(['supplier', 'lines.item'])
            ->whereHas('purchaseReceipts', function ($s) {
                $s->where('status', 'posted')
                    ->where(function ($q) {
                        $q->whereNull('is_replacement')
                            ->orWhere('is_replacement', false);
                    });
            })
            ->withSum(['purchaseReceipts as posted_grn_total' => function ($q) {
                $q->where('status', 'posted')
                    ->where(function ($q) {
                        $q->whereNull('is_replacement')
                            ->orWhere('is_replacement', false);
                    });
            }], 'grand_total')
            ->withSum(['purchaseReturns as posted_return_total' => function ($q) {
                $q->where('status', 'posted')
                    ->whereNull('voided_at')
                    ->where(function ($q) {
                        $q->whereNull('resolution_type')
                            ->orWhere('resolution_type', '!=', 'replacement');
                    });
            }], 'total')
            ->withSum(['activePayments as posted_payment_total' => function ($q) {
                $q->where('type', 'payment');
            }], 'amount')
            ->withSum(['activePayments as posted_dp_apply_total' => function ($q) {
                $q->where('type', 'dp_apply');
            }], 'amount')
            ->orderByDesc('date')
            ->get(['id', 'code', 'date', 'supplier_id', 'grand_total', 'paid_amount', 'payment_status'])
            ->map(function (PurchaseOrder $po) {
                $debt = max(0, round(
                    (float) ($po->posted_grn_total ?? 0)
                    - (float) ($po->posted_return_total ?? 0),
                    2
                ));
                $settled = (float) ($po->posted_payment_total ?? 0)
                    + (float) ($po->posted_dp_apply_total ?? 0);
                $po->payment_outstanding = PurchaseOrder::normalizePaymentRemainder($debt - $settled);

                return $po;
            })
            ->filter(fn (PurchaseOrder $po) => (float) $po->payment_outstanding > 0)
            ->values();

        return view('purchasing.purchase_payments.index', compact(
            'payments', 'summary', 'suppliers', 'paymentMethods', 'cashAccounts', 'openPos'
        ));
    }

    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * GET shortcut for the payment tab on a purchase order.
     *
     * Payment creation remains handled by the POST endpoint below; this
     * route only makes the direct /payments URL safe to open in a browser.
     */
    public function showPayments(PurchaseOrder $purchase_order)
    {
        return redirect()
            ->route('purchasing.purchase_orders.show', $purchase_order)
            ->withFragment('payments');
    }

    /**
     * Store a settlement payment allocated to one posted GRN.
     *
     * The PO link is retained for backward-compatible AP aggregation, while
     * purchase_receipt_id makes the payment traceable to a specific receipt.
     */
    public function storeForReceipt(Request $request, PurchaseReceipt $purchase_receipt)
    {
        $this->ensureOwner($request);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'string'],
            'ref_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            'supplier_invoice_id' => ['nullable', 'integer', 'exists:supplier_invoices,id'],
        ]);

        if ($purchase_receipt->status !== 'posted') {
            throw ValidationException::withMessages([
                'amount' => 'Pembayaran hanya bisa dibuat untuk GRN yang sudah POSTED.',
            ]);
        }

        if ($purchase_receipt->is_replacement) {
            throw ValidationException::withMessages([
                'amount' => 'GRN replacement tidak dapat dibayar langsung.',
            ]);
        }

        $amount = $this->toNumber($data['amount']);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus > 0.',
            ]);
        }

        $pm = PaymentMethod::query()->findOrFail((int) $data['payment_method_id']);
        $mode = $this->detectPaymentMode($pm);
        if (! in_array($mode, ['cash', 'transfer'], true)) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Pembayaran GRN hanya boleh menggunakan CASH atau TRANSFER.',
            ]);
        }

        $cashAccountId = $this->resolveCashAccountId($pm, $data['cash_account_id'] ?? null);
        $this->validateCashAccount($cashAccountId, $mode);

        DB::transaction(function () use ($request, $data, &$amount, $cashAccountId, $purchase_receipt) {
            $lockedReceipt = PurchaseReceipt::query()
                ->whereKey($purchase_receipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReceipt->status !== 'posted' || $lockedReceipt->is_replacement) {
                throw ValidationException::withMessages([
                    'amount' => 'GRN sudah tidak dapat menerima pembayaran.',
                ]);
            }

            $lockedOrder = PurchaseOrder::query()
                ->whereKey($lockedReceipt->purchase_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'amount' => 'PO cancelled tidak bisa menerima pembayaran.',
                ]);
            }

            $receiptReturnTotal = (float) PurchaseReturn::query()
                ->where('purchase_receipt_id', $lockedReceipt->id)
                ->where('status', 'posted')
                ->whereNull('voided_at')
                ->where(function ($q) {
                    $q->whereNull('resolution_type')
                        ->orWhere('resolution_type', '!=', 'replacement');
                })
                ->sum('total');

            $receiptPaid = (float) PurchasePayment::query()
                ->where('purchase_receipt_id', $lockedReceipt->id)
                ->whereNull('voided_at')
                ->where('type', 'payment')
                ->sum('amount');

            $receiptOutstanding = PurchaseOrder::normalizePaymentRemainder(
                (float) $lockedReceipt->grand_total - $receiptReturnTotal - $receiptPaid
            );

            // Also cap against the PO-wide AP balance so legacy PO payments
            // and GRN payments cannot collectively overpay the supplier.
            $poOutstanding = $this->rawApOutstandingByGrn($lockedOrder);
            $available = min($receiptOutstanding, $poOutstanding);

            if ($available <= 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'GRN ini sudah lunas atau saldo AP PO sudah habis.',
                ]);
            }

            if ($amount > $available + PurchaseOrder::paymentRoundingTolerance()) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal melebihi sisa hutang GRN.',
                ]);
            }

            $amount = min(round($amount, 2), $available);

            $supplierInvoiceId = ! empty($data['supplier_invoice_id'])
                ? (int) $data['supplier_invoice_id']
                : SupplierInvoice::query()
                    ->where('purchase_order_id', $lockedOrder->id)
                    ->where('supplier_id', $lockedOrder->supplier_id)
                    ->whereIn('status', ['posted', 'partial_paid'])
                    ->orderBy('invoice_date')
                    ->value('id');

            if ($supplierInvoiceId) {
                $invoiceMatchesOrder = SupplierInvoice::query()
                    ->whereKey($supplierInvoiceId)
                    ->where('purchase_order_id', $lockedOrder->id)
                    ->where('supplier_id', $lockedOrder->supplier_id)
                    ->whereIn('status', ['posted', 'partial_paid'])
                    ->exists();

                if (! $invoiceMatchesOrder) {
                    throw ValidationException::withMessages([
                        'supplier_invoice_id' => 'Invoice supplier harus milik PO dan supplier yang sama, serta belum lunas/void.',
                    ]);
                }
            }

            $payment = PurchasePayment::create([
                'purchase_order_id' => (int) $lockedOrder->id,
                'purchase_receipt_id' => (int) $lockedReceipt->id,
                'supplier_invoice_id' => $supplierInvoiceId,
                'date' => $data['date'],
                'payment_method_id' => (int) $data['payment_method_id'],
                'cash_account_id' => (int) $cashAccountId,
                'type' => 'payment',
                'amount' => $amount,
                'ref_no' => $data['ref_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => (int) $request->user()->id,
            ]);

            $journal = $this->journalService->postPurchasePayment(
                $payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod'])
            );

            if ($journal && empty($payment->journal_id) && ! empty($journal->id)) {
                $payment->forceFill(['journal_id' => (int) $journal->id])->save();
            }

            $this->recalcPaymentStatus($lockedOrder);

            if ($supplierInvoiceId) {
                $this->syncInvoicePaymentStatus($supplierInvoiceId);
            }
        });

        return back()->with('success', 'Pembayaran GRN berhasil disimpan.');
    }

    /**
     * Store DP / Payment (pelunasan) dari modal show PO.
     *
     * Rules:
     * - DP: boleh CASH / TRANSFER / CREDIT
     * - PAYMENT (pelunasan): hanya boleh CASH / TRANSFER (bukan CREDIT)
     * - CASH: wajib akun 1101
     * - TRANSFER: wajib akun 1111-1114
     * - CREDIT: cash_account_id harus null
     * - PAYMENT: hanya boleh kalau ada GRN posted dan tidak boleh melebihi outstanding
     * - DP: boleh dicatat sebelum GRN posted dan boleh melebihi nilai PO
     */
    public function store(Request $request, PurchaseOrder $purchase_order)
    {
        $this->ensureOwner($request);

        $grnPostedTotal = (float) $purchase_order->purchaseReceipts()
            ->where('status', 'posted')
            ->where(function ($q) {
                $q->whereNull('is_replacement')
                    ->orWhere('is_replacement', false);
            })
            ->sum('grand_total');

        if (($purchase_order->status ?? '') === 'cancelled') {
            return back()->with('error', 'PO cancelled tidak bisa menerima pembayaran.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'type' => ['required', 'in:dp,payment'],
            'amount' => ['required', 'string'],
            'ref_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            // Tahap 4: link ke Supplier Invoice (opsional — backward compat)
            'supplier_invoice_id' => ['nullable', 'integer', 'exists:supplier_invoices,id'],
        ]);

        $amount = $this->toNumber($data['amount']);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus > 0.',
            ]);
        }

        if (($data['type'] ?? '') === 'payment' && $grnPostedTotal <= 0.0001) {
            throw ValidationException::withMessages([
                'amount' => 'Pelunasan hanya bisa dilakukan setelah GRN POSTED.',
            ]);
        }

        /** @var PaymentMethod $pm */
        $pm = PaymentMethod::query()->findOrFail((int) $data['payment_method_id']);
        $mode = $this->detectPaymentMode($pm); // cash|transfer|credit|unknown
        if ($mode === 'unknown') {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Mode payment method tidak valid. Pastikan mode: cash/transfer/credit.',
            ]);
        }

        // =====================================================
        // 0) CREDIT hanya boleh untuk DP (bukan pelunasan)
        // =====================================================
        if ($mode === 'credit' && ($data['type'] ?? '') === 'payment') {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Metode TEMPO/CREDIT tidak boleh untuk pelunasan. Gunakan CASH/TRANSFER.',
            ]);
        }

        // =====================================================
        // 1) Resolve cash/bank sesuai mode
        //    - credit: harus null
        //    - cash/transfer: bisa default/fallback
        // =====================================================
        $cashAccountId = $this->resolveCashAccountId($pm, $data['cash_account_id'] ?? null);

        // Validasi akun sesuai mode
        if ($mode === 'credit') {
            // Pastikan tidak ada akun kas/bank
            $cashAccountId = null;
        } elseif ($mode === 'cash') {
            $this->validateCashAccount($cashAccountId, 'cash');
        } elseif ($mode === 'transfer') {
            $this->validateCashAccount($cashAccountId, 'transfer');
        }

        // =====================================================
        // 2) Validasi hutang outstanding hanya untuk PELUNASAN
        //    DP tidak mengurangi hutang (DP masuk 1151)
        // =====================================================
        if (($data['type'] ?? '') === 'payment') {
            $rawOutstanding = $this->rawApOutstandingByGrn($purchase_order);

            if ($rawOutstanding <= 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Tidak ada hutang yang bisa dibayar (belum ada GRN posted atau hutang sudah lunas).',
                ]);
            }

            if ($amount > $rawOutstanding + PurchaseOrder::paymentRoundingTolerance()) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal melebihi sisa hutang.',
                ]);
            }

            // Jika UI menampilkan saldo pecahan sebagai Rp1, terima inputnya
            // tetapi simpan hanya saldo riil agar jurnal tidak overpay.
            $amount = min(round($amount, 2), $rawOutstanding);
        }

        // DP tetap berada di akun Uang Muka Pembelian. DP tidak boleh
        // otomatis mengurangi invoice supplier; offset dilakukan eksplisit
        // melalui flow applyDp().
        if (($data['type'] ?? '') === 'dp' && ! empty($data['supplier_invoice_id'])) {
            throw ValidationException::withMessages([
                'supplier_invoice_id' => 'DP tidak boleh dikaitkan langsung ke invoice. Gunakan Offset DP setelah GRN POSTED.',
            ]);
        }

        $supplierInvoiceId = null;
        if (($data['type'] ?? '') === 'payment') {
            $supplierInvoiceId = ! empty($data['supplier_invoice_id'])
                ? (int) $data['supplier_invoice_id']
                : SupplierInvoice::where('purchase_order_id', $purchase_order->id)
                    ->where('supplier_id', $purchase_order->supplier_id)
                    ->whereIn('status', ['posted', 'partial_paid'])
                    ->orderBy('invoice_date')
                    ->value('id');

            if ($supplierInvoiceId) {
                $invoiceMatchesOrder = SupplierInvoice::query()
                    ->whereKey($supplierInvoiceId)
                    ->where('purchase_order_id', $purchase_order->id)
                    ->where('supplier_id', $purchase_order->supplier_id)
                    ->whereIn('status', ['posted', 'partial_paid'])
                    ->exists();

                if (! $invoiceMatchesOrder) {
                    throw ValidationException::withMessages([
                        'supplier_invoice_id' => 'Invoice supplier harus milik PO dan supplier yang sama, serta belum lunas/void.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($purchase_order, $data, &$amount, $request, $cashAccountId, $supplierInvoiceId) {
            // Re-check dengan row lock agar dua pembayaran bersamaan tidak
            // sama-sama memakai saldo hutang yang sama.
            $lockedOrder = PurchaseOrder::query()
                ->whereKey($purchase_order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (($data['type'] ?? '') === 'payment') {
                $rawOutstanding = $this->rawApOutstandingByGrn($lockedOrder);
                if ($rawOutstanding <= 0.0001) {
                    throw ValidationException::withMessages([
                        'amount' => 'Tidak ada hutang yang bisa dibayar setelah saldo diperbarui.',
                    ]);
                }
                if ($amount > $rawOutstanding + PurchaseOrder::paymentRoundingTolerance()) {
                    throw ValidationException::withMessages([
                        'amount' => 'Nominal melebihi sisa hutang setelah saldo diperbarui.',
                    ]);
                }
                $amount = min(round($amount, 2), $rawOutstanding);
            }

            $payment = PurchasePayment::create([
                'purchase_order_id' => (int) $lockedOrder->id,
                'supplier_invoice_id' => $supplierInvoiceId, // nullable
                'date' => $data['date'],
                'payment_method_id' => (int) $data['payment_method_id'],
                'cash_account_id' => $cashAccountId ? (int) $cashAccountId : null, // CREDIT => null
                'type' => $data['type'], // dp|payment
                'amount' => round($amount, 2),
                'ref_no' => $data['ref_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => (int) $request->user()->id,
            ]);

            // ✅ Post journal + pastikan journal_id terset (di JournalService)
            $journal = $this->journalService->postPurchasePayment(
                $payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod'])
            );

            // ✅ SAFETY: kalau JournalService return Journal, set journal_id di sini juga
            if ($journal && empty($payment->journal_id) && ! empty($journal->id)) {
                $payment->journal_id = (int) $journal->id;
                $payment->save();
            }

            $this->recalcPaymentStatus($lockedOrder);

            // Tahap 4: sync paid_amount + status ke Supplier Invoice jika dipilih
            if ($supplierInvoiceId) {
                $this->syncInvoicePaymentStatus($supplierInvoiceId);
            }
        });

        return back()->with('success', 'Pembayaran tersimpan.');
    }

    /**
     * Store one supplier payment allocated across multiple POs.
     *
     * A combined payment is persisted as one payment row per PO so the
     * existing AP, journal, PO status, and detail history remain traceable.
     * All POs must belong to the same supplier.
     */
    public function storeCombined(Request $request)
    {
        $this->ensureOwner($request);

        $data = $request->validate([
            'purchase_order_ids' => ['required', 'array', 'min:2'],
            'purchase_order_ids.*' => ['required', 'integer', 'distinct', 'exists:purchase_orders,id'],
            'amounts' => ['required', 'array'],
            'amounts.*' => ['required', 'string'],
            'date' => ['required', 'date'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'ref_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $poIds = collect($data['purchase_order_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($poIds->count() < 2) {
            throw ValidationException::withMessages([
                'purchase_order_ids' => 'Pilih minimal dua PO untuk pembayaran gabungan.',
            ]);
        }

        /** @var PaymentMethod $pm */
        $pm = PaymentMethod::query()->findOrFail((int) $data['payment_method_id']);
        $mode = $this->detectPaymentMode($pm);
        if (! in_array($mode, ['cash', 'transfer'], true)) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Pembayaran gabungan hanya boleh menggunakan CASH atau TRANSFER.',
            ]);
        }

        $cashAccountId = $this->resolveCashAccountId($pm, $data['cash_account_id'] ?? null);
        $this->validateCashAccount($cashAccountId, $mode);

        DB::transaction(function () use ($request, $data, $poIds, $cashAccountId) {
            // Lock in a stable order to avoid two combined payments racing on
            // the same set of PO balances.
            $orders = PurchaseOrder::query()
                ->whereIn('id', $poIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== $poIds->count()) {
                throw ValidationException::withMessages([
                    'purchase_order_ids' => 'Salah satu PO tidak ditemukan.',
                ]);
            }

            $supplierIds = $orders->pluck('supplier_id')->unique()->values();
            if ($supplierIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'purchase_order_ids' => 'Pembayaran gabungan hanya boleh untuk PO dari supplier yang sama.',
                ]);
            }

            $allocations = [];
            foreach ($orders as $order) {
                if (($order->status ?? '') === 'cancelled') {
                    throw ValidationException::withMessages([
                        'purchase_order_ids' => "PO {$order->code} sudah cancelled dan tidak bisa dibayar.",
                    ]);
                }

                $amount = $this->toNumber(data_get($data, "amounts.{$order->id}"));
                $outstanding = $this->rawApOutstandingByGrn($order);
                $receipts = $this->receiptsWithOutstanding($order);
                $receiptCapacity = min(
                    $outstanding,
                    (float) $receipts->sum('receipt_outstanding')
                );

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        "amounts.{$order->id}" => "Alokasi untuk PO {$order->code} harus lebih dari 0.",
                    ]);
                }

                if ($receiptCapacity <= 0.0001) {
                    throw ValidationException::withMessages([
                        "amounts.{$order->id}" => "PO {$order->code} sudah tidak memiliki GRN dengan hutang outstanding.",
                    ]);
                }

                if ($amount > $receiptCapacity + PurchaseOrder::paymentRoundingTolerance()) {
                    throw ValidationException::withMessages([
                        "amounts.{$order->id}" => "Alokasi PO {$order->code} melebihi sisa hutangnya.",
                    ]);
                }

                $allocations[(int) $order->id] = [
                    'order' => $order,
                    'amount' => min(round($amount, 2), $receiptCapacity),
                    'receipts' => $receipts,
                ];
            }

            foreach ($allocations as $allocation) {
                /** @var PurchaseOrder $order */
                $order = $allocation['order'];
                $remaining = $allocation['amount'];

                // Allocate oldest GRN first. A single combined action may
                // therefore create several GRN-linked payment rows for one PO.
                foreach ($allocation['receipts'] as $receipt) {
                    if ($remaining <= 0.0001) {
                        break;
                    }

                    $receiptAmount = min($remaining, (float) $receipt->receipt_outstanding);
                    if ($receiptAmount <= 0.0001) {
                        continue;
                    }

                    $payment = PurchasePayment::create([
                        'purchase_order_id' => (int) $order->id,
                        'purchase_receipt_id' => (int) $receipt->id,
                        'date' => $data['date'],
                        'payment_method_id' => (int) $data['payment_method_id'],
                        'cash_account_id' => (int) $cashAccountId,
                        'type' => 'payment',
                        'amount' => round($receiptAmount, 2),
                        'ref_no' => $data['ref_no'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'created_by' => (int) $request->user()->id,
                    ]);

                    $journal = $this->journalService->postPurchasePayment(
                        $payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod'])
                    );

                    if ($journal && empty($payment->journal_id) && ! empty($journal->id)) {
                        $payment->forceFill(['journal_id' => (int) $journal->id])->save();
                    }

                    $remaining -= $receiptAmount;
                }

                $this->recalcPaymentStatus($order);
            }
        });

        return back()->with('success', 'Pembayaran gabungan supplier berhasil disimpan untuk '.$poIds->count().' PO.');
    }

    public function void(Request $request, PurchaseOrder $purchase_order, PurchasePayment $payment)
    {
        $this->ensureOwner($request);

        if ((int) $payment->purchase_order_id !== (int) $purchase_order->id) {
            abort(404);
        }

        if ($payment->voided_at) {
            return back()->with('error', 'Pembayaran sudah di-VOID.');
        }

        DB::transaction(function () use ($payment, $purchase_order, $request) {
            $invoiceId = $payment->supplier_invoice_id; // ambil sebelum void

            $payment->voided_at = now();
            $payment->voided_by = (int) $request->user()->id;
            $payment->save();

            // ✅ Paling aman: void via journal_id jika ada
            if (! empty($payment->journal_id)) {
                $this->journalService->voidById((int) $payment->journal_id);
            } else {
                // fallback: void by source (pastikan JournalService memang set source_type/source_id)
                $this->journalService->voidBySource(JournalService::SRC_PURCHASE_PAYMENT, (int) $payment->id);
            }

            $this->recalcPaymentStatus($purchase_order);

            // Tahap 4: re-sync invoice jika payment ini terkait invoice
            if ($invoiceId) {
                $this->syncInvoicePaymentStatus($invoiceId);
            }
        });

        return back()->with('success', 'Pembayaran berhasil di-VOID.');
    }

    /**
     * Apply DP (offset DP 1151 ke AP 2101)
     * - bikin PurchasePayment type=dp_apply (tanpa kas/bank)
     * - jurnal: Dr AP (2101) Cr DP (1151)
     */
    public function applyDp(Request $request, PurchaseOrder $purchase_order)
    {
        $this->ensureOwner($request);

        if (($purchase_order->status ?? '') === 'cancelled') {
            return back()->with('error', 'PO cancelled tidak bisa diproses.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $amountReq = $this->toNumber($data['amount'] ?? 0);
        if ($amountReq <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal harus > 0.',
            ]);
        }

        // Hitung uang muka tersedia, termasuk alokasi pinjaman supplier.
        $dpTotal = (float) $purchase_order->activePayments()
            ->whereIn('type', ['dp', 'loan_apply'])
            ->sum('amount');
        $dpApplied = (float) $purchase_order->activePayments()->where('type', 'dp_apply')->sum('amount');
        $dpAvailable = PurchaseOrder::normalizePaymentRemainder($dpTotal - $dpApplied);

        // hitung hutang outstanding bersih (GRN posted - return posted - payment - dp_apply)
        $debt = $this->netDebtByGrn($purchase_order);
        $apOutstanding = $this->calcApOutstandingByGrn($purchase_order);

        if ($debt <= 0.0001) {
            throw ValidationException::withMessages([
                'amount' => 'Belum ada GRN POSTED, hutang belum terbentuk.',
            ]);
        }

        if ($dpAvailable <= 0.0001) {
            throw ValidationException::withMessages([
                'amount' => 'DP tidak tersedia (sudah habis di-offset atau belum ada DP).',
            ]);
        }

        if ($apOutstanding <= 0.0001) {
            throw ValidationException::withMessages([
                'amount' => 'Hutang sudah lunas, tidak ada yang bisa di-offset.',
            ]);
        }

        $amount = min($amountReq, $dpAvailable, $apOutstanding);
        $amount = round($amount, 2);

        DB::transaction(function () use ($request, $purchase_order, $data, $amountReq, &$amount) {
            $lockedOrder = PurchaseOrder::query()
                ->whereKey($purchase_order->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Recalculate both balances while the PO is locked. Ini mencegah
            // dua request Offset DP memakai saldo DP/AP yang sama.
            $dpTotal = (float) $lockedOrder->activePayments()
                ->whereIn('type', ['dp', 'loan_apply'])
                ->sum('amount');
            $dpApplied = (float) $lockedOrder->activePayments()->where('type', 'dp_apply')->sum('amount');
            $dpAvailable = PurchaseOrder::normalizePaymentRemainder($dpTotal - $dpApplied);
            $apOutstanding = $this->calcApOutstandingByGrn($lockedOrder);

            if ($dpAvailable <= 0.0001 || $apOutstanding <= 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo DP atau hutang sudah berubah. Muat ulang PO lalu coba lagi.',
                ]);
            }

            $amount = round(min($amountReq, $dpAvailable, $apOutstanding), 2);

            // Pastikan ada PaymentMethod khusus DP_APPLY (mode=credit)
            $pmId = PaymentMethod::query()
                ->where('code', 'DP_APPLY')
                ->value('id');

            if (! $pmId) {
                throw ValidationException::withMessages([
                    'amount' => 'PaymentMethod code=DP_APPLY belum ada. Buat dulu payment method "Offset DP".',
                ]);
            }

            $payment = PurchasePayment::create([
                'purchase_order_id' => (int) $lockedOrder->id,
                'date' => $data['date'],
                'payment_method_id' => (int) $pmId,
                'cash_account_id' => null,
                'type' => 'dp_apply',
                'amount' => $amount,
                'ref_no' => null,
                'notes' => $data['notes'] ?? 'Apply DP ke hutang (AP)',
                'created_by' => (int) $request->user()->id,
            ]);

            $journal = $this->journalService->postPurchasePayment(
                $payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod'])
            );

            // SAFETY: set journal_id jika JournalService return Journal
            if ($journal && empty($payment->journal_id) && ! empty($journal->id)) {
                $payment->journal_id = (int) $journal->id;
                $payment->save();
            }

            $this->recalcPaymentStatus($lockedOrder);
        });

        return back()->with('success', 'DP berhasil di-offset ke hutang.');
    }

    /**
     * Alokasikan sebagian saldo pinjaman supplier menjadi uang muka PO.
     * Tidak ada kas baru yang keluar:
     *   Dr Uang Muka Pembelian
     *   Cr Piutang Pinjaman Supplier
     */
    public function applySupplierLoan(Request $request, PurchaseOrder $purchase_order)
    {
        $this->ensureOwner($request);

        if (($purchase_order->status ?? '') === 'cancelled') {
            return back()->with('error', 'PO cancelled tidak bisa diproses.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'supplier_loan_id' => ['required', 'integer', 'exists:supplier_loans,id'],
            'amount' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $amountReq = $this->toNumber($data['amount'] ?? 0);
        if ($amountReq <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal alokasi harus > 0.']);
        }

        DB::transaction(function () use ($request, $purchase_order, $data, $amountReq) {
            $lockedOrder = PurchaseOrder::query()
                ->whereKey($purchase_order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $loan = SupplierLoan::query()
                ->whereKey((int) $data['supplier_loan_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $loan->supplier_id !== (int) $lockedOrder->supplier_id) {
                throw ValidationException::withMessages([
                    'supplier_loan_id' => 'Pinjaman harus berasal dari supplier yang sama dengan PO.',
                ]);
            }
            if (! in_array($loan->status, ['posted', 'settled'], true)) {
                throw ValidationException::withMessages([
                    'supplier_loan_id' => 'Pinjaman supplier harus POSTED terlebih dahulu.',
                ]);
            }

            $repaid = (float) $loan->repayments()
                ->where('status', 'posted')
                ->sum('amount');
            $allocated = (float) PurchasePayment::query()
                ->where('supplier_loan_id', $loan->id)
                ->where('type', 'loan_apply')
                ->whereNull('voided_at')
                ->sum('amount');
            $loanAvailable = max(0, (float) $loan->principal_amount - $repaid - $allocated);

            $poPaidOrAdvanced = (float) $lockedOrder->activePayments()
                ->whereIn('type', ['dp', 'loan_apply', 'payment'])
                ->sum('amount');
            $poAvailable = max(0, round((float) $lockedOrder->grand_total - $poPaidOrAdvanced, 2));
            $amount = round(min($amountReq, $loanAvailable, $poAvailable), 2);

            if ($loanAvailable <= 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo pinjaman supplier sudah habis.',
                ]);
            }
            if ($poAvailable <= 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Nilai PO sudah seluruhnya dialokasikan atau dibayar.',
                ]);
            }
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal alokasi tidak valid.',
                ]);
            }

            $paymentMethodId = (int) PaymentMethod::query()
                ->where('code', 'LOAN_APPLY')
                ->value('id');

            if ($paymentMethodId <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'PaymentMethod code=LOAN_APPLY belum tersedia.',
                ]);
            }

            $payment = PurchasePayment::create([
                'purchase_order_id' => (int) $lockedOrder->id,
                'supplier_loan_id' => (int) $loan->id,
                'date' => $data['date'],
                'payment_method_id' => $paymentMethodId,
                'cash_account_id' => null,
                'type' => 'loan_apply',
                'amount' => $amount,
                'ref_no' => null,
                'notes' => $data['notes'] ?? 'Alokasi saldo pinjaman supplier ke PO',
                'created_by' => (int) $request->user()->id,
            ]);

            $journal = $this->journalService->postPurchasePayment(
                $payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod', 'supplierLoan'])
            );

            if ($journal && empty($payment->journal_id) && ! empty($journal->id)) {
                $payment->journal_id = (int) $journal->id;
                $payment->save();
            }

            $this->recalcPaymentStatus($lockedOrder);
        });

        return back()->with('success', 'Saldo pinjaman supplier berhasil dialokasikan ke PO.');
    }

    // ======================================================================
    // INTERNAL: Payment Status (berdasarkan total pembayaran terhadap nilai PO)
    // ======================================================================

    /**
     * DP adalah uang yang sudah dibayarkan ke supplier, sehingga ikut dihitung
     * untuk status pembayaran PO. dp_apply hanya jurnal pemindahan DP ke AP,
     * bukan pembayaran baru dan tidak boleh dihitung dua kali.
     *
     * Status:
     * - unpaid: belum ada pembayaran
     * - partial: pembayaran masih di bawah nilai PO
     * - paid: pembayaran sama dengan nilai PO
     * - overpaid: pembayaran melebihi nilai PO (menjadi piutang supplier)
     */
    protected function recalcPaymentStatus(PurchaseOrder $order): void
    {
        $grand = round((float) $order->grand_total, 2);
        $eps = PurchaseOrder::paymentRoundingTolerance();

        $agg = $order->activePayments()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type IN ('dp', 'loan_apply', 'payment') THEN amount ELSE 0 END), 0) as paid
            ")
            ->first();

        $paid = round((float) ($agg->paid ?? 0), 2);

        if ($grand <= 0.0001 || $paid <= 0.0001) {
            $order->paid_amount = 0;
            $order->payment_status = 'unpaid';
            $order->save();
            $order->evaluateAutoClose();

            return;
        }

        $status = 'unpaid';
        if ($paid > $grand + $eps) {
            $status = 'overpaid';
        } elseif ($paid + $eps >= $grand) {
            $status = 'paid';
        } elseif ($paid > $eps) {
            $status = 'partial';
        }

        // Pembayaran adalah konfirmasi bahwa PO sudah diproses. Draft tidak
        // boleh tetap tampil sebagai dokumen aktif setelah ada pembayaran.
        if ($order->status === 'draft' && in_array($status, ['partial', 'paid', 'overpaid'], true)) {
            $order->status = 'approved';
            $order->approved_by = auth()->id();
            $order->approved_at = now();
        }

        // Jangan clamp nilai overpaid agar selisih piutang tetap terlihat.
        $order->paid_amount = $paid;
        $order->payment_status = $status;
        $order->save();

        $order->evaluateAutoClose();
    }

    /**
     * Outstanding hutang berbasis GRN posted bersih:
     * total_grn_posted - total_return_posted - total_payment(type=payment) - dp_apply
     */
    protected function calcApOutstandingByGrn(PurchaseOrder $order): float
    {
        return PurchaseOrder::normalizePaymentRemainder($this->rawApOutstandingByGrn($order));
    }

    /**
     * Saldo AP riil sebelum toleransi pembulatan diterapkan.
     */
    protected function rawApOutstandingByGrn(PurchaseOrder $order): float
    {
        $debt = (float) $this->netDebtByGrn($order);

        $agg = $order->activePayments()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as paid,
                COALESCE(SUM(CASE WHEN type = 'dp_apply' THEN amount ELSE 0 END), 0) as dp_applied
            ")
            ->first();

        $paid = (float) ($agg->paid ?? 0);
        $dpApplied = (float) ($agg->dp_applied ?? 0);

        return max(0, round($debt - $paid - $dpApplied, 2));
    }

    protected function netDebtByGrn(PurchaseOrder $order): float
    {
        return max(0, round($this->totalGrnPosted($order) - $this->totalPostedReturns($order), 2));
    }

    protected function totalGrnPosted(PurchaseOrder $order): float
    {
        return (float) $order->purchaseReceipts()
            ->where('status', 'posted')
            ->where(function ($q) {
                $q->whereNull('is_replacement')
                    ->orWhere('is_replacement', false);
            })
            ->sum('grand_total');
    }

    protected function totalPostedReturns(PurchaseOrder $order): float
    {
        return (float) PurchaseReturn::query()
            ->where('purchase_order_id', $order->id)
            ->where('status', 'posted')
            ->whereNull('voided_at')
            ->where(function ($q) {
                $q->whereNull('resolution_type')
                    ->orWhere('resolution_type', '!=', 'replacement');
            })
            ->sum('total');
    }

    /**
     * Posted, non-replacement GRNs with their currently available AP balance.
     * Used by combined payments to retain the GRN-level payment trail.
     */
    protected function receiptsWithOutstanding(PurchaseOrder $order)
    {
        return $order->purchaseReceipts()
            ->where('status', 'posted')
            ->where(function ($q) {
                $q->whereNull('is_replacement')
                    ->orWhere('is_replacement', false);
            })
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(function (PurchaseReceipt $receipt) {
                $returnTotal = (float) PurchaseReturn::query()
                    ->where('purchase_receipt_id', $receipt->id)
                    ->where('status', 'posted')
                    ->whereNull('voided_at')
                    ->where(function ($q) {
                        $q->whereNull('resolution_type')
                            ->orWhere('resolution_type', '!=', 'replacement');
                    })
                    ->sum('total');
                $paidTotal = (float) PurchasePayment::query()
                    ->where('purchase_receipt_id', $receipt->id)
                    ->whereNull('voided_at')
                    ->where('type', 'payment')
                    ->sum('amount');

                $receipt->receipt_outstanding = PurchaseOrder::normalizePaymentRemainder(
                    (float) $receipt->grand_total - $returnTotal - $paidTotal
                );

                return $receipt;
            })
            ->filter(fn (PurchaseReceipt $receipt) => $receipt->receipt_outstanding > 0.0001)
            ->values();
    }

    // ======================================================================
    // INTERNAL: Mode + Validation
    // ======================================================================

    protected function validateCashAccount(?int $cashAccountId, string $mode): void
    {
        if (! $cashAccountId) {
            throw ValidationException::withMessages([
                'cash_account_id' => $mode === 'cash'
                ? 'Untuk CASH, wajib pilih akun 1101 (Kas).'
                : 'Untuk TRANSFER, wajib pilih akun 1111/1112/1113/1114.',
            ]);
        }

        $acc = Account::query()->find($cashAccountId);
        if (! $acc || (int) ($acc->is_cash ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Akun yang dipilih bukan akun kas/bank.',
            ]);
        }

        $code = (string) ($acc->code ?? '');

        if ($mode === 'cash' && $code !== '1101') {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Untuk CASH, akun harus 1101 (Kas).',
            ]);
        }

        if ($mode === 'transfer' && ! in_array($code, self::TRANSFER_BANK_CODES, true)) {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Untuk TRANSFER, pilih akun bank/ewallet: 1111/1112/1113/1114.',
            ]);
        }
    }

    protected function ensureOwner(Request $request): void
    {
        abort_unless($request->user()?->isOwner(), 403, 'Hanya owner yang boleh mengakses nominal pembayaran purchasing.');
    }

    /**
     * Deteksi mode pembayaran
     */
    protected function detectPaymentMode(PaymentMethod $pm): string
    {
        $mode = strtolower((string) ($pm->mode ?? ''));
        if (in_array($mode, ['cash', 'transfer', 'credit'], true)) {
            return $mode;
        }

        $code = strtoupper((string) ($pm->code ?? ''));

        if (str_contains($code, 'CASH')) {
            return 'cash';
        }

        if (str_contains($code, 'TRF') || str_contains($code, 'TRANSFER') || str_contains($code, 'BANK')) {
            return 'transfer';
        }

        if (str_contains($code, 'TEMPO') || str_contains($code, 'CREDIT')) {
            return 'credit';
        }

        return 'unknown';
    }

    /**
     * Resolve cash_account_id:
     * - credit: selalu null
     * - user pilih -> pakai
     * - default_cash_account_id -> pakai
     * - fallback: cash=1101, transfer=1111
     */
    protected function resolveCashAccountId(PaymentMethod $pm, ?int $selectedAccountId): ?int
    {
        $mode = $this->detectPaymentMode($pm);

        if ($mode === 'credit') {
            return null;
        }

        if ($selectedAccountId) {
            return $selectedAccountId;
        }

        if (! empty($pm->default_cash_account_id)) {
            return (int) $pm->default_cash_account_id;
        }

        $fallbackCode = match ($mode) {
            'cash' => '1101',
            'transfer' => '1111',
            default => '1101',
        };

        return Account::query()
            ->where('code', $fallbackCode)
            ->where('is_active', 1)
            ->value('id');
    }

    // ======================================================================
    // INTERNAL: Supplier Invoice Payment Sync (Tahap 4)
    // ======================================================================

    /**
     * Recalculate paid_amount dan status di supplier_invoices
     * berdasarkan semua active payment yang terkait invoice tersebut.
     * Dipanggil setelah store() dan void() payment.
     */
    protected function syncInvoicePaymentStatus(int $invoiceId): void
    {
        // Guard: tabel supplier_invoices harus ada
        if (! \Illuminate\Support\Facades\Schema::hasTable('supplier_invoices')) {
            return;
        }

        $invoice = SupplierInvoice::find($invoiceId);
        if (! $invoice) {
            return;
        }

        // Void invoice tidak di-sync
        if ($invoice->status === 'void') {
            return;
        }

        // Hitung total payment aktif (non-void) yang terkait invoice ini
        $totalPaid = (float) PurchasePayment::query()
            ->where('supplier_invoice_id', $invoiceId)
            ->whereNull('voided_at')
            ->where('type', 'payment')
            ->sum('amount');

        $totalAmount = (float) $invoice->total_amount;
        $eps = PurchaseOrder::paymentRoundingTolerance();

        $newStatus = 'posted';
        if ($totalPaid >= $totalAmount - $eps && $totalAmount > 0) {
            $newStatus = 'paid';
        } elseif ($totalPaid > $eps) {
            $newStatus = 'partial_paid';
        }

        $invoice->paid_amount = round($totalPaid, 2);
        $invoice->status = $newStatus;
        $invoice->save();
    }

    /**
     * Normalisasi angka indo
     */
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
