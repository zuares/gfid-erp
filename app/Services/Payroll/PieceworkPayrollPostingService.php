<?php

namespace App\Services\Payroll;

use App\Models\Account;
use App\Models\Journal;
use App\Models\PieceworkPayrollPeriod;
use App\Services\Accounting\JournalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PieceworkPayrollPostingService
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * FINALIZE merekonsiliasi upah yang sudah dikapitalisasi oleh produksi.
     * Hanya selisih yang diposting agar upah tidak masuk HPP dua kali.
     */
    public function finalize(PieceworkPayrollPeriod $period): PieceworkPayrollPeriod
    {
        return DB::transaction(function () use ($period) {
            // Lock header payroll agar finalize/pay yang bersamaan tidak membaca
            // status lama lalu membuat jurnal atau marker ganda.
            $period = PieceworkPayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->getKey());

            $payable = Account::where('code', '2102')->first();
            if (! $payable) {
                throw new \RuntimeException('Akun 2102 (Hutang Upah Borongan) tidak ditemukan.');
            }

            $activeAccrual = Journal::query()
                ->where('source_type', 'piecework_payroll_period_accrual')
                ->where('source_id', $period->id)
                ->whereNull('voided_at')
                ->latest('id')
                ->first();

            if ($period->status === 'final') {
                // Repair marker untuk periode lama yang sudah final sebelum field
                // accounting masuk ke $fillable.
                $updates = [];
                if (! $period->payable_account_id) {
                    $updates['payable_account_id'] = $payable->id;
                }
                if (! $period->finalized_at) {
                    $updates['finalized_at'] = now();
                }
                if (! $period->accrual_journal_id && $activeAccrual) {
                    $updates['accrual_journal_id'] = $activeAccrual->id;
                }

                if ($updates) {
                    $period->forceFill($updates)->save();
                }

                return $period->fresh();
            }

            // hitung total
            $total = (float) $period->lines()->sum('amount');
            if ($total <= 0) {
                throw new \RuntimeException('Total payroll 0. Tidak bisa finalize.');
            }

            // anti dobel journal
            if ($period->accrual_journal_id) {
                $existing = Journal::find($period->accrual_journal_id);
                if ($existing && ! $existing->voided_at) {
                    throw new \RuntimeException('Sudah ada jurnal accrual aktif untuk periode ini.');
                }
            }

            $sourceTypes = match ((string) $period->module) {
                'cutting' => [JournalService::SRC_CUTTING_JOB_WAGE, JournalService::SRC_CUTTING_WIP],
                'sewing' => [JournalService::SRC_SEWING_PICKUP_WAGE, JournalService::SRC_SEWING_RETURN_OK, JournalService::SRC_SEWING_REWORK_OK],
                default => [],
            };

            $alreadyAccrued = empty($sourceTypes) ? 0.0 : (float) DB::table('journal_lines as jl')
                ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                ->whereNull('j.voided_at')
                ->whereIn('j.source_type', $sourceTypes)
                ->whereBetween('j.date', [$period->period_start, $period->period_end])
                ->where('jl.account_id', $payable->id)
                ->sum('jl.credit');

            $difference = round($total - $alreadyAccrued, 2);
            $journal = null;

            if (abs($difference) > 0.01) {
                $inventoryCode = $period->module === 'finishing' ? '1203' : '1202';
                $inventory = Account::where('code', $inventoryCode)->firstOrFail();
                $payrollLabel = $period->module === 'daily' ? 'Payroll Harian' : 'Payroll Borongan';
                $desc = strtoupper($period->module).' '.$payrollLabel.' (REKONSILIASI) '
                    .$period->period_start.' s/d '.$period->period_end;

                $lines = $difference > 0
                    ? [
                        ['account_id' => $inventory->id, 'debit' => $difference, 'credit' => 0],
                        ['account_id' => $payable->id, 'debit' => 0, 'credit' => $difference],
                    ]
                    : [
                        ['account_id' => $payable->id, 'debit' => abs($difference), 'credit' => 0],
                        ['account_id' => $inventory->id, 'debit' => 0, 'credit' => abs($difference)],
                    ];

                $journal = $this->journalService->post(
                    date: $period->period_end,
                    sourceType: 'piecework_payroll_period_accrual',
                    sourceId: $period->id,
                    description: $desc,
                    lines: $lines,
                );
            }

            $period->forceFill([
                'total_amount' => $total,
                'status' => 'final',
                'finalized_at' => now(),
                'finalized_by' => Auth::id(),
                'payable_account_id' => $payable->id,
                'accrual_journal_id' => $journal?->id ?: $activeAccrual?->id,
            ])->save();

            return $period;
        });
    }

    /**
     * PAY:
     * Dr 2102 Hutang Upah Borongan
     * Cr Kas/Bank (yang dipilih)
     */
    public function pay(PieceworkPayrollPeriod $period, int $paidFromAccountId): PieceworkPayrollPeriod
    {
        return DB::transaction(function () use ($period, $paidFromAccountId) {
            // Satu lock per payroll period menjadi guard utama terhadap double
            // submit dari dua request yang datang hampir bersamaan.
            $period = PieceworkPayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->getKey());

            if ($period->status !== 'final') {
                throw new \RuntimeException('Periode harus FINAL sebelum dibayar.');
            }

            // Recovery untuk data lama: jurnal payment mungkin sudah terbentuk,
            // tetapi marker di payroll period gagal tersimpan.
            $existingPayment = Journal::query()
                ->with('lines')
                ->where('source_type', 'piecework_payroll_period_payment')
                ->where('source_id', $period->id)
                ->whereNull('voided_at')
                ->latest('id')
                ->first();

            if ($existingPayment) {
                $paidAmount = round((float) $existingPayment->lines->sum('debit'), 2);
                if ($paidAmount <= 0) {
                    throw new \RuntimeException('Jurnal pembayaran payroll ditemukan tetapi nilainya tidak valid.');
                }

                $paidFromLine = $existingPayment->lines
                    ->first(fn ($line) => (float) $line->credit > 0);

                $period->forceFill([
                    'payment_journal_id' => $existingPayment->id,
                    'paid_from_account_id' => $paidFromLine?->account_id ?: $period->paid_from_account_id,
                    'paid_at' => $period->paid_at ?: ($existingPayment->posted_at ?: now()),
                    'total_amount' => $period->total_amount ?: $paidAmount,
                ])->save();

                return $period->fresh();
            }

            if ($period->payment_journal_id || $period->paid_at) {
                throw new \RuntimeException(
                    'Marker pembayaran payroll ada, tetapi jurnal pembayaran aktif tidak ditemukan. Periksa jurnal sebelum membayar ulang.'
                );
            }

            $total = (float) ($period->total_amount ?? 0);
            if ($total <= 0) {
                // safety: hitung ulang kalau total kosong
                $total = (float) $period->lines()->sum('amount');
            }
            if ($total <= 0) {
                throw new \RuntimeException('Total payroll 0. Tidak bisa dibayar.');
            }

            // hutang upah borongan (ambil dari period kalau ada)
            $payableId = (int) ($period->payable_account_id ?: 0);
            if (! $payableId) {
                $payable = Account::where('code', '2102')->first();
                if (! $payable) {
                    throw new \RuntimeException('Akun 2102 tidak ditemukan.');
                }

                $payableId = $payable->id;
            }

            // akun kas/bank pembayaran
            $paidFrom = Account::findOrFail($paidFromAccountId);
            if (! $paidFrom->is_cash) {
                throw new \RuntimeException('Akun pembayaran harus akun Kas/Bank.');
            }

            $payrollLabel = $period->module === 'daily' ? 'Payroll Harian' : 'Payroll Borongan';
            $desc = strtoupper($period->module).' '.$payrollLabel.' (PAY) '
            .$period->period_start.' s/d '.$period->period_end
            .' via '.$paidFrom->name;

            $journal = $this->journalService->post(
                date: now()->toDateString(),
                sourceType: 'piecework_payroll_period_payment',
                sourceId: $period->id,
                description: $desc,
                lines: [
                    ['account_id' => $payableId, 'debit' => $total, 'credit' => 0],
                    ['account_id' => $paidFrom->id, 'debit' => 0, 'credit' => $total],
                ]
            );

            $period->forceFill([
                'paid_from_account_id' => $paidFrom->id,
                'paid_at' => now(),
                'paid_by' => Auth::id(),
                'payment_journal_id' => $journal->id,
                'total_amount' => $total,
            ])->save();

            return $period->fresh();
        });
    }
}
