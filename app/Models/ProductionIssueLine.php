<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionIssueLine extends Model
{
    protected $fillable = [
        'production_issue_id',
        'item_id',
        'qty',
        'lot_id',
        'unit_cost',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(ProductionIssue::class, 'production_issue_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }
}
