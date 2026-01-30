<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class JournalService
{
    // ====== COA CODES ======
    public const CODE_AP = '2101'; // Hutang Dagang
    public const CODE_INV_RAW = '1201'; // Persediaan Bahan Baku
    public const CODE_ADV_PURCHASE = '1151'; // Uang Muka Pembelian
    public const CODE_SUPPLIER_CLAIM = '1305'; // Piutang Supplier

    // ====== SOURCE TYPES ======
    public const SRC_PURCHASE_PAYMENT = 'purchase_payment';
    public const SRC_GRN_ACCRUAL = 'purchase_receipt_post';
    public const SRC_GRN_APPLY_DP = 'purchase_dp_apply';
    public const SRC_PURCHASE_RETURN = 'purchase_return_post';
    public const SRC_PURCHASE_RETURN_INV = 'purchase_return_inv';
    public const SRC_PURCHASE_RETURN_EXP = 'purchase_return_exp';

    // public const SRC_PO_EXPENSE_APPROVE = 'purchase_order_expense_approve';

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
     * Void journal (soft) + buat reversal journal (AKTIF).
     * - jurnal lama di-void (voided_at terisi)
     * - reversal journal dibuat (swap debit/credit) dan tetap aktif (voided_at NULL)
     */
    public function void(Journal $journal, ?string $reason = null): Journal
    {
        return DB::transaction(function () use ($journal, $reason) {

            $journal = Journal::query()
                ->with('lines')
                ->whereKey($journal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($journal->voided_at) {
                throw ValidationException::withMessages([
                    'journal' => 'Journal sudah void.',
                ]);
            }

            if ($journal->lines->count() < 2) {
                throw ValidationException::withMessages([
                    'journal' => 'Journal lines tidak valid untuk reversal.',
                ]);
            }

            // 1) Void jurnal lama
            $journal->forceFill([
                'voided_at' => now(),
            ])->save();

            // 2) Deskripsi reversal
            $revDesc = trim(
                'Reversal: ' . ($journal->description ?? '-') .
                ($reason ? " | {$reason}" : '')
            );

            // 3) Buat reversal journal (AKTIF)
            $rev = Journal::create([
                'date' => $journal->date,
                'description' => $revDesc,
                'source_type' => $journal->source_type,
                'source_id' => $journal->source_id,
                'posted_at' => now(),
                // voided_at sengaja NULL
            ]);

            $rev->lines()->createMany(
                $journal->lines->map(function ($l) {
                    return [
                        'account_id' => (int) $l->account_id,
                        'debit' => (float) $l->credit,
                        'credit' => (float) $l->debit,
                    ];
                })->all()
            );

            return $rev;
        });
    }

    /**
     * Void by source (helper)
     */
    public function voidBySource(string $sourceType, int $sourceId, ?string $reason = null): ?Journal
    {
        return DB::transaction(function () use ($sourceType, $sourceId, $reason) {

            $j = Journal::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->whereNull('voided_at')
                ->lockForUpdate()
                ->first();

            if (!$j) {
                return null;
            }

            return $this->void($j, $reason);
        });
    }

    /**
     * Void by id (helper) – kompatibel dengan PurchasePaymentController kamu.
     */
    public function voidById(int $journalId, ?string $reason = null): Journal
    {
        return DB::transaction(function () use ($journalId, $reason) {
            $j = Journal::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($journalId);

            return $this->void($j, $reason);
        });
    }

    // =========================================================
    // PURCHASING
    // =========================================================

    /**
     * Post journal untuk PurchasePayment:
     * - type=dp      : Dr 1151 / Cr Kas/Bank
     * - type=payment : Dr 2101 / Cr Kas/Bank
     * - type=dp_apply: Dr 2101 / Cr 1151 (tanpa kas/bank)
     *
     * NOTE:
     * - mode credit/tempo: TIDAK boleh untuk payment (pelunasan), hanya dp atau dp_apply
     * - Idempotent via (source_type=SRC_PURCHASE_PAYMENT, source_id=payment_id)
     * - Set payment.journal_id
     */
    public function postPurchasePayment(PurchasePayment $payment): Journal
    {
        return DB::transaction(function () use ($payment) {

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

            // anti double click: kalau sudah punya journal_id dan journal masih ada
            if (!empty($payment->journal_id)) {
                $existing = Journal::query()->find((int) $payment->journal_id);
                if ($existing) {
                    return $existing;
                }
                // edge case: journal hilang -> reset agar bisa post ulang
                $payment->forceFill(['journal_id' => null])->save();
            }

            // row lock
            $locked = PurchasePayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if ($locked && !empty($locked->journal_id)) {
                $existing = Journal::query()->find((int) $locked->journal_id);
                if ($existing) {
                    return $existing;
                }
                $locked->forceFill(['journal_id' => null])->save();
            }

            $advId = $this->accountIdByCode(self::CODE_ADV_PURCHASE);
            $apId = $this->accountIdByCode(self::CODE_AP);

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

            // dp_apply (tanpa kas/bank)
            if ($payment->type === 'dp_apply') {
                $journal = $this->post(
                    $date,
                    $sourceType,
                    $sourceId,
                    trim("Apply DP ke Hutang {$poCode}"),
                    [
                        ['account_id' => $apId, 'debit' => $amount, 'credit' => 0],
                        ['account_id' => $advId, 'debit' => 0, 'credit' => $amount],
                    ]
                );

                $payment->forceFill(['journal_id' => $journal->id])->save();
                return $journal;
            }

            // dp / payment: wajib kas/bank untuk cash/transfer
            $mode = $this->detectPaymentModeFromMethod($payment->paymentMethod);

            if (in_array($mode, ['credit', 'tempo'], true)) {
                // credit/tempo: dp boleh, payment tidak boleh (controller kamu sudah larang payment)
                if ($payment->type !== 'dp') {
                    throw ValidationException::withMessages([
                        'payment' => 'Metode TEMPO/CREDIT tidak membuat jurnal kas/bank.',
                    ]);
                }
                throw ValidationException::withMessages([
                    'payment' => 'DP dengan mode CREDIT/TEMPO tidak membuat jurnal kas/bank. (DP credit seharusnya hanya catatan non-cash atau gunakan flow lain).',
                ]);
            }

            if (!$payment->cash_account_id) {
                throw ValidationException::withMessages([
                    'payment' => 'cash_account_id wajib untuk jurnal pembayaran (kas/bank).',
                ]);
            }

            $cashAccountId = (int) $payment->cash_account_id;
            $this->ensureAccountIsCash($cashAccountId);

            // dp
            if ($payment->type === 'dp') {
                $journal = $this->post(
                    $date,
                    $sourceType,
                    $sourceId,
                    trim("DP Pembelian {$poCode} {$suffix}"),
                    [
                        ['account_id' => $advId, 'debit' => $amount, 'credit' => 0],
                        ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount],
                    ]
                );

                $payment->forceFill(['journal_id' => $journal->id])->save();
                return $journal;
            }

            // payment (pelunasan hutang)
            $journal = $this->post(
                $date,
                $sourceType,
                $sourceId,
                trim("Pelunasan Hutang {$poCode} {$suffix}"),
                [
                    ['account_id' => $apId, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $cashAccountId, 'debit' => 0, 'credit' => $amount],
                ]
            );

            $payment->forceFill(['journal_id' => $journal->id])->save();
            return $journal;
        });
    }
    public function postGrnSplit(PurchaseReceipt $grn): void
    {
        DB::transaction(function () use ($grn) {

            $grn->loadMissing(['order', 'lines']);

            if (!$grn->order) {
                throw ValidationException::withMessages([
                    'grn' => 'GRN tidak punya PO.',
                ]);
            }

            $hasAlloc = Schema::hasColumn('purchase_receipt_lines', 'allocation');
            $hasExpAcc = Schema::hasColumn('purchase_receipt_lines', 'expense_account_id');

            if (!$hasAlloc) {
                throw ValidationException::withMessages([
                    'grn' => 'purchase_receipt_lines.allocation belum ada. Tambahkan kolom allocation agar bisa split jurnal.',
                ]);
            }

            // =========================
            // 1) INV JOURNAL (hpp) -> Dr INV / Cr AP
            // =========================
            $invAmount = (float) $grn->lines->where('allocation', 'hpp')->sum('line_total');

            if ($invAmount > 0.0001) {
                $invId = $this->accountIdByCode(self::CODE_INV_RAW); // 1201 (sementara 1 akun)
                $apId = $this->accountIdByCode(self::CODE_AP); // 2101

                $this->post(
                    $this->dateOnly($grn->date),
                    self::SRC_GRN_ACCRUAL_INV,
                    (int) $grn->id,
                    "GRN {$grn->code} - Inventory",
                    [
                        ['account_id' => $invId, 'debit' => round($invAmount, 2), 'credit' => 0],
                        ['account_id' => $apId, 'debit' => 0, 'credit' => round($invAmount, 2)],
                    ]
                );
            }

            // =========================
            // 2) EXP JOURNAL (expense) -> Dr EXP / Cr AP
            // =========================
            $expLines = $grn->lines->where('allocation', 'expense');

            if ($expLines->count() > 0) {

                if (!$hasExpAcc) {
                    throw ValidationException::withMessages([
                        'grn' => 'purchase_receipt_lines.expense_account_id belum ada. Tambahkan agar expense bisa dijurnal.',
                    ]);
                }

                $groups = [];
                $expTotal = 0.0;

                foreach ($expLines as $ln) {
                    $amt = (float) ($ln->line_total ?? 0);
                    if ($amt <= 0) {
                        continue;
                    }

                    $accId = (int) ($ln->expense_account_id ?? 0);
                    if ($accId <= 0) {
                        throw ValidationException::withMessages([
                            'grn' => 'Ada GRN expense line tapi expense_account_id kosong. Pastikan default_expense_account_id di master item, dan line ikut ke GRN.',
                        ]);
                    }

                    $groups[$accId] = ($groups[$accId] ?? 0) + $amt;
                    $expTotal += $amt;
                }

                $expTotal = round($expTotal, 2);

                if ($expTotal > 0) {
                    $apId = $this->accountIdByCode(self::CODE_AP);

                    $lines = [];
                    foreach ($groups as $accId => $amt) {
                        $lines[] = [
                            'account_id' => (int) $accId,
                            'debit' => round((float) $amt, 2),
                            'credit' => 0,
                        ];
                    }

                    $lines[] = [
                        'account_id' => (int) $apId,
                        'debit' => 0,
                        'credit' => $expTotal,
                    ];

                    $this->post(
                        $this->dateOnly($grn->date),
                        self::SRC_GRN_ACCRUAL_EXP,
                        (int) $grn->id,
                        "GRN {$grn->code} - Expense",
                        $lines
                    );
                }
            }
        });
    }

    /**
     * GRN Posted (accrual inventory vs AP) + apply DP.
     * (Tetap seperti versi kamu: hanya tempo/credit. Kamu bilang GRN nanti.)
     */
    public function postGrnAccrual(PurchaseReceipt $grn): void
    {
        DB::transaction(function () use ($grn) {

            $grn->loadMissing(['order.paymentMethod']);

            $po = $grn->order ?? null;
            if (!$po) {
                throw ValidationException::withMessages([
                    'grn' => 'GRN tidak punya PO.',
                ]);
            }

            $mode = $this->detectPaymentModeFromMethod($po->paymentMethod);

            // sementara: accrual hutang hanya relevan kalau tempo/credit
            if (!in_array($mode, ['credit', 'tempo'], true)) {
                return;
            }

            $amount = (float) ($grn->grand_total ?? 0);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'grn' => 'GRN grand_total harus > 0 untuk accrual.',
                ]);
            }

            $invId = $this->accountIdByCode(self::CODE_INV_RAW);
            $apId = $this->accountIdByCode(self::CODE_AP);
            $advId = $this->accountIdByCode(self::CODE_ADV_PURCHASE);

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
            if (!method_exists($po, 'activePayments')) {
                return;
            }

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
        });
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
