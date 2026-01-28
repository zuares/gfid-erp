<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'code', 'date', 'purchase_receipt_id', 'purchase_order_id', 'supplier_id',
        'status', 'total', 'notes', 'journal_id',
        'created_by', 'posted_by', 'posted_at', 'voided_by', 'voided_at',
    ];

    public function grn()
    {return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');}
    public function order()
    {return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');}
    public function supplier()
    {return $this->belongsTo(Supplier::class);}
    public function lines()
    {return $this->hasMany(PurchaseReturnLine::class);}

    public function scopeActive($q)
    {return $q->whereNull('voided_at');}
}
