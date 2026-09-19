<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSavingsTransaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';
    public const SOURCE_PAYROLL_BONUS = 'payroll_attendance_bonus';

    protected $fillable = [
        'employee_id', 'payroll_period_id', 'date', 'amount', 'type', 'status',
        'journal_id', 'source_type', 'source_id', 'created_by', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PieceworkPayrollPeriod::class, 'payroll_period_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
