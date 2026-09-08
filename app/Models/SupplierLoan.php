<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierLoan extends Model
{
    protected $fillable = [
        'supplier_id', 'date', 'due_date', 'principal_amount', 'cash_account_id',
        'receivable_account_id', 'description', 'reference', 'status', 'journal_id',
        'created_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'principal_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'receivable_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(SupplierLoanRepayment::class);
    }

    public function purchasePayments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class, 'supplier_loan_id');
    }

    public function postedAllocations(): HasMany
    {
        return $this->purchasePayments()
            ->where('type', 'loan_apply')
            ->whereNull('voided_at');
    }

    public function postedRepayments(): HasMany
    {
        return $this->repayments()->where('status', 'posted');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPostedRepaymentAmountAttribute(): float
    {
        if (array_key_exists('posted_repayment_amount', $this->attributes)) {
            return (float) $this->attributes['posted_repayment_amount'];
        }

        return (float) $this->postedRepayments()->sum('amount');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, (float) $this->principal_amount - $this->posted_repayment_amount);
    }

    public function getAllocatedAmountAttribute(): float
    {
        if (array_key_exists('allocated_amount', $this->attributes)) {
            return (float) $this->attributes['allocated_amount'];
        }

        return (float) $this->postedAllocations()->sum('amount');
    }

    public function getAllocationAvailableAmountAttribute(): float
    {
        return max(0, $this->outstanding_amount - $this->allocated_amount);
    }
}
