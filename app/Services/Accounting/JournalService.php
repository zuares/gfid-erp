<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class JournalService
{
    // ====== COA CODES ======
    public const CODE_AP = '2101'; // Hutang Dagang
    public const CODE_INV_RAW = '1201'; // Persediaan Bahan Baku
    public const CODE_INV_RM = self::CODE_INV_RAW;
    public const CODE_INV_WIP = '1202'; // Persediaan WIP
    public const CODE_INV_FG  = '1203'; // Persediaan Barang Jadi
    public const CODE_INV_DEFECT = '1204'; // Persediaan Barang Cacat
    public const CODE_INV_PACKAGING = '1205'; // Persediaan Packaging
    public const CODE_PAYABLE = self::CODE_AP;
    public const CODE_PAYROLL_PAYABLE = '2102';
    public const CODE_HPP = '5101'; // Harga Pokok Penjualan
    public const CODE_ADV_PURCHASE = '1151'; // Uang Muka Pembelian
    public const CODE_SUPPLIER_CLAIM = '1305'; // Piutang Supplier
    public const CODE_EXP_OPEX = '6101'; // Biaya Operasional Umum (untuk selisih adjustment)

    // ====== SOURCE TYPES ======
    public const SRC_PURCHASE_PAYMENT = 'purchase_payment';
    public const SRC_GRN_ACCRUAL = 'purchase_receipt_post';
    public const SRC_GRN_ACCRUAL_INV = 'grn_inv';
    public const SRC_GRN_ACCRUAL_EXP = 'grn_exp';
    public const SRC_PURCHASE_RECEIPT = 'purchase_receipt';
    public const SRC_GRN_APPLY_DP = 'purchase_dp_apply';
    public const SRC_PURCHASE_RETURN = 'purchase_return_post';
    public const SRC_PURCHASE_RETURN_INV = 'purchase_return_inv';
    public const SRC_PURCHASE_RETURN_EXP = 'purchase_return_exp';
    public const SRC_FINISHING_JOB = 'finishing_job';
    public const SRC_FINISHING_BOM = 'finishing_bom';
    public const SRC_CUTTING_JOB = 'cutting_job';
    public const SRC_CUTTING_WIP = 'cutting_wip';
    public const SRC_SEWING_PICKUP = 'App\\Models\\SewingPickup';
    public const SRC_SEWING_PICKUP_SUPPLY = 'sewing_pickup_supply';
    public const SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP = 'sewing_pickup_supply_followup';
    public const SRC_SEWING_PICKUP_SUPPLY_VOID_LINE = 'sewing_pickup_supply_void_line';
    public const SRC_SEWING_RETURN_OK = 'sewing_return_ok';
    public const SRC_SEWING_RETURN_REJECT = 'sewing_return_reject';
    public const SRC_SEWING_REWORK_OK = 'sewing_reject_rework_ok';
    public const SRC_SHIPMENT_COGS = 'shipment_cogs';
    public const SRC_WIP_FIN_ADJUSTMENT = 'wip_fin_adjustment';
    public const SRC_INVENTORY_ADJUSTMENT = 'inventory_adjustment';

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
     * Void journal (soft) + simpan reversal sebagai jejak audit.
     *
     * Seluruh laporan mengabaikan jurnal dengan voided_at terisi. Karena itu
     * jurnal asli dan reversal harus sama-sama berstatus void agar nilainya
     * tidak terhitung dua kali.
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

            // 3) Simpan reversal untuk audit, bukan sebagai jurnal aktif baru.
            $rev = Journal::create([
                'date' => $journal->date,
                'description' => $revDesc,
                'source_type' => $journal->source_type,
                'source_id' => $journal->source_id,
                'posted_at' => now(),
                'voided_at' => now(),
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
    // PRODUCTION
    // =========================================================

    /**
     * Post jurnal untuk FinishingJob (WIP → Barang Jadi / Barang Cacat).
     *
     * Dipanggil saat FinishingJob di-post. Idempotent via (source_type, source_id).
     *
     * Per line finishing job:
     *   qty_ok   → Dr 1203 Barang Jadi / Cr 1202 WIP
     *   qty_reject → Dr 1204 Barang Cacat / Cr 1202 WIP
     *
     * Nilai (amount) = qty × unit_cost dari inventory mutation yang sudah terjadi.
     * Kalau unit_cost tidak bisa ditentukan (0), journal di-skip untuk baris tsb.
     */
    public function postFinishingJob(\App\Models\FinishingJob $job): ?Journal
    {
        // Idempotent guard
        $existing = Journal::query()
            ->where('source_type', self::SRC_FINISHING_JOB)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $job->loadMissing(['lines']);

        if ($job->lines->isEmpty()) {
            return null;
        }

        $wipId    = $this->accountIdByCode(self::CODE_INV_WIP);
        $fgId     = $this->accountIdByCode(self::CODE_INV_FG);
        $defectId = $this->accountIdByCode(self::CODE_INV_DEFECT);

        $wipWarehouseId = (int) \App\Models\Warehouse::where('code', 'WIP-FIN')->value('id');

        // Aggregate lines per item_id (satu job bisa punya banyak lines item yang sama)
        $byItem = [];
        foreach ($job->lines as $line) {
            $itemId    = (int) $line->item_id;
            $qtyOk     = (int) round((float) ($line->qty_ok     ?? 0));
            $qtyReject = (int) round((float) ($line->qty_reject ?? 0));

            if (!isset($byItem[$itemId])) {
                $byItem[$itemId] = ['qty_ok' => 0, 'qty_reject' => 0];
            }
            $byItem[$itemId]['qty_ok']     += $qtyOk;
            $byItem[$itemId]['qty_reject'] += $qtyReject;
        }

        // Ambil total nilai keluar WIP-FIN per item dari inventory_mutations
        // Gunakan SUM(ABS(total_cost)) agar multi-line per item ter-capture semua
        $mutCosts = DB::table('inventory_mutations')
            ->where('source_type', \App\Models\FinishingJob::class)
            ->where('source_id', $job->id)
            ->where('warehouse_id', $wipWarehouseId)
            ->where('qty_change', '<', 0)
            ->groupBy('item_id')
            ->selectRaw('item_id, SUM(ABS(total_cost)) as total_cost, SUM(ABS(qty_change)) as total_qty')
            ->get()
            ->keyBy('item_id');

        $journalLines = [];
        $totalWip     = 0.0;

        foreach ($byItem as $itemId => $agg) {
            $qtyOk     = $agg['qty_ok'];
            $qtyReject = $agg['qty_reject'];
            $qtyUsed   = $qtyOk + $qtyReject;

            if ($qtyUsed <= 0) {
                continue;
            }

            $mut = $mutCosts->get($itemId);
            if (!$mut || (float) $mut->total_cost <= 0) {
                continue; // tidak ada nilai → skip
            }

            // Total amount untuk seluruh qty yang keluar dari WIP-FIN item ini
            $totalCost = (float) $mut->total_cost;

            // Proporsi: qty_ok dan qty_reject dibagi dari total mutation qty
            $mutQty = (float) $mut->total_qty;
            if ($mutQty <= 0) {
                continue;
            }

            $amountOk     = round($totalCost * ($qtyOk     / $mutQty), 2);
            $amountReject = round($totalCost * ($qtyReject / $mutQty), 2);

            if ($amountOk > 0) {
                $journalLines[] = ['account_id' => $fgId,     'debit' => $amountOk,     'credit' => 0];
                $totalWip += $amountOk;
            }

            if ($amountReject > 0) {
                $journalLines[] = ['account_id' => $defectId, 'debit' => $amountReject, 'credit' => 0];
                $totalWip += $amountReject;
            }
        }

        if (empty($journalLines) || $totalWip <= 0) {
            return null;
        }

        $bomCost = $this->finishingBomCostForJob((int) $job->id);
        if ($bomCost > 0) {
            $baseWip = round($totalWip, 2);
            $totalBomAllocated = 0.0;
            $lastDebitIndex = null;

            foreach ($journalLines as $index => $line) {
                if (($line['debit'] ?? 0) > 0) {
                    $lastDebitIndex = $index;
                }
            }

            foreach ($journalLines as $index => &$line) {
                $debit = round((float) ($line['debit'] ?? 0), 2);
                if ($debit <= 0) {
                    continue;
                }

                if ($index === $lastDebitIndex) {
                    $allocation = round($bomCost - $totalBomAllocated, 2);
                } else {
                    $allocation = $baseWip > 0 ? round($bomCost * ($debit / $baseWip), 2) : 0.0;
                }

                if ($allocation <= 0) {
                    continue;
                }

                $line['debit'] = round($debit + $allocation, 2);
                $totalBomAllocated = round($totalBomAllocated + $allocation, 2);
            }
            unset($line);

            $totalWip = round($totalWip + $totalBomAllocated, 2);
        }

        // Sisi kredit: WIP keluar (total semua item)
        $journalLines[] = ['account_id' => $wipId, 'debit' => 0, 'credit' => round($totalWip, 2)];

        return $this->post(
            $this->dateOnly($job->date),
            self::SRC_FINISHING_JOB,
            (int) $job->id,
            "Finishing {$job->code} — WIP → Barang Jadi",
            $journalLines
        );
    }

    /**
     * Post jurnal konsumsi BOM finishing.
     *
     * Mutasi finishing_bom disimpan per finishing_job_line_id:
     *   inventory_mutations.source_type = finishing_bom
     *   inventory_mutations.source_id   = finishing_job_lines.id
     *
     * Jurnal dibuat agregat per finishing job:
     *   Dr 1202 Persediaan WIP
     *   Cr 1201 Persediaan Bahan Baku
     */
    public function postFinishingBom(\App\Models\FinishingJob $job): ?Journal
    {
        $existing = Journal::query()
            ->where('source_type', self::SRC_FINISHING_BOM)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $rows = DB::table('inventory_mutations as im')
            ->join('finishing_job_lines as fjl', 'fjl.id', '=', 'im.source_id')
            ->where('im.source_type', 'finishing_bom')
            ->where('fjl.finishing_job_id', (int) $job->id)
            ->where('im.qty_change', '<', 0)
            ->groupBy('im.item_id')
            ->selectRaw('im.item_id, SUM(ABS(im.total_cost)) as total_cost')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $wipId = $this->accountIdByCode(self::CODE_INV_WIP);
        $rmId = $this->accountIdByCode(self::CODE_INV_RAW);

        $totalCost = 0.0;
        $creditLines = [];

        foreach ($rows as $row) {
            $amount = round((float) ($row->total_cost ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $totalCost += $amount;
            $creditLines[] = [
                'account_id' => $rmId,
                'debit' => 0,
                'credit' => $amount,
            ];
        }

        $totalCost = round($totalCost, 2);
        if ($totalCost <= 0 || empty($creditLines)) {
            return null;
        }

        $journalLines = [[
            'account_id' => $wipId,
            'debit' => $totalCost,
            'credit' => 0,
        ]];

        array_push($journalLines, ...$creditLines);

        return $this->post(
            $this->dateOnly($job->date),
            self::SRC_FINISHING_BOM,
            (int) $job->id,
            "Finishing {$job->code} — Konsumsi BOM",
            $journalLines
        );
    }

    /**
     * Cutting material keluar dari RM dan menjadi WIP cutting.
     *
     * Jurnal:
     *   Dr 1202 Persediaan WIP
     *   Cr 1201 Persediaan Bahan Baku
     */
    public function postCuttingJob(\App\Models\CuttingJob $job): ?Journal
    {
        return $this->postInventoryMovementFromMutations(
            journalSourceType: self::SRC_CUTTING_JOB,
            journalSourceId: (int) $job->id,
            mutationSourceType: self::SRC_CUTTING_JOB,
            mutationSourceId: (int) $job->id,
            date: $this->dateOnly($job->date),
            description: "Cutting {$job->code} — Bahan baku → WIP",
            debitAccountCode: self::CODE_INV_WIP,
            creditAccountCode: self::CODE_INV_RAW,
            direction: 'out'
        );
    }

    /**
     * Hasil QC cutting masuk WIP-CUT.
     *
     * Akun WIP internal masih satu akun (1202), jadi jurnal ini menjadi
     * Dr 1202 / Cr 1202 sebagai jejak movement produksi yang idempotent.
     */
    public function postCuttingWip(\App\Models\CuttingJob $job, ?string $date = null): ?Journal
    {
        $existing = Journal::query()
            ->where('source_type', self::SRC_CUTTING_WIP)
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->first();
        if ($existing) return $existing;

        $rawCost = $this->mutationAmount(self::SRC_CUTTING_JOB, (int) $job->id, 'out');
        $okCost = $this->mutationAmount(self::SRC_CUTTING_WIP, (int) $job->id, 'in');
        $rejectCost = $this->mutationAmount('cutting_reject', (int) $job->id, 'in');
        $outputCost = round($okCost + $rejectCost, 2);
        $laborCost = round($outputCost - $rawCost, 2);

        if ($outputCost <= 0 || $rawCost <= 0 || $laborCost < -0.01) return null;
        if (abs($laborCost) <= 0.01) {
            $rawCost = $outputCost;
            $laborCost = 0.0;
        }

        $lines = [];
        if ($okCost > 0) $lines[] = ['account_id' => $this->accountIdByCode(self::CODE_INV_WIP), 'debit' => $okCost, 'credit' => 0];
        if ($rejectCost > 0) $lines[] = ['account_id' => $this->accountIdByCode(self::CODE_INV_DEFECT), 'debit' => $rejectCost, 'credit' => 0];
        $lines[] = ['account_id' => $this->accountIdByCode(self::CODE_INV_WIP), 'debit' => 0, 'credit' => $rawCost];
        if ($laborCost > 0.01) {
            $lines[] = ['account_id' => $this->accountIdByCode(self::CODE_PAYROLL_PAYABLE), 'debit' => 0, 'credit' => $laborCost];
        }

        return $this->post(
            $this->dateOnly($date ?: $job->date),
            self::SRC_CUTTING_WIP,
            (int) $job->id,
            "Cutting {$job->code} — Hasil QC + Upah Cutting",
            $lines
        );
    }

    /**
     * Ambil jahit memindahkan stok dari WIP-CUT ke WIP-SEW (material cost saja).
     * Upah jahit TIDAK diakui di sini — diakui saat Setoran Jahit OK (postSewingReturnOk).
     */
    public function postSewingPickup(\App\Models\SewingPickup $pickup): ?Journal
    {
        // WIP-SEW dinilai material only; postValueAddedTransfer tidak diperlukan.
        // outCost (WIP-CUT keluar) == inCost (WIP-SEW masuk) → tidak ada selisih upah.
        return $this->postInventoryMovementFromMutations(
            journalSourceType: self::SRC_SEWING_PICKUP,
            journalSourceId: (int) $pickup->id,
            mutationSourceType: \App\Models\SewingPickup::class,
            mutationSourceId: (int) $pickup->id,
            date: $this->dateOnly($pickup->date),
            description: "Ambil Jahit {$pickup->code} — WIP-CUT → WIP-SEW",
            debitAccountCode: self::CODE_INV_WIP,
            creditAccountCode: self::CODE_INV_WIP,
            direction: 'out'
        );
    }

    /**
     * Kelengkapan jahit (rib, label, karet, dll) keluar dari RM dan masuk nilai WIP.
     */
    public function postSewingPickupSupply(\App\Models\SewingPickup $pickup): ?Journal
    {
        return $this->postInventoryMovementFromMutations(
            journalSourceType: self::SRC_SEWING_PICKUP_SUPPLY,
            journalSourceId: (int) $pickup->id,
            mutationSourceType: self::SRC_SEWING_PICKUP_SUPPLY,
            mutationSourceId: (int) $pickup->id,
            date: $this->dateOnly($pickup->date),
            description: "Ambil Jahit {$pickup->code} — Kelengkapan Jahit RM → WIP",
            debitAccountCode: self::CODE_INV_WIP,
            creditAccountCode: self::CODE_INV_RAW,
            direction: 'out'
        );
    }

    public function postSewingPickupSupplyFollowup(
        \App\Models\InventoryAdjustment $adjustment,
        \App\Models\SewingPickup $pickup
    ): ?Journal {
        return $this->postInventoryMovementFromMutations(
            journalSourceType: self::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP,
            journalSourceId: (int) $adjustment->id,
            mutationSourceType: self::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP,
            mutationSourceId: (int) $adjustment->id,
            date: $this->dateOnly($adjustment->date),
            description: "Kelengkapan Menyusul {$pickup->code}",
            debitAccountCode: self::CODE_INV_WIP,
            creditAccountCode: self::CODE_INV_RAW,
            direction: 'out'
        );
    }

    public function postSewingPickupSupplyFollowupByAdjustment(int $adjustmentId): ?Journal
    {
        $adjustment = \App\Models\InventoryAdjustment::query()->find($adjustmentId);
        if (!$adjustment || $adjustment->reference_type !== \App\Models\SewingPickup::class) {
            return null;
        }
        $pickup = \App\Models\SewingPickup::query()->find($adjustment->reference_id);
        return $pickup ? $this->postSewingPickupSupplyFollowup($adjustment, $pickup) : null;
    }

    /**
     * Void satu pickup line — reversal material WIP-CUT → WIP-SEW.
     *
     * Original pickup line contribution (setelah refactor wage-at-return):
     *   Dr 1202 WIP-SEW  (unit_cost × qty)   — material only
     *   Cr 1202 WIP-CUT  (unit_cost × qty)   — material only
     *
     * Reversal:
     *   Dr 1202 WIP-CUT  (materialCost)
     *   Cr 1202 WIP-SEW  (materialCost)
     *
     * NOTE: wage_per_pcs TIDAK di-reverse di sini karena upah belum diakui
     * saat Ambil Jahit — upah baru diakui saat Setor Jahit OK.
     */
    public function postSewingPickupLineVoid(\App\Models\SewingPickupLine $line): ?Journal
    {
        $existing = Journal::query()
            ->where('source_type', 'sewing_pickup_line_void')
            ->where('source_id', (int) $line->id)
            ->whereNull('voided_at')
            ->first();
        if ($existing) return $existing;

        // unit_cost sekarang = material only (wage_per_pcs tidak termasuk)
        $materialCost = round((float) $line->unit_cost * (float) $line->qty_bundle, 2);
        if ($materialCost <= 0) return null;

        $pickup = \App\Models\SewingPickup::find($line->sewing_pickup_id);
        $date   = $pickup ? $this->dateOnly($pickup->date) : $this->dateOnly(now());
        $desc   = "VOID Line Ambil Jahit" . ($pickup ? " {$pickup->code}" : '') . " — Line #{$line->id}";

        $wipId = $this->accountIdByCode(self::CODE_INV_WIP);
        return $this->post($date, 'sewing_pickup_line_void', (int) $line->id, $desc, [
            ['account_id' => $wipId, 'debit' => $materialCost, 'credit' => 0],
            ['account_id' => $wipId, 'debit' => 0, 'credit' => $materialCost],
        ]);
    }

    public function postSewingPickupSupplyVoidLine(\App\Models\SewingPickupLine $line): ?Journal
    {
        return $this->postInventoryMovementFromMutations(
            journalSourceType: self::SRC_SEWING_PICKUP_SUPPLY_VOID_LINE,
            journalSourceId: (int) $line->id,
            mutationSourceType: self::SRC_SEWING_PICKUP_SUPPLY_VOID_LINE,
            mutationSourceId: (int) $line->id,
            date: $this->dateOnly($line->voided_at ?: now()),
            description: "VOID Kelengkapan Ambil Jahit — Line {$line->id}",
            debitAccountCode: self::CODE_INV_RAW,
            creditAccountCode: self::CODE_INV_WIP,
            direction: 'in'
        );
    }

    /**
     * Setoran jahit OK: WIP-SEW (material) → WIP-FIN (material + upah).
     * postValueAddedTransfer mendeteksi inCost - outCost = upah jahit
     * dan mengkreditnya ke 2102 Hutang Upah Borongan.
     */
    public function postSewingReturnOk(\App\Models\SewingReturn $return): ?Journal
    {
        return $this->postValueAddedTransfer(
            journalSourceType: self::SRC_SEWING_RETURN_OK,
            journalSourceId: (int) $return->id,
            mutationSourceType: self::SRC_SEWING_RETURN_OK,
            mutationSourceId: (int) $return->id,
            date: $this->dateOnly($return->date),
            description: "Setoran Jahit {$return->code} — WIP-SEW → WIP-FIN + Upah",
            debitAccountCode: self::CODE_INV_WIP,
            creditAccountCode: self::CODE_INV_WIP,
        );
    }

    public function postSewingReturnReject(\App\Models\SewingReturn $return): ?Journal
    {
        return $this->postValueAddedTransfer(
            journalSourceType: self::SRC_SEWING_RETURN_REJECT,
            journalSourceId: (int) $return->id,
            mutationSourceType: self::SRC_SEWING_RETURN_REJECT,
            mutationSourceId: (int) $return->id,
            date: $this->dateOnly($return->date),
            description: "Setoran Jahit {$return->code} — WIP-SEW → Barang Cacat",
            debitAccountCode: self::CODE_INV_DEFECT,
            creditAccountCode: self::CODE_INV_WIP,
        );
    }

    public function postSewingReworkOk(\App\Models\SewingReturn $return): ?Journal
    {
        return $this->postValueAddedTransfer(
            journalSourceType: self::SRC_SEWING_REWORK_OK,
            journalSourceId: (int) $return->id,
            mutationSourceType: self::SRC_SEWING_REWORK_OK,
            mutationSourceId: (int) $return->id,
            date: $this->dateOnly($return->date),
            description: "Setor Ulang {$return->code} — Barang Cacat → WIP + Upah",
            debitAccountCode: self::CODE_INV_WIP,
            creditAccountCode: self::CODE_INV_DEFECT,
        );
    }

    /**
     * HPP shipment dari nilai real inventory_mutations.
     *
     * Mutasi stok memakai source_type = shipment, sedangkan jurnal COGS
     * memakai source_type = shipment_cogs agar tidak bentrok dengan dokumen stok.
     */
    public function postShipmentCogsFromMutations(\App\Models\Shipment $shipment): ?Journal
    {
        $existing = Journal::query()
            ->where('source_type', self::SRC_SHIPMENT_COGS)
            ->where('source_id', (int) $shipment->id)
            ->whereNull('voided_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $rows = DB::table('inventory_mutations as im')
            ->join('items as i', 'i.id', '=', 'im.item_id')
            ->where('im.source_type', 'shipment')
            ->where('im.source_id', (int) $shipment->id)
            ->where('im.qty_change', '<', 0)
            ->groupBy('i.item_role')
            ->selectRaw('COALESCE(i.item_role, "finished_good") as item_role, SUM(ABS(im.total_cost)) as total_cost')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $itemRoleToCode = [
            'finished_good' => self::CODE_INV_FG,
            'wip' => self::CODE_INV_WIP,
            'raw_material' => self::CODE_INV_RAW,
            'production_supply' => self::CODE_INV_RAW,
            'shipping_supply' => self::CODE_INV_PACKAGING,
        ];

        $hppId = $this->accountIdByCode(self::CODE_HPP);
        $creditLines = [];
        $totalHpp = 0.0;

        foreach ($rows as $row) {
            $amount = round((float) ($row->total_cost ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $accountCode = $itemRoleToCode[(string) ($row->item_role ?? '')] ?? self::CODE_INV_FG;
            $creditLines[] = [
                'account_id' => $this->accountIdByCode($accountCode),
                'debit' => 0,
                'credit' => $amount,
            ];
            $totalHpp = round($totalHpp + $amount, 2);
        }

        if ($totalHpp <= 0 || empty($creditLines)) {
            return null;
        }

        $lines = [[
            'account_id' => $hppId,
            'debit' => $totalHpp,
            'credit' => 0,
        ]];

        array_push($lines, ...$creditLines);

        return $this->post(
            $this->dateOnly($shipment->date),
            self::SRC_SHIPMENT_COGS,
            (int) $shipment->id,
            "COGS Shipment {$shipment->code}",
            $lines
        );
    }

    public function postPurchaseReceiptMutationSource(int $sourceId): ?Journal
    {
        return $this->postInventoryAccrualFromMutationSource(
            journalSourceType: self::SRC_GRN_ACCRUAL_INV,
            mutationSourceType: self::SRC_PURCHASE_RECEIPT,
            sourceId: $sourceId,
            descriptionPrefix: 'GRN historis'
        );
    }

    public function postPurchaseReturnMutationSource(int $sourceId): ?Journal
    {
        return $this->postInventoryReturnFromMutationSource(
            journalSourceType: self::SRC_PURCHASE_RETURN_INV,
            mutationSourceType: 'purchase_return',
            sourceId: $sourceId,
            descriptionPrefix: 'Retur pembelian historis'
        );
    }

    public function postInventoryAdjustment(\App\Models\InventoryAdjustment $adjustment): ?Journal
    {
        $existing = Journal::query()
            ->where('source_type', self::SRC_INVENTORY_ADJUSTMENT)
            ->where('source_id', $adjustment->id)
            ->whereNull('voided_at')
            ->first();
        if ($existing) return $existing;

        $rows = DB::table('inventory_mutations as im')
            ->join('items as i', 'i.id', '=', 'im.item_id')
            ->where('im.source_id', $adjustment->id)
            ->whereIn('im.source_type', [
                \App\Models\InventoryAdjustment::class,
                'stock_opname_adjustment',
                'inventory_adjustment_in',
                'inventory_adjustment_out',
            ])
            ->whereNotNull('im.total_cost')
            ->groupBy('i.item_role', 'im.direction')
            ->selectRaw('COALESCE(i.item_role,"raw_material") item_role, im.direction, SUM(ABS(im.total_cost)) amount')
            ->get();
        if ($rows->isEmpty()) return null;

        $lines = [];
        $totalIn = 0.0;
        $totalOut = 0.0;
        foreach ($rows as $row) {
            $amount = round((float) $row->amount, 2);
            if ($amount <= 0) continue;
            $accountId = $this->accountIdByCode($this->inventoryAccountCodeForRole((string) $row->item_role));
            if ($row->direction === 'in') {
                $lines[] = ['account_id' => $accountId, 'debit' => $amount, 'credit' => 0];
                $totalIn += $amount;
            } else {
                $lines[] = ['account_id' => $accountId, 'debit' => 0, 'credit' => $amount];
                $totalOut += $amount;
            }
        }

        $opexId = $this->accountIdByCode(self::CODE_EXP_OPEX);
        if ($totalOut > 0) $lines[] = ['account_id' => $opexId, 'debit' => round($totalOut, 2), 'credit' => 0];
        if ($totalIn > 0) $lines[] = ['account_id' => $opexId, 'debit' => 0, 'credit' => round($totalIn, 2)];
        if (count($lines) < 2) return null;

        return $this->post(
            $this->dateOnly($adjustment->date),
            self::SRC_INVENTORY_ADJUSTMENT,
            (int) $adjustment->id,
            "Penyesuaian Stok {$adjustment->code}",
            $lines,
        );
    }

    /**
     * Void jurnal FinishingJob.
     */
    public function voidFinishingJob(\App\Models\FinishingJob $job, ?string $reason = null): ?Journal
    {
        return $this->voidBySource(self::SRC_FINISHING_JOB, (int) $job->id, $reason);
    }

    /**
     * Post jurnal untuk WipFinAdjustment.
     *
     * type='out' (WIP keluar/koreksi minus):
     *   Dr 6101 Biaya Operasional / Cr 1202 WIP
     *
     * type='in' (WIP masuk/koreksi plus):
     *   Dr 1202 WIP / Cr 6101 Biaya Operasional
     *
     * Amount = SUM(qty × unit_cost) dari inventory_mutations adjustment ini.
     */
    public function postWipFinAdjustment(\App\Models\WipFinAdjustment $adj): ?Journal
    {
        // Idempotent guard
        $existing = Journal::query()
            ->where('source_type', self::SRC_WIP_FIN_ADJUSTMENT)
            ->where('source_id', $adj->id)
            ->whereNull('voided_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $wipId  = $this->accountIdByCode(self::CODE_INV_WIP);
        $opexId = $this->accountIdByCode(self::CODE_EXP_OPEX);

        // Ambil total nilai dari inventory_mutations
        $amount = (float) DB::table('inventory_mutations')
            ->where('source_type', \App\Models\WipFinAdjustment::class)
            ->where('source_id', $adj->id)
            ->sum(DB::raw('ABS(total_cost)'));

        if ($amount <= 0) {
            return null;
        }

        $amount = round($amount, 2);
        $type   = strtolower($adj->type ?? 'out');
        $desc   = "Adj WIP {$adj->code} ({$type})";

        if ($type === 'in') {
            // WIP bertambah: Dr WIP / Cr Opex
            return $this->post(
                $this->dateOnly($adj->date),
                self::SRC_WIP_FIN_ADJUSTMENT,
                (int) $adj->id,
                $desc,
                [
                    ['account_id' => $wipId,  'debit' => $amount, 'credit' => 0],
                    ['account_id' => $opexId, 'debit' => 0, 'credit' => $amount],
                ]
            );
        }

        // type='out': WIP berkurang: Dr Opex / Cr WIP
        return $this->post(
            $this->dateOnly($adj->date),
            self::SRC_WIP_FIN_ADJUSTMENT,
            (int) $adj->id,
            $desc,
            [
                ['account_id' => $opexId, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $wipId,  'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    /**
     * Void jurnal WipFinAdjustment.
     */
    public function voidWipFinAdjustment(\App\Models\WipFinAdjustment $adj, ?string $reason = null): ?Journal
    {
        return $this->voidBySource(self::SRC_WIP_FIN_ADJUSTMENT, (int) $adj->id, $reason);
    }

    // =========================================================
    // INVENTORY ADJUSTMENT
    // =========================================================

    /**
     * Void jurnal InventoryAdjustment.
     */
    public function voidInventoryAdjustment(\App\Models\InventoryAdjustment $adjustment, ?string $reason = null): ?Journal
    {
        return $this->voidBySource(self::SRC_INVENTORY_ADJUSTMENT, (int) $adjustment->id, $reason);
    }

    protected function postInventoryMovementFromMutations(
        string $journalSourceType,
        int $journalSourceId,
        string $mutationSourceType,
        int $mutationSourceId,
        string $date,
        string $description,
        string $debitAccountCode,
        string $creditAccountCode,
        string $direction = 'out'
    ): ?Journal {
        $existing = Journal::query()
            ->where('source_type', $journalSourceType)
            ->where('source_id', $journalSourceId)
            ->whereNull('voided_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $query = DB::table('inventory_mutations')
            ->where('source_type', $mutationSourceType)
            ->where('source_id', $mutationSourceId);

        if ($direction === 'in') {
            $query->where('qty_change', '>', 0);
        } else {
            $query->where('qty_change', '<', 0);
        }

        $amount = round((float) $query->sum(DB::raw('ABS(total_cost)')), 2);

        if ($amount <= 0) {
            return null;
        }

        $debitId = $this->accountIdByCode($debitAccountCode);
        $creditId = $this->accountIdByCode($creditAccountCode);

        return $this->post(
            $this->dateOnly($date),
            $journalSourceType,
            $journalSourceId,
            $description,
            [
                ['account_id' => $debitId, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $creditId, 'debit' => 0, 'credit' => $amount],
            ]
        );
    }

    protected function postValueAddedTransfer(
        string $journalSourceType,
        int $journalSourceId,
        string $mutationSourceType,
        int $mutationSourceId,
        string $date,
        string $description,
        string $debitAccountCode,
        string $creditAccountCode,
    ): ?Journal {
        $existing = Journal::query()
            ->where('source_type', $journalSourceType)
            ->where('source_id', $journalSourceId)
            ->whereNull('voided_at')
            ->first();
        if ($existing) return $existing;

        $outCost = $this->mutationAmount($mutationSourceType, $mutationSourceId, 'out');
        $inCost = $this->mutationAmount($mutationSourceType, $mutationSourceId, 'in');
        if ($outCost <= 0 && $inCost <= 0) return null;

        $baseCost = $outCost > 0 ? $outCost : $inCost;
        $laborCost = round(max($inCost - $outCost, 0), 2);
        $destinationCost = round($baseCost + $laborCost, 2);

        $lines = [
            ['account_id' => $this->accountIdByCode($debitAccountCode), 'debit' => $destinationCost, 'credit' => 0],
            ['account_id' => $this->accountIdByCode($creditAccountCode), 'debit' => 0, 'credit' => $baseCost],
        ];
        if ($laborCost > 0) {
            $lines[] = ['account_id' => $this->accountIdByCode(self::CODE_PAYROLL_PAYABLE), 'debit' => 0, 'credit' => $laborCost];
        }

        return $this->post($date, $journalSourceType, $journalSourceId, $description, $lines);
    }

    protected function mutationAmount(string $sourceType, int $sourceId, string $direction): float
    {
        $query = DB::table('inventory_mutations')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId);

        $direction === 'in'
            ? $query->where('qty_change', '>', 0)
            : $query->where('qty_change', '<', 0);

        return round((float) $query->sum(DB::raw('ABS(total_cost)')), 2);
    }

    protected function finishingBomCostForJob(int $finishingJobId): float
    {
        return round((float) DB::table('inventory_mutations as im')
            ->join('finishing_job_lines as fjl', 'fjl.id', '=', 'im.source_id')
            ->where('im.source_type', self::SRC_FINISHING_BOM)
            ->where('fjl.finishing_job_id', $finishingJobId)
            ->where('im.qty_change', '<', 0)
            ->sum(DB::raw('ABS(im.total_cost)')), 2);
    }

    protected function postInventoryAccrualFromMutationSource(
        string $journalSourceType,
        string $mutationSourceType,
        int $sourceId,
        string $descriptionPrefix
    ): ?Journal {
        $existing = Journal::query()
            ->where('source_type', $journalSourceType)
            ->where('source_id', $sourceId)
            ->whereNull('voided_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $rows = $this->inventoryMutationCostRows($mutationSourceType, $sourceId, 'in');
        if ($rows->isEmpty()) {
            return null;
        }

        $lines = [];
        $total = 0.0;

        foreach ($rows as $row) {
            $amount = round((float) ($row->total_cost ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $lines[] = [
                'account_id' => $this->accountIdByCode($this->inventoryAccountCodeForRole((string) ($row->item_role ?? ''))),
                'debit' => $amount,
                'credit' => 0,
            ];
            $total = round($total + $amount, 2);
        }

        if ($total <= 0 || empty($lines)) {
            return null;
        }

        $lines[] = [
            'account_id' => $this->accountIdByCode(self::CODE_AP),
            'debit' => 0,
            'credit' => $total,
        ];

        return $this->post(
            $this->mutationSourceDate($mutationSourceType, $sourceId),
            $journalSourceType,
            $sourceId,
            "{$descriptionPrefix} #{$sourceId}",
            $lines
        );
    }

    protected function postInventoryReturnFromMutationSource(
        string $journalSourceType,
        string $mutationSourceType,
        int $sourceId,
        string $descriptionPrefix
    ): ?Journal {
        $existing = Journal::query()
            ->where('source_type', $journalSourceType)
            ->where('source_id', $sourceId)
            ->whereNull('voided_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $rows = $this->inventoryMutationCostRows($mutationSourceType, $sourceId, 'out');
        if ($rows->isEmpty()) {
            return null;
        }

        $creditLines = [];
        $total = 0.0;

        foreach ($rows as $row) {
            $amount = round((float) ($row->total_cost ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $creditLines[] = [
                'account_id' => $this->accountIdByCode($this->inventoryAccountCodeForRole((string) ($row->item_role ?? ''))),
                'debit' => 0,
                'credit' => $amount,
            ];
            $total = round($total + $amount, 2);
        }

        if ($total <= 0 || empty($creditLines)) {
            return null;
        }

        $lines = [[
            'account_id' => $this->accountIdByCode(self::CODE_AP),
            'debit' => $total,
            'credit' => 0,
        ]];

        array_push($lines, ...$creditLines);

        return $this->post(
            $this->mutationSourceDate($mutationSourceType, $sourceId),
            $journalSourceType,
            $sourceId,
            "{$descriptionPrefix} #{$sourceId}",
            $lines
        );
    }

    protected function inventoryMutationCostRows(string $sourceType, int $sourceId, string $direction): Collection
    {
        $query = DB::table('inventory_mutations as im')
            ->join('items as i', 'i.id', '=', 'im.item_id')
            ->where('im.source_type', $sourceType)
            ->where('im.source_id', $sourceId);

        if ($direction === 'in') {
            $query->where('im.qty_change', '>', 0);
        } else {
            $query->where('im.qty_change', '<', 0);
        }

        return $query
            ->groupBy('i.item_role')
            ->selectRaw('COALESCE(i.item_role, "raw_material") as item_role, SUM(ABS(im.total_cost)) as total_cost')
            ->get();
    }

    protected function mutationSourceDate(string $sourceType, int $sourceId): string
    {
        $date = DB::table('inventory_mutations')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->min('date');

        return $this->dateOnly($date ?: now());
    }

    protected function inventoryAccountCodeForRole(string $role): string
    {
        return match ($role) {
            'finished_good' => self::CODE_INV_FG,
            'wip' => self::CODE_INV_WIP,
            'shipping_supply' => self::CODE_INV_PACKAGING,
            default => self::CODE_INV_RAW,
        };
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
