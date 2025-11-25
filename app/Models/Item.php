<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    // Kalau nama tabel default "items", tidak perlu $table

    protected $fillable = [
        'code',
        'name',
        'unit',
        'type',
        'item_category_id',
        'last_purchase_price',
        'hpp',
        'active',
        'consumption_cutting', // ⭐ NEW

        'consumption_cutting_basis_qty', // ⭐ NEW
    ];

    protected $casts = [
        'last_purchase_price' => 'decimal:2',
        'hpp' => 'decimal:2',
        'active' => 'boolean',
        'consumption_cutting' => 'decimal:2', // ⭐ NEW
        'consumption_cutting_basis_qty' => 'decimal:4', // ⭐ NEW
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    /**
     * Kategori item (SJR, LJR, RIB, dll)
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    /**
     * Relasi ke detail PO yang menggunakan item ini.
     */
    public function purchaseOrderLines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * Harga historis per supplier.
     */
    public function supplierPrices()
    {
        return $this->hasMany(SupplierPrice::class);
    }

    /**
     * LOT kain / stok berbasis LOT untuk item ini (kalau LOT kamu pakai item_id).
     */
    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    /**
     * Stok per gudang (kalau di InventoryStock ada item_id).
     */
    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Material yang dipakai di production batch (kalau di schema kamu ada).
     */
    public function productionBatchMaterials()
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    /* ==========================
     *  HELPER
     * ==========================
     */

    public function isMaterial(): bool
    {
        return $this->type === 'material';
    }

    public function isFinished(): bool
    {
        return $this->type === 'finished';
    }

    public function isAccessory(): bool
    {
        return $this->type === 'accessory';
    }
}
