<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnLine extends Model
{
    /** Daftar alasan retur (code => label) untuk dropdown per baris. */
    public const REASONS = [
        'defect'     => 'Rusak/Defect',
        'wrong_item' => 'Salah kirim',
        'over_qty'   => 'Kelebihan qty',
        'expired'    => 'Expired',
        'quality'    => 'Kualitas',
        'other'      => 'Lainnya',
    ];

    protected $fillable = [
        'purchase_return_id', 'purchase_receipt_line_id', 'item_id', 'lot_id',
        'qty', 'allocated_qty', 'unit_price', 'line_total', 'notes', 'reason_code',
        'replacement_item_id', 'replacement_qty_expected', 'replacement_qty_received'
    ];

    public function ret()
    {return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');}
    public function grnLine()
    {return $this->belongsTo(PurchaseReceiptLine::class, 'purchase_receipt_line_id');}
    public function item()
    {return $this->belongsTo(Item::class);}
    public function lot()
    {return $this->belongsTo(Lot::class);}
    public function photos()
    {return $this->hasMany(PurchaseReturnLinePhoto::class, 'purchase_return_line_id');}

    /** Label alasan yang mudah dibaca. */
    public function getReasonLabelAttribute(): ?string
    {
        return $this->reason_code ? (self::REASONS[$this->reason_code] ?? $this->reason_code) : null;
    }
}
