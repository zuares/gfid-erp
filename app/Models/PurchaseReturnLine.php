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
        'qty', 'purchase_unit', 'stock_unit', 'conversion_factor', 'stock_qty',
        'allocated_qty', 'unit_price', 'line_total', 'notes', 'reason_code',
        'replacement_item_id', 'replacement_qty_expected', 'replacement_qty_received'
    ];

    protected $casts = [
        'qty' => 'float',
        'allocated_qty' => 'float',
        'conversion_factor' => 'decimal:6',
        'stock_qty' => 'float',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function effectivePurchaseUnit(): string
    {
        return trim((string) ($this->purchase_unit ?: $this->grnLine?->effectivePurchaseUnit() ?: $this->item?->purchaseUnit() ?: 'pcs'));
    }

    public function effectiveStockUnit(): string
    {
        return trim((string) ($this->stock_unit ?: $this->grnLine?->effectiveStockUnit() ?: $this->item?->stockUnit() ?: 'pcs'));
    }

    public function effectiveConversionFactor(): float
    {
        $factor = (float) ($this->conversion_factor ?? $this->grnLine?->effectiveConversionFactor() ?? $this->item?->purchaseConversionFactor() ?? 1);
        return $factor > 0 ? $factor : 1.0;
    }

    public function stockQty(): float
    {
        if ($this->stock_qty !== null) {
            return (float) $this->stock_qty;
        }

        return round((float) $this->qty * $this->effectiveConversionFactor(), 6);
    }

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
