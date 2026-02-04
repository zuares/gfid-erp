<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionReceiptLine extends Model
{
    protected $fillable = [
        'production_receipt_id',
        'item_id',
        'qty_good',
        'lot_id',
        'unit_cost',
    ];

    public function receipt()
    {
        return $this->belongsTo(ProductionReceipt::class, 'production_receipt_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
