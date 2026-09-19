<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id', 'date', 'due_date', 'principal_amount', 'installment_amount',
        'installment_count', 'cash_account_id', 'receivable_account_id', 'description',
        'reference', 'status', 'journal_id', 'created_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'principal_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
        return $this->hasMany(EmployeeLoanRepayment::class);
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

        return (float) $this->repayments()->where('status', 'posted')->sum('amount');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, (float) $this->principal_amount - $this->posted_repayment_amount);
    }
}
