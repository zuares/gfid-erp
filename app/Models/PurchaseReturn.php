<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'code', 'date', 'purchase_receipt_id', 'purchase_order_id', 'supplier_id',
        'status', 'total', 'notes', 'journal_id',
        'created_by', 'posted_by', 'posted_at', 'voided_by', 'voided_at',
        // Tahap 9 — QC link
        'qc_id', 'return_reason',
        'resolution_type', 'replacement_status', 'replacement_expected_at',
        'replacement_received_at', 'replacement_receipt_id'
    ];

    public function grn()
    {return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');}
    public function order()
    {return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');}
    public function supplier()
    {return $this->belongsTo(Supplier::class);}
    public function lines()
    {return $this->hasMany(PurchaseReturnLine::class);}

    /** Tahap 9 — QC yang memicu return ini */
    public function qc()
    {
        return $this->belongsTo(\App\Models\PurchaseReceiptQc::class, 'qc_id');
    }

    public function scopeActive($q)
    {return $q->whereNull('voided_at');}
}
