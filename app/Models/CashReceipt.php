<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashReceipt extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'cash_account_id',
        'source_account_id',
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

    public function cashAccount()
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function sourceAccount()
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }
}
