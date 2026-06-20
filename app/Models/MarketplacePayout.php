<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplacePayout extends Model
{
    protected $fillable = [
        'date',
        'marketplace_name',
        'amount',
        'bank_account_id',
        'reference',
        'description',
        'status',
        'journal_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
