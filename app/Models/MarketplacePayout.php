<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplacePayout extends Model
{
    protected $fillable = [
        'date',
        'marketplace_name',
        'store_id',
        'source',
        'amount',
        'bank_account_id',
        'reference',
        'external_transaction_id',
        'transaction_type',
        'transaction_created_at',
        'source_payload',
        'description',
        'status',
        'journal_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'date'                   => 'date',
        'amount'                 => 'decimal:2',
        'transaction_created_at' => 'datetime',
        'source_payload'         => 'array',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
