<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Services\Accounting\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasePaymentController extends Controller
{
    // Bank/ewallet yang boleh untuk TRANSFER
    private const TRANSFER_BANK_CODES = ['1111', '1112', '1113', '1114'];

    public function __construct(
        protected JournalService $journalService
    ) {}

    public function store(Request $request, PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status === 'cancelled') {
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
        ]);

        $amount = $this->toNumber($data['amount']);
        if ($amount <= 0) {
            return back()->with('error', 'Nominal pembayaran harus > 0.');
        }

        /** @var PaymentMethod $pm */
        $pm = PaymentMethod::findOrFail((int) $data['payment_method_id']);
        $mode = $this->detectPaymentMode($pm); // cash|transfer|credit|unknown

        // =====================================================
        // 0) CREDIT/TEMPO itu bukan "pembayaran"
        // =====================================================
        if ($mode === 'credit') {
            throw ValidationException::withMessages([
                'payment_method_id' => 'Metode TEMPO/CREDIT tidak dicatat sebagai pembayaran. Gunakan CASH/TRANSFER untuk pembayaran. Hutang dicatat saat GRN diposting.',
            ]);
        }

        // =====================================================
        // 1) Resolve cash/bank (boleh override user)
        // =====================================================
        $cashAccountId = $this->resolveCashAccountId($pm, $data['cash_account_id'] ?? null);

        if (!$cashAccountId) {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Akun kas/bank wajib dipilih.',
            ]);
        }

        $acc = Account::query()->find($cashAccountId);
        if (!$acc || (int) ($acc->is_cash ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'cash_account_id' => 'Akun yang dipilih bukan akun kas/bank.',
            ]);
        }

        // TRANSFER: wajib bank tertentu (1111..1114)
        if ($mode === 'transfer') {
            $code = (string) ($acc->code ?? '');
            if (!in_array($code, self::TRANSFER_BANK_CODES, true)) {
                throw ValidationException::withMessages([
                    'cash_account_id' => 'Untuk TRANSFER, pilih akun bank/ewallet: 1111/1112/1113/1114.',
                ]);
            }
        }

        // =====================================================
        // 2) Validasi hutang outstanding hanya untuk PELUNASAN
        //    DP tidak mengurangi hutang (DP masuk 1151)
        // =====================================================
        if ($data['type'] === 'payment') {
            $outstanding = $this->calcApOutstandingByGrn($purchase_order);

            if ($outstanding <= 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'Tidak ada hutang yang bisa dibayar (belum ada GRN posted atau hutang sudah lunas).',
                ]);
            }

            if ($amount > $outstanding + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal melebihi sisa hutang.',
                ]);
            }
        }

        // (Opsional) batas DP supaya tidak kebablasan
        if ($data['type'] === 'dp') {
            $dpTotal = (float) $purchase_order->activePayments()->where('type', 'dp')->sum('amount');
            if ($dpTotal + $amount > (float) $purchase_order->grand_total + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Total DP melebihi nilai PO.',
                ]);
            }
        }

        DB::transaction(function () use ($purchase_order, $data, $amount, $request, $cashAccountId) {

            $payment = PurchasePayment::create([
                'purchase_order_id' => (int) $purchase_order->id,
                'date' => $data['date'],
                'payment_method_id' => (int) $data['payment_method_id'],
                'cash_account_id' => (int) $cashAccountId,
                'type' => $data['type'], // dp|payment
                'amount' => $amount,
                'ref_no' => $data['ref_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => (int) $request->user()->id,
            ]);

            // Jurnal: DP => 1151, Payment => 2101 (aturan di JournalService)
            $this->journalService->postPurchasePayment(
                $payment->fresh(['purchaseOrder', 'cashAccount', 'paymentMethod'])
            );

            $this->recalcPaymentStatus($purchase_order);
        });

        return back()->with('success', 'Pembayaran tersimpan.');
    }

    public function void(Request $request, PurchaseOrder $purchase_order, PurchasePayment $payment)
    {
        if ((int) $payment->purchase_order_id !== (int) $purchase_order->id) {
            abort(404);
        }

        if ($payment->voided_at) {
            return back()->with('error', 'Pembayaran sudah di-VOID.');
        }

        DB::transaction(function () use ($payment, $purchase_order, $request) {

            $payment->voided_at = now();
            $payment->voided_by = (int) $request->user()->id;
            $payment->save();

            // void journal by source (tidak butuh kolom journal_id)
            $this->journalService->voidBySource(JournalService::SRC_PURCHASE_PAYMENT, (int) $payment->id);

            $this->recalcPaymentStatus($purchase_order);
        });

        return back()->with('success', 'Pembayaran berhasil di-VOID.');
    }

    /**
     * paid_amount & payment_status untuk hutang
     * hanya menghitung type=payment (pelunasan hutang)
     */
    protected function recalcPaymentStatus(PurchaseOrder $order): void
    {
        $paid = (float) $order->activePayments()
            ->where('type', 'payment')
            ->sum('amount');

        // status hutang sebaiknya berbasis hutang real (GRN posted)
        $debt = $this->totalGrnPosted($order);

        $eps = 0.01;

        $status = 'unpaid';
        if ($paid > $eps && $paid + $eps < $debt) {
            $status = 'partial';
        } elseif ($paid + $eps >= $debt && $debt > 0) {
            $status = 'paid';
        }

        $order->paid_amount = round($paid, 2);
        $order->payment_status = $status;
        $order->save();
    }

    /**
     * Outstanding hutang berbasis GRN posted:
     * total_grn_posted - total_payment(type=payment)
     */
    protected function calcApOutstandingByGrn(PurchaseOrder $order): float
    {
        $debt = $this->totalGrnPosted($order);

        $paid = (float) $order->activePayments()
            ->where('type', 'payment')
            ->sum('amount');

        return max(0, round($debt - $paid, 2));
    }

    protected function totalGrnPosted(PurchaseOrder $order): float
    {
        return (float) $order->purchaseReceipts()
            ->where('status', 'posted')
            ->sum('grand_total');
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

        if (str_contains($code, 'TRF') || str_contains($code, 'TRANSFER')) {
            return 'transfer';
        }

        if (str_contains($code, 'TEMPO') || str_contains($code, 'CREDIT')) {
            return 'credit';
        }

        return 'unknown';
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

    /**
     * Resolve cash_account_id:
     * - user pilih -> pakai
     * - default_cash_account_id -> pakai
     * - fallback: cash=1101, transfer=1111
     */
    protected function resolveCashAccountId(PaymentMethod $pm, ?int $selectedAccountId): ?int
    {
        if ($selectedAccountId) {
            return $selectedAccountId;
        }

        if (!empty($pm->default_cash_account_id)) {
            return (int) $pm->default_cash_account_id;
        }

        $mode = $this->detectPaymentMode($pm);

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
}
