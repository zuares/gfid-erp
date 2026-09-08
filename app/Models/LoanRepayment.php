<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends Model
{
    protected $fillable = [
        'loan_id', 'date', 'principal_amount', 'interest_amount', 'interest_account_id',
        'cash_account_id', 'reference', 'status', 'journal_id', 'created_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
    ];

    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function cashAccount(): BelongsTo { return $this->belongsTo(Account::class, 'cash_account_id'); }
    public function interestAccount(): BelongsTo { return $this->belongsTo(Account::class, 'interest_account_id'); }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
}
