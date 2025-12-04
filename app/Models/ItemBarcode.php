<?php

// app/Models/ItemBarcode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemBarcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'barcode',
        'type',
        'notes',
        'is_active',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // Scope untuk cari by barcode (aktif saja)
    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode)
            ->where('is_active', true);
    }
}
