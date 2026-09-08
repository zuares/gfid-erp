<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'date', 'lender', 'principal_amount', 'cash_account_id', 'liability_account_id',
        'description', 'reference', 'status', 'journal_id', 'created_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'principal_amount' => 'decimal:2',
    ];

    public function cashAccount(): BelongsTo { return $this->belongsTo(Account::class, 'cash_account_id'); }
    public function liabilityAccount(): BelongsTo { return $this->belongsTo(Account::class, 'liability_account_id'); }
    public function journal(): BelongsTo { return $this->belongsTo(Journal::class); }
    public function repayments(): HasMany { return $this->hasMany(LoanRepayment::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
