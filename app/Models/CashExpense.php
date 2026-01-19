<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashExpense extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'expense_account_id',
        'cash_account_id',
        'description',
        'reference',
        'status',
        'journal_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function cashAccount()
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }
}
