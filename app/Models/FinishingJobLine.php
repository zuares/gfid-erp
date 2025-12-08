<?php

namespace App\Models;

use App\Models\Employee;
use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishingJobLine extends Model
{
    protected $fillable = [
        'finishing_job_id',
        'bundle_id',
        'operator_id',
        'item_id',
        'qty_in',
        'qty_ok',
        'qty_reject',
        'reject_reason',
        'reject_notes',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    // ====== RELATIONSHIPS ======

    public function job(): BelongsTo
    {
        return $this->belongsTo(FinishingJob::class, 'finishing_job_id');
    }

    public function bundle(): BelongsTo
    {
        // ganti namespace CuttingJobBundle sesuai project kamu
        return $this->belongsTo(CuttingJobBundle::class, 'bundle_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

}
