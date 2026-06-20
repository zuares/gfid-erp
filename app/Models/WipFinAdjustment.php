<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WipFinAdjustment extends Model
{
    protected $table = 'wip_fin_adjustments';

    protected $fillable = [
        'code',
        'date',
        'type',        // in | out
        'reason',
        'notes',
        'status',      // draft | posted | void
        'posted_at',
        'posted_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date'      => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(WipFinAdjustmentLine::class, 'wip_fin_adjustment_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
