<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'kind',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    /**
     * Satu kategori memiliki banyak item.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /* ==========================
     *  SCOPE & HELPER
     * ==========================
     */

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeKind($query, string $kind)
    {
        return $query->where('kind', $kind);
    }

    public static function kindLabels(): array
    {
        return [
            'product' => 'Kategori Produk Jadi',
            'material' => 'Bahan Baku',
            'support' => 'Bahan Pendukung',
            'accessory' => 'Accessories',
            'packaging' => 'Packaging & Shipping',
            'operational' => 'ATK & Operasional',
            'other' => 'Lainnya',
        ];
    }

    public function getKindLabelAttribute(): string
    {
        return self::kindLabels()[$this->kind] ?? $this->kind ?? 'Lainnya';
    }

    public function isActive(): bool
    {
        return $this->active === true;
    }
}
