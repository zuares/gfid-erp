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
     * FINALIZE:
     * Dr 5101 HPP
     * Cr 2102 Hutang Upah Borongan
     */
    public function finalize(PieceworkPayrollPeriod $period): PieceworkPayrollPeriod
    {
        return DB::transaction(function () use ($period) {
            $period->refresh();

            if ($period->status === 'final') {
                return $period;
            }

            // hitung total
            $total = (float) $period->lines()->sum('amount');
            if ($total <= 0) {
                throw new \RuntimeException('Total payroll 0. Tidak bisa finalize.');
            }

            // akun HPP (5101)
            $hpp = Account::where('code', '5101')->first();
            if (!$hpp) {
                throw new \RuntimeException('Akun 5101 (HPP) tidak ditemukan.');
            }

            // akun hutang upah borongan (2102)
            $payable = Account::where('code', '2102')->first();
            if (!$payable) {
                throw new \RuntimeException('Akun 2102 (Hutang Upah Borongan) tidak ditemukan.');
            }

            // anti dobel journal
            if ($period->accrual_journal_id) {
                $existing = Journal::find($period->accrual_journal_id);
                if ($existing && !$existing->voided_at) {
                    throw new \RuntimeException('Sudah ada jurnal accrual aktif untuk periode ini.');
                }
            }

            $desc = strtoupper($period->module) . ' Payroll Borongan (FINAL) '
            . $period->period_start . ' s/d ' . $period->period_end;

            $journal = $this->journalService->post(
                date: $period->period_end, // umumnya accrual di akhir periode
                sourceType: 'piecework_payroll_period_accrual',
                sourceId: $period->id,
                description: $desc,
                lines: [
                    ['account_id' => $hpp->id, 'debit' => $total, 'credit' => 0],
                    ['account_id' => $payable->id, 'debit' => 0, 'credit' => $total],
                ]
            );

            $period->update([
                'total_amount' => $total,
                'status' => 'final',
                'finalized_at' => now(),
                'finalized_by' => Auth::id(),
                'payable_account_id' => $payable->id,
                'accrual_journal_id' => $journal->id,
            ]);

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
            $period->refresh();

            if ($period->status !== 'final') {
                throw new \RuntimeException('Periode harus FINAL sebelum dibayar.');
            }

            if ($period->paid_at || $period->payment_journal_id) {
                throw new \RuntimeException('Periode ini sudah dicatat pembayaran sebelumnya.');
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
            if (!$payableId) {
                $payable = Account::where('code', '2102')->first();
                if (!$payable) {
                    throw new \RuntimeException('Akun 2102 tidak ditemukan.');
                }

                $payableId = $payable->id;
            }

            // akun kas/bank pembayaran
            $paidFrom = Account::findOrFail($paidFromAccountId);
            if (!$paidFrom->is_cash) {
                throw new \RuntimeException('Akun pembayaran harus akun Kas/Bank.');
            }

            $desc = strtoupper($period->module) . ' Payroll Borongan (PAY) '
            . $period->period_start . ' s/d ' . $period->period_end
            . ' via ' . $paidFrom->name;

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

            $period->update([
                'paid_from_account_id' => $paidFrom->id,
                'paid_at' => now(),
                'paid_by' => Auth::id(),
                'payment_journal_id' => $journal->id,
                'total_amount' => $total,
            ]);

            return $period;
        });
    }
}
