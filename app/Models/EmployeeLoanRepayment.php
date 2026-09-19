<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoanRepayment extends Model
{
    protected $fillable = [
        'employee_loan_id', 'date', 'amount', 'cash_account_id', 'reference',
        'status', 'journal_id', 'created_by', 'notes',
    ];

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function employeeLoan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
