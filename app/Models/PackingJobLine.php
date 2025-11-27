<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingJobLine extends Model
{
    protected $fillable = [
        'packing_job_id',
        'item_id',
        'qty_fg',
        'qty_packed',
        'packed_at',
        'notes',
    ];

    protected $casts = [
        'qty_fg' => 'float',
        'qty_packed' => 'float',
        'packed_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(PackingJob::class, 'packing_job_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
