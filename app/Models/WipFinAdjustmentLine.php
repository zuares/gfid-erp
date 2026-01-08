<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WipFinAdjustmentLine extends Model
{
    protected $fillable = [
        'wip_fin_adjustment_id', 'bundle_id', 'item_id', 'qty', 'line_notes',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(WipFinAdjustment::class, 'wip_fin_adjustment_id');
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'bundle_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
