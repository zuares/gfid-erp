<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinishingJob extends Model
{
    protected $fillable = [
        'code',
        'date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ====== RELATIONSHIPS ======

    public function lines(): HasMany
    {
        return $this->hasMany(FinishingJobLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // ====== HELPERS ======

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'bundle_id');
    }

    public function sewingOperator(): BelongsTo
    {
        // FK-nya pakai operator_id
        return $this->belongsTo(Employee::class, 'operator_id');
    }

}
