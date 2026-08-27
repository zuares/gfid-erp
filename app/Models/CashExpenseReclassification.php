<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashExpenseReclassification extends Model
{
    protected $fillable = [
        'cash_expense_id',
        'from_expense_account_id',
        'to_expense_account_id',
        'journal_id',
        'amount',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashExpense()
    {
        return $this->belongsTo(CashExpense::class);
    }

    public function fromExpenseAccount()
    {
        return $this->belongsTo(Account::class, 'from_expense_account_id');
    }

    public function toExpenseAccount()
    {
        return $this->belongsTo(Account::class, 'to_expense_account_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
