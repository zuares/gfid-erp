<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PieceworkPayrollPeriod extends Model
{
    protected $fillable = [
        'module',
        'period_start',
        'period_end',
        'status',
        'total_amount',
        'notes',
        'created_by',
        'posted_by',
        'finalized_at',
        'finalized_by',
        'journal_id',
        'payable_account_id',
        'paid_at',
        'paid_by',
        'paid_from_account_id',
        'accrual_journal_id',
        'payment_journal_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_amount' => 'decimal:2',
        'finalized_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function lines()
    {
        return $this->hasMany(PieceworkPayrollLine::class, 'payroll_period_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeCutting($query)
    {
        return $query->where('module', 'cutting');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}
