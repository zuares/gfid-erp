<?php

namespace App\Services\Accounting;

use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    public function __construct(private JournalService $journals) {}

    public function post(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            $locked = Loan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'posted') return $locked;
            if ($locked->status === 'void') {
                throw ValidationException::withMessages(['status' => 'Pinjaman sudah VOID, tidak bisa diposting.']);
            }
            if ((float) $locked->principal_amount <= 0) {
                throw ValidationException::withMessages(['principal_amount' => 'Nominal pinjaman harus lebih dari 0.']);
            }
            if ((int) $locked->cash_account_id === (int) $locked->liability_account_id) {
                throw ValidationException::withMessages(['account' => 'Akun kas dan akun utang tidak boleh sama.']);
            }

            $journal = $this->journals->post(
                $locked->date->toDateString(),
                'loan',
                $locked->id,
                trim(($locked->description ?: 'Penerimaan pinjaman') . ($locked->reference ? " (#{$locked->reference})" : '')),
                [
                    ['account_id' => $locked->cash_account_id, 'debit' => (float) $locked->principal_amount, 'credit' => 0],
                    ['account_id' => $locked->liability_account_id, 'debit' => 0, 'credit' => (float) $locked->principal_amount],
                ],
                ['reference_no' => $locked->reference, 'notes' => $locked->notes],
            );

            $locked->update(['status' => 'posted', 'journal_id' => $journal->id]);
            return $locked->fresh();
        });
    }

    public function void(Loan $loan, ?string $reason = null): Loan
    {
        return DB::transaction(function () use ($loan, $reason) {
            $locked = Loan::query()->with('journal')->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'void') return $locked;
            if ($locked->status !== 'posted' || !$locked->journal) {
                throw ValidationException::withMessages(['status' => 'Hanya pinjaman POSTED yang bisa di-VOID.']);
            }
            if ($locked->repayments()->where('status', 'posted')->exists()) {
                throw ValidationException::withMessages(['status' => 'Pinjaman yang sudah memiliki pembayaran POSTED tidak boleh di-VOID.']);
            }

            $this->journals->void($locked->journal, $reason);
            $locked->update(['status' => 'void', 'notes' => trim(($locked->notes ?? '') . ($reason ? "\nVOID: {$reason}" : ''))]);
            return $locked->fresh();
        });
    }

    public function postRepayment(LoanRepayment $repayment): LoanRepayment
    {
        return DB::transaction(function () use ($repayment) {
            $locked = LoanRepayment::query()->with('loan')->whereKey($repayment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'posted') return $locked;
            if ($locked->status === 'void') throw ValidationException::withMessages(['status' => 'Pembayaran sudah VOID.']);
            if ($locked->loan->status !== 'posted') throw ValidationException::withMessages(['loan' => 'Pinjaman harus POSTED sebelum pembayaran dicatat.']);

            $principal = (float) $locked->principal_amount;
            $interest = (float) $locked->interest_amount;
            if ($principal < 0 || $interest < 0 || ($principal + $interest) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Pokok atau bunga harus diisi dan totalnya lebih dari 0.']);
            }
            if ($interest > 0 && !$locked->interest_account_id) {
                throw ValidationException::withMessages(['interest_account_id' => 'Akun beban bunga wajib dipilih jika ada bunga.']);
            }

            $paidPrincipal = (float) $locked->loan->repayments()->where('status', 'posted')->sum('principal_amount');
            if ($principal > ((float) $locked->loan->principal_amount - $paidPrincipal + 0.01)) {
                throw ValidationException::withMessages(['principal_amount' => 'Pokok pembayaran melebihi sisa utang pinjaman.']);
            }

            $lines = [];
            if ($principal > 0) {
                $lines[] = ['account_id' => $locked->loan->liability_account_id, 'debit' => $principal, 'credit' => 0];
            }
            if ($interest > 0) {
                $lines[] = ['account_id' => $locked->interest_account_id, 'debit' => $interest, 'credit' => 0];
            }
            $lines[] = ['account_id' => $locked->cash_account_id, 'debit' => 0, 'credit' => $principal + $interest];

            $journal = $this->journals->post(
                $locked->date->toDateString(),
                'loan_repayment',
                $locked->id,
                'Pembayaran pinjaman' . ($locked->reference ? " (#{$locked->reference})" : ''),
                $lines,
                ['reference_no' => $locked->reference, 'notes' => $locked->notes],
            );
            $locked->update(['status' => 'posted', 'journal_id' => $journal->id]);
            return $locked->fresh();
        });
    }

    public function voidRepayment(LoanRepayment $repayment, ?string $reason = null): LoanRepayment
    {
        return DB::transaction(function () use ($repayment, $reason) {
            $locked = LoanRepayment::query()->with('journal')->whereKey($repayment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'void') return $locked;
            if ($locked->status !== 'posted' || !$locked->journal) throw ValidationException::withMessages(['status' => 'Hanya pembayaran POSTED yang bisa di-VOID.']);
            $this->journals->void($locked->journal, $reason);
            $locked->update(['status' => 'void', 'notes' => trim(($locked->notes ?? '') . ($reason ? "\nVOID: {$reason}" : ''))]);
            return $locked->fresh();
        });
    }
}
