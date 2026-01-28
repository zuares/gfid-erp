<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnLine extends Model
{
    protected $fillable = [
        'purchase_return_id', 'purchase_receipt_line_id', 'item_id', 'lot_id',
        'qty', 'unit_price', 'line_total', 'notes',
    ];

    public function ret()
    {return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');}
    public function grnLine()
    {return $this->belongsTo(PurchaseReceiptLine::class, 'purchase_receipt_line_id');}
    public function item()
    {return $this->belongsTo(Item::class);}
    public function lot()
    {return $this->belongsTo(Lot::class);}
}
