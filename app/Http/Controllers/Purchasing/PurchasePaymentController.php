<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
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
            ->with(['purchaseOrder.supplier', 'paymentMethod', 'cashAccount'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $q->whereHas('purchaseOrder', fn($s) => $s->where('supplier_id', $request->integer('supplier_id')));
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
            'total_dp'      => (float) ($summaryRows->get('dp')?->total ?? 0),
            'count'         => (int)   $summaryRows->sum('cnt'),
        ];

        $payments  = $q->paginate(30)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $cashAccounts   = Account::where('is_cash', true)->where('is_active', true)->orderBy('code')->get();

        // POs with outstanding debt for create form
        $openPos = PurchaseOrder::query()
            ->with('supplier')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereHas('purchaseReceipts', fn($s) => $s->where('status', 'posted'))
            ->orderByDesc('date')
            ->get(['id', 'code', 'date', 'supplier_id', 'grand_total', 'paid_amount', 'payment_status'])
            ->filter(fn (PurchaseOrder $po) => PurchaseOrder::normalizePaymentRemainder(
                (float) $po->grand_total - (float) $po->paid_amount
            ) > 0)
            ->values();

        return view('purchasing.purchase_payments.index', compact(
            'payments', 'summary', 'suppliers', 'paymentMethods', 'cashAccounts', 'openPos'
        ));
    }

    public function __construct(
        protected JournalService $journalService
    ) {}

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

        // Auto-link ke Supplier Invoice aktif milik PO ini (jika tidak dipilih manual)
        $supplierInvoiceId = !empty($data['supplier_invoice_id'])
            ? (int) $data['supplier_invoice_id']
            : SupplierInvoice::where('purchase_order_id', $purchase_order->id)
                ->whereIn('status', ['posted', 'partial_paid'])
                ->orderBy('invoice_date')
                ->value('id');

        DB::transaction(function () use ($purchase_order, $data, $amount, $request, $cashAccountId, $supplierInvoiceId) {
            $payment = PurchasePayment::create([
                'purchase_order_id' => (int) $purchase_order->id,
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
            if ($journal && empty($payment->journal_id) && !empty($journal->id)) {
                $payment->journal_id = (int) $journal->id;
                $payment->save();
            }

            $this->recalcPaymentStatus($purchase_order);

            // Tahap 4: sync paid_amount + status ke Supplier Invoice jika dipilih
            if ($supplierInvoiceId) {
                $this->syncInvoicePaymentStatus($supplierInvoiceId);
            }
        });

        return back()->with('success', 'Pembayaran tersimpan.');
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
            if (!empty($payment->journal_id)) {
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

        // hitung DP tersedia
        $dpTotal = (float) $purchase_order->activePayments()->where('type', 'dp')->sum('amount');
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

        DB::transaction(function () use ($request, $purchase_order, $data, $amount) {
            // Pastikan ada PaymentMethod khusus DP_APPLY (mode=credit)
            $pmId = PaymentMethod::query()
                ->where('code', 'DP_APPLY')
                ->value('id');

            if (!$pmId) {
                throw ValidationException::withMessages([
                    'amount' => 'PaymentMethod code=DP_APPLY belum ada. Buat dulu payment method "Offset DP".',
                ]);
            }

            $payment = PurchasePayment::create([
                'purchase_order_id' => (int) $purchase_order->id,
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
            if ($journal && empty($payment->journal_id) && !empty($journal->id)) {
                $payment->journal_id = (int) $journal->id;
                $payment->save();
            }

            $this->recalcPaymentStatus($purchase_order);
        });

        return back()->with('success', 'DP berhasil di-offset ke hutang.');
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
                COALESCE(SUM(CASE WHEN type IN ('dp', 'payment') THEN amount ELSE 0 END), 0) as paid
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
            ->sum('grand_total');
    }

    protected function totalPostedReturns(PurchaseOrder $order): float
    {
        return (float) PurchaseReturn::query()
            ->where('purchase_order_id', $order->id)
            ->where('status', 'posted')
            ->whereNull('voided_at')
            ->sum('total');
    }

    // ======================================================================
    // INTERNAL: Mode + Validation
    // ======================================================================

    protected function validateCashAccount(?int $cashAccountId, string $mode): void
    {
        if (!$cashAccountId) {
            throw ValidationException::withMessages([
                'cash_account_id' => $mode === 'cash'
                ? 'Untuk CASH, wajib pilih akun 1101 (Kas).'
                : 'Untuk TRANSFER, wajib pilih akun 1111/1112/1113/1114.',
            ]);
        }

        $acc = Account::query()->find($cashAccountId);
        if (!$acc || (int) ($acc->is_cash ?? 0) !== 1) {
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

        if ($mode === 'transfer' && !in_array($code, self::TRANSFER_BANK_CODES, true)) {
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

        if (!empty($pm->default_cash_account_id)) {
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
        if (!\Illuminate\Support\Facades\Schema::hasTable('supplier_invoices')) {
            return;
        }

        $invoice = SupplierInvoice::find($invoiceId);
        if (!$invoice) {
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
