<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrdDispatchCorrectionLine extends Model
{
    protected $fillable = [
        'prd_dispatch_correction_id',
        'stock_request_line_id',
        'item_id',
        'qty_adjust',
        'notes',
    ];

    protected $casts = [
        'qty_adjust' => 'float',
    ];

    public function correction()
    {
        return $this->belongsTo(PrdDispatchCorrection::class, 'prd_dispatch_correction_id');
    }

    public function stockRequestLine()
    {
        return $this->belongsTo(StockRequestLine::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
