<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalService
{
    // ====== COA CODES (sesuai seeder kamu) ======
    public const CODE_AP = '2101'; // Hutang Dagang
    public const CODE_INV_RAW = '1201'; // Persediaan Bahan Baku
    public const CODE_ADV_PURCHASE = '1151'; // Uang Muka Pembelian

    // ====== SOURCE TYPES ======
    public const SRC_PURCHASE_PAYMENT = 'purchase_payment';
    public const SRC_GRN_ACCRUAL = 'purchase_receipt_post';
    public const SRC_GRN_APPLY_DP = 'purchase_dp_apply';

    /**
     * Post journal + lines (balanced).
     * Idempotent: jika source_type + source_id (aktif / belum void) sudah ada, return existing.
     *
     * $lines format:
     * [
     *   ['account_id' => 1, 'debit' => 10000, 'credit' => 0],
     *   ['account_id' => 2, 'debit' => 0, 'credit' => 10000],
     * ]
     */
    public function post(
        string $date,
        string $sourceType,
        ?int $sourceId,
        string $description,
        array $lines
    ): Journal {
        return DB::transaction(function () use ($date, $sourceType, $sourceId, $description, $lines) {

            $date = $this->dateOnly($date);

            if (count($lines) < 2) {
                throw ValidationException::withMessages([
                    'journal' => 'Journal lines minimal 2 baris.',
                ]);
            }

            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($lines as $i => $line) {
                if (!isset($line['account_id'])) {
                    throw ValidationException::withMessages([
                        'journal' => "Line #{$i}: account_id wajib.",
                    ]);
                }

                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit < 0 || $credit < 0) {
                    throw ValidationException::withMessages([
                        'journal' => "Line #{$i}: debit/credit tidak boleh negatif.",
                    ]);
                }

                // harus salah satu, tidak boleh dua-duanya, tidak boleh dua-duanya 0
                if (($debit > 0 && $credit > 0) || ($debit == 0 && $credit == 0)) {
                    throw ValidationException::withMessages([
                        'journal' => "Line #{$i}: isi salah satu, debit atau credit.",
                    ]);
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (abs($totalDebit - $totalCredit) > 0.0001) {
                throw ValidationException::withMessages([
                    'journal' => 'Journal tidak balance. Total debit harus sama dengan total credit.',
                ]);
            }

            // ✅ Idempotent hanya kalau sourceId tersedia (tidak null)
            if ($sourceId !== null) {
                $existing = Journal::query()
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->whereNull('voided_at')
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $journal = Journal::create([
                'date' => $date,
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany(array_map(function ($line) {
                return [
                    'account_id' => (int) $line['account_id'],
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                ];
            }, $lines));

            return $journal;
        });
    }

    /**
     * Void journal (soft).
     */
    public function void(Journal $journal): Journal
    {
        if ($journal->voided_at) {
            return $journal;
        }

        $journal->update([
            'voided_at' => now(),
        ]);

        return $journal;
    }

    /**
     * Void by source (helper)
     */
    public function voidBySource(string $sourceType, int $sourceId): ?Journal
    {
        $j = Journal::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereNull('voided_at')
            ->first();

        if (!$j) {
            return null;
        }

        return $this->void($j);
    }

    // =========================================================
    // PURCHASING
    // =========================================================

    /**
     * Payment (uang keluar):
     * - type=dp     : Dr Uang Muka Pembelian (1151), Cr Kas/Bank
     * - type=payment: Dr Hutang Dagang (2101), Cr Kas/Bank
     *
     * NOTE:
     * - Jika payment method = credit/tempo -> tidak posting jurnal kas/bank (karena tidak ada uang keluar).
     * - Tidak menyimpan journal_id ke payment (agar tidak tergantung kolom journal_id).
     */
    public function postPurchasePayment(PurchasePayment $payment): Journal
    {
        $payment->loadMissing(['purchaseOrder', 'paymentMethod', 'cashAccount']);

        if ($payment->voided_at) {
            throw ValidationException::withMessages([
                'payment' => 'Payment sudah void.',
            ]);
        }

        $amount = (float) $payment->amount;
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'payment' => 'Amount harus > 0.',
            ]);
        }

        // mode: cash/transfer/credit/tempo
        $mode = $this->detectPaymentModeFromMethod($payment->paymentMethod);

        if (in_array($mode, ['credit', 'tempo'], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Metode TEMPO/CREDIT tidak membuat jurnal kas/bank. Pembayaran kas/bank hanya untuk cash/transfer.',
            ]);
        }

        if (!$payment->cash_account_id) {
            throw ValidationException::withMessages([
                'payment' => 'cash_account_id wajib untuk jurnal pembayaran (kas/bank).',
            ]);
        }

        $cashAccountId = (int) $payment->cash_account_id;
        $this->ensureAccountIsCash($cashAccountId);

        $advId = $this->accountIdByCode(self::CODE_ADV_PURCHASE); // 1151
        $apId = $this->accountIdByCode(self::CODE_AP); // 2101

        $po = $payment->purchaseOrder;
        $poCode = $po?->code ?? '-';

        $date = $this->dateOnly($payment->date);

        $pmName = $payment->paymentMethod?->name;
        $cashName = $payment->cashAccount?->name;
        $suffix = trim(implode(' ', array_filter([
            $pmName ? "via {$pmName}" : null,
            $cashName ? "({$cashName})" : null,
        ])));

        $sourceType = self::SRC_PURCHASE_PAYMENT;
        $sourceId = (int) $payment->id;

        // ✅ DP -> 1151, Pelunasan -> 2101
        if ($payment->type === 'dp') {
            return $this->post(
                $date,
                $sourceType,
                $sourceId,
                trim("DP Pembelian {$poCode} {$suffix}"),
                [
                    ['account_id' => $advId, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount],
                ]
            );
        }

        return $this->post(
            $date,
            $sourceType,
            $sourceId,
            trim("Pelunasan Hutang {$poCode} {$suffix}"),
            [
                ['account_id' => $apId, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    /**
     * GRN Posted (accrual inventory vs AP) + apply DP.
     *
     * - Jurnal 1 (accrual):
     *   Dr Persediaan (1201) / Cr Hutang Dagang (2101)
     *
     * - Jurnal 2 (apply DP):
     *   Dr Hutang Dagang (2101) / Cr Uang Muka (1151)
     *
     * Idempotent by:
     * - SRC_GRN_ACCRUAL source_id = grn id
     * - SRC_GRN_APPLY_DP source_id = grn id
     */
    public function postGrnAccrual(PurchaseReceipt $grn): void
    {
        $grn->loadMissing(['order.paymentMethod']);

        $po = $grn->order ?? null;
        if (!$po) {
            throw ValidationException::withMessages([
                'grn' => 'GRN tidak punya PO.',
            ]);
        }

        $mode = $this->detectPaymentModeFromMethod($po->paymentMethod);

        // ✅ accrual hutang hanya relevan kalau tempo/credit
        // kalau kamu mau selalu accrual apapun metodenya, hapus if ini.
        if (!in_array($mode, ['credit', 'tempo'], true)) {
            return;
        }

        $amount = (float) ($grn->grand_total ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'grn' => 'GRN grand_total harus > 0 untuk accrual.',
            ]);
        }

        $invId = $this->accountIdByCode(self::CODE_INV_RAW); // 1201
        $apId = $this->accountIdByCode(self::CODE_AP); // 2101
        $advId = $this->accountIdByCode(self::CODE_ADV_PURCHASE); // 1151

        $date = $this->dateOnly($grn->date);

        // 1) Accrual inventory vs AP
        $this->post(
            $date,
            self::SRC_GRN_ACCRUAL,
            (int) $grn->id,
            "GRN {$grn->code} - Hutang Dagang",
            [
                ['account_id' => $invId, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $apId, 'debit' => 0, 'credit' => $amount],
            ]
        );

        // 2) Apply DP dari PO (hanya DP aktif)
        $dpTotal = (float) $po->activePayments()
            ->where('type', 'dp')
            ->sum('amount');

        if ($dpTotal <= 0.0001) {
            return;
        }

        $apply = min($dpTotal, $amount);

        $this->post(
            $date,
            self::SRC_GRN_APPLY_DP,
            (int) $grn->id,
            "Apply DP {$po->code} ke Hutang",
            [
                ['account_id' => $apId, 'debit' => $apply, 'credit' => 0],
                ['account_id' => $advId, 'debit' => 0, 'credit' => $apply],
            ]
        );
    }

    // =========================================================
    // HELPERS
    // =========================================================

    protected function accountIdByCode(string $code): int
    {
        $acc = Account::query()
            ->where('code', $code)
            ->where('is_active', 1)
            ->first();

        if (!$acc) {
            throw ValidationException::withMessages([
                'account' => "Account code {$code} tidak ditemukan / tidak aktif.",
            ]);
        }

        return (int) $acc->id;
    }

    /**
     * Mode deteksi dari PaymentMethod.
     * Support: cash, transfer, credit, tempo (fallback dari code)
     */
    protected function detectPaymentModeFromMethod($pm): string
    {
        if (!$pm) {
            return 'unknown';
        }

        $mode = strtolower((string) ($pm->mode ?? ''));
        if (in_array($mode, ['cash', 'transfer', 'credit', 'tempo'], true)) {
            return $mode;
        }

        $code = strtoupper((string) ($pm->code ?? ''));

        if (str_contains($code, 'CASH')) {
            return 'cash';
        }
        if (str_contains($code, 'TRF') || str_contains($code, 'TRANSFER')) {
            return 'transfer';
        }
        if (str_contains($code, 'TEMPO')) {
            return 'tempo';
        }
        if (str_contains($code, 'CREDIT')) {
            return 'credit';
        }

        return 'unknown';
    }

    /**
     * Pastikan accountId memang akun kas/bank.
     */
    protected function ensureAccountIsCash(int $accountId): void
    {
        $acc = Account::query()->whereKey($accountId)->first();
        if (!$acc) {
            throw ValidationException::withMessages([
                'account' => "Account id {$accountId} tidak ditemukan.",
            ]);
        }

        if ((int) ($acc->is_cash ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'account' => "Akun {$acc->code} bukan akun kas/bank.",
            ]);
        }
    }

    protected function dateOnly($value): string
    {
        return Carbon::parse($value)->toDateString();
    }
}
