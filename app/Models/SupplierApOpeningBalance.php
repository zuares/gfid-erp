<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierApOpeningBalance extends Model
{
    protected $fillable = [
        'supplier_id',
        'journal_id',
        'date',
        'invoice_date',
        'due_date',
        'reference_no',
        'amount',
        'ap_account_id',
        'offset_account_id',
        'notes',
        'status',
        'posted_at',
        'posted_by',
        'voided_at',
        'voided_by',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'float',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ap_account_id');
    }

    public function offsetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'offset_account_id');
    }

    public function isVoided(): bool
    {
        return $this->status === 'void' || ! is_null($this->voided_at);
    }
}
