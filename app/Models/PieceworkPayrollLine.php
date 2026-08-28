<?php

namespace App\Models;

use App\Models\ItemCategory;
use Illuminate\Database\Eloquent\Model;

class PieceworkPayrollLine extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'work_date',
        'attendance_status',
        'attendance_factor',
        'item_category_id',
        'item_id',
        'total_qty_ok',
        'rate_per_pcs',
        'rate_per_day',
        'amount',
    ];

    protected $casts = [
        'total_qty_ok' => 'decimal:2',
        'rate_per_pcs' => 'decimal:2',
        'attendance_factor' => 'decimal:2',
        'rate_per_day' => 'decimal:2',
        'amount' => 'decimal:2',
        'work_date' => 'date',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function payrollPeriod()
    {
        return $this->belongsTo(PieceworkPayrollPeriod::class, 'payroll_period_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function period()
    {
        return $this->belongsTo(PieceworkPayrollPeriod::class, 'payroll_period_id');
    }
}
