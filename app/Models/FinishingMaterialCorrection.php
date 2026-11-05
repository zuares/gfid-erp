<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinishingMaterialCorrection extends Model
{
    protected $fillable = [
        'finishing_item_id',
        'material_item_id',
        'action',
        'qty',
        'unit',
        'reason',
        'created_by',
        'status',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function finishingItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'finishing_item_id');
    }

    public function materialItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'material_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(null, 'reference_type', 'reference_id');
    }
}
