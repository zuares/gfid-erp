<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransfer extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'from_cash_account_id',
        'to_cash_account_id',
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

    public function fromCashAccount()
    {
        return $this->belongsTo(Account::class, 'from_cash_account_id');
    }

    public function toCashAccount()
    {
        return $this->belongsTo(Account::class, 'to_cash_account_id');
    }
}
