<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\EmployeeSavingsTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeSavingsService
{
    public function __construct(private JournalService $journals) {}

    public function post(EmployeeSavingsTransaction $transaction): EmployeeSavingsTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $locked = EmployeeSavingsTransaction::query()
                ->with('employee')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'posted') {
                return $locked;
            }
            if ($locked->status === 'void') {
                throw ValidationException::withMessages(['status' => 'Pembayaran tabungan sudah VOID.']);
            }
            if ($locked->type !== EmployeeSavingsTransaction::TYPE_PAYMENT) {
                throw ValidationException::withMessages(['type' => 'Hanya transaksi pembayaran yang bisa diposting dari menu ini.']);
            }

            $amount = (float) $locked->amount;
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus lebih dari 0.']);
            }

            $this->validateCashAccount((int) $locked->cash_account_id);

            $deposited = (float) EmployeeSavingsTransaction::query()
                ->where('employee_id', $locked->employee_id)
                ->where('type', EmployeeSavingsTransaction::TYPE_DEPOSIT)
                ->where('status', 'posted')
                ->sum('amount');

            $paid = (float) EmployeeSavingsTransaction::query()
                ->where('employee_id', $locked->employee_id)
                ->where('type', EmployeeSavingsTransaction::TYPE_PAYMENT)
                ->where('status', 'posted')
                ->where('id', '<>', $locked->id)
                ->sum('amount');

            $outstanding = max(0, $deposited - $paid);
            if ($amount > $outstanding + 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran melebihi saldo tabungan karyawan. Sisa: Rp '.number_format($outstanding, 2, ',', '.'),
                ]);
            }

            $journal = $this->journals->post(
                $locked->date->toDateString(),
                JournalService::SRC_EMPLOYEE_SAVINGS_PAYMENT,
                $locked->id,
                'Pembayaran tabungan karyawan · '.($locked->employee?->name ?: 'Karyawan')
                    .($locked->reference ? " (#{$locked->reference})" : ''),
                [
                    ['account_id' => $this->savingsAccount()->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $locked->cash_account_id, 'debit' => 0, 'credit' => $amount],
                ],
                [
                    'reference_no' => $locked->reference,
                    'notes' => $locked->notes,
                    'created_by' => $locked->created_by ?? auth()->id(),
                ],
            );

            $locked->update(['status' => 'posted', 'journal_id' => $journal->id]);

            return $locked->fresh(['employee', 'cashAccount', 'journal']);
        });
    }

    public function void(EmployeeSavingsTransaction $transaction, ?string $reason = null): EmployeeSavingsTransaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            $locked = EmployeeSavingsTransaction::query()
                ->with('journal')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'void') {
                return $locked;
            }
            if ($locked->status !== 'posted' || ! $locked->journal) {
                throw ValidationException::withMessages(['status' => 'Hanya pembayaran POSTED yang bisa di-VOID.']);
            }

            $this->journals->void($locked->journal, $reason);
            $locked->update([
                'status' => 'void',
                'notes' => trim(($locked->notes ?? '').($reason ? "\nVOID: {$reason}" : '')),
            ]);

            return $locked->fresh(['employee', 'cashAccount', 'journal']);
        });
    }

    public function outstandingForEmployee(int $employeeId, ?int $excludePaymentId = null): float
    {
        $deposited = (float) EmployeeSavingsTransaction::query()
            ->where('employee_id', $employeeId)
            ->where('type', EmployeeSavingsTransaction::TYPE_DEPOSIT)
            ->where('status', 'posted')
            ->sum('amount');

        $paidQuery = EmployeeSavingsTransaction::query()
            ->where('employee_id', $employeeId)
            ->where('type', EmployeeSavingsTransaction::TYPE_PAYMENT)
            ->where('status', 'posted');

        if ($excludePaymentId !== null) {
            $paidQuery->where('id', '<>', $excludePaymentId);
        }

        return max(0, $deposited - (float) $paidQuery->sum('amount'));
    }

    private function savingsAccount(): Account
    {
        $account = Account::query()
            ->where('code', JournalService::CODE_EMPLOYEE_SAVINGS)
            ->where('type', 'liability')
            ->where('is_active', true)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'account' => 'Akun 2104 · Tabungan Karyawan harus aktif dan bertipe liability.',
            ]);
        }

        return $account;
    }

    private function validateCashAccount(int $accountId): void
    {
        if (! Account::query()->whereKey($accountId)->where('is_cash', true)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['cash_account_id' => 'Akun kas/bank harus aktif dan bertipe kas/bank.']);
        }
    }
}
