<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLoanRepayment extends Model
{
    protected $fillable = [
        'supplier_loan_id', 'date', 'amount', 'cash_account_id', 'reference',
        'status', 'journal_id', 'created_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function supplierLoan(): BelongsTo
    {
        return $this->belongsTo(SupplierLoan::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
